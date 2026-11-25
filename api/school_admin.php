<?php
declare(strict_types=1);

$action = $apiAction ?? '';
$body   = Response::getJsonBody();
$slug   = strtolower(trim((string)($_GET['school_slug'] ?? $_POST['school_slug'] ?? $body['school_slug'] ?? '')));

$school = Tenant::findSchoolBySlug($slug);
if (!$school) {
    Response::json(['success' => false, 'message' => 'Tenant sekolah tidak ditemukan.'], 404);
}
$admin    = Auth::requireSchoolRole($school, ['school_admin'], true);
$schoolId = (int)$school['id'];
$pdo      = Database::pdo();

// =========================================================================
// 1. DASHBOARD MONITORING PEGAWAI & SISWA
// =========================================================================
if ($action === 'dashboard') {
    $today = date('Y-m-d');
    $totalEmp = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE school_id = {$schoolId} AND role = 'employee' AND is_active = 1")->fetchColumn();
    $totalStu = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE school_id = {$schoolId} AND is_active = 1")->fetchColumn();
    $totalTeachers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE school_id = {$schoolId} AND role = 'teacher' AND is_active = 1")->fetchColumn();

    $empTodayStmt = $pdo->prepare("
        SELECT status, COUNT(*) AS cnt FROM employee_attendances
        WHERE school_id = ? AND attendance_date = ?
        GROUP BY status
    ");
    $empTodayStmt->execute([$schoolId, $today]);
    $empCounts = ['hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'dinas_luar' => 0, 'alpha' => 0];
    foreach ($empTodayStmt->fetchAll() as $r) {
        $empCounts[$r['status']] = (int)$r['cnt'];
    }
    $empPresent = $empCounts['hadir'] + $empCounts['terlambat'] + $empCounts['dinas_luar'];
    $empNotYet  = max(0, $totalEmp - array_sum($empCounts));

    $stuTodayStmt = $pdo->prepare("
        SELECT r.status, COUNT(*) AS cnt
        FROM student_attendance_records r
        JOIN student_attendance_sessions s ON s.id = r.session_id
        WHERE r.school_id = ? AND s.session_date = ?
        GROUP BY r.status
    ");
    $stuTodayStmt->execute([$schoolId, $today]);
    $stuCounts = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpha' => 0];
    foreach ($stuTodayStmt->fetchAll() as $r) {
        $stuCounts[$r['status']] = (int)$r['cnt'];
    }

    // 7-day trend chart
    $trend = [];
    for ($i = 6; $i >= 0; $i--) {
        $dt = date('Y-m-d', strtotime("-{$i} days"));
        $eCnt = $pdo->prepare("SELECT COUNT(*) FROM employee_attendances WHERE school_id = ? AND attendance_date = ? AND status IN ('hadir','terlambat','dinas_luar')");
        $eCnt->execute([$schoolId, $dt]);
        $sCnt = $pdo->prepare("SELECT COUNT(*) FROM student_attendance_records r JOIN student_attendance_sessions s ON s.id = r.session_id WHERE r.school_id = ? AND s.session_date = ? AND r.status = 'hadir'");
        $sCnt->execute([$schoolId, $dt]);
        $trend[] = [
            'date'  => date('d M', strtotime($dt)),
            'emp'   => (int)$eCnt->fetchColumn(),
            'stu'   => (int)$sCnt->fetchColumn(),
        ];
    }

    $pendingCorr = $pdo->prepare("
        SELECT c.*, u.name AS employee_name, u.position_title
        FROM attendance_corrections c
        JOIN users u ON u.id = c.user_id
        WHERE c.school_id = ? AND c.status = 'pending'
        ORDER BY c.created_at DESC LIMIT 6
    ");
    $pendingCorr->execute([$schoolId]);

    $latestLogs = $pdo->prepare("
        SELECT a.*, u.name AS employee_name, u.position_title
        FROM employee_attendances a
        JOIN users u ON u.id = a.user_id
        WHERE a.school_id = ? AND a.attendance_date = ?
        ORDER BY COALESCE(a.checkout_time, a.checkin_time, a.updated_at) DESC
        LIMIT 8
    ");
    $latestLogs->execute([$schoolId, $today]);

    Response::json([
        'success' => true,
        'data'    => [
            'totals'              => ['employees' => $totalEmp, 'students' => $totalStu, 'teachers' => $totalTeachers],
            'emp_today'           => array_merge($empCounts, ['present' => $empPresent, 'not_yet' => $empNotYet]),
            'stu_today'           => $stuCounts,
            'trend'               => $trend,
            'pending_corrections' => $pendingCorr->fetchAll(),
            'latest_logs'         => $latestLogs->fetchAll(),
            'school'              => [
                'name'             => $school['name'],
                'package_name'     => $school['package_name'],
                'subscription_end' => format_date_id($school['subscription_end']),
            ],
        ],
    ]);
}

// =========================================================================
// 2. SHIFTS MASTER & PENUGASAN PEGAWAI
// =========================================================================
function ensure_shift_columns(PDO $pdo): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM shifts")->fetchAll(PDO::FETCH_COLUMN);
        $alters = [
            'code'                     => "ADD COLUMN `code` VARCHAR(20) NOT NULL DEFAULT 'REG' AFTER `school_id`",
            'checkin_open_before'      => "ADD COLUMN `checkin_open_before` INT UNSIGNED NOT NULL DEFAULT 60 AFTER `end_time`",
            'max_late_minutes'         => "ADD COLUMN `max_late_minutes` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `late_tolerance_minutes`",
            'early_checkout_tolerance' => "ADD COLUMN `early_checkout_tolerance` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `max_late_minutes`",
            'is_cross_day'             => "ADD COLUMN `is_cross_day` TINYINT(1) NOT NULL DEFAULT 0 AFTER `early_checkout_tolerance`",
            'color'                    => "ADD COLUMN `color` VARCHAR(20) NOT NULL DEFAULT '#1E5BF0' AFTER `is_cross_day`",
            'description'              => "ADD COLUMN `description` VARCHAR(200) NULL AFTER `color`",
            'is_active'                => "ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_default`",
        ];
        foreach ($alters as $col => $sqlPart) {
            if (!in_array($col, $cols, true)) {
                $pdo->exec("ALTER TABLE `shifts` {$sqlPart}");
            }
        }
    } catch (Throwable) {
    }
}

if ($action === 'shifts-list') {
    ensure_shift_columns($pdo);
    $stmt = $pdo->prepare("
        SELECT sh.*,
               (SELECT COUNT(*) FROM users u WHERE u.school_id = sh.school_id AND u.role = 'employee' AND u.is_active = 1 AND u.default_shift_id = sh.id) AS employee_count
        FROM shifts sh
        WHERE sh.school_id = ?
        ORDER BY sh.is_default DESC, sh.is_active DESC, sh.name ASC
    ");
    $stmt->execute([$schoolId]);
    $shifts = $stmt->fetchAll();

    $empStmt = $pdo->prepare("
        SELECT u.id, u.nip_nik, u.name, u.position_title, u.default_shift_id, sh.name AS shift_name, sh.code AS shift_code, sh.color AS shift_color,
               (SELECT COUNT(*) FROM employee_schedules es WHERE es.user_id = u.id) AS custom_days_count
        FROM users u
        LEFT JOIN shifts sh ON sh.id = u.default_shift_id
        WHERE u.school_id = ? AND u.role = 'employee' AND u.is_active = 1
        ORDER BY u.name ASC
    ");
    $empStmt->execute([$schoolId]);
    $employees = $empStmt->fetchAll();

    Response::json([
        'success' => true,
        'data'    => [
            'shifts'    => $shifts,
            'employees' => $employees,
        ],
    ]);
}

if ($action === 'shift-save') {
    Security::requireCsrf();
    ensure_shift_columns($pdo);

    $id             = (int)($body['id'] ?? 0);
    $code           = strtoupper(trim((string)($body['code'] ?? '')));
    $name           = trim((string)($body['name'] ?? ''));
    $startTime      = trim((string)($body['start_time'] ?? '07:15'));
    $endTime        = trim((string)($body['end_time'] ?? '15:00'));
    $openBefore     = max(0, min(480, (int)($body['checkin_open_before'] ?? 60)));
    $tolerance      = max(0, min(300, (int)($body['late_tolerance_minutes'] ?? 10)));
    $maxLate        = max(0, min(720, (int)($body['max_late_minutes'] ?? 0)));
    $earlyCheckout  = max(0, min(240, (int)($body['early_checkout_tolerance'] ?? 0)));
    $isCrossDay     = !empty($body['is_cross_day']) ? 1 : 0;
    $color          = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($body['color'] ?? '')) ? (string)$body['color'] : '#1E5BF0';
    $description    = trim((string)($body['description'] ?? ''));
    $isDefault      = !empty($body['is_default']) ? 1 : 0;
    $isActive       = isset($body['is_active']) ? (!empty($body['is_active']) ? 1 : 0) : 1;

    if ($name === '') {
        Response::json(['success' => false, 'message' => 'Nama shift wajib diisi.'], 422);
    }
    if ($code === '') {
        $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'SHF', 0, 6));
    }
    if (strlen($startTime) === 5) $startTime .= ':00';
    if (strlen($endTime) === 5) $endTime .= ':00';

    if ($isDefault) {
        $pdo->prepare("UPDATE shifts SET is_default = 0 WHERE school_id = ?")->execute([$schoolId]);
    }

    if ($id > 0) {
        $pdo->prepare("
            UPDATE shifts
            SET code = ?, name = ?, start_time = ?, end_time = ?, checkin_open_before = ?,
                late_tolerance_minutes = ?, max_late_minutes = ?, early_checkout_tolerance = ?,
                is_cross_day = ?, color = ?, description = ?, is_default = ?, is_active = ?
            WHERE id = ? AND school_id = ?
        ")->execute([
            $code, $name, $startTime, $endTime, $openBefore,
            $tolerance, $maxLate, $earlyCheckout,
            $isCrossDay, $color, $description ?: null, $isDefault, $isActive, $id, $schoolId
        ]);
        Response::json(['success' => true, 'mode' => 'updated', 'message' => "Shift '{$name}' berhasil diperbarui."]);
    } else {
        $pdo->prepare("
            INSERT INTO shifts (school_id, code, name, start_time, end_time, checkin_open_before, late_tolerance_minutes, max_late_minutes, early_checkout_tolerance, is_cross_day, color, description, is_default, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $schoolId, $code, $name, $startTime, $endTime, $openBefore,
            $tolerance, $maxLate, $earlyCheckout,
            $isCrossDay, $color, $description ?: null, $isDefault, $isActive
        ]);
        Response::json(['success' => true, 'mode' => 'saved', 'message' => "Shift baru '{$name}' berhasil dibuat."]);
    }
}

if ($action === 'shift-set-default') {
    Security::requireCsrf();
    $id = (int)($body['id'] ?? 0);
    $pdo->prepare("UPDATE shifts SET is_default = 0 WHERE school_id = ?")->execute([$schoolId]);
    $pdo->prepare("UPDATE shifts SET is_default = 1, is_active = 1 WHERE id = ? AND school_id = ?")->execute([$id, $schoolId]);
    Response::json(['success' => true, 'mode' => 'updated', 'message' => 'Shift default sekolah berhasil diubah.']);
}

if ($action === 'shift-assign-employees') {
    Security::requireCsrf();
    $shiftId = (int)($body['shift_id'] ?? 0);
    $userIds = is_array($body['user_ids'] ?? null) ? array_map('intval', $body['user_ids']) : [];

    $shChk = $pdo->prepare("SELECT id, name FROM shifts WHERE id = ? AND school_id = ? LIMIT 1");
    $shChk->execute([$shiftId, $schoolId]);
    $sh = $shChk->fetch();
    if (!$sh) {
        Response::json(['success' => false, 'message' => 'Shift tidak ditemukan.'], 404);
    }

    // Clear employees who were previously assigned to this shift if unchecked, then assign selected
    $pdo->beginTransaction();
    try {
        if (!empty($body['sync_mode'])) {
            $pdo->prepare("UPDATE users SET default_shift_id = NULL WHERE school_id = ? AND role = 'employee' AND default_shift_id = ?")
                ->execute([$schoolId, $shiftId]);
        }
        $upd = $pdo->prepare("UPDATE users SET default_shift_id = ? WHERE id = ? AND school_id = ? AND role = 'employee'");
        $count = 0;
        foreach ($userIds as $uid) {
            if ($uid > 0) {
                $upd->execute([$shiftId, $uid, $schoolId]);
                $count += $upd->rowCount() >= 0 ? 1 : 0;
            }
        }
        $pdo->commit();
        Response::json([
            'success' => true,
            'mode'    => 'updated',
            'message' => "Penugasan shift '{$sh['name']}' berhasil disesuaikan untuk {$count} pegawai.",
        ]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        Response::json(['success' => false, 'message' => 'Gagal menetapkan shift pegawai: ' . $e->getMessage()], 500);
    }
}

if ($action === 'employee-shift-quick') {
    Security::requireCsrf();
    $userId  = (int)($body['user_id'] ?? 0);
    $shiftId = !empty($body['shift_id']) ? (int)$body['shift_id'] : null;

    if ($shiftId !== null) {
        $chk = $pdo->prepare("SELECT id FROM shifts WHERE id = ? AND school_id = ?");
        $chk->execute([$shiftId, $schoolId]);
        if (!$chk->fetch()) {
            Response::json(['success' => false, 'message' => 'Shift tidak valid.'], 422);
        }
    }
    $pdo->prepare("UPDATE users SET default_shift_id = ? WHERE id = ? AND school_id = ? AND role = 'employee'")
        ->execute([$shiftId, $userId, $schoolId]);
    Response::json(['success' => true, 'mode' => 'updated', 'message' => 'Shift pegawai berhasil diperbarui.']);
}

if ($action === 'shift-delete') {
    Security::requireCsrf();
    $id = (int)($body['id'] ?? 0);
    $pdo->prepare("DELETE FROM shifts WHERE id = ? AND school_id = ?")->execute([$id, $schoolId]);
    Response::json(['success' => true, 'message' => 'Shift berhasil dihapus.']);
}

// =========================================================================
// 2B. KELOLA KANTOR / LOKASI & SHIFT PER KANTOR
// =========================================================================
function ensure_office_schema(PDO $pdo, array $school): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `offices` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `school_id` INT UNSIGNED NOT NULL,
              `code` VARCHAR(20) NOT NULL DEFAULT 'KTR',
              `name` VARCHAR(150) NOT NULL,
              `address` VARCHAR(255) NULL,
              `latitude` DECIMAL(10,7) NOT NULL,
              `longitude` DECIMAL(10,7) NOT NULL,
              `radius` INT UNSIGNED NOT NULL DEFAULT 150,
              `is_default` TINYINT(1) NOT NULL DEFAULT 0,
              `is_active` TINYINT(1) NOT NULL DEFAULT 1,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              INDEX `idx_offices_school` (`school_id`),
              CONSTRAINT `fk_offices_school` FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `office_shifts` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `school_id` INT UNSIGNED NOT NULL,
              `office_id` INT UNSIGNED NOT NULL,
              `shift_id` INT UNSIGNED NOT NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              UNIQUE KEY `uq_office_shift` (`office_id`, `shift_id`),
              INDEX `idx_os_school` (`school_id`),
              CONSTRAINT `fk_os_school` FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`) ON DELETE CASCADE,
              CONSTRAINT `fk_os_office` FOREIGN KEY (`office_id`) REFERENCES `offices`(`id`) ON DELETE CASCADE,
              CONSTRAINT `fk_os_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $userCols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('default_office_id', $userCols, true)) {
            $pdo->exec("ALTER TABLE `users` ADD COLUMN `default_office_id` INT UNSIGNED NULL AFTER `assigned_classes`");
        }

        $esCols = $pdo->query("SHOW COLUMNS FROM employee_schedules")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('office_id', $esCols, true)) {
            $pdo->exec("ALTER TABLE `employee_schedules` ADD COLUMN `office_id` INT UNSIGNED NULL AFTER `specific_date`");
        }

        // Seed 1 default office from current school coordinates if school has no offices yet
        $sid = (int)$school['id'];
        $cnt = (int)$pdo->query("SELECT COUNT(*) FROM offices WHERE school_id = {$sid}")->fetchColumn();
        if ($cnt === 0) {
            $locName = !empty($school['office_location_name']) ? $school['office_location_name'] : ('Kantor Utama - ' . $school['name']);
            $pdo->prepare("
                INSERT INTO offices (school_id, code, name, address, latitude, longitude, radius, is_default, is_active)
                VALUES (?, 'UTM', ?, ?, ?, ?, ?, 1, 1)
            ")->execute([
                $sid,
                $locName,
                $school['address'] ?? '',
                (float)$school['office_lat'],
                (float)$school['office_lng'],
                (int)$school['office_radius'],
            ]);
            $newOfficeId = (int)$pdo->lastInsertId();
            // Attach all existing school shifts to this initial default office
            $shIds = $pdo->query("SELECT id FROM shifts WHERE school_id = {$sid}")->fetchAll(PDO::FETCH_COLUMN);
            $insOs = $pdo->prepare("INSERT IGNORE INTO office_shifts (school_id, office_id, shift_id) VALUES (?, ?, ?)");
            foreach ($shIds as $shId) {
                $insOs->execute([$sid, $newOfficeId, (int)$shId]);
            }
        }
    } catch (Throwable) {
    }
}

