<?php
declare(strict_types=1);

$action = $apiAction ?? '';
$body   = Response::getJsonBody();
$slug   = strtolower(trim((string)($_GET['school_slug'] ?? $_POST['school_slug'] ?? $body['school_slug'] ?? '')));

$school = Tenant::findSchoolBySlug($slug);
if (!$school) {
    Response::json(['success' => false, 'message' => 'Sekolah tidak ditemukan.'], 404);
}
$teacher  = Auth::requireSchoolRole($school, ['teacher', 'school_admin'], true);
Tenant::requireFeature($school, 'teacher_qr_scan', true);

$schoolId = (int)$school['id'];
$pdo      = Database::pdo();

// 1. Pull available classes from students table
if ($action === 'classes') {
    $stmt = $pdo->prepare("
        SELECT class_name, COUNT(*) AS student_count
        FROM students
        WHERE school_id = ? AND is_active = 1
        GROUP BY class_name
        ORDER BY class_name ASC
    ");
    $stmt->execute([$schoolId]);
    Response::json(['success' => true, 'data' => $stmt->fetchAll()]);
}

// 2. List attendance sessions
if ($action === 'sessions-list') {
    $sql = "
        SELECT s.*, u.name AS teacher_name,
               (SELECT COUNT(*) FROM student_attendance_records r WHERE r.session_id = s.id) AS total_students,
               (SELECT COUNT(*) FROM student_attendance_records r WHERE r.session_id = s.id AND r.status = 'hadir') AS hadir_count,
               (SELECT COUNT(*) FROM student_attendance_records r WHERE r.session_id = s.id AND r.status = 'sakit') AS sakit_count,
               (SELECT COUNT(*) FROM student_attendance_records r WHERE r.session_id = s.id AND r.status = 'izin') AS izin_count,
               (SELECT COUNT(*) FROM student_attendance_records r WHERE r.session_id = s.id AND r.status = 'alpha') AS alpha_count
        FROM student_attendance_sessions s
        JOIN users u ON u.id = s.teacher_id
        WHERE s.school_id = ?
    ";
    $params = [$schoolId];
    if ($teacher['role'] === 'teacher') {
        $sql .= " AND s.teacher_id = ?";
        $params[] = (int)$teacher['id'];
    }
    $sql .= " ORDER BY s.session_date DESC, s.created_at DESC LIMIT 60";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    Response::json(['success' => true, 'data' => $stmt->fetchAll()]);
}

// 3. Create session & automatically pull all students in chosen class
if ($action === 'session-create') {
    Security::requireCsrf();
    $className    = trim((string)($body['class_name'] ?? ''));
    $subjectName  = trim((string)($body['subject_name'] ?? ($teacher['subject_name'] ?? 'Umum')));
    $sessionTitle = trim((string)($body['session_title'] ?? ''));
    $sessionDate  = trim((string)($body['session_date'] ?? date('Y-m-d')));
    $startTime    = trim((string)($body['start_time'] ?? date('H:i')));
    $endTime      = trim((string)($body['end_time'] ?? date('H:i', strtotime('+90 minutes'))));

    if ($className === '') {
        Response::json(['success' => false, 'message' => 'Silakan pilih kelas terlebih dahulu.'], 422);
    }

    // Pull active students of this class
    $stuStmt = $pdo->prepare("SELECT id FROM students WHERE school_id = ? AND class_name = ? AND is_active = 1");
    $stuStmt->execute([$schoolId, $className]);
    $students = $stuStmt->fetchAll();

    if (empty($students)) {
        Response::json(['success' => false, 'message' => "Tidak ditemukan siswa aktif pada kelas '{$className}'."], 422);
    }

    if ($sessionTitle === '') {
        $sessionTitle = "Absen {$subjectName} - Kelas {$className}";
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare("
            INSERT INTO student_attendance_sessions (school_id, teacher_id, class_name, subject_name, session_title, session_date, start_time, end_time, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'open')
        ")->execute([$schoolId, $teacher['id'], $className, $subjectName, $sessionTitle, $sessionDate, $startTime, $endTime]);
        $sessionId = (int)$pdo->lastInsertId();

        $insRec = $pdo->prepare("
            INSERT INTO student_attendance_records (school_id, session_id, student_id, status)
            VALUES (?, ?, ?, 'alpha')
        ");
        foreach ($students as $st) {
            $insRec->execute([$schoolId, $sessionId, $st['id']]);
        }

        $pdo->commit();
        Response::json([
            'success' => true,
            'mode'    => 'saved',
            'message' => "Sesi absen '{$sessionTitle}' berhasil dibuat! " . count($students) . " siswa kelas {$className} ditarik otomatis.",
            'data'    => ['session_id' => $sessionId],
        ]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        Response::json(['success' => false, 'message' => 'Gagal membuat sesi absen: ' . $e->getMessage()], 500);
    }
}

// 4. Get session detail & students list
if ($action === 'session-detail') {
    $sessionId = (int)($_GET['session_id'] ?? 0);
    $sStmt = $pdo->prepare("SELECT * FROM student_attendance_sessions WHERE id = ? AND school_id = ? LIMIT 1");
    $sStmt->execute([$sessionId, $schoolId]);
    $session = $sStmt->fetch();
    if (!$session) {
        Response::json(['success' => false, 'message' => 'Sesi absen tidak ditemukan.'], 404);
    }

    $rStmt = $pdo->prepare("
        SELECT r.id AS record_id, r.status, r.scanned_at, r.scan_method, r.note,
               st.id AS student_id, st.nisn, st.name, st.class_name, st.gender, st.qr_token
        FROM student_attendance_records r
        JOIN students st ON st.id = r.student_id
        WHERE r.session_id = ? AND r.school_id = ?
        ORDER BY st.name ASC
    ");
    $rStmt->execute([$sessionId, $schoolId]);

    Response::json([
        'success' => true,
        'data'    => [
            'session' => $session,
            'records' => $rStmt->fetchAll(),
        ],
    ]);
}

// 5. Open/Close session
if ($action === 'session-toggle') {
    Security::requireCsrf();
    $sessionId = (int)($body['session_id'] ?? 0);
    $status    = ($body['status'] ?? 'closed') === 'open' ? 'open' : 'closed';
    $pdo->prepare("UPDATE student_attendance_sessions SET status = ? WHERE id = ? AND school_id = ?")->execute([$status, $sessionId, $schoolId]);
    Response::json(['success' => true, 'mode' => 'updated', 'message' => $status === 'open' ? 'Sesi absen dibuka kembali.' : 'Sesi absen telah ditutup.']);
}

// 6. Scan QR Code to mark student present
if ($action === 'scan-qr') {
    Security::requireCsrf();
    $sessionId = (int)($body['session_id'] ?? 0);
    $code      = trim((string)($body['code'] ?? ''));

    if (!$sessionId || $code === '') {
        Response::json(['success' => false, 'message' => 'Sesi dan kode QR tidak boleh kosong.'], 422);
    }

    $sStmt = $pdo->prepare("SELECT * FROM student_attendance_sessions WHERE id = ? AND school_id = ? LIMIT 1");
    $sStmt->execute([$sessionId, $schoolId]);
    $session = $sStmt->fetch();
    if (!$session) {
        Response::json(['success' => false, 'message' => 'Sesi absen tidak ditemukan.'], 404);
    }
    if ($session['status'] !== 'open') {
        Response::json(['success' => false, 'message' => 'Sesi absen ini sudah ditutup. Buka kembali untuk melakukan scan.'], 422);
    }

    // Clean scanned string (trim spaces/newlines from QR or USB barcode scanner)
    $code = trim(preg_replace('/\s+/', '', $code) ?? '');

    // Find student by qr_token (case-insensitive) or NISN inside THIS school
    $stStmt = $pdo->prepare("SELECT * FROM students WHERE school_id = ? AND (UPPER(qr_token) = UPPER(?) OR nisn = ?) AND is_active = 1 LIMIT 1");
    $stStmt->execute([$schoolId, $code, $code]);
    $student = $stStmt->fetch();
    if (!$student) {
        Response::json(['success' => false, 'message' => "Kartu QR ({$code}) tidak dikenali di sekolah ini."], 404);
    }

    // Check record in session
    $recStmt = $pdo->prepare("SELECT * FROM student_attendance_records WHERE session_id = ? AND student_id = ? LIMIT 1");
    $recStmt->execute([$sessionId, $student['id']]);
    $rec = $recStmt->fetch();

    if (!$rec) {
        // If student belongs to this session's class (e.g. newly added after session creation), auto-add & mark present!
        if (strcasecmp(trim((string)$student['class_name']), trim((string)$session['class_name'])) === 0) {
            $now = date('Y-m-d H:i:s');
            $pdo->prepare("
                INSERT INTO student_attendance_records (school_id, session_id, student_id, status, scanned_at, scan_method)
                VALUES (?, ?, ?, 'hadir', ?, 'qr')
            ")->execute([$schoolId, $sessionId, $student['id'], $now]);

            Response::json([
                'success'         => true,
                'already_scanned' => false,
                'message'         => "HADIR! {$student['name']} ({$student['nisn']}) berhasil discan.",
                'student'         => $student,
                'scanned_at'      => date('H:i:s', strtotime($now)),
            ]);
        }

        Response::json([
            'success' => false,
            'message' => "Siswa {$student['name']} berasal dari kelas {$student['class_name']}, bukan kelas {$session['class_name']}.",
        ], 422);
    }

    if ($rec['status'] === 'hadir') {
        Response::json([
            'success'        => true,
            'already_scanned'=> true,
            'message'        => "{$student['name']} sudah tercatat HADIR sebelumnya pada " . ($rec['scanned_at'] ? date('H:i:s', strtotime($rec['scanned_at'])) : '-'),
            'student'        => $student,
        ]);
    }

    $now = date('Y-m-d H:i:s');
    $pdo->prepare("UPDATE student_attendance_records SET status = 'hadir', scanned_at = ?, scan_method = 'qr' WHERE id = ?")
        ->execute([$now, $rec['id']]);

    Response::json([
        'success'         => true,
        'already_scanned' => false,
        'message'         => "HADIR! {$student['name']} ({$student['nisn']}) berhasil discan.",
        'student'         => $student,
        'scanned_at'      => date('H:i:s', strtotime($now)),
    ]);
}

// 7. Manual status update
if ($action === 'record-status') {
    Security::requireCsrf();
    $recordId = (int)($body['record_id'] ?? 0);
    $status   = (string)($body['status'] ?? 'hadir');
    $note     = trim((string)($body['note'] ?? ''));

    if (!in_array($status, ['hadir', 'sakit', 'izin', 'alpha'], true)) {
        Response::json(['success' => false, 'message' => 'Status tidak valid.'], 422);
    }

    $pdo->prepare("
        UPDATE student_attendance_records
        SET status = ?, scanned_at = IF(?='hadir', COALESCE(scanned_at, NOW()), NULL), scan_method = 'manual', note = ?
        WHERE id = ? AND school_id = ?
    ")->execute([$status, $status, $note, $recordId, $schoolId]);

    Response::json(['success' => true, 'mode' => 'updated', 'message' => 'Status kehadiran siswa diperbarui.']);
}

// 8. Rekapitulasi & Laporan Absensi Siswa
if ($action === 'reports') {
    $className = trim((string)($_GET['class_name'] ?? ''));
    $startDate = trim((string)($_GET['start_date'] ?? date('Y-m-01')));
    $endDate   = trim((string)($_GET['end_date'] ?? date('Y-m-d')));

    $sql = "
        SELECT st.id, st.nisn, st.name, st.class_name,
               COUNT(r.id) AS total_sessions,
               SUM(CASE WHEN r.status = 'hadir' THEN 1 ELSE 0 END) AS hadir,
               SUM(CASE WHEN r.status = 'sakit' THEN 1 ELSE 0 END) AS sakit,
               SUM(CASE WHEN r.status = 'izin' THEN 1 ELSE 0 END) AS izin,
               SUM(CASE WHEN r.status = 'alpha' THEN 1 ELSE 0 END) AS alpha
        FROM students st
        LEFT JOIN student_attendance_records r ON r.student_id = st.id
        LEFT JOIN student_attendance_sessions s ON s.id = r.session_id AND s.session_date BETWEEN ? AND ?
        WHERE st.school_id = ? AND st.is_active = 1
    ";
    $params = [$startDate, $endDate, $schoolId];
    if ($className !== '') {
        $sql .= " AND st.class_name = ?";
        $params[] = $className;
    }
    $sql .= " GROUP BY st.id ORDER BY st.class_name ASC, st.name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Re-query strictly counting sessions inside date range so LEFT JOIN counts accurately
    $strictStmt = $pdo->prepare("
        SELECT r.student_id,
               COUNT(r.id) AS total_sessions,
               SUM(CASE WHEN r.status = 'hadir' THEN 1 ELSE 0 END) AS hadir,
               SUM(CASE WHEN r.status = 'sakit' THEN 1 ELSE 0 END) AS sakit,
               SUM(CASE WHEN r.status = 'izin' THEN 1 ELSE 0 END) AS izin,
               SUM(CASE WHEN r.status = 'alpha' THEN 1 ELSE 0 END) AS alpha
        FROM student_attendance_records r
        JOIN student_attendance_sessions s ON s.id = r.session_id
        WHERE r.school_id = ? AND s.session_date BETWEEN ? AND ?
        GROUP BY r.student_id
    ");
    $strictStmt->execute([$schoolId, $startDate, $endDate]);
    $aggMap = [];
    foreach ($strictStmt->fetchAll() as $a) {
        $aggMap[(int)$a['student_id']] = $a;
    }

    $recap = [];
    foreach ($rows as $r) {
        $a = $aggMap[(int)$r['id']] ?? ['total_sessions' => 0, 'hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpha' => 0];
        $tot = (int)$a['total_sessions'];
        $hadir = (int)$a['hadir'];
        $pct = $tot > 0 ? round(($hadir / $tot) * 100, 1) : 0;
        $recap[] = [
            'nisn'           => $r['nisn'],
            'name'           => $r['name'],
            'class_name'     => $r['class_name'],
            'total_sessions' => $tot,
            'hadir'          => $hadir,
            'sakit'          => (int)$a['sakit'],
            'izin'           => (int)$a['izin'],
            'alpha'          => (int)$a['alpha'],
            'percentage'     => $pct,
        ];
    }

    $sessStmt = $pdo->prepare("
        SELECT s.*, u.name AS teacher_name,
               (SELECT COUNT(*) FROM student_attendance_records r WHERE r.session_id = s.id AND r.status='hadir') AS hadir,
               (SELECT COUNT(*) FROM student_attendance_records r WHERE r.session_id = s.id) AS total
        FROM student_attendance_sessions s
        JOIN users u ON u.id = s.teacher_id
        WHERE s.school_id = ? AND s.session_date BETWEEN ? AND ?
        " . ($className !== '' ? " AND s.class_name = ?" : "") . "
        ORDER BY s.session_date DESC, s.created_at DESC
    ");
    $sp = [$schoolId, $startDate, $endDate];
    if ($className !== '') $sp[] = $className;
    $sessStmt->execute($sp);

    $classes = $pdo->prepare("SELECT DISTINCT class_name FROM students WHERE school_id = ? ORDER BY class_name ASC");
    $classes->execute([$schoolId]);

    Response::json([
        'success' => true,
        'data'    => [
            'recap'    => $recap,
            'sessions' => $sessStmt->fetchAll(),
            'classes'  => array_column($classes->fetchAll(), 'class_name'),
        ],
    ]);
}

Response::json(['success' => false, 'message' => 'Aksi Admin Guru tidak dikenali.'], 404);
