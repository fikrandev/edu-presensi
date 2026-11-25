<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/core/Security.php';

function run_saas_seed(PDO $pdo): array
{
    $log = [];

    // 1. SaaS Settings
    $exists = (int)$pdo->query("SELECT COUNT(*) FROM saas_settings WHERE id = 1")->fetchColumn();
    if ($exists === 0) {
        $stmt = $pdo->prepare("
            INSERT INTO saas_settings (id, app_name, tagline, display_domain, logo_path, favicon_path, wa_provider, wa_api_url, wa_template_h3, wa_template_h0, cron_secret)
            VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            'EduPresensi',
            'Sistem SaaS Manajemen Absensi Sekolah Modern',
            'eduabsen.sekolah-indonesia.id',
            'assets/img/default-logo.svg',
            'assets/img/default-favicon.svg',
            'fonnte',
            'https://api.fonnte.com/send',
            "Halo Admin *{nama_sekolah}*,\n\nPengingat tagihan langganan *{app_name}* paket *{paket}* akan jatuh tempo dalam *3 hari* pada *{tanggal_expired}*.\nNominal: *{nominal}*.\n\nPortal sekolah: {link_portal}\nTerima kasih.",
            "Halo Admin *{nama_sekolah}*,\n\nHari ini (*{tanggal_expired}*) adalah jatuh tempo langganan *{app_name}* paket *{paket}*.\nNominal: *{nominal}*.\n\nMohon segera lakukan perpanjangan agar layanan absensi tetap aktif.\nPortal: {link_portal}",
            'edupresensi-cron-2026',
        ]);
        $log[] = 'SaaS default settings initialized.';
    }

    // 2. Superadmin account
    $saCheck = $pdo->prepare("SELECT id FROM users WHERE role = 'superadmin' AND LOWER(email) IN ('superadmin', 'superadmin@edupresensi.id') LIMIT 1");
    $saCheck->execute();
    if (!$saCheck->fetch()) {
        $pdo->prepare("
            INSERT INTO users (school_id, role, nip_nik, name, email, phone, password_hash, position_title, is_active)
            VALUES (NULL, 'superadmin', 'SA-001', 'Superadmin EduPresensi', 'superadmin', '081100000001', ?, 'Owner SaaS', 1)
        ")->execute([Security::hashPassword('Admin123!')]);
        $log[] = 'Superadmin created: superadmin / Admin123!';
    }

    // 3. Packages
    $pkgCount = (int)$pdo->query("SELECT COUNT(*) FROM packages")->fetchColumn();
    if ($pkgCount === 0) {
        $allFeatures = array_keys(SAAS_FEATURES);
        $basicFeatures = ['employee_attendance', 'excel_import', 'employee_reports', 'attendance_correction'];
        $proFeatures = ['employee_attendance', 'custom_shift_location', 'excel_import', 'employee_reports', 'attendance_correction', 'student_management', 'teacher_qr_scan', 'teacher_reports'];

        $pkgs = [
            ['Starter Sekolah', 'starter', 'Paket dasar absensi pegawai GPS & PWA untuk sekolah kecil.', 150000, 800000, 1500000, 40, 300, $basicFeatures, 1],
            ['Professional Sekolah', 'professional', 'Lengkap dengan absensi pegawai custom shift/koordinat & absensi siswa QR Code.', 350000, 1900000, 3500000, 150, 1200, $proFeatures, 2],
            ['Enterprise Yayasan', 'enterprise', 'Semua fitur terbuka penuh termasuk notifikasi WA Gateway dan kuota besar.', 650000, 3500000, 6500000, 1000, 5000, $allFeatures, 3],
        ];
        $stmt = $pdo->prepare("
            INSERT INTO packages (name, slug, description, price_monthly, price_6months, price_yearly, max_employees, max_students, features_json, is_active, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
        ");
        foreach ($pkgs as $p) {
            $stmt->execute([$p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7], json_encode($p[8]), $p[9]]);
        }
        $log[] = '3 default packages created.';
    }

    $entPkgId = (int)$pdo->query("SELECT id FROM packages WHERE slug = 'enterprise' LIMIT 1")->fetchColumn() ?: 1;
    $proPkgId = (int)$pdo->query("SELECT id FROM packages WHERE slug = 'professional' LIMIT 1")->fetchColumn() ?: 1;

    // 4. Demo Schools (including Bina Insan Palu as mentioned in prompt!)
    $schoolCheck = $pdo->prepare("SELECT id FROM schools WHERE slug = ? LIMIT 1");
    $schoolCheck->execute(['bina-insan-palu']);
    $binaId = (int)$schoolCheck->fetchColumn();

    if (!$binaId) {
        $start = date('Y-m-d', strtotime('-15 days'));
        $end   = date('Y-m-d', strtotime('+165 days'));
        $pdo->prepare("
            INSERT INTO schools (package_id, name, slug, npsn, email, phone, address, principal_name, office_lat, office_lng, office_radius, subscription_status, subscription_start, subscription_end)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)
        ")->execute([
            $entPkgId,
            'SDIT Bina Insan Palu',
            'bina-insan-palu',
            '40203511',
            'admin@binainsanpalu.sch.id',
            '085241000111',
            'Jl. Dewi Sartika No. 45, Palu Selatan, Kota Palu, Sulawesi Tengah',
            'Ust. Ahmad Fauzi, S.Pd.I',
            -0.9165000,
            119.8832000,
            200,
            $start,
            $end,
        ]);
        $binaId = (int)$pdo->lastInsertId();

        // Add paid transaction for Bina Insan Palu
        $pdo->prepare("
            INSERT INTO subscription_transactions (invoice_number, school_id, package_id, duration_months, amount, status, payment_method, notes, period_start, period_end, paid_at)
            VALUES (?, ?, ?, 6, 3500000, 'paid', 'Transfer BSI', 'Langganan 6 Bulan Paket Enterprise', ?, ?, NOW())
        ")->execute(['INV-' . date('Ym') . '-0001', $binaId, $entPkgId, $start, $end]);

        // Second demo school expiring in 3 days (to demonstrate H-3 WA reminder!)
        $h3End = date('Y-m-d', strtotime('+3 days'));
        $pdo->prepare("
            INSERT INTO schools (package_id, name, slug, npsn, email, phone, address, principal_name, office_lat, office_lng, office_radius, subscription_status, subscription_start, subscription_end)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)
        ")->execute([
            $proPkgId,
            'SMA Nusantara Makassar',
            'sma-nusantara-makassar',
            '40311209',
            'admin@smanusantara.sch.id',
            '081355000222',
            'Jl. Pettarani No. 12, Makassar',
            'Dra. Hj. Siti Rahmawati, M.Pd',
            -5.1476650,
            119.4327310,
            150,
            date('Y-m-d', strtotime('-27 days')),
            $h3End,
        ]);
        $smaId = (int)$pdo->lastInsertId();
        $pdo->prepare("
            INSERT INTO subscription_transactions (invoice_number, school_id, package_id, duration_months, amount, status, payment_method, notes, period_start, period_end, paid_at)
            VALUES (?, ?, ?, 1, 350000, 'paid', 'Transfer BCA', 'Langganan Bulanan Paket Professional', ?, ?, NOW())
        ")->execute(['INV-' . date('Ym') . '-0002', $smaId, $proPkgId, date('Y-m-d', strtotime('-27 days')), $h3End]);

        // Admin for SMA Nusantara
        $pdo->prepare("
            INSERT INTO users (school_id, role, nip_nik, name, email, phone, password_hash, position_title, is_active)
            VALUES (?, 'school_admin', 'ADM-SMA', 'Admin SMA Nusantara', 'admin@smanusantara.sch.id', '081355000222', ?, 'Admin Sekolah', 1)
        ")->execute([$smaId, Security::hashPassword('Admin123!')]);

        // Shifts for Bina Insan Palu
        $pdo->prepare("INSERT INTO shifts (school_id, name, start_time, end_time, late_tolerance_minutes, is_default) VALUES (?, 'Shift Pagi Reguler', '07:15:00', '15:00:00', 10, 1)")->execute([$binaId]);
        $shiftPagi = (int)$pdo->lastInsertId();
        $pdo->prepare("INSERT INTO shifts (school_id, name, start_time, end_time, late_tolerance_minutes, is_default) VALUES (?, 'Shift Jumat', '07:15:00', '11:30:00', 10, 0)")->execute([$binaId]);
        $shiftJumat = (int)$pdo->lastInsertId();
        $pdo->prepare("INSERT INTO shifts (school_id, name, start_time, end_time, late_tolerance_minutes, is_default) VALUES (?, 'Shift Siang / Ekskul', '10:00:00', '17:00:00', 15, 0)")->execute([$binaId]);

        // Users for Bina Insan Palu: School Admin, Teacher, 3 Employees
        $pw = Security::hashPassword('Admin123!');
        $pdo->prepare("
            INSERT INTO users (school_id, role, nip_nik, name, email, phone, password_hash, position_title, default_shift_id, is_active)
            VALUES (?, 'school_admin', 'ADM-001', 'Admin Bina Insan', 'admin@binainsan.sch.id', '085241000111', ?, 'Administrator Sekolah', ?, 1)
        ")->execute([$binaId, $pw, $shiftPagi]);

        $pdo->prepare("
            INSERT INTO users (school_id, role, nip_nik, name, email, phone, password_hash, position_title, subject_name, assigned_classes, default_shift_id, is_active)
            VALUES (?, 'teacher', 'GRU-001', 'Ustadzah Nurul Hidayah, S.Pd', 'guru@binainsan.sch.id', '085241000112', ?, 'Guru Kelas', 'Matematika & Tematik', '6A,6B,5A', ?, 1)
        ")->execute([$binaId, $pw, $shiftPagi]);
        $teacherId = (int)$pdo->lastInsertId();

        $employees = [
            ['PEG-001', 'Budi Santoso, S.Pd', 'pegawai@binainsan.sch.id', '085241000113', 'Guru Tetap Yayasan'],
            ['PEG-002', 'Rina Kartika, S.Kom', 'rina@binainsan.sch.id', '085241000114', 'Staf Tata Usaha'],
            ['PEG-003', 'Hendra Wijaya', 'hendra@binainsan.sch.id', '085241000115', 'Keamanan & Sarpras'],
        ];
        $empIds = [];
        $insEmp = $pdo->prepare("
            INSERT INTO users (school_id, role, nip_nik, name, email, phone, password_hash, position_title, default_shift_id, default_lat, default_lng, default_radius, is_active)
            VALUES (?, 'employee', ?, ?, ?, ?, ?, ?, ?, NULL, NULL, NULL, 1)
        ");
        foreach ($employees as $e) {
            $insEmp->execute([$binaId, $e[0], $e[1], $e[2], $e[3], $pw, $e[4], $shiftPagi]);
            $empIds[] = (int)$pdo->lastInsertId();
        }

        // Custom Friday schedule for Budi Santoso (demo per-day custom shift & location)
        $pdo->prepare("
            INSERT INTO employee_schedules (school_id, user_id, day_of_week, shift_id, is_day_off, custom_lat, custom_lng, custom_radius, location_name)
            VALUES (?, ?, 5, ?, 0, -0.9180000, 119.8850000, 250, 'Gedung B / Masjid Sekolah')
        ")->execute([$binaId, $empIds[0], $shiftJumat]);

        // Sample employee attendance over last 5 days
        $insAtt = $pdo->prepare("
            INSERT INTO employee_attendances (school_id, user_id, attendance_date, shift_id, shift_name, scheduled_start, scheduled_end, target_lat, target_lng, target_radius, checkin_time, checkin_lat, checkin_lng, checkin_distance, checkout_time, checkout_lat, checkout_lng, checkout_distance, status, late_minutes)
            VALUES (?, ?, ?, ?, 'Shift Pagi Reguler', '07:15:00', '15:00:00', -0.9165000, 119.8832000, 200, ?, -0.9165100, 119.8832100, 14.5, ?, -0.9165200, 119.8832200, 18.2, ?, ?)
        ");
        for ($d = 4; $d >= 1; $d--) {
            $dt = date('Y-m-d', strtotime("-{$d} days"));
            foreach ($empIds as $idx => $eid) {
                $isLate = ($idx === 1 && $d % 2 === 0);
                $inTime = $dt . ($isLate ? ' 07:38:00' : ' 07:05:00');
                $outTime = $dt . ' 15:08:00';
                $insAtt->execute([$binaId, $eid, $dt, $shiftPagi, $inTime, $outTime, $isLate ? 'terlambat' : 'hadir', $isLate ? 23 : 0]);
            }
        }

        // Sample students for classes 6A & 6B
        $students = [
            ['0091230001', 'Ahmad Zaki Pratama', '6A', 'L', 'Bapak Hendri', '085241110001'],
            ['0091230002', 'Aisyah Putri Rahma', '6A', 'P', 'Ibu Mariana', '085241110002'],
            ['0091230003', 'Muhammad Rizky Fadillah', '6A', 'L', 'Bapak Ruslan', '085241110003'],
            ['0091230004', 'Siti Khadijah Azzahra', '6A', 'P', 'Ibu Fatimah', '085241110004'],
            ['0091230005', 'Daffa Alfarizi', '6B', 'L', 'Bapak Syahrul', '085241110005'],
            ['0091230006', 'Naila Husna Salsabila', '6B', 'P', 'Ibu Dewi', '085241110006'],
        ];
        $insStu = $pdo->prepare("
            INSERT INTO students (school_id, nisn, name, class_name, gender, guardian_name, guardian_phone, qr_token, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $stuIds6A = [];
        foreach ($students as $st) {
            $token = 'EP-' . $binaId . '-' . strtoupper(bin2hex(random_bytes(5)));
            $insStu->execute([$binaId, $st[0], $st[1], $st[2], $st[3], $st[4], $st[5], $token]);
            if ($st[2] === '6A') {
                $stuIds6A[] = (int)$pdo->lastInsertId();
            }
        }

        // Sample student attendance session for class 6A today
        $pdo->prepare("
            INSERT INTO student_attendance_sessions (school_id, teacher_id, class_name, subject_name, session_title, session_date, start_time, end_time, status)
            VALUES (?, ?, '6A', 'Matematika', 'Pertemuan Pagi Kelas 6A', CURDATE(), '07:30:00', '09:00:00', 'open')
        ")->execute([$binaId, $teacherId]);
        $sessId = (int)$pdo->lastInsertId();

        $insRec = $pdo->prepare("
            INSERT INTO student_attendance_records (school_id, session_id, student_id, status, scanned_at, scan_method)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach ($stuIds6A as $idx => $sid) {
            if ($idx < 3) {
                $insRec->execute([$binaId, $sessId, $sid, 'hadir', date('Y-m-d H:i:s'), 'qr']);
            } else {
                $insRec->execute([$binaId, $sessId, $sid, 'alpha', null, null]);
            }
        }

        $log[] = 'Demo school "bina-insan-palu" & "sma-nusantara-makassar" seeded with sample users, students & attendance.';
    }

    return $log;
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $pdo = Database::pdo();
    $res = run_saas_seed($pdo);
    echo implode(PHP_EOL, $res ?: ['Seed already up to date.']) . PHP_EOL;
}
