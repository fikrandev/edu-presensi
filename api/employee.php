<?php
declare(strict_types=1);

$action = $apiAction ?? '';
$body   = Response::getJsonBody();
$slug   = strtolower(trim((string)($_GET['school_slug'] ?? $_POST['school_slug'] ?? $body['school_slug'] ?? '')));

$school = Tenant::findSchoolBySlug($slug);
if (!$school) {
    Response::json(['success' => false, 'message' => 'Sekolah tidak ditemukan.'], 404);
}
$emp = Auth::requireSchoolRole($school, ['employee', 'teacher', 'school_admin'], true);
Tenant::requireFeature($school, 'employee_attendance', true);

$schoolId = (int)$school['id'];
$userId   = (int)$emp['id'];
$pdo      = Database::pdo();

/**
 * Resolve today's effective Shift & Target Office Coordinates for this employee.
 * Priority: 1. Specific Date Override -> 2. Day-of-Week Rule -> 3. Employee Default -> 4. School Default.
 */
function resolve_employee_schedule(PDO $pdo, array $school, array $emp, string $date): array
{
    $schoolId = (int)$school['id'];
    $userId   = (int)$emp['id'];
    $dow      = (int)date('N', strtotime($date)); // 1=Mon..7=Sun

    // Check specific date override first
    $stmt = $pdo->prepare("SELECT * FROM employee_schedules WHERE school_id = ? AND user_id = ? AND specific_date = ? LIMIT 1");
    $stmt->execute([$schoolId, $userId, $date]);
    $rule = $stmt->fetch();

    if (!$rule) {
        $stmt = $pdo->prepare("SELECT * FROM employee_schedules WHERE school_id = ? AND user_id = ? AND day_of_week = ? LIMIT 1");
        $stmt->execute([$schoolId, $userId, $dow]);
        $rule = $stmt->fetch();
    }

    $shiftId = ($rule && !empty($rule['shift_id'])) ? (int)$rule['shift_id'] : (!empty($emp['default_shift_id']) ? (int)$emp['default_shift_id'] : null);
    $shift   = null;
    if ($shiftId) {
        $shStmt = $pdo->prepare("SELECT * FROM shifts WHERE id = ? AND school_id = ? LIMIT 1");
        $shStmt->execute([$shiftId, $schoolId]);
        $shift = $shStmt->fetch();
    }
    if (!$shift) {
        $shStmt = $pdo->prepare("SELECT * FROM shifts WHERE school_id = ? ORDER BY is_default DESC, id ASC LIMIT 1");
        $shStmt->execute([$schoolId]);
        $shift = $shStmt->fetch();
    }

    $forceGlobal = !empty($school['force_global_location']);
    $globalLocName = !empty($school['office_location_name']) ? $school['office_location_name'] : $school['name'];

    if ($forceGlobal) {
        $targetLat = (float)$school['office_lat'];
        $targetLng = (float)$school['office_lng'];
        $targetRad = (int)$school['office_radius'];
        $locName   = $globalLocName . ' (Lokasi Global)';
    } else {
        // Resolve office from schedule override/day OR employee's assigned office
        $resolvedOffice = null;
        $officeId = ($rule && !empty($rule['office_id']))
            ? (int)$rule['office_id']
            : (!empty($emp['default_office_id']) ? (int)$emp['default_office_id'] : null);

        if ($officeId) {
            try {
                $ofStmt = $pdo->prepare("SELECT * FROM offices WHERE id = ? AND school_id = ? AND is_active = 1 LIMIT 1");
                $ofStmt->execute([$officeId, $schoolId]);
                $resolvedOffice = $ofStmt->fetch() ?: null;
            } catch (Throwable) {
                $resolvedOffice = null;
            }
        }

        if ($rule && $rule['custom_lat'] !== null && $rule['custom_lng'] !== null) {
            $targetLat = (float)$rule['custom_lat'];
            $targetLng = (float)$rule['custom_lng'];
            $targetRad = $rule['custom_radius'] !== null ? (int)$rule['custom_radius'] : (int)$school['office_radius'];
            $locName   = !empty($rule['location_name']) ? $rule['location_name'] : $globalLocName;
        } elseif ($rule && !empty($rule['office_id']) && $resolvedOffice) {
            $targetLat = (float)$resolvedOffice['latitude'];
            $targetLng = (float)$resolvedOffice['longitude'];
            $targetRad = (int)$resolvedOffice['radius'];
            $locName   = $resolvedOffice['name'];
        } elseif ($emp['default_lat'] !== null && $emp['default_lng'] !== null) {
            $targetLat = (float)$emp['default_lat'];
            $targetLng = (float)$emp['default_lng'];
            $targetRad = $emp['default_radius'] !== null ? (int)$emp['default_radius'] : (int)$school['office_radius'];
            $locName   = $globalLocName . ' (Custom Pegawai)';
        } elseif ($resolvedOffice) {
            $targetLat = (float)$resolvedOffice['latitude'];
            $targetLng = (float)$resolvedOffice['longitude'];
            $targetRad = (int)$resolvedOffice['radius'];
            $locName   = $resolvedOffice['name'];
        } else {
            $targetLat = (float)$school['office_lat'];
            $targetLng = (float)$school['office_lng'];
            $targetRad = (int)$school['office_radius'];
            $locName   = $globalLocName;
        }
    }

    return [
        'date'                     => $date,
        'day_name'                 => day_name_id($dow),
        'is_day_off'               => ($rule && (int)$rule['is_day_off'] === 1) || (!$rule && $dow === 7),
        'shift_id'                 => $shift ? (int)$shift['id'] : null,
        'shift_code'               => $shift['code'] ?? 'REG',
        'shift_name'               => $shift ? $shift['name'] : 'Jam Kerja Default',
        'start_time'               => $shift ? $shift['start_time'] : '07:15:00',
        'end_time'                 => $shift ? $shift['end_time'] : '15:00:00',
        'checkin_open_before'      => isset($shift['checkin_open_before']) ? (int)$shift['checkin_open_before'] : 60,
        'late_tolerance_minutes'   => $shift ? (int)$shift['late_tolerance_minutes'] : 10,
        'max_late_minutes'         => isset($shift['max_late_minutes']) ? (int)$shift['max_late_minutes'] : 0,
        'early_checkout_tolerance' => isset($shift['early_checkout_tolerance']) ? (int)$shift['early_checkout_tolerance'] : 0,
        'is_cross_day'             => !empty($shift['is_cross_day']),
        'target_lat'               => $targetLat,
        'target_lng'               => $targetLng,
        'target_radius'            => max(20, $targetRad),
        'location_name'            => $locName,
    ];
}

