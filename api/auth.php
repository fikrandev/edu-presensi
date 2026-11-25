<?php
declare(strict_types=1);

$action = $apiAction ?? '';
$body   = Response::getJsonBody();

if ($action === 'superadmin-login') {
    Security::requireCsrf();
    $email    = strtolower(trim((string)($body['email'] ?? '')));
    $password = (string)($body['password'] ?? '');

    if ($email === '' || $password === '') {
        Response::json(['success' => false, 'message' => 'Email dan password wajib diisi.'], 422);
    }
    if (!Security::checkLoginRateLimit('sa:' . $email)) {
        Response::json(['success' => false, 'message' => 'Terlalu banyak percobaan login gagal. Coba lagi 15 menit lagi.'], 429);
    }

    $pdo = Database::pdo();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'superadmin' AND LOWER(email) = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !Security::verifyPassword($password, $user['password_hash'])) {
        Security::recordFailedLogin('sa:' . $email);
        Response::json(['success' => false, 'message' => 'Email atau password Superadmin tidak valid.'], 401);
    }

    Security::clearLoginAttempts('sa:' . $email);
    // Superadmin Web Admin: strictly 3-hour session (never persistent forever)
    Auth::loginUser($user, false);

    Response::json([
        'success'  => true,
        'message'  => 'Login Superadmin berhasil (sesi aktif selama 3 jam).',
        'redirect' => app_url('/superadmin/dashboard'),
    ]);
}

if ($action === 'school-login') {
    Security::requireCsrf();
    $slug       = strtolower(trim((string)($body['school_slug'] ?? '')));
    $identifier = trim((string)($body['identifier'] ?? ''));
    $password   = (string)($body['password'] ?? '');
    $remember   = !isset($body['remember_forever']) || (bool)$body['remember_forever'];

    if ($slug === '' || $identifier === '' || $password === '') {
        Response::json(['success' => false, 'message' => 'Email/NIP dan password wajib diisi.'], 422);
    }

    $school = Tenant::findSchoolBySlug($slug);
    if (!$school) {
        Response::json(['success' => false, 'message' => 'Sekolah tidak ditemukan.'], 404);
    }
    if (!Tenant::isSchoolActive($school)) {
        Response::json(['success' => false, 'message' => 'Langganan sekolah ini sedang tidak aktif atau telah jatuh tempo.'], 402);
    }

    $rateKey = $slug . ':' . strtolower($identifier);
    if (!Security::checkLoginRateLimit($rateKey)) {
        Response::json(['success' => false, 'message' => 'Terlalu banyak percobaan login gagal. Coba lagi 15 menit lagi.'], 429);
    }

    $pdo = Database::pdo();
    $stmt = $pdo->prepare("
        SELECT * FROM users
        WHERE school_id = ?
          AND role IN ('school_admin', 'teacher', 'employee')
          AND is_active = 1
          AND (LOWER(email) = LOWER(?) OR nip_nik = ?)
        LIMIT 1
    ");
    $stmt->execute([$school['id'], $identifier, $identifier]);
    $user = $stmt->fetch();

    if (!$user || !Security::verifyPassword($password, $user['password_hash'])) {
        Security::recordFailedLogin($rateKey);
        Response::json(['success' => false, 'message' => 'Email/NIP atau password salah.'], 401);
    }

    Security::clearLoginAttempts($rateKey);
    // Policy: Mobile PWA Employee = Forever Login; Web Admin / Guru = Strict 3-Hour Session
    $persistForever = ($user['role'] === 'employee');
    Auth::loginUser($user, $persistForever);

    $redirectMap = [
        'school_admin' => Tenant::schoolLocalUrl($school['slug'], 'admin/dashboard'),
        'teacher'      => Tenant::schoolLocalUrl($school['slug'], 'guru/dashboard'),
        'employee'     => Tenant::schoolLocalUrl($school['slug'], 'pegawai/dashboard'),
    ];

    $sessionNote = $persistForever ? ' (Sesi Mobile PWA aktif selamanya).' : ' (Sesi Web Admin aktif selama 3 jam).';
    Response::json([
        'success'  => true,
        'message'  => 'Login berhasil. Selamat datang, ' . $user['name'] . '!' . $sessionNote,
        'role'     => $user['role'],
        'redirect' => $redirectMap[$user['role']] ?? Tenant::schoolLocalUrl($school['slug']),
    ]);
}

if ($action === 'logout') {
    Security::requireCsrf();
    Auth::logout();
    Response::json(['success' => true, 'message' => 'Berhasil keluar dari sesi.']);
}

Response::json(['success' => false, 'message' => 'Aksi auth tidak dikenali.'], 404);