if ($action === 'offices-list') {
    ensure_shift_columns($pdo);
    ensure_school_location_columns($pdo);
    ensure_office_schema($pdo, $school);

    $ofStmt = $pdo->prepare("
        SELECT o.*,
               (SELECT COUNT(*) FROM users u WHERE u.school_id = o.school_id AND u.role = 'employee' AND u.is_active = 1 AND u.default_office_id = o.id) AS employee_count
        FROM offices o
        WHERE o.school_id = ?
        ORDER BY o.is_default DESC, o.is_active DESC, o.name ASC
    ");
    $ofStmt->execute([$schoolId]);
    $offices = $ofStmt->fetchAll();

    // Load shifts mapped per office
    $osStmt = $pdo->prepare("
        SELECT os.office_id, sh.id, sh.code, sh.name, sh.start_time, sh.end_time, sh.late_tolerance_minutes, sh.color
        FROM office_shifts os
        JOIN shifts sh ON sh.id = os.shift_id
        WHERE os.school_id = ?
        ORDER BY sh.is_default DESC, sh.name ASC
    ");
    $osStmt->execute([$schoolId]);
    $shiftsByOffice = [];
    foreach ($osStmt->fetchAll() as $row) {
        $shiftsByOffice[(int)$row['office_id']][] = $row;
    }

    foreach ($offices as &$of) {
        $oid = (int)$of['id'];
        $of['shifts']    = $shiftsByOffice[$oid] ?? [];
        $of['shift_ids'] = array_map(static fn($s) => (int)$s['id'], $of['shifts']);
    }

    $allShifts = $pdo->prepare("SELECT * FROM shifts WHERE school_id = ? AND is_active = 1 ORDER BY is_default DESC, name ASC");
    $allShifts->execute([$schoolId]);

    $allEmps = $pdo->prepare("
        SELECT u.id, u.nip_nik, u.name, u.position_title, u.default_office_id, u.default_shift_id,
               o.name AS office_name, sh.name AS shift_name
        FROM users u
        LEFT JOIN offices o ON o.id = u.default_office_id
        LEFT JOIN shifts sh ON sh.id = u.default_shift_id
        WHERE u.school_id = ? AND u.role = 'employee' AND u.is_active = 1
        ORDER BY u.name ASC
    ");
    $allEmps->execute([$schoolId]);

    Response::json([
        'success' => true,
        'data'    => [
            'offices'   => $offices,
            'shifts'    => $allShifts->fetchAll(),
            'employees' => $allEmps->fetchAll(),
        ],
    ]);
}

if ($action === 'office-save') {
    Security::requireCsrf();
    ensure_shift_columns($pdo);
    ensure_school_location_columns($pdo);
    ensure_office_schema($pdo, $school);

    $id        = (int)($body['id'] ?? 0);
    $code      = strtoupper(trim((string)($body['code'] ?? '')));
    $name      = trim((string)($body['name'] ?? ''));
    $address   = trim((string)($body['address'] ?? ''));
    $lat       = isset($body['latitude']) && $body['latitude'] !== '' ? (float)$body['latitude'] : null;
    $lng       = isset($body['longitude']) && $body['longitude'] !== '' ? (float)$body['longitude'] : null;
    $radius    = max(20, (int)($body['radius'] ?? 150));
    $isDefault = !empty($body['is_default']) ? 1 : 0;
    $isActive  = isset($body['is_active']) ? (!empty($body['is_active']) ? 1 : 0) : 1;
    $shiftIds  = is_array($body['shift_ids'] ?? null) ? array_values(array_unique(array_map('intval', $body['shift_ids']))) : [];

    if ($name === '') {
        Response::json(['success' => false, 'message' => 'Nama kantor / lokasi wajib diisi.'], 422);
    }
    if ($lat === null || $lng === null || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        Response::json(['success' => false, 'message' => 'Koordinat Latitude dan Longitude kantor tidak valid.'], 422);
    }
    if ($code === '') {
        $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'KTR', 0, 5));
    }

    $pdo->beginTransaction();
    try {
        if ($isDefault) {
            $pdo->prepare("UPDATE offices SET is_default = 0 WHERE school_id = ?")->execute([$schoolId]);
            // Sync global school default coordinates with the default office
            $pdo->prepare("UPDATE schools SET office_location_name = ?, office_lat = ?, office_lng = ?, office_radius = ? WHERE id = ?")
                ->execute([$name, $lat, $lng, $radius, $schoolId]);
        }

        if ($id > 0) {
            $pdo->prepare("
                UPDATE offices
                SET code = ?, name = ?, address = ?, latitude = ?, longitude = ?, radius = ?, is_default = ?, is_active = ?
                WHERE id = ? AND school_id = ?
            ")->execute([$code, $name, $address, $lat, $lng, $radius, $isDefault, $isActive, $id, $schoolId]);
            $officeId = $id;
            $mode = 'updated';
            $msg  = "Kantor '{$name}' beserta " . count($shiftIds) . " shift di dalamnya berhasil diperbarui.";
        } else {
            $pdo->prepare("
                INSERT INTO offices (school_id, code, name, address, latitude, longitude, radius, is_default, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([$schoolId, $code, $name, $address, $lat, $lng, $radius, $isDefault, $isActive]);
            $officeId = (int)$pdo->lastInsertId();
            $mode = 'saved';
            $msg  = "Kantor baru '{$name}' berhasil ditambahkan dengan " . count($shiftIds) . " shift.";
        }

        // Sync office_shifts
        $pdo->prepare("DELETE FROM office_shifts WHERE office_id = ? AND school_id = ?")->execute([$officeId, $schoolId]);
        if (!empty($shiftIds)) {
            $insOs = $pdo->prepare("INSERT IGNORE INTO office_shifts (school_id, office_id, shift_id) VALUES (?, ?, ?)");
            foreach ($shiftIds as $shId) {
                if ($shId > 0) {
                    $insOs->execute([$schoolId, $officeId, $shId]);
                }
            }
        }

        $pdo->commit();
        Response::json(['success' => true, 'mode' => $mode, 'message' => $msg, 'data' => ['office_id' => $officeId]]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        Response::json(['success' => false, 'message' => 'Gagal menyimpan kantor: ' . $e->getMessage()], 500);
    }
}

if ($action === 'office-assign-employees') {
    Security::requireCsrf();
    ensure_office_schema($pdo, $school);

    $officeId = (int)($body['office_id'] ?? 0);
    $userIds  = is_array($body['user_ids'] ?? null) ? array_map('intval', $body['user_ids']) : [];
    $shiftId  = !empty($body['shift_id']) ? (int)$body['shift_id'] : null;

    $ofChk = $pdo->prepare("SELECT id, name FROM offices WHERE id = ? AND school_id = ? LIMIT 1");
    $ofChk->execute([$officeId, $schoolId]);
    $of = $ofChk->fetch();
    if (!$of) {
        Response::json(['success' => false, 'message' => 'Kantor tidak ditemukan.'], 404);
    }

    $pdo->beginTransaction();
    try {
        // Unassign previously assigned employees of this office if unchecked
        $pdo->prepare("UPDATE users SET default_office_id = NULL WHERE school_id = ? AND role = 'employee' AND default_office_id = ?")
            ->execute([$schoolId, $officeId]);

        $count = 0;
        foreach ($userIds as $uid) {
            if ($uid <= 0) continue;
            if ($shiftId !== null) {
                $pdo->prepare("UPDATE users SET default_office_id = ?, default_shift_id = ?, default_lat = NULL, default_lng = NULL, default_radius = NULL WHERE id = ? AND school_id = ? AND role = 'employee'")
                    ->execute([$officeId, $shiftId, $uid, $schoolId]);
            } else {
                $pdo->prepare("UPDATE users SET default_office_id = ?, default_lat = NULL, default_lng = NULL, default_radius = NULL WHERE id = ? AND school_id = ? AND role = 'employee'")
                    ->execute([$officeId, $uid, $schoolId]);
            }
            $count++;
        }
        $pdo->commit();
        Response::json([
            'success' => true,
            'mode'    => 'updated',
            'message' => "{$count} pegawai berhasil ditempatkan di kantor '{$of['name']}'.",
        ]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        Response::json(['success' => false, 'message' => 'Gagal menetapkan pegawai kantor: ' . $e->getMessage()], 500);
    }
}

if ($action === 'office-delete') {
    Security::requireCsrf();
    ensure_office_schema($pdo, $school);
    $id = (int)($body['id'] ?? 0);
    $pdo->prepare("UPDATE users SET default_office_id = NULL WHERE school_id = ? AND default_office_id = ?")->execute([$schoolId, $id]);
    $pdo->prepare("UPDATE employee_schedules SET office_id = NULL WHERE school_id = ? AND office_id = ?")->execute([$schoolId, $id]);
    $pdo->prepare("DELETE FROM offices WHERE id = ? AND school_id = ?")->execute([$id, $schoolId]);
    Response::json(['success' => true, 'message' => 'Kantor berhasil dihapus.']);
}

// =========================================================================
// 3. DATA PEGAWAI + IMPORT EXCEL (.XLSX ONLY) + CUSTOM SHIFT & KOORDINAT
// =========================================================================
if ($action === 'employees-list') {
    ensure_shift_columns($pdo);
    ensure_school_location_columns($pdo);
    ensure_office_schema($pdo, $school);

    $search = trim((string)($_GET['search'] ?? ''));
    $sql = "
        SELECT u.id, u.nip_nik, u.name, u.email, u.phone, u.position_title, u.default_office_id, u.default_shift_id,
               u.default_lat, u.default_lng, u.default_radius, u.is_active, u.last_login_at,
               sh.name AS shift_name, sh.start_time, sh.end_time,
               o.name AS office_name, o.code AS office_code, o.latitude AS office_lat_ref, o.longitude AS office_lng_ref, o.radius AS office_radius_ref,
               (SELECT COUNT(*) FROM employee_schedules es WHERE es.user_id = u.id) AS custom_schedule_count
        FROM users u
        LEFT JOIN shifts sh ON sh.id = u.default_shift_id
        LEFT JOIN offices o ON o.id = u.default_office_id
        WHERE u.school_id = ? AND u.role = 'employee'
    ";
    $params = [$schoolId];
    if ($search !== '') {
        $sql .= " AND (u.name LIKE ? OR u.nip_nik LIKE ? OR u.email LIKE ? OR u.position_title LIKE ?)";
        $like = "%{$search}%";
        array_push($params, $like, $like, $like, $like);
    }
    $sql .= " ORDER BY u.name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $shifts = $pdo->prepare("SELECT * FROM shifts WHERE school_id = ? ORDER BY is_default DESC, name ASC");
    $shifts->execute([$schoolId]);

    $ofStmt = $pdo->prepare("SELECT * FROM offices WHERE school_id = ? AND is_active = 1 ORDER BY is_default DESC, name ASC");
    $ofStmt->execute([$schoolId]);
    $offices = $ofStmt->fetchAll();

    $osStmt = $pdo->prepare("SELECT office_id, shift_id FROM office_shifts WHERE school_id = ?");
    $osStmt->execute([$schoolId]);
    $osMap = [];
    foreach ($osStmt->fetchAll() as $r) {
        $osMap[(int)$r['office_id']][] = (int)$r['shift_id'];
    }
    foreach ($offices as &$of) {
        $of['shift_ids'] = $osMap[(int)$of['id']] ?? [];
    }

    $locRow = $pdo->query("SELECT office_location_name, office_lat, office_lng, office_radius, force_global_location FROM schools WHERE id = {$schoolId}")->fetch() ?: [];

    Response::json([
        'success' => true,
        'data'    => [
            'employees' => $stmt->fetchAll(),
            'shifts'    => $shifts->fetchAll(),
            'offices'   => $offices,
            'office'    => [
                'location_name'         => $locRow['office_location_name'] ?? $school['name'],
                'lat'                   => (float)($locRow['office_lat'] ?? $school['office_lat']),
                'lng'                   => (float)($locRow['office_lng'] ?? $school['office_lng']),
                'radius'                => (int)($locRow['office_radius'] ?? $school['office_radius']),
                'force_global_location' => (int)($locRow['force_global_location'] ?? 0),
            ],
        ],
    ]);
}

if ($action === 'employee-save') {
    Security::requireCsrf();
    ensure_office_schema($pdo, $school);

    $id         = (int)($body['id'] ?? 0);
    $nip        = trim((string)($body['nip_nik'] ?? ''));
    $name       = trim((string)($body['name'] ?? ''));
    $email      = strtolower(trim((string)($body['email'] ?? '')));
    $phone      = trim((string)($body['phone'] ?? ''));
    $position   = trim((string)($body['position_title'] ?? ''));
    $password   = (string)($body['password'] ?? '');
    $officeId   = !empty($body['default_office_id']) ? (int)$body['default_office_id'] : null;
    $shiftId    = !empty($body['default_shift_id']) ? (int)$body['default_shift_id'] : null;
    $lat        = ($body['default_lat'] ?? '') !== '' ? (float)$body['default_lat'] : null;
    $lng        = ($body['default_lng'] ?? '') !== '' ? (float)$body['default_lng'] : null;
    $radius     = ($body['default_radius'] ?? '') !== '' ? (int)$body['default_radius'] : null;
    $isActive   = isset($body['is_active']) ? (int)(bool)$body['is_active'] : 1;

    if ($name === '' || $email === '') {
        Response::json(['success' => false, 'message' => 'Nama lengkap dan email pegawai wajib diisi.'], 422);
    }

    $dup = $pdo->prepare("SELECT id FROM users WHERE school_id = ? AND LOWER(email) = ? AND id <> ?");
    $dup->execute([$schoolId, $email, $id]);
    if ($dup->fetch()) {
        Response::json(['success' => false, 'message' => "Email '{$email}' sudah digunakan pegawai lain di sekolah ini."], 422);
    }

    if ($id > 0) {
        if ($password !== '') {
            $pdo->prepare("
                UPDATE users
                SET nip_nik=?, name=?, email=?, phone=?, position_title=?, default_office_id=?, default_shift_id=?, default_lat=?, default_lng=?, default_radius=?, is_active=?, password_hash=?
                WHERE id=? AND school_id=? AND role='employee'
            ")->execute([$nip, $name, $email, $phone, $position, $officeId, $shiftId, $lat, $lng, $radius, $isActive, Security::hashPassword($password), $id, $schoolId]);
        } else {
            $pdo->prepare("
                UPDATE users
                SET nip_nik=?, name=?, email=?, phone=?, position_title=?, default_office_id=?, default_shift_id=?, default_lat=?, default_lng=?, default_radius=?, is_active=?
                WHERE id=? AND school_id=? AND role='employee'
            ")->execute([$nip, $name, $email, $phone, $position, $officeId, $shiftId, $lat, $lng, $radius, $isActive, $id, $schoolId]);
        }
        Response::json(['success' => true, 'mode' => 'updated', 'message' => "Data pegawai '{$name}' berhasil diperbarui."]);
    } else {
        $countEmp = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE school_id = {$schoolId} AND role = 'employee'")->fetchColumn();
        if (!empty($school['max_employees']) && $countEmp >= (int)$school['max_employees']) {
            Response::json(['success' => false, 'message' => "Kuota maksimal pegawai paket Anda ({$school['max_employees']} orang) telah tercapai."], 422);
        }
        $pdo->prepare("
            INSERT INTO users (school_id, role, nip_nik, name, email, phone, password_hash, position_title, default_office_id, default_shift_id, default_lat, default_lng, default_radius, is_active)
            VALUES (?, 'employee', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([$schoolId, $nip, $name, $email, $phone, Security::hashPassword($password ?: 'Pegawai123!'), $position, $officeId, $shiftId, $lat, $lng, $radius, $isActive]);
        Response::json(['success' => true, 'mode' => 'saved', 'message' => "Pegawai '{$name}' berhasil ditambahkan."]);
    }
}

if ($action === 'employee-delete') {
    Security::requireCsrf();
    $id = (int)($body['id'] ?? 0);
    $pdo->prepare("DELETE FROM users WHERE id = ? AND school_id = ? AND role = 'employee'")->execute([$id, $schoolId]);
    Response::json(['success' => true, 'message' => 'Data pegawai berhasil dihapus.']);
}

if ($action === 'employees-template') {
    Tenant::requireFeature($school, 'excel_import', true);
    $defShift = $pdo->query("SELECT name FROM shifts WHERE school_id = {$schoolId} ORDER BY is_default DESC LIMIT 1")->fetchColumn() ?: 'Shift Pagi Reguler';
    $rows = [
        ['NIP', 'Nama Lengkap', 'Email', 'No HP', 'Jabatan', 'Password', 'Nama Shift', 'Latitude Kantor', 'Longitude Kantor', 'Radius Meter'],
        ['PEG-101', 'Ahmad Rifai, S.Pd', 'rifai@sekolah.sch.id', '085241001122', 'Guru Honorer', 'Pegawai123!', $defShift, $school['office_lat'], $school['office_lng'], $school['office_radius']],
        ['PEG-102', 'Dewi Lestari', 'dewi@sekolah.sch.id', '085241001133', 'Staf Perpustakaan', 'Pegawai123!', $defShift, $school['office_lat'], $school['office_lng'], $school['office_radius']],
    ];
    XlsxHelper::downloadXlsx('Template_Import_Pegawai_' . $school['slug'] . '.xlsx', $rows, 'Pegawai');
}

if ($action === 'employees-import') {
    Security::requireCsrf();
    Tenant::requireFeature($school, 'excel_import', true);

    $rows = [];
    if (!empty($_FILES['excel_file']['tmp_name'])) {
        $ext = strtolower(pathinfo($_FILES['excel_file']['name'] ?? '', PATHINFO_EXTENSION));
        if ($ext === 'csv' || !in_array($ext, ['xlsx'], true)) {
            Response::json(['success' => false, 'message' => 'Format harus Microsoft Excel (.xlsx), bukan CSV!'], 422);
        }
        $rawRows = XlsxHelper::readXlsx($_FILES['excel_file']['tmp_name']);
        if (count($rawRows) < 2) {
            Response::json(['success' => false, 'message' => 'File Excel tidak memiliki baris data.'], 422);
        }
        $headers = array_map(static fn($h) => strtolower(trim((string)$h)), $rawRows[0]);
        for ($i = 1; $i < count($rawRows); $i++) {
            $assoc = [];
            foreach ($headers as $idx => $h) {
                $assoc[$h] = $rawRows[$i][$idx] ?? '';
            }
            $rows[] = $assoc;
        }
    } elseif (is_array($body['rows'] ?? null)) {
        foreach ($body['rows'] as $r) {
            if (!is_array($r)) continue;
            $norm = [];
            foreach ($r as $k => $v) {
                $norm[strtolower(trim((string)$k))] = trim((string)$v);
            }
            $rows[] = $norm;
        }
    }

    if (empty($rows)) {
        Response::json(['success' => false, 'message' => 'Tidak ada baris data Excel yang valid untuk diimpor.'], 422);
    }

    // Build shift map
    $shiftMap = [];
    $defShiftId = null;
    $shStmt = $pdo->prepare("SELECT id, name, is_default FROM shifts WHERE school_id = ?");
    $shStmt->execute([$schoolId]);
    foreach ($shStmt->fetchAll() as $sh) {
        $shiftMap[strtolower(trim($sh['name']))] = (int)$sh['id'];
        if ((int)$sh['is_default'] === 1 || $defShiftId === null) {
            $defShiftId = (int)$sh['id'];
        }
    }

    $inserted = 0;
    $updated  = 0;
    $skipped  = 0;

    foreach ($rows as $r) {
        $name  = $r['nama lengkap'] ?? $r['nama'] ?? $r['name'] ?? '';
        $email = strtolower($r['email'] ?? '');
        $nip   = $r['nip'] ?? $r['nip/nik'] ?? $r['nik'] ?? '';
        $phone = $r['no hp'] ?? $r['hp'] ?? $r['phone'] ?? '';
        $pos   = $r['jabatan'] ?? $r['position'] ?? 'Pegawai';
        $pass  = $r['password'] ?? 'Pegawai123!';
        $shNm  = strtolower($r['nama shift'] ?? $r['shift'] ?? '');
        $lat   = ($r['latitude kantor'] ?? $r['latitude'] ?? $r['lat'] ?? '') !== '' ? (float)($r['latitude kantor'] ?? $r['latitude'] ?? $r['lat']) : null;
        $lng   = ($r['longitude kantor'] ?? $r['longitude'] ?? $r['lng'] ?? '') !== '' ? (float)($r['longitude kantor'] ?? $r['longitude'] ?? $r['lng']) : null;
        $rad   = ($r['radius meter'] ?? $r['radius'] ?? '') !== '' ? (int)($r['radius meter'] ?? $r['radius']) : null;

        if ($name === '' || $email === '') {
            $skipped++;
            continue;
        }
        $shiftId = $shiftMap[$shNm] ?? $defShiftId;

        $check = $pdo->prepare("SELECT id FROM users WHERE school_id = ? AND LOWER(email) = ? LIMIT 1");
        $check->execute([$schoolId, $email]);
        $existingId = (int)$check->fetchColumn();

        if ($existingId > 0) {
            $pdo->prepare("
                UPDATE users SET nip_nik=?, name=?, phone=?, position_title=?, default_shift_id=?, default_lat=?, default_lng=?, default_radius=?
                WHERE id=? AND school_id=?
            ")->execute([$nip, $name, $phone, $pos, $shiftId, $lat, $lng, $rad, $existingId, $schoolId]);
            $updated++;
        } else {
            $pdo->prepare("
                INSERT INTO users (school_id, role, nip_nik, name, email, phone, password_hash, position_title, default_shift_id, default_lat, default_lng, default_radius, is_active)
                VALUES (?, 'employee', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ")->execute([$schoolId, $nip, $name, $email, $phone, Security::hashPassword($pass ?: 'Pegawai123!'), $pos, $shiftId, $lat, $lng, $rad]);
            $inserted++;
        }
    }

    Response::json([
        'success' => true,
        'mode'    => 'saved',
        'message' => "Import Excel Pegawai selesai! Ditambahkan: {$inserted}, Diperbarui: {$updated}, Dilewati: {$skipped}.",
    ]);
}

// Custom Shift & Coordinate per Day per Employee
if ($action === 'employee-schedule-get') {
    $userId = (int)($_GET['user_id'] ?? 0);
    $empStmt = $pdo->prepare("SELECT id, name, nip_nik, default_shift_id, default_lat, default_lng, default_radius FROM users WHERE id = ? AND school_id = ? AND role = 'employee' LIMIT 1");
    $empStmt->execute([$userId, $schoolId]);
    $emp = $empStmt->fetch();
    if (!$emp) {
        Response::json(['success' => false, 'message' => 'Pegawai tidak ditemukan.'], 404);
    }

    $weekly = $pdo->prepare("SELECT * FROM employee_schedules WHERE school_id = ? AND user_id = ? AND day_of_week IS NOT NULL ORDER BY day_of_week ASC");
    $weekly->execute([$schoolId, $userId]);

    $overrides = $pdo->prepare("
        SELECT es.*, sh.name AS shift_name, o.name AS office_name
        FROM employee_schedules es
        LEFT JOIN shifts sh ON sh.id = es.shift_id
        LEFT JOIN offices o ON o.id = es.office_id
        WHERE es.school_id = ? AND es.user_id = ? AND es.specific_date IS NOT NULL
        ORDER BY es.specific_date DESC LIMIT 30
    ");
    $overrides->execute([$schoolId, $userId]);

    Response::json([
        'success' => true,
        'data'    => [
            'employee'  => $emp,
            'weekly'    => $weekly->fetchAll(),
            'overrides' => $overrides->fetchAll(),
        ],
    ]);
}

if ($action === 'employee-schedule-save') {
    Security::requireCsrf();
    Tenant::requireFeature($school, 'custom_shift_location', true);
    ensure_office_schema($pdo, $school);
    $userId = (int)($body['user_id'] ?? 0);

    // Verify employee belongs to school
    $chk = $pdo->prepare("SELECT id FROM users WHERE id = ? AND school_id = ? AND role = 'employee'");
    $chk->execute([$userId, $schoolId]);
    if (!$chk->fetch()) {
        Response::json(['success' => false, 'message' => 'Pegawai tidak ditemukan.'], 404);
    }

    // Save weekly rules (Mon=1..Sun=7)
    if (is_array($body['weekly'] ?? null)) {
        $pdo->prepare("DELETE FROM employee_schedules WHERE school_id = ? AND user_id = ? AND day_of_week IS NOT NULL")
            ->execute([$schoolId, $userId]);

        $ins = $pdo->prepare("
            INSERT INTO employee_schedules (school_id, user_id, day_of_week, office_id, shift_id, is_day_off, custom_lat, custom_lng, custom_radius, location_name)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($body['weekly'] as $row) {
            $dow = (int)($row['day_of_week'] ?? 0);
            if ($dow < 1 || $dow > 7) continue;
            $officeId = !empty($row['office_id']) ? (int)$row['office_id'] : null;
            $shiftId  = !empty($row['shift_id']) ? (int)$row['shift_id'] : null;
            $isOff    = !empty($row['is_day_off']) ? 1 : 0;
            $lat      = ($row['custom_lat'] ?? '') !== '' ? (float)$row['custom_lat'] : null;
            $lng      = ($row['custom_lng'] ?? '') !== '' ? (float)$row['custom_lng'] : null;
            $rad      = ($row['custom_radius'] ?? '') !== '' ? (int)$row['custom_radius'] : null;
            $locName  = trim((string)($row['location_name'] ?? ''));

            if ($officeId !== null || $shiftId !== null || $isOff === 1 || $lat !== null || $lng !== null || $locName !== '') {
                $ins->execute([$schoolId, $userId, $dow, $officeId, $shiftId, $isOff, $lat, $lng, $rad, $locName ?: null]);
            }
        }
    }

    // Optional specific date override
    if (!empty($body['override']['specific_date'])) {
        $ov = $body['override'];
        $dt = $ov['specific_date'];
        $pdo->prepare("DELETE FROM employee_schedules WHERE school_id = ? AND user_id = ? AND specific_date = ?")
            ->execute([$schoolId, $userId, $dt]);
        $pdo->prepare("
            INSERT INTO employee_schedules (school_id, user_id, specific_date, office_id, shift_id, is_day_off, custom_lat, custom_lng, custom_radius, location_name)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $schoolId,
            $userId,
            $dt,
            !empty($ov['office_id']) ? (int)$ov['office_id'] : null,
            !empty($ov['shift_id']) ? (int)$ov['shift_id'] : null,
            !empty($ov['is_day_off']) ? 1 : 0,
            ($ov['custom_lat'] ?? '') !== '' ? (float)$ov['custom_lat'] : null,
            ($ov['custom_lng'] ?? '') !== '' ? (float)$ov['custom_lng'] : null,
            ($ov['custom_radius'] ?? '') !== '' ? (int)$ov['custom_radius'] : null,
            trim((string)($ov['location_name'] ?? '')) ?: null,
        ]);
    }

    Response::json(['success' => true, 'mode' => 'updated', 'message' => 'Jadwal kantor, shift & koordinat custom pegawai berhasil disimpan!']);
}

if ($action === 'employee-schedule-delete-override') {
    Security::requireCsrf();
    $id = (int)($body['id'] ?? 0);
    $pdo->prepare("DELETE FROM employee_schedules WHERE id = ? AND school_id = ? AND specific_date IS NOT NULL")->execute([$id, $schoolId]);
    Response::json(['success' => true, 'message' => 'Override tanggal khusus berhasil dihapus.']);
}

// =========================================================================
// 4. DATA SISWA + IMPORT EXCEL (.XLSX ONLY) + QR CODE
// =========================================================================
if ($action === 'students-list') {
    Tenant::requireFeature($school, 'student_management', true);
    $search = trim((string)($_GET['search'] ?? ''));
    $classFilter = trim((string)($_GET['class_name'] ?? ''));

    $sql = "SELECT * FROM students WHERE school_id = ?";
    $params = [$schoolId];
    if ($search !== '') {
        $sql .= " AND (name LIKE ? OR nisn LIKE ? OR class_name LIKE ?)";
        $like = "%{$search}%";
        array_push($params, $like, $like, $like);
    }
    if ($classFilter !== '') {
        $sql .= " AND class_name = ?";
        $params[] = $classFilter;
    }
    $sql .= " ORDER BY class_name ASC, name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $classes = $pdo->prepare("SELECT DISTINCT class_name FROM students WHERE school_id = ? ORDER BY class_name ASC");
    $classes->execute([$schoolId]);

    Response::json([
        'success' => true,
        'data'    => [
            'students' => $stmt->fetchAll(),
            'classes'  => array_column($classes->fetchAll(), 'class_name'),
        ],
    ]);
}

if ($action === 'student-save') {
    Security::requireCsrf();
    Tenant::requireFeature($school, 'student_management', true);
    $id            = (int)($body['id'] ?? 0);
    $nisn          = trim((string)($body['nisn'] ?? ''));
    $name          = trim((string)($body['name'] ?? ''));
    $className     = trim((string)($body['class_name'] ?? ''));
    $gender        = in_array($body['gender'] ?? 'L', ['L', 'P'], true) ? $body['gender'] : 'L';
    $guardianName  = trim((string)($body['guardian_name'] ?? ''));
    $guardianPhone = trim((string)($body['guardian_phone'] ?? ''));
    $isActive      = isset($body['is_active']) ? (int)(bool)$body['is_active'] : 1;

    if ($nisn === '' || $name === '' || $className === '') {
        Response::json(['success' => false, 'message' => 'NISN, Nama Siswa, dan Kelas wajib diisi.'], 422);
    }

    $dup = $pdo->prepare("SELECT id FROM students WHERE school_id = ? AND nisn = ? AND id <> ?");
    $dup->execute([$schoolId, $nisn, $id]);
    if ($dup->fetch()) {
        Response::json(['success' => false, 'message' => "NISN '{$nisn}' sudah terdaftar di sekolah ini."], 422);
    }

    if ($id > 0) {
        $pdo->prepare("
            UPDATE students SET nisn=?, name=?, class_name=?, gender=?, guardian_name=?, guardian_phone=?, is_active=?
            WHERE id=? AND school_id=?
        ")->execute([$nisn, $name, $className, $gender, $guardianName, $guardianPhone, $isActive, $id, $schoolId]);
        Response::json(['success' => true, 'mode' => 'updated', 'message' => "Data siswa '{$name}' berhasil diperbarui."]);
    } else {
        $countStu = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE school_id = {$schoolId}")->fetchColumn();
        if (!empty($school['max_students']) && $countStu >= (int)$school['max_students']) {
            Response::json(['success' => false, 'message' => "Kuota maksimal siswa paket Anda ({$school['max_students']} siswa) telah tercapai."], 422);
        }
        $qrToken = 'EP-' . $schoolId . '-' . strtoupper(bin2hex(random_bytes(5)));
        $pdo->prepare("
            INSERT INTO students (school_id, nisn, name, class_name, gender, guardian_name, guardian_phone, qr_token, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([$schoolId, $nisn, $name, $className, $gender, $guardianName, $guardianPhone, $qrToken, $isActive]);
        Response::json(['success' => true, 'mode' => 'saved', 'message' => "Siswa '{$name}' berhasil ditambahkan beserta QR Code unik."]);
    }
}

if ($action === 'student-delete') {
    Security::requireCsrf();
    $id = (int)($body['id'] ?? 0);
    $pdo->prepare("DELETE FROM students WHERE id = ? AND school_id = ?")->execute([$id, $schoolId]);
    Response::json(['success' => true, 'message' => 'Data siswa berhasil dihapus.']);
}

if ($action === 'students-template') {
    Tenant::requireFeature($school, 'excel_import', true);
    $rows = [
        ['NISN', 'Nama Lengkap', 'Kelas', 'Jenis Kelamin (L/P)', 'Nama Wali', 'No HP Wali'],
        ['0091230101', 'Fajar Ramadhan', '6A', 'L', 'Bapak Rahman', '085241220011'],
        ['0091230102', 'Zahra Aulia Putri', '6A', 'P', 'Ibu Kartini', '085241220012'],
    ];
    XlsxHelper::downloadXlsx('Template_Import_Siswa_' . $school['slug'] . '.xlsx', $rows, 'Siswa');
}

if ($action === 'students-import') {
    Security::requireCsrf();
    Tenant::requireFeature($school, 'excel_import', true);
    Tenant::requireFeature($school, 'student_management', true);

    $rows = [];
    if (is_array($body['rows'] ?? null)) {
        foreach ($body['rows'] as $r) {
            if (!is_array($r)) continue;
            $norm = [];
            foreach ($r as $k => $v) {
                $norm[strtolower(trim((string)$k))] = trim((string)$v);
            }
            $rows[] = $norm;
        }
    }
    if (empty($rows)) {
        Response::json(['success' => false, 'message' => 'Data baris Excel siswa kosong.'], 422);
    }

    $inserted = 0;
    $updated  = 0;
    $skipped  = 0;

    foreach ($rows as $r) {
        $nisn  = $r['nisn'] ?? $r['nis'] ?? '';
        $name  = $r['nama lengkap'] ?? $r['nama'] ?? $r['name'] ?? '';
        $cls   = $r['kelas'] ?? $r['class'] ?? '';
        $genRaw = strtoupper(substr($r['jenis kelamin (l/p)'] ?? $r['jenis kelamin'] ?? $r['jk'] ?? 'L', 0, 1));
        $gen   = in_array($genRaw, ['L', 'P'], true) ? $genRaw : 'L';
        $wName = $r['nama wali'] ?? $r['wali'] ?? '';
        $wPhone= $r['no hp wali'] ?? $r['hp wali'] ?? '';

        if ($nisn === '' || $name === '' || $cls === '') {
            $skipped++;
            continue;
        }

        $chk = $pdo->prepare("SELECT id FROM students WHERE school_id = ? AND nisn = ? LIMIT 1");
        $chk->execute([$schoolId, $nisn]);
        $exId = (int)$chk->fetchColumn();
        if ($exId > 0) {
            $pdo->prepare("UPDATE students SET name=?, class_name=?, gender=?, guardian_name=?, guardian_phone=? WHERE id=? AND school_id=?")
                ->execute([$name, $cls, $gen, $wName, $wPhone, $exId, $schoolId]);
            $updated++;
        } else {
            $qrToken = 'EP-' . $schoolId . '-' . strtoupper(bin2hex(random_bytes(5)));
            $pdo->prepare("
                INSERT INTO students (school_id, nisn, name, class_name, gender, guardian_name, guardian_phone, qr_token, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ")->execute([$schoolId, $nisn, $name, $cls, $gen, $wName, $wPhone, $qrToken]);
            $inserted++;
        }
    }

    Response::json([
        'success' => true,
        'mode'    => 'saved',
        'message' => "Import Excel Siswa selesai! Ditambahkan: {$inserted}, Diperbarui: {$updated}, Dilewati: {$skipped}.",
    ]);
}

// =========================================================================
// 5. LAPORAN ABSENSI PEGAWAI (LOG, MATRIX MONITORING, REKAPITULASI & %)
//    + APPROVAL PERBAIKAN ABSEN
// =========================================================================
if ($action === 'reports-data') {
    Tenant::requireFeature($school, 'employee_reports', true);
    $month = trim((string)($_GET['month'] ?? date('Y-m')));
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        $month = date('Y-m');
    }
    $empIdFilter = (int)($_GET['user_id'] ?? 0);

    $startDate = $month . '-01';
    $daysInMonth = (int)date('t', strtotime($startDate));
    $endDate = $month . '-' . str_pad((string)$daysInMonth, 2, '0', STR_PAD_LEFT);

    // Fetch active employees
    $empSql = "SELECT id, nip_nik, name, position_title FROM users WHERE school_id = ? AND role = 'employee' AND is_active = 1";
    $empParams = [$schoolId];
    if ($empIdFilter > 0) {
        $empSql .= " AND id = ?";
        $empParams[] = $empIdFilter;
    }
    $empSql .= " ORDER BY name ASC";
    $empStmt = $pdo->prepare($empSql);
    $empStmt->execute($empParams);
    $employees = $empStmt->fetchAll();

    // Fetch logs for month
    $logSql = "
        SELECT a.*, u.nip_nik, u.name AS employee_name, u.position_title
        FROM employee_attendances a
        JOIN users u ON u.id = a.user_id
        WHERE a.school_id = ? AND a.attendance_date BETWEEN ? AND ?
    ";
    $logParams = [$schoolId, $startDate, $endDate];
    if ($empIdFilter > 0) {
        $logSql .= " AND a.user_id = ?";
        $logParams[] = $empIdFilter;
    }
    $logSql .= " ORDER BY a.attendance_date DESC, u.name ASC";
    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute($logParams);
    $logs = $logStmt->fetchAll();

    // Index logs by [user_id][day_number]
    $byUserDay = [];
    foreach ($logs as $l) {
        $d = (int)date('j', strtotime($l['attendance_date']));
        $byUserDay[(int)$l['user_id']][$d] = $l;
    }

    // Count working days elapsed in selected month (Mon-Sat excluding Sunday)
    $today = date('Y-m-d');
    $effectiveEndDay = ($month === date('Y-m')) ? (int)date('j') : ($month < date('Y-m') ? $daysInMonth : 0);
    $workDaysCount = 0;
    for ($d = 1; $d <= $effectiveEndDay; $d++) {
        $dow = (int)date('N', strtotime($month . '-' . str_pad((string)$d, 2, '0', STR_PAD_LEFT)));
        if ($dow < 7) {
            $workDaysCount++;
        }
    }

    $matrix  = [];
    $summary = [];
    $codeMap = [
        'hadir'      => 'H',
        'terlambat'  => 'T',
        'izin'       => 'I',
        'sakit'      => 'S',
        'dinas_luar' => 'DL',
        'alpha'      => 'A',
    ];

    foreach ($employees as $emp) {
        $uid = (int)$emp['id'];
        $daysRow = [];
        $cnt = ['hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'dinas_luar' => 0, 'alpha' => 0, 'late_minutes' => 0];

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateStr = $month . '-' . str_pad((string)$d, 2, '0', STR_PAD_LEFT);
            $dow = (int)date('N', strtotime($dateStr));
            if (isset($byUserDay[$uid][$d])) {
                $rec = $byUserDay[$uid][$d];
                $st = $rec['status'];
                $daysRow[$d] = [
                    'code' => $codeMap[$st] ?? 'H',
                    'status' => $st,
                    'in' => $rec['checkin_time'] ? date('H:i', strtotime($rec['checkin_time'])) : '-',
                    'out' => $rec['checkout_time'] ? date('H:i', strtotime($rec['checkout_time'])) : '-',
                ];
                if (isset($cnt[$st])) $cnt[$st]++;
                $cnt['late_minutes'] += (int)$rec['late_minutes'];
            } elseif ($dow === 7) {
                $daysRow[$d] = ['code' => 'L', 'status' => 'libur', 'in' => '-', 'out' => '-'];
            } elseif ($dateStr <= $today) {
                $daysRow[$d] = ['code' => '-', 'status' => 'empty', 'in' => '-', 'out' => '-'];
                $cnt['alpha']++;
            } else {
                $daysRow[$d] = ['code' => '', 'status' => 'future', 'in' => '-', 'out' => '-'];
            }
        }

        $presentTotal = $cnt['hadir'] + $cnt['terlambat'] + $cnt['dinas_luar'];
        $denom = max(1, $workDaysCount);
        $percentage = $workDaysCount > 0 ? min(100, round(($presentTotal / $denom) * 100, 1)) : 0;

        $matrix[] = [
            'user_id'  => $uid,
            'name'     => $emp['name'],
            'nip_nik'  => $emp['nip_nik'],
            'position' => $emp['position_title'],
            'days'     => $daysRow,
        ];
        $summary[] = [
            'user_id'        => $uid,
            'nip_nik'        => $emp['nip_nik'],
            'name'           => $emp['name'],
            'position_title' => $emp['position_title'],
            'work_days'      => $workDaysCount,
            'hadir'          => $cnt['hadir'],
            'terlambat'      => $cnt['terlambat'],
            'dinas_luar'     => $cnt['dinas_luar'],
            'izin'           => $cnt['izin'],
            'sakit'          => $cnt['sakit'],
            'alpha'          => $cnt['alpha'],
            'total_present'  => $presentTotal,
            'late_minutes'   => $cnt['late_minutes'],
            'percentage'     => $percentage,
        ];
    }

    // Also include pending & recent attendance corrections
    $corrStmt = $pdo->prepare("
        SELECT c.*, u.name AS employee_name, u.nip_nik, u.position_title
        FROM attendance_corrections c
        JOIN users u ON u.id = c.user_id
        WHERE c.school_id = ?
        ORDER BY FIELD(c.status, 'pending', 'approved', 'rejected'), c.created_at DESC
        LIMIT 80
    ");
    $corrStmt->execute([$schoolId]);

    Response::json([
        'success' => true,
        'data'    => [
            'month'         => $month,
            'days_in_month' => $daysInMonth,
            'work_days'     => $workDaysCount,
            'employees'     => $employees,
            'logs'          => $logs,
            'matrix'        => $matrix,
            'summary'       => $summary,
            'corrections'   => $corrStmt->fetchAll(),
        ],
    ]);
}

if ($action === 'correction-review') {
    Security::requireCsrf();
    $corrId    = (int)($body['id'] ?? 0);
    $decision  = (string)($body['status'] ?? '');
    $adminNote = trim((string)($body['admin_note'] ?? ''));

    if (!in_array($decision, ['approved', 'rejected'], true)) {
        Response::json(['success' => false, 'message' => 'Keputusan harus approved atau rejected.'], 422);
    }

    $stmt = $pdo->prepare("SELECT * FROM attendance_corrections WHERE id = ? AND school_id = ? LIMIT 1");
    $stmt->execute([$corrId, $schoolId]);
    $corr = $stmt->fetch();
    if (!$corr) {
        Response::json(['success' => false, 'message' => 'Pengajuan tidak ditemukan.'], 404);
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE attendance_corrections SET status = ?, reviewed_by = ?, reviewed_at = NOW(), admin_note = ? WHERE id = ?")
            ->execute([$decision, $admin['id'], $adminNote, $corrId]);

        if ($decision === 'approved') {
            $dt = $corr['attendance_date'];
            $uid = (int)$corr['user_id'];
            $type = $corr['correction_type'];

            $newStatus = match ($type) {
                'izin'       => 'izin',
                'sakit'      => 'sakit',
                'dinas_luar' => 'dinas_luar',
                default      => 'hadir',
            };
            $inDt  = !empty($corr['requested_checkin']) ? ($dt . ' ' . $corr['requested_checkin']) : null;
            $outDt = !empty($corr['requested_checkout']) ? ($dt . ' ' . $corr['requested_checkout']) : null;

            $ex = $pdo->prepare("SELECT id, checkin_time, checkout_time FROM employee_attendances WHERE school_id = ? AND user_id = ? AND attendance_date = ?");
            $ex->execute([$schoolId, $uid, $dt]);
            $row = $ex->fetch();
            if ($row) {
                $pdo->prepare("
                    UPDATE employee_attendances
                    SET checkin_time = COALESCE(?, checkin_time),
                        checkout_time = COALESCE(?, checkout_time),
                        status = ?, is_corrected = 1, checkin_note = CONCAT(COALESCE(checkin_note,''), ' [Perbaikan disetujui]')
                    WHERE id = ?
                ")->execute([$inDt, $outDt, $newStatus, $row['id']]);
            } else {
                $pdo->prepare("
                    INSERT INTO employee_attendances (school_id, user_id, attendance_date, shift_name, checkin_time, checkout_time, status, is_corrected, checkin_note)
                    VALUES (?, ?, ?, 'Perbaikan Absen', ?, ?, ?, 1, ?)
                ")->execute([$schoolId, $uid, $dt, $inDt, $outDt, $newStatus, 'Disetujui admin: ' . $corr['reason']]);
            }
        }

        $pdo->commit();
        Response::json([
            'success' => true,
            'mode'    => 'updated',
            'message' => $decision === 'approved' ? 'Pengajuan perbaikan disetujui & log kehadiran otomatis diperbarui.' : 'Pengajuan perbaikan ditolak.',
        ]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        Response::json(['success' => false, 'message' => 'Gagal memproses pengajuan: ' . $e->getMessage()], 500);
    }
}

// =========================================================================
// 6. AKUN GURU UNTUK ABSEN SISWA
// =========================================================================
if ($action === 'teachers-list') {
    Tenant::requireFeature($school, 'teacher_qr_scan', true);
    $stmt = $pdo->prepare("
        SELECT id, nip_nik, name, email, phone, subject_name, assigned_classes, is_active, last_login_at
        FROM users
        WHERE school_id = ? AND role = 'teacher'
        ORDER BY name ASC
    ");
    $stmt->execute([$schoolId]);
    $classes = $pdo->prepare("SELECT DISTINCT class_name FROM students WHERE school_id = ? ORDER BY class_name ASC");
    $classes->execute([$schoolId]);

    Response::json([
        'success' => true,
        'data'    => [
            'teachers' => $stmt->fetchAll(),
            'classes'  => array_column($classes->fetchAll(), 'class_name'),
        ],
    ]);
}

if ($action === 'teacher-save') {
    Security::requireCsrf();
    Tenant::requireFeature($school, 'teacher_qr_scan', true);
    $id       = (int)($body['id'] ?? 0);
    $nip      = trim((string)($body['nip_nik'] ?? ''));
    $name     = trim((string)($body['name'] ?? ''));
    $email    = strtolower(trim((string)($body['email'] ?? '')));
    $phone    = trim((string)($body['phone'] ?? ''));
    $subject  = trim((string)($body['subject_name'] ?? ''));
    $classes  = trim((string)($body['assigned_classes'] ?? ''));
    $password = (string)($body['password'] ?? '');
    $isActive = isset($body['is_active']) ? (int)(bool)$body['is_active'] : 1;

    if ($name === '' || $email === '') {
        Response::json(['success' => false, 'message' => 'Nama guru dan email wajib diisi.'], 422);
    }

    $dup = $pdo->prepare("SELECT id FROM users WHERE school_id = ? AND LOWER(email) = ? AND id <> ?");
    $dup->execute([$schoolId, $email, $id]);
    if ($dup->fetch()) {
        Response::json(['success' => false, 'message' => "Email '{$email}' sudah digunakan akun lain."], 422);
    }

    if ($id > 0) {
        if ($password !== '') {
            $pdo->prepare("UPDATE users SET nip_nik=?, name=?, email=?, phone=?, subject_name=?, assigned_classes=?, is_active=?, password_hash=? WHERE id=? AND school_id=? AND role='teacher'")
                ->execute([$nip, $name, $email, $phone, $subject, $classes, $isActive, Security::hashPassword($password), $id, $schoolId]);
        } else {
            $pdo->prepare("UPDATE users SET nip_nik=?, name=?, email=?, phone=?, subject_name=?, assigned_classes=?, is_active=? WHERE id=? AND school_id=? AND role='teacher'")
                ->execute([$nip, $name, $email, $phone, $subject, $classes, $isActive, $id, $schoolId]);
        }
        Response::json(['success' => true, 'mode' => 'updated', 'message' => "Akun guru '{$name}' berhasil diperbarui."]);
    } else {
        $pdo->prepare("
            INSERT INTO users (school_id, role, nip_nik, name, email, phone, password_hash, position_title, subject_name, assigned_classes, is_active)
            VALUES (?, 'teacher', ?, ?, ?, ?, ?, 'Guru', ?, ?, ?)
        ")->execute([$schoolId, $nip, $name, $email, $phone, Security::hashPassword($password ?: 'Guru123!'), $subject, $classes, $isActive]);
        Response::json(['success' => true, 'mode' => 'saved', 'message' => "Akun guru '{$name}' berhasil dibuat."]);
    }
}

if ($action === 'teacher-delete') {
    Security::requireCsrf();
    $id = (int)($body['id'] ?? 0);
    $pdo->prepare("DELETE FROM users WHERE id = ? AND school_id = ? AND role = 'teacher'")->execute([$id, $schoolId]);
    Response::json(['success' => true, 'message' => 'Akun guru berhasil dihapus.']);
}

// =========================================================================
// 7. PENGATURAN NAMA SEKOLAH & KOORDINAT LOKASI GLOBAL
// =========================================================================
function ensure_school_location_columns(PDO $pdo): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM schools")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('office_location_name', $cols, true)) {
            $pdo->exec("ALTER TABLE `schools` ADD COLUMN `office_location_name` VARCHAR(150) NULL AFTER `principal_name`");
        }
        if (!in_array('force_global_location', $cols, true)) {
            $pdo->exec("ALTER TABLE `schools` ADD COLUMN `force_global_location` TINYINT(1) NOT NULL DEFAULT 0 AFTER `office_radius`");
        }
    } catch (Throwable) {
    }
}

if ($action === 'settings-get') {
    ensure_school_location_columns($pdo);
    $fresh = $pdo->prepare("SELECT office_location_name, force_global_location, office_lat, office_lng, office_radius FROM schools WHERE id = ?");
    $fresh->execute([$schoolId]);
    $locRow = $fresh->fetch() ?: [];

    $totalEmp  = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE school_id = {$schoolId} AND role = 'employee' AND is_active = 1")->fetchColumn();
    $customEmp = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE school_id = {$schoolId} AND role = 'employee' AND is_active = 1 AND (default_lat IS NOT NULL OR default_lng IS NOT NULL)")->fetchColumn();

    Response::json([
        'success' => true,
        'data'    => [
            'name'                  => $school['name'],
            'slug'                  => $school['slug'],
            'display_url'           => Tenant::schoolDisplayUrl($school['slug']),
            'npsn'                  => $school['npsn'],
            'email'                 => $school['email'],
            'phone'                 => $school['phone'],
            'address'               => $school['address'],
            'principal_name'        => $school['principal_name'],
            'office_location_name'  => $locRow['office_location_name'] ?? $school['name'],
            'office_lat'            => (float)($locRow['office_lat'] ?? $school['office_lat']),
            'office_lng'            => (float)($locRow['office_lng'] ?? $school['office_lng']),
            'office_radius'         => (int)($locRow['office_radius'] ?? $school['office_radius']),
            'force_global_location' => (int)($locRow['force_global_location'] ?? 0),
            'employee_stats'        => [
                'total'  => $totalEmp,
                'custom' => $customEmp,
                'global' => max(0, $totalEmp - $customEmp),
            ],
            'package_name'          => $school['package_name'],
            'subscription_status'   => $school['subscription_status'],
            'subscription_end'      => format_date_id($school['subscription_end']),
            'features'              => $school['features'],
        ],
    ]);
}

if ($action === 'global-location-save') {
    Security::requireCsrf();
    ensure_school_location_columns($pdo);

    $locName       = trim((string)($body['office_location_name'] ?? $school['name']));
    $lat           = isset($body['office_lat']) && $body['office_lat'] !== '' ? (float)$body['office_lat'] : null;
    $lng           = isset($body['office_lng']) && $body['office_lng'] !== '' ? (float)$body['office_lng'] : null;
    $radius        = max(20, (int)($body['office_radius'] ?? 150));
    $forceGlobal   = !empty($body['force_global_location']) ? 1 : 0;
    $applyToAll    = !empty($body['apply_to_all']);
    $resetDailyLoc = !empty($body['reset_daily_locations']);

    if ($lat === null || $lng === null || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        Response::json(['success' => false, 'message' => 'Koordinat Latitude (-90 s/d 90) dan Longitude (-180 s/d 180) tidak valid.'], 422);
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare("
            UPDATE schools
            SET office_location_name = ?, office_lat = ?, office_lng = ?, office_radius = ?, force_global_location = ?
            WHERE id = ?
        ")->execute([$locName ?: $school['name'], $lat, $lng, $radius, $forceGlobal, $schoolId]);

        $extraMsg = '';
        if ($applyToAll) {
            ensure_office_schema($pdo, $school);
            $pdo->prepare("UPDATE users SET default_office_id = NULL, default_lat = NULL, default_lng = NULL, default_radius = NULL WHERE school_id = ? AND role = 'employee'")
                ->execute([$schoolId]);
            $extraMsg = ' Seluruh pegawai kini otomatis mengikuti Lokasi Global.';
        }
        if ($resetDailyLoc) {
            $pdo->prepare("UPDATE employee_schedules SET office_id = NULL, custom_lat = NULL, custom_lng = NULL, custom_radius = NULL, location_name = NULL WHERE school_id = ?")
                ->execute([$schoolId]);
            $extraMsg .= ' Koordinat custom harian juga telah disamakan ke Global.';
        }

        $pdo->commit();
        Response::json([
            'success' => true,
            'mode'    => 'updated',
            'message' => "Koordinat Lokasi Global ({$locName}) berhasil diperbarui!{$extraMsg}",
        ]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        Response::json(['success' => false, 'message' => 'Gagal menyimpan lokasi global: ' . $e->getMessage()], 500);
    }
}

if ($action === 'settings-save') {
    Security::requireCsrf();
    ensure_school_location_columns($pdo);
    $name        = trim((string)($body['name'] ?? ''));
    $npsn        = trim((string)($body['npsn'] ?? ''));
    $email       = trim((string)($body['email'] ?? ''));
    $phone       = trim((string)($body['phone'] ?? ''));
    $address     = trim((string)($body['address'] ?? ''));
    $principal   = trim((string)($body['principal_name'] ?? ''));
    $locName     = trim((string)($body['office_location_name'] ?? $name));
    $lat         = (float)($body['office_lat'] ?? $school['office_lat']);
    $lng         = (float)($body['office_lng'] ?? $school['office_lng']);
    $radius      = max(20, (int)($body['office_radius'] ?? 150));
    $forceGlobal = !empty($body['force_global_location']) ? 1 : 0;

    if ($name === '') {
        Response::json(['success' => false, 'message' => 'Nama sekolah tidak boleh kosong.'], 422);
    }

    $pdo->prepare("
        UPDATE schools
        SET name = ?, npsn = ?, email = ?, phone = ?, address = ?, principal_name = ?,
            office_location_name = ?, office_lat = ?, office_lng = ?, office_radius = ?, force_global_location = ?
        WHERE id = ?
    ")->execute([$name, $npsn, $email, $phone, $address, $principal, $locName ?: $name, $lat, $lng, $radius, $forceGlobal, $schoolId]);

    Response::json(['success' => true, 'mode' => 'updated', 'message' => 'Pengaturan sekolah & lokasi global berhasil diperbarui!']);
}

Response::json(['success' => false, 'message' => 'Aksi Admin Sekolah tidak dikenali.'], 404);
