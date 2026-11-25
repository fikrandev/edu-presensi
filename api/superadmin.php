<?php
declare(strict_types=1);

$superadmin = Auth::requireSuperadmin(true);
$action     = $apiAction ?? '';
$pdo        = Database::pdo();
$body       = Response::getJsonBody();

// =========================================================================
// DASHBOARD MONITORING PELANGGAN
// =========================================================================
if ($action === 'dashboard') {
    // Sync expired statuses automatically
    $pdo->exec("UPDATE schools SET subscription_status = 'expired' WHERE subscription_status IN ('active','trial') AND subscription_end < CURDATE()");

    $stats = [
        'total_schools'     => (int)$pdo->query("SELECT COUNT(*) FROM schools")->fetchColumn(),
        'active_schools'    => (int)$pdo->query("SELECT COUNT(*) FROM schools WHERE subscription_status IN ('active','trial')")->fetchColumn(),
        'expiring_soon'     => (int)$pdo->query("SELECT COUNT(*) FROM schools WHERE subscription_status IN ('active','trial') AND subscription_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetchColumn(),
        'expired_schools'   => (int)$pdo->query("SELECT COUNT(*) FROM schools WHERE subscription_status IN ('expired','suspended')")->fetchColumn(),
        'total_employees'   => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('employee','teacher') AND is_active = 1")->fetchColumn(),
        'total_students'    => (int)$pdo->query("SELECT COUNT(*) FROM students WHERE is_active = 1")->fetchColumn(),
        'revenue_month'     => (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM subscription_transactions WHERE status = 'paid' AND DATE_FORMAT(COALESCE(paid_at, created_at), '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')")->fetchColumn(),
        'revenue_total'     => (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM subscription_transactions WHERE status = 'paid'")->fetchColumn(),
    ];

    // Last 6 months revenue chart
    $months = [];
    for ($i = 5; $i >= 0; $i--) {
        $ym = date('Y-m', strtotime("-{$i} months"));
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM subscription_transactions WHERE status = 'paid' AND DATE_FORMAT(COALESCE(paid_at, created_at), '%Y-%m') = ?");
        $stmt->execute([$ym]);
        $months[] = [
            'ym'     => $ym,
            'label'  => date('M Y', strtotime($ym . '-01')),
            'amount' => (float)$stmt->fetchColumn(),
        ];
    }

    // Expiring soon schools (<= 14 days or recently expired)
    $expiringList = $pdo->query("
        SELECT s.id, s.name, s.slug, s.phone, s.subscription_status, s.subscription_end,
               DATEDIFF(s.subscription_end, CURDATE()) AS days_left,
               p.name AS package_name, p.price_monthly
        FROM schools s
        LEFT JOIN packages p ON p.id = s.package_id
        WHERE s.subscription_end <= DATE_ADD(CURDATE(), INTERVAL 14 DAY)
        ORDER BY s.subscription_end ASC
        LIMIT 10
    ")->fetchAll();

    // Recent transactions
    $recentTx = $pdo->query("
        SELECT t.*, s.name AS school_name, p.name AS package_name
        FROM subscription_transactions t
        JOIN schools s ON s.id = t.school_id
        LEFT JOIN packages p ON p.id = t.package_id
        ORDER BY t.created_at DESC
        LIMIT 8
    ")->fetchAll();

    Response::json([
        'success' => true,
        'data'    => [
            'stats'         => $stats,
            'revenue_chart' => $months,
            'expiring_list' => $expiringList,
            'recent_tx'     => $recentTx,
            'display_domain'=> Tenant::saasSettings()['display_domain'],
        ],
    ]);
}

// =========================================================================
// DAFTAR SEKOLAH BERLANGGANAN (CRUD + EXTEND 1/6/12 BULAN)
// =========================================================================
if ($action === 'schools-list') {
    $pdo->exec("UPDATE schools SET subscription_status = 'expired' WHERE subscription_status IN ('active','trial') AND subscription_end < CURDATE()");
    $search = trim((string)($_GET['search'] ?? ''));
    $status = trim((string)($_GET['status'] ?? ''));

    $sql = "
        SELECT s.*, p.name AS package_name, p.price_monthly, p.price_6months, p.price_yearly,
               DATEDIFF(s.subscription_end, CURDATE()) AS days_left,
               (SELECT COUNT(*) FROM users u WHERE u.school_id = s.id AND u.role = 'employee') AS emp_count,
               (SELECT COUNT(*) FROM students st WHERE st.school_id = s.id) AS stu_count
        FROM schools s
        LEFT JOIN packages p ON p.id = s.package_id
        WHERE 1=1
    ";
    $params = [];
    if ($search !== '') {
        $sql .= " AND (s.name LIKE ? OR s.slug LIKE ? OR s.email LIKE ? OR s.phone LIKE ?)";
        $like = "%{$search}%";
        array_push($params, $like, $like, $like, $like);
    }
    if ($status === 'expiring') {
        $sql .= " AND s.subscription_status IN ('active','trial') AND s.subscription_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($status !== '') {
        $sql .= " AND s.subscription_status = ?";
        $params[] = $status;
    }
    $sql .= " ORDER BY s.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $schools = $stmt->fetchAll();

    $packages = $pdo->query("SELECT id, name, price_monthly, price_6months, price_yearly FROM packages WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

    Response::json([
        'success' => true,
        'data'    => [
            'schools'        => $schools,
            'packages'       => $packages,
            'display_domain' => Tenant::saasSettings()['display_domain'],
            'base_url'       => app_url('/'),
        ],
    ]);
}

if ($action === 'school-save') {
    Security::requireCsrf();
    $id          = (int)($body['id'] ?? 0);
    $name        = trim((string)($body['name'] ?? ''));
    $slug        = Security::slugify((string)($body['slug'] ?? $name));
    $packageId   = (int)($body['package_id'] ?? 0);
    $email       = strtolower(trim((string)($body['email'] ?? '')));
    $phone       = trim((string)($body['phone'] ?? ''));
    $address     = trim((string)($body['address'] ?? ''));
    $npsn        = trim((string)($body['npsn'] ?? ''));
    $duration    = (int)($body['duration_months'] ?? 1);
    $status      = in_array($body['subscription_status'] ?? 'active', ['active', 'trial', 'expired', 'suspended'], true)
        ? (string)$body['subscription_status'] : 'active';
    $adminEmail  = strtolower(trim((string)($body['admin_email'] ?? $email)));
    $adminPass   = (string)($body['admin_password'] ?? 'Admin123!');

    if ($name === '' || !$packageId) {
        Response::json(['success' => false, 'message' => 'Nama sekolah dan paket langganan wajib diisi.'], 422);
    }
    if (!Security::isValidSlug($slug)) {
        Response::json(['success' => false, 'message' => "Subfolder URL '{$slug}' tidak valid atau termasuk kata yang dipesan sistem."], 422);
    }

    // Check unique slug
    $dup = $pdo->prepare("SELECT id FROM schools WHERE slug = ? AND id <> ?");
    $dup->execute([$slug, $id]);
    if ($dup->fetch()) {
        Response::json(['success' => false, 'message' => "Subfolder '/{$slug}' sudah digunakan sekolah lain."], 422);
    }

    $pkgStmt = $pdo->prepare("SELECT * FROM packages WHERE id = ? LIMIT 1");
    $pkgStmt->execute([$packageId]);
    $pkg = $pkgStmt->fetch();
    if (!$pkg) {
        Response::json(['success' => false, 'message' => 'Paket langganan tidak ditemukan.'], 404);
    }

    if ($id > 0) {
        $stmt = $pdo->prepare("
            UPDATE schools
            SET package_id = ?, name = ?, slug = ?, npsn = ?, email = ?, phone = ?, address = ?, subscription_status = ?
            WHERE id = ?
        ");
        $stmt->execute([$packageId, $name, $slug, $npsn, $email, $phone, $address, $status, $id]);
        Response::json(['success' => true, 'mode' => 'updated', 'message' => "Data sekolah '{$name}' berhasil diperbarui."]);
    } else {
        if (!in_array($duration, [1, 6, 12], true)) {
            $duration = 1;
        }
        $start = date('Y-m-d');
        $end   = date('Y-m-d', strtotime("+{$duration} months"));
        $price = match ($duration) {
            6       => (float)$pkg['price_6months'],
            12      => (float)$pkg['price_yearly'],
            default => (float)$pkg['price_monthly'],
        };

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO schools (package_id, name, slug, npsn, email, phone, address, subscription_status, subscription_start, subscription_end)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$packageId, $name, $slug, $npsn, $email, $phone, $address, $status, $start, $end]);
            $newSchoolId = (int)$pdo->lastInsertId();

            // Create default shift for the new school
            $pdo->prepare("INSERT INTO shifts (school_id, name, start_time, end_time, late_tolerance_minutes, is_default) VALUES (?, 'Shift Pagi Reguler', '07:15:00', '15:00:00', 10, 1)")
                ->execute([$newSchoolId]);
            $defShiftId = (int)$pdo->lastInsertId();

            // Create default School Admin user
            $useEmail = $adminEmail !== '' ? $adminEmail : "admin@{$slug}.sch.id";
            $pdo->prepare("
                INSERT INTO users (school_id, role, nip_nik, name, email, phone, password_hash, position_title, default_shift_id, is_active)
                VALUES (?, 'school_admin', 'ADM-001', ?, ?, ?, ?, 'Admin Sekolah', ?, 1)
            ")->execute([$newSchoolId, 'Admin ' . $name, $useEmail, $phone, Security::hashPassword($adminPass ?: 'Admin123!'), $defShiftId]);

            // Record initial subscription invoice
            $inv = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $durLabel = $duration === 12 ? '1 Tahun' : ($duration === 6 ? '6 Bulan' : '1 Bulan');
            $pdo->prepare("
                INSERT INTO subscription_transactions (invoice_number, school_id, package_id, duration_months, amount, status, payment_method, notes, period_start, period_end, paid_at)
                VALUES (?, ?, ?, ?, ?, 'paid', 'Transfer Bank', ?, ?, ?, NOW())
            ")->execute([$inv, $newSchoolId, $packageId, $duration, $price, "Pendaftaran Baru {$durLabel} - {$pkg['name']}", $start, $end]);

            $pdo->commit();
            Response::json([
                'success' => true,
                'mode'    => 'saved',
                'message' => "Sekolah '{$name}' berhasil didaftarkan! Portal: /{$slug} | Admin: {$useEmail}",
            ]);
        } catch (Throwable $e) {
            $pdo->rollBack();
            Response::json(['success' => false, 'message' => 'Gagal menyimpan sekolah: ' . $e->getMessage()], 500);
        }
    }
}

if ($action === 'school-extend') {
    Security::requireCsrf();
    $schoolId      = (int)($body['school_id'] ?? 0);
    $duration      = (int)($body['duration_months'] ?? 1);
    $paymentMethod = trim((string)($body['payment_method'] ?? 'Transfer Bank'));
    $customAmount  = isset($body['amount']) && $body['amount'] !== '' ? (float)$body['amount'] : null;
    $notes         = trim((string)($body['notes'] ?? ''));

    if (!in_array($duration, [1, 6, 12], true)) {
        Response::json(['success' => false, 'message' => 'Durasi perpanjangan harus 1 bulan, 6 bulan, atau 12 bulan (1 tahun).'], 422);
    }

    $stmt = $pdo->prepare("
        SELECT s.*, p.name AS package_name, p.price_monthly, p.price_6months, p.price_yearly
        FROM schools s
        LEFT JOIN packages p ON p.id = s.package_id
        WHERE s.id = ? LIMIT 1
    ");
    $stmt->execute([$schoolId]);
    $school = $stmt->fetch();
    if (!$school) {
        Response::json(['success' => false, 'message' => 'Sekolah tidak ditemukan.'], 404);
    }

    $today = date('Y-m-d');
    $baseDate = (!empty($school['subscription_end']) && $school['subscription_end'] >= $today)
        ? $school['subscription_end']
        : $today;
    $newEnd = date('Y-m-d', strtotime($baseDate . " +{$duration} months"));

    $defaultPrice = match ($duration) {
        6       => (float)($school['price_6months'] ?? 0),
        12      => (float)($school['price_yearly'] ?? 0),
        default => (float)($school['price_monthly'] ?? 0),
    };
    $amount = $customAmount !== null ? $customAmount : $defaultPrice;
    $durLabel = $duration === 12 ? '1 Tahun (12 Bulan)' : ($duration === 6 ? '6 Bulan' : '1 Bulan');

    $pdo->beginTransaction();
    try {
        $pdo->prepare("
            UPDATE schools
            SET subscription_status = 'active',
                subscription_end = ?,
                last_reminded_h3_date = NULL,
                last_reminded_h0_date = NULL
            WHERE id = ?
        ")->execute([$newEnd, $schoolId]);

        $inv = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $pdo->prepare("
            INSERT INTO subscription_transactions (invoice_number, school_id, package_id, duration_months, amount, status, payment_method, notes, period_start, period_end, paid_at)
            VALUES (?, ?, ?, ?, ?, 'paid', ?, ?, ?, ?, NOW())
        ")->execute([
            $inv,
            $schoolId,
            $school['package_id'],
            $duration,
            $amount,
            $paymentMethod,
            $notes !== '' ? $notes : "Perpanjangan {$durLabel} - {$school['package_name']}",
            $baseDate,
            $newEnd,
        ]);
        $pdo->commit();

        Response::json([
            'success' => true,
            'mode'    => 'updated',
            'message' => "Masa aktif {$school['name']} berhasil diperpanjang {$durLabel} hingga " . format_date_id($newEnd) . " (Invoice {$inv}).",
        ]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        Response::json(['success' => false, 'message' => 'Gagal memperpanjang langganan: ' . $e->getMessage()], 500);
    }
}

if ($action === 'school-status') {
    Security::requireCsrf();
    $schoolId = (int)($body['school_id'] ?? 0);
    $status   = (string)($body['status'] ?? '');
    if (!in_array($status, ['active', 'trial', 'expired', 'suspended'], true)) {
        Response::json(['success' => false, 'message' => 'Status tidak valid.'], 422);
    }
    $pdo->prepare("UPDATE schools SET subscription_status = ? WHERE id = ?")->execute([$status, $schoolId]);
    Response::json(['success' => true, 'mode' => 'updated', 'message' => 'Status langganan sekolah diperbarui.']);
}

if ($action === 'school-delete') {
    Security::requireCsrf();
    $schoolId = (int)($body['school_id'] ?? 0);
    $pdo->prepare("DELETE FROM schools WHERE id = ?")->execute([$schoolId]);
    Response::json(['success' => true, 'message' => 'Sekolah dan seluruh datanya telah dihapus.']);
}

// =========================================================================
// DAFTAR PAKET & FITUR CHECKBOX
// =========================================================================
if ($action === 'packages-list') {
    $rows = $pdo->query("
        SELECT p.*, (SELECT COUNT(*) FROM schools s WHERE s.package_id = p.id) AS schools_count
        FROM packages p
        ORDER BY p.sort_order ASC, p.price_monthly ASC
    ")->fetchAll();
    foreach ($rows as &$r) {
        $r['features'] = Tenant::decodeFeatures($r['features_json']);
    }
    Response::json([
        'success' => true,
        'data'    => [
            'packages'         => $rows,
            'feature_registry' => SAAS_FEATURES,
        ],
    ]);
}

if ($action === 'package-save') {
    Security::requireCsrf();
    $id           = (int)($body['id'] ?? 0);
    $name         = trim((string)($body['name'] ?? ''));
    $slug         = Security::slugify((string)($body['slug'] ?? $name));
    $description  = trim((string)($body['description'] ?? ''));
    $priceMonthly = (float)($body['price_monthly'] ?? 0);
    $price6m      = (float)($body['price_6months'] ?? 0);
    $priceYearly  = (float)($body['price_yearly'] ?? 0);
    $maxEmp       = max(1, (int)($body['max_employees'] ?? 50));
    $maxStu       = max(1, (int)($body['max_students'] ?? 500));
    $isActive     = isset($body['is_active']) ? (int)(bool)$body['is_active'] : 1;
    $rawFeatures  = is_array($body['features'] ?? null) ? $body['features'] : [];

    // Filter only valid registered features
    $validKeys = array_keys(SAAS_FEATURES);
    $features  = array_values(array_intersect($rawFeatures, $validKeys));

    if ($name === '' || $slug === '') {
        Response::json(['success' => false, 'message' => 'Nama paket wajib diisi.'], 422);
    }

    if ($id > 0) {
        $stmt = $pdo->prepare("
            UPDATE packages
            SET name = ?, slug = ?, description = ?, price_monthly = ?, price_6months = ?, price_yearly = ?,
                max_employees = ?, max_students = ?, features_json = ?, is_active = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $slug, $description, $priceMonthly, $price6m, $priceYearly, $maxEmp, $maxStu, json_encode($features), $isActive, $id]);
        Response::json(['success' => true, 'mode' => 'updated', 'message' => "Paket '{$name}' berhasil diperbarui dengan " . count($features) . " fitur aktif."]);
    } else {
        $dup = $pdo->prepare("SELECT id FROM packages WHERE slug = ?");
        $dup->execute([$slug]);
        if ($dup->fetch()) {
            $slug .= '-' . random_int(10, 99);
        }
        $stmt = $pdo->prepare("
            INSERT INTO packages (name, slug, description, price_monthly, price_6months, price_yearly, max_employees, max_students, features_json, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $slug, $description, $priceMonthly, $price6m, $priceYearly, $maxEmp, $maxStu, json_encode($features), $isActive]);
        Response::json(['success' => true, 'mode' => 'saved', 'message' => "Paket baru '{$name}' berhasil dibuat dengan " . count($features) . " fitur tercentang."]);
    }
}

if ($action === 'package-delete') {
    Security::requireCsrf();
    $id = (int)($body['id'] ?? 0);
    $used = (int)$pdo->query("SELECT COUNT(*) FROM schools WHERE package_id = " . $id)->fetchColumn();
    if ($used > 0) {
        Response::json(['success' => false, 'message' => "Paket tidak dapat dihapus karena sedang digunakan oleh {$used} sekolah."], 422);
    }
    $pdo->prepare("DELETE FROM packages WHERE id = ?")->execute([$id]);
    Response::json(['success' => true, 'message' => 'Paket berhasil dihapus.']);
}

// =========================================================================
// KEUANGAN, OMSET, HARGA PAKET
// =========================================================================
if ($action === 'finance-summary') {
    $month = trim((string)($_GET['month'] ?? ''));

    $totalOmset = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM subscription_transactions WHERE status = 'paid'")->fetchColumn();
    $monthOmset = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM subscription_transactions WHERE status = 'paid' AND DATE_FORMAT(COALESCE(paid_at, created_at), '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')")->fetchColumn();
    $pendingSum = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM subscription_transactions WHERE status = 'pending'")->fetchColumn();
    $paidCount  = (int)$pdo->query("SELECT COUNT(*) FROM subscription_transactions WHERE status = 'paid'")->fetchColumn();

    $sql = "
        SELECT t.*, s.name AS school_name, s.slug AS school_slug, p.name AS package_name
        FROM subscription_transactions t
        JOIN schools s ON s.id = t.school_id
        LEFT JOIN packages p ON p.id = t.package_id
        WHERE 1=1
    ";
    $params = [];
    if ($month !== '' && preg_match('/^\d{4}-\d{2}$/', $month)) {
        $sql .= " AND DATE_FORMAT(COALESCE(t.paid_at, t.created_at), '%Y-%m') = ?";
        $params[] = $month;
    }
    $sql .= " ORDER BY t.created_at DESC LIMIT 200";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $packages = $pdo->query("SELECT id, name, price_monthly, price_6months, price_yearly, is_active FROM packages ORDER BY sort_order ASC")->fetchAll();
    $schools  = $pdo->query("SELECT id, name, package_id FROM schools ORDER BY name ASC")->fetchAll();

    Response::json([
        'success' => true,
        'data'    => [
            'summary'      => [
                'total_omset' => $totalOmset,
                'month_omset' => $monthOmset,
                'pending_sum' => $pendingSum,
                'paid_count'  => $paidCount,
            ],
            'transactions' => $stmt->fetchAll(),
            'packages'     => $packages,
            'schools'      => $schools,
        ],
    ]);
}

if ($action === 'transaction-save') {
    Security::requireCsrf();
    $id            = (int)($body['id'] ?? 0);
    $schoolId      = (int)($body['school_id'] ?? 0);
    $packageId     = (int)($body['package_id'] ?? 0) ?: null;
    $duration      = (int)($body['duration_months'] ?? 1);
    $amount        = (float)($body['amount'] ?? 0);
    $status        = in_array($body['status'] ?? 'paid', ['paid', 'pending', 'cancelled'], true) ? $body['status'] : 'paid';
    $paymentMethod = trim((string)($body['payment_method'] ?? 'Transfer Bank'));
    $notes         = trim((string)($body['notes'] ?? ''));

    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE subscription_transactions SET status = ?, payment_method = ?, amount = ?, notes = ?, paid_at = IF(?='paid', COALESCE(paid_at, NOW()), paid_at) WHERE id = ?");
        $stmt->execute([$status, $paymentMethod, $amount, $notes, $status, $id]);
        Response::json(['success' => true, 'mode' => 'updated', 'message' => 'Transaksi keuangan berhasil diperbarui.']);
    }

    if (!$schoolId || $amount < 0) {
        Response::json(['success' => false, 'message' => 'Sekolah dan nominal transaksi wajib diisi.'], 422);
    }

    $inv = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    $stmt = $pdo->prepare("
        INSERT INTO subscription_transactions (invoice_number, school_id, package_id, duration_months, amount, status, payment_method, notes, paid_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, IF(?='paid', NOW(), NULL))
    ");
    $stmt->execute([$inv, $schoolId, $packageId, $duration, $amount, $status, $paymentMethod, $notes, $status]);
    Response::json(['success' => true, 'mode' => 'saved', 'message' => "Transaksi {$inv} berhasil dicatat."]);
}

if ($action === 'package-price-quick') {
    Security::requireCsrf();
    $id = (int)($body['id'] ?? 0);
    $pdo->prepare("UPDATE packages SET price_monthly = ?, price_6months = ?, price_yearly = ? WHERE id = ?")
        ->execute([(float)$body['price_monthly'], (float)$body['price_6months'], (float)$body['price_yearly'], $id]);
    Response::json(['success' => true, 'mode' => 'updated', 'message' => 'Harga paket berhasil diperbarui.']);
}

// =========================================================================
// WA GATEWAY PENGINGAT JATUH TEMPO (H-3 & H-0)
// =========================================================================
if ($action === 'wa-config-get') {
    $saas = Tenant::saasSettings(true);
    $logs = $pdo->query("
        SELECT l.*, s.name AS school_name
        FROM wa_logs l
        LEFT JOIN schools s ON s.id = l.school_id
        ORDER BY l.sent_at DESC
        LIMIT 50
    ")->fetchAll();

    // Preview schools eligible for H-3 or H-0 right now
    $upcoming = $pdo->query("
        SELECT s.id, s.name, s.phone, s.subscription_end, DATEDIFF(s.subscription_end, CURDATE()) AS days_left, p.name AS package_name
        FROM schools s
        LEFT JOIN packages p ON p.id = s.package_id
        WHERE s.subscription_end IN (CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 DAY))
        ORDER BY s.subscription_end ASC
    ")->fetchAll();

    $schoolsList = $pdo->query("
        SELECT s.id, s.name, s.slug, s.phone, s.subscription_end, p.name AS package_name, p.price_monthly
        FROM schools s
        LEFT JOIN packages p ON p.id = s.package_id
        ORDER BY s.name ASC
    ")->fetchAll();

    $cfgStatus = WaGateway::isConfigured($saas);

    Response::json([
        'success' => true,
        'data'    => [
            'config'   => [
                'wa_provider'    => $saas['wa_provider'],
                'wa_api_url'     => $saas['wa_api_url'],
                'wa_api_token'   => $saas['wa_api_token'],
                'wa_sender'      => $saas['wa_sender'],
                'wa_enabled'     => (int)$saas['wa_enabled'],
                'wa_template_h3' => $saas['wa_template_h3'],
                'wa_template_h0' => $saas['wa_template_h0'],
                'cron_secret'    => $saas['cron_secret'],
                'cron_url'       => app_full_url('/cron/wa_reminder.php?key=' . urlencode((string)$saas['cron_secret'])),
                'is_ready'       => $cfgStatus['ready'],
                'ready_reason'   => $cfgStatus['reason'],
            ],
            'upcoming' => $upcoming,
            'schools'  => $schoolsList,
            'logs'     => $logs,
        ],
    ]);
}

if ($action === 'wa-config-save') {
    Security::requireCsrf();
    $provider = strtolower(trim((string)($body['wa_provider'] ?? 'fonnte')));
    $apiUrl   = trim((string)($body['wa_api_url'] ?? ''));
    $apiToken = trim((string)($body['wa_api_token'] ?? ''));
    $sender   = trim((string)($body['wa_sender'] ?? ''));
    $enabled  = !empty($body['wa_enabled']) ? 1 : 0;

    if ($enabled === 1) {
        if ($apiUrl === '' || !filter_var($apiUrl, FILTER_VALIDATE_URL)) {
            Response::json(['success' => false, 'message' => 'API Endpoint URL wajib valid sebelum mengaktifkan Live WA Gateway.'], 422);
        }
        if ($apiToken === '') {
            Response::json(['success' => false, 'message' => 'API Token / Key wajib diisi sebelum mengaktifkan Live WA Gateway.'], 422);
        }
    }

    $stmt = $pdo->prepare("
        UPDATE saas_settings
        SET wa_provider = ?, wa_api_url = ?, wa_api_token = ?, wa_sender = ?, wa_enabled = ?,
            wa_template_h3 = ?, wa_template_h0 = ?, cron_secret = ?
        WHERE id = 1
    ");
    $stmt->execute([
        $provider,
        $apiUrl,
        $apiToken,
        $sender,
        $enabled,
        (string)($body['wa_template_h3'] ?? ''),
        (string)($body['wa_template_h0'] ?? ''),
        trim((string)($body['cron_secret'] ?? 'edupresensi-cron-2026')),
    ]);
    Tenant::saasSettings(true);
    Response::json(['success' => true, 'mode' => 'updated', 'message' => 'Pengaturan Live WA Gateway berhasil disimpan!']);
}

if ($action === 'wa-test') {
    Security::requireCsrf();
    $phone = trim((string)($body['phone'] ?? ''));
    $msg   = trim((string)($body['message'] ?? 'Tes koneksi Live WA Gateway dari EduPresensi SaaS.'));
    if ($phone === '') {
        Response::json(['success' => false, 'message' => 'Nomor WhatsApp tujuan wajib diisi.'], 422);
    }
    $res = WaGateway::send(null, $phone, $msg, 'test');
    Response::json([
        'success' => $res['ok'],
        'mode'    => 'saved',
        'message' => $res['ok']
            ? "Berhasil terkirim ke {$phone}! {$res['response']}"
            : $res['response'],
        'data'    => $res,
    ], $res['ok'] ? 200 : 400);
}

if ($action === 'wa-send-school') {
    Security::requireCsrf();
    $schoolId = (int)($body['school_id'] ?? 0);
    $tplType  = ($body['template_type'] ?? 'h3') === 'h0' ? 'h0' : 'h3';

    $stmt = $pdo->prepare("
        SELECT s.*, p.name AS package_name, p.price_monthly
        FROM schools s
        LEFT JOIN packages p ON p.id = s.package_id
        WHERE s.id = ? LIMIT 1
    ");
    $stmt->execute([$schoolId]);
    $sc = $stmt->fetch();
    if (!$sc) {
        Response::json(['success' => false, 'message' => 'Sekolah tidak ditemukan.'], 404);
    }
    if (empty($sc['phone'])) {
        Response::json(['success' => false, 'message' => "Nomor WhatsApp sekolah '{$sc['name']}' belum diisi."], 422);
    }

    $saas = Tenant::saasSettings(true);
    $tpl  = $tplType === 'h0' ? $saas['wa_template_h0'] : $saas['wa_template_h3'];
    $msg  = WaGateway::renderTemplate($tpl, [
        'app_name'        => $saas['app_name'],
        'nama_sekolah'    => $sc['name'],
        'paket'           => $sc['package_name'] ?? 'Standar',
        'tanggal_expired' => format_date_id($sc['subscription_end']),
        'nominal'         => format_rupiah($sc['price_monthly'] ?? 0),
        'link_portal'     => 'https://' . Tenant::schoolDisplayUrl($sc['slug']),
    ]);

    $res = WaGateway::send((int)$sc['id'], $sc['phone'], $msg, 'manual');
    Response::json([
        'success' => $res['ok'],
        'mode'    => 'saved',
        'message' => $res['ok']
            ? "Pengingat WA berhasil dikirim ke {$sc['name']} ({$sc['phone']}). {$res['response']}"
            : $res['response'],
        'data'    => $res,
    ], $res['ok'] ? 200 : 400);
}

if ($action === 'wa-run-reminders') {
    Security::requireCsrf();
    $res = WaGateway::runDueReminders();
    if (empty($res['ok'])) {
        Response::json(['success' => false, 'message' => $res['error'] ?: 'WA Gateway belum siap.'], 422);
    }
    $total = $res['h3_sent'] + $res['h0_sent'];
    Response::json([
        'success' => $res['failed'] === 0 || $total > 0,
        'mode'    => 'saved',
        'message' => "Pengiriman selesai! Terkirim nyata: {$res['h3_sent']} pengingat H-3, {$res['h0_sent']} pengingat Hari-H, Gagal: {$res['failed']}." . ($total === 0 && $res['failed'] === 0 ? ' Tidak ada sekolah yang tepat jatuh tempo H-3 atau Hari-H hari ini.' : ''),
        'data'    => $res,
    ]);
}

// =========================================================================
// BRANDING LOGO & FAVICON GLOBAL SAAS
// =========================================================================
if ($action === 'branding-get') {
    $saas = Tenant::saasSettings(true);
    Response::json([
        'success' => true,
        'data'    => [
            'app_name'       => $saas['app_name'],
            'tagline'        => $saas['tagline'],
            'display_domain' => $saas['display_domain'],
            'logo_url'       => Tenant::brandingAssetUrl($saas['logo_path'], 'assets/img/default-logo.svg'),
            'favicon_url'    => Tenant::brandingAssetUrl($saas['favicon_path'], 'assets/img/default-favicon.svg'),
        ],
    ]);
}

if ($action === 'branding-save') {
    Security::requireCsrf();
    $saas          = Tenant::saasSettings(true);
    $appName       = trim((string)($_POST['app_name'] ?? $saas['app_name']));
    $tagline       = trim((string)($_POST['tagline'] ?? $saas['tagline']));
    $displayDomain = trim((string)($_POST['display_domain'] ?? $saas['display_domain']));
    $logoPath      = $saas['logo_path'];
    $faviconPath   = $saas['favicon_path'];

    if (!empty($_FILES['logo']['name'])) {
        $up = Security::uploadImage($_FILES['logo'], 'branding', ['png', 'jpg', 'jpeg', 'webp', 'svg'], 2097152);
        if (!$up['ok']) {
            Response::json(['success' => false, 'message' => 'Logo: ' . $up['error']], 422);
        }
        $logoPath = $up['path'];
    }

    if (!empty($_FILES['favicon']['name'])) {
        $upFav = Security::uploadImage($_FILES['favicon'], 'branding', ['png', 'ico', 'svg', 'webp', 'jpg', 'jpeg'], 1048576);
        if (!$upFav['ok']) {
            Response::json(['success' => false, 'message' => 'Favicon: ' . $upFav['error']], 422);
        }
        $faviconPath = $upFav['path'];
    }

    $pdo->prepare("UPDATE saas_settings SET app_name = ?, tagline = ?, display_domain = ?, logo_path = ?, favicon_path = ? WHERE id = 1")
        ->execute([$appName ?: 'EduPresensi', $tagline, $displayDomain ?: 'eduabsen.sekolah-indonesia.id', $logoPath, $faviconPath]);

    Tenant::saasSettings(true);
    Response::json([
        'success' => true,
        'mode'    => 'updated',
        'message' => 'Branding Logo, Favicon & PWA Icon seluruh aplikasi SaaS berhasil diperbarui!',
        'data'    => [
            'logo_url'    => Tenant::brandingAssetUrl($logoPath, 'assets/img/default-logo.svg'),
            'favicon_url' => Tenant::brandingAssetUrl($faviconPath, 'assets/img/default-favicon.svg'),
        ],
    ]);
}

// =========================================================================
// AKUN & UBAH PASSWORD SUPERADMIN
// =========================================================================
if ($action === 'account-get') {
    Response::json([
        'success' => true,
        'data'    => [
            'id'            => (int)$superadmin['id'],
            'name'          => $superadmin['name'],
            'username'      => $superadmin['email'],
            'phone'         => $superadmin['phone'],
            'last_login_at' => $superadmin['last_login_at'],
        ],
    ]);
}

if ($action === 'account-update') {
    Security::requireCsrf();
    $name     = trim((string)($body['name'] ?? ''));
    $username = strtolower(trim((string)($body['username'] ?? '')));
    $phone    = trim((string)($body['phone'] ?? ''));

    if ($name === '' || $username === '') {
        Response::json(['success' => false, 'message' => 'Nama dan username Superadmin wajib diisi.'], 422);
    }
    if (preg_match('/\s/', $username)) {
        Response::json(['success' => false, 'message' => 'Username tidak boleh mengandung spasi.'], 422);
    }

    $dup = $pdo->prepare("SELECT id FROM users WHERE role = 'superadmin' AND LOWER(email) = ? AND id <> ? LIMIT 1");
    $dup->execute([$username, $superadmin['id']]);
    if ($dup->fetch()) {
        Response::json(['success' => false, 'message' => "Username '{$username}' sudah digunakan akun Superadmin lain."], 422);
    }

    $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ? AND role = 'superadmin'")
        ->execute([$name, $username, $phone, $superadmin['id']]);

    Response::json(['success' => true, 'mode' => 'updated', 'message' => "Profil & username Superadmin ('{$username}') berhasil diperbarui!"]);
}

if ($action === 'password-update') {
    Security::requireCsrf();
    $oldPass     = (string)($body['old_password'] ?? '');
    $newPass     = (string)($body['new_password'] ?? '');
    $confirmPass = (string)($body['confirm_password'] ?? '');

    if ($oldPass === '' || $newPass === '') {
        Response::json(['success' => false, 'message' => 'Password lama dan password baru wajib diisi.'], 422);
    }
    if (strlen($newPass) < 6) {
        Response::json(['success' => false, 'message' => 'Password baru minimal 6 karakter.'], 422);
    }
    if ($confirmPass !== '' && $newPass !== $confirmPass) {
        Response::json(['success' => false, 'message' => 'Konfirmasi password baru tidak cocok.'], 422);
    }
    if (!Security::verifyPassword($oldPass, $superadmin['password_hash'])) {
        Response::json(['success' => false, 'message' => 'Password lama tidak sesuai.'], 422);
    }

    $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ? AND role = 'superadmin'")
        ->execute([Security::hashPassword($newPass), $superadmin['id']]);

    Response::json(['success' => true, 'mode' => 'updated', 'message' => 'Password Superadmin berhasil diperbarui!']);
}

Response::json(['success' => false, 'message' => 'Aksi Superadmin tidak dikenali.'], 404);