// 1. Dashboard monitoring kehadiran pribadi
if ($action === 'dashboard') {
    $today    = date('Y-m-d');
    $month    = date('Y-m');
    $schedule = resolve_employee_schedule($pdo, $school, $emp, $today);

    $tStmt = $pdo->prepare("SELECT * FROM employee_attendances WHERE school_id = ? AND user_id = ? AND attendance_date = ? LIMIT 1");
    $tStmt->execute([$schoolId, $userId, $today]);
    $todayAtt = $tStmt->fetch() ?: null;

    // Monthly summary
    $mStmt = $pdo->prepare("
        SELECT status, COUNT(*) AS cnt, COALESCE(SUM(late_minutes),0) AS late_mins
        FROM employee_attendances
        WHERE school_id = ? AND user_id = ? AND DATE_FORMAT(attendance_date, '%Y-%m') = ?
        GROUP BY status
    ");
    $mStmt->execute([$schoolId, $userId, $month]);
    $stats = ['hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'dinas_luar' => 0, 'alpha' => 0, 'late_minutes' => 0];
    foreach ($mStmt->fetchAll() as $r) {
        if (isset($stats[$r['status']])) $stats[$r['status']] = (int)$r['cnt'];
        $stats['late_minutes'] += (int)$r['late_mins'];
    }

    // Working days elapsed this month
    $workDays = 0;
    for ($d = 1; $d <= (int)date('j'); $d++) {
        $dow = (int)date('N', strtotime($month . '-' . str_pad((string)$d, 2, '0', STR_PAD_LEFT)));
        if ($dow < 7) $workDays++;
    }
    $present = $stats['hadir'] + $stats['terlambat'] + $stats['dinas_luar'];
    $stats['percentage'] = $workDays > 0 ? min(100, round(($present / $workDays) * 100, 1)) : 0;
    $stats['work_days']  = $workDays;

    $hStmt = $pdo->prepare("
        SELECT * FROM employee_attendances
        WHERE school_id = ? AND user_id = ?
        ORDER BY attendance_date DESC LIMIT 15
    ");
    $hStmt->execute([$schoolId, $userId]);

    $pendingCorr = (int)$pdo->query("SELECT COUNT(*) FROM attendance_corrections WHERE school_id = {$schoolId} AND user_id = {$userId} AND status = 'pending'")->fetchColumn();

    Response::json([
        'success' => true,
        'data'    => [
            'user' => [
                'name'           => $emp['name'],
                'nip_nik'        => $emp['nip_nik'],
                'position_title' => $emp['position_title'],
                'avatar_url'     => $emp['avatar_path'] ? app_url($emp['avatar_path']) : null,
            ],
            'schedule'            => $schedule,
            'today'               => $todayAtt,
            'monthly'             => $stats,
            'history'             => $hStmt->fetchAll(),
            'pending_corrections' => $pendingCorr,
            'server_time'         => date('H:i:s'),
            'server_date_id'      => format_date_id($today),
        ],
    ]);
}

// 2. Check-in GPS + Geofence + Optional Selfie
if ($action === 'checkin') {
    Security::requireCsrf();
    $today    = date('Y-m-d');
    $lat      = isset($body['lat']) ? (float)$body['lat'] : null;
    $lng      = isset($body['lng']) ? (float)$body['lng'] : null;
    $note     = trim((string)($body['note'] ?? ''));
    $photoB64 = (string)($body['photo'] ?? '');

    if ($lat === null || $lng === null) {
        Response::json(['success' => false, 'message' => 'Lokasi GPS belum terdeteksi. Aktifkan izin lokasi di HP Anda.'], 422);
    }

    $schedule = resolve_employee_schedule($pdo, $school, $emp, $today);
    $distance = Security::haversineDistance($lat, $lng, $schedule['target_lat'], $schedule['target_lng']);

    if ($distance > $schedule['target_radius']) {
        Response::json([
            'success' => false,
            'message' => "Anda berada di luar radius lokasi absen ({$schedule['location_name']}). Jarak Anda: " . round($distance) . " m (Maksimal: {$schedule['target_radius']} m).",
        ], 422);
    }

    $chk = $pdo->prepare("SELECT id, checkin_time FROM employee_attendances WHERE school_id = ? AND user_id = ? AND attendance_date = ? LIMIT 1");
    $chk->execute([$schoolId, $userId, $today]);
    $existing = $chk->fetch();
    if ($existing && !empty($existing['checkin_time'])) {
        Response::json(['success' => false, 'message' => 'Anda sudah melakukan Check-in hari ini pada ' . date('H:i', strtotime($existing['checkin_time'])) . '.'], 422);
    }

    // Enforce shift check-in window & calculate lateness accurately
    $nowTs      = time();
    $schedTs    = strtotime($today . ' ' . $schedule['start_time']);
    $openBefore = (int)($schedule['checkin_open_before'] ?? 60);
    if ($openBefore > 0) {
        $earliestTs = $schedTs - ($openBefore * 60);
        if ($nowTs < $earliestTs) {
            Response::json([
                'success' => false,
                'message' => 'Absen masuk untuk shift ' . $schedule['shift_name'] . ' baru dibuka mulai pukul ' . date('H:i', $earliestTs) . ' (' . $openBefore . ' menit sebelum jam masuk).',
            ], 422);
        }
    }

    $maxLate = (int)($schedule['max_late_minutes'] ?? 0);
    if ($maxLate > 0 && $nowTs > ($schedTs + ($maxLate * 60))) {
        Response::json([
            'success' => false,
            'message' => 'Batas waktu absen masuk (maksimal terlambat ' . $maxLate . ' menit / pukul ' . date('H:i', $schedTs + ($maxLate * 60)) . ') telah lewat. Silakan ajukan Perbaikan Absen.',
        ], 422);
    }

    $tolTs   = $schedTs + ($schedule['late_tolerance_minutes'] * 60);
    $lateMin = $nowTs > $tolTs ? (int)ceil(($nowTs - $schedTs) / 60) : 0;
    $status  = $lateMin > 0 ? 'terlambat' : 'hadir';

    $photoPath = $photoB64 !== '' ? Security::saveBase64Image($photoB64, 'attendance') : null;
    $nowStr    = date('Y-m-d H:i:s', $nowTs);

    if ($existing) {
        $pdo->prepare("
            UPDATE employee_attendances
            SET checkin_time=?, checkin_lat=?, checkin_lng=?, checkin_distance=?, checkin_photo=?, checkin_note=?, status=?, late_minutes=?
            WHERE id=?
        ")->execute([$nowStr, $lat, $lng, $distance, $photoPath, $note, $status, $lateMin, $existing['id']]);
    } else {
        $pdo->prepare("
            INSERT INTO employee_attendances (school_id, user_id, attendance_date, shift_id, shift_name, scheduled_start, scheduled_end, target_lat, target_lng, target_radius, checkin_time, checkin_lat, checkin_lng, checkin_distance, checkin_photo, checkin_note, status, late_minutes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $schoolId, $userId, $today, $schedule['shift_id'], $schedule['shift_name'],
            $schedule['start_time'], $schedule['end_time'], $schedule['target_lat'], $schedule['target_lng'], $schedule['target_radius'],
            $nowStr, $lat, $lng, $distance, $photoPath, $note, $status, $lateMin,
        ]);
    }

    $msg = $lateMin > 0
        ? "Check-in berhasil tercatat pada " . date('H:i', $nowTs) . " (Terlambat {$lateMin} menit, jarak " . round($distance) . " m)."
        : "Check-in TEPAT WAKTU berhasil tercatat pada " . date('H:i', $nowTs) . " (jarak " . round($distance) . " m).";

    Response::json(['success' => true, 'mode' => 'saved', 'message' => $msg]);
}

// 3. Check-out GPS + Geofence + Optional Selfie
if ($action === 'checkout') {
    Security::requireCsrf();
    $today    = date('Y-m-d');
    $lat      = isset($body['lat']) ? (float)$body['lat'] : null;
    $lng      = isset($body['lng']) ? (float)$body['lng'] : null;
    $note     = trim((string)($body['note'] ?? ''));
    $photoB64 = (string)($body['photo'] ?? '');

    if ($lat === null || $lng === null) {
        Response::json(['success' => false, 'message' => 'Lokasi GPS belum terdeteksi.'], 422);
    }

    $chk = $pdo->prepare("SELECT * FROM employee_attendances WHERE school_id = ? AND user_id = ? AND attendance_date = ? LIMIT 1");
    $chk->execute([$schoolId, $userId, $today]);
    $existing = $chk->fetch();
    if (!$existing || empty($existing['checkin_time'])) {
        Response::json(['success' => false, 'message' => 'Anda belum melakukan Check-in hari ini.'], 422);
    }
    if (!empty($existing['checkout_time'])) {
        Response::json(['success' => false, 'message' => 'Anda sudah melakukan Check-out hari ini.'], 422);
    }

    $schedule = resolve_employee_schedule($pdo, $school, $emp, $today);
    $distance = Security::haversineDistance($lat, $lng, $schedule['target_lat'], $schedule['target_lng']);
    if ($distance > $schedule['target_radius']) {
        Response::json([
            'success' => false,
            'message' => "Anda berada di luar radius lokasi pulang ({$schedule['location_name']}). Jarak Anda: " . round($distance) . " m (Maks: {$schedule['target_radius']} m).",
        ], 422);
    }

    $photoPath = $photoB64 !== '' ? Security::saveBase64Image($photoB64, 'attendance') : null;
    $nowStr    = date('Y-m-d H:i:s');

    $pdo->prepare("
        UPDATE employee_attendances
        SET checkout_time=?, checkout_lat=?, checkout_lng=?, checkout_distance=?, checkout_photo=?, checkout_note=?
        WHERE id=?
    ")->execute([$nowStr, $lat, $lng, $distance, $photoPath, $note, $existing['id']]);

    Response::json([
        'success' => true,
        'mode'    => 'updated',
        'message' => 'Check-out berhasil tercatat pada ' . date('H:i') . ' (jarak ' . round($distance) . ' m). Hati-hati di jalan!',
    ]);
}

// 4. Pengajuan Perbaikan Absen
if ($action === 'corrections-list') {
    $stmt = $pdo->prepare("SELECT * FROM attendance_corrections WHERE school_id = ? AND user_id = ? ORDER BY created_at DESC LIMIT 40");
    $stmt->execute([$schoolId, $userId]);
    Response::json(['success' => true, 'data' => $stmt->fetchAll()]);
}

if ($action === 'correction-create') {
    Security::requireCsrf();
    Tenant::requireFeature($school, 'attendance_correction', true);

    $date   = trim((string)($_POST['attendance_date'] ?? $body['attendance_date'] ?? ''));
    $type   = trim((string)($_POST['correction_type'] ?? $body['correction_type'] ?? ''));
    $reqIn  = trim((string)($_POST['requested_checkin'] ?? $body['requested_checkin'] ?? '')) ?: null;
    $reqOut = trim((string)($_POST['requested_checkout'] ?? $body['requested_checkout'] ?? '')) ?: null;
    $reason = trim((string)($_POST['reason'] ?? $body['reason'] ?? ''));

    $allowedTypes = ['lupa_checkin', 'lupa_checkout', 'koreksi_jam', 'izin', 'sakit', 'dinas_luar'];
    if ($date === '' || !in_array($type, $allowedTypes, true) || $reason === '') {
        Response::json(['success' => false, 'message' => 'Tanggal, jenis pengajuan, dan alasan wajib diisi.'], 422);
    }

    $attachmentPath = null;
    if (!empty($_FILES['attachment']['name'])) {
        $up = Security::uploadImage($_FILES['attachment'], 'corrections', ['png', 'jpg', 'jpeg', 'webp'], 2097152);
        if (!$up['ok']) {
            Response::json(['success' => false, 'message' => $up['error']], 422);
        }
        $attachmentPath = $up['path'];
    }

    $pdo->prepare("
        INSERT INTO attendance_corrections (school_id, user_id, attendance_date, correction_type, requested_checkin, requested_checkout, reason, attachment_path, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ")->execute([$schoolId, $userId, $date, $type, $reqIn, $reqOut, $reason, $attachmentPath]);

    Response::json(['success' => true, 'mode' => 'saved', 'message' => 'Pengajuan perbaikan absen berhasil dikirim dan menunggu persetujuan Admin Sekolah.']);
}

// 5. Pengaturan Akun & Keamanan Sesi PWA Selamanya
if ($action === 'account-get') {
    Response::json([
        'success' => true,
        'data'    => [
            'user' => [
                'id'             => $emp['id'],
                'nip_nik'        => $emp['nip_nik'],
                'name'           => $emp['name'],
                'email'          => $emp['email'],
                'phone'          => $emp['phone'],
                'position_title' => $emp['position_title'],
                'avatar_url'     => $emp['avatar_path'] ? app_url($emp['avatar_path']) : null,
            ],
            'devices' => Auth::listDevices($userId),
        ],
    ]);
}

if ($action === 'account-update') {
    Security::requireCsrf();
    $name  = trim((string)($_POST['name'] ?? $body['name'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? $body['phone'] ?? ''));
    if ($name === '') {
        Response::json(['success' => false, 'message' => 'Nama lengkap wajib diisi.'], 422);
    }

    $avatarPath = $emp['avatar_path'];
    if (!empty($_FILES['avatar']['name'])) {
        $up = Security::uploadImage($_FILES['avatar'], 'avatars', ['png', 'jpg', 'jpeg', 'webp'], 1572864);
        if (!$up['ok']) {
            Response::json(['success' => false, 'message' => $up['error']], 422);
        }
        $avatarPath = $up['path'];
    }

    $pdo->prepare("UPDATE users SET name = ?, phone = ?, avatar_path = ? WHERE id = ?")->execute([$name, $phone, $avatarPath, $userId]);
    Response::json(['success' => true, 'mode' => 'updated', 'message' => 'Profil akun berhasil diperbarui.']);
}

if ($action === 'password-update') {
    Security::requireCsrf();
    $oldPass = (string)($body['old_password'] ?? '');
    $newPass = (string)($body['new_password'] ?? '');

    if (strlen($newPass) < 6) {
        Response::json(['success' => false, 'message' => 'Password baru minimal 6 karakter.'], 422);
    }
    if (!Security::verifyPassword($oldPass, $emp['password_hash'])) {
        Response::json(['success' => false, 'message' => 'Password lama tidak sesuai.'], 422);
    }

    $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([Security::hashPassword($newPass), $userId]);
    Response::json(['success' => true, 'mode' => 'updated', 'message' => 'Password berhasil diperbarui!']);
}

if ($action === 'device-revoke') {
    Security::requireCsrf();
    $tokenId = (int)($body['token_id'] ?? 0);
    Auth::revokeDevice($userId, $tokenId);
    Response::json(['success' => true, 'mode' => 'updated', 'message' => 'Sesi perangkat tersebut berhasil dicabut.']);
}

Response::json(['success' => false, 'message' => 'Aksi Pegawai tidak dikenali.'], 404);
