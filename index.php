<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Security.php';
require_once __DIR__ . '/core/Response.php';
require_once __DIR__ . '/core/Tenant.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/WaGateway.php';
require_once __DIR__ . '/core/XlsxHelper.php';

Security::startSession();

// Parse clean request path relative to auto-detected app base path
$reqUri   = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$basePath = app_base_path();
if ($basePath !== '' && (str_starts_with($reqUri, $basePath) || (DIRECTORY_SEPARATOR === '\\' && stripos($reqUri, $basePath) === 0))) {
    $reqUri = substr($reqUri, strlen($basePath));
}
if (str_starts_with($reqUri, '/index.php')) {
    $reqUri = substr($reqUri, 10);
}
$route = trim($reqUri, '/');
$segments = $route === '' ? [] : explode('/', $route);
$seg0 = strtolower($segments[0] ?? '');
$seg1 = strtolower($segments[1] ?? '');
$seg2 = strtolower($segments[2] ?? '');

// =========================================================================
// 1. API ROUTES: /api/{module}/{action}
// =========================================================================
if ($seg0 === 'api') {
    $apiModule = $seg1;
    $apiAction = $seg2;
    $apiFileMap = [
        'auth'         => __DIR__ . '/api/auth.php',
        'superadmin'   => __DIR__ . '/api/superadmin.php',
        'school_admin' => __DIR__ . '/api/school_admin.php',
        'teacher'      => __DIR__ . '/api/teacher.php',
        'employee'     => __DIR__ . '/api/employee.php',
    ];
    if (isset($apiFileMap[$apiModule]) && file_exists($apiFileMap[$apiModule])) {
        require $apiFileMap[$apiModule];
        exit;
    }
    Response::json(['success' => false, 'message' => 'API endpoint tidak ditemukan.'], 404);
}

// =========================================================================
// 2. ROOT LANDING PAGE
// =========================================================================
if ($route === '') {
    $packages = [];
    $demoSchools = [];
    $dbReady = true;
    try {
        $pdo = Database::pdo();
        $packages = $pdo->query("SELECT * FROM packages WHERE is_active = 1 ORDER BY sort_order ASC, price_monthly ASC")->fetchAll();
        $demoSchools = $pdo->query("SELECT name, slug, subscription_status FROM schools WHERE subscription_status IN ('active','trial') ORDER BY id ASC LIMIT 6")->fetchAll();
    } catch (Throwable) {
        $dbReady = false;
    }
    Response::render('public/landing', [
        'pageTitle'   => 'Beranda',
        'packages'    => $packages,
        'demoSchools' => $demoSchools,
        'dbReady'     => $dbReady,
    ]);
    exit;
}

// =========================================================================
// 3. SUPERADMIN PORTAL: /superadmin/...
// =========================================================================
if ($seg0 === 'superadmin') {
    if ($seg1 === 'login') {
        $user = Auth::user();
        if ($user && $user['role'] === 'superadmin') {
            Response::redirect(app_url('/superadmin/dashboard'));
        }
        $packages = [];
        try {
            $packages = Database::pdo()->query("SELECT * FROM packages WHERE is_active = 1 ORDER BY sort_order ASC, price_monthly ASC")->fetchAll();
        } catch (Throwable) {
        }
        Response::render('superadmin/login', ['pageTitle' => 'Login Superadmin', 'packages' => $packages]);
        exit;
    }

    if ($seg1 === 'logout') {
        Auth::logout();
        Response::redirect(app_url('/superadmin/login'));
    }

    $user = Auth::requireSuperadmin(false);
    $page = $seg1 === '' ? 'dashboard' : $seg1;

    $saViews = [
        'dashboard'  => ['view' => 'superadmin/dashboard', 'title' => 'Dashboard Monitoring', 'loadChartJs' => true],
        'schools'    => ['view' => 'superadmin/schools', 'title' => 'Daftar Sekolah Berlangganan'],
        'packages'   => ['view' => 'superadmin/packages', 'title' => 'Daftar Paket & Fitur'],
        'finance'    => ['view' => 'superadmin/finance', 'title' => 'Keuangan & Omset'],
        'wa-gateway' => ['view' => 'superadmin/wa_gateway', 'title' => 'WA Gateway Pengingat'],
        'branding'   => ['view' => 'superadmin/branding', 'title' => 'Logo & Favicon SaaS'],
    ];

    if (!isset($saViews[$page])) {
        http_response_code(404);
        Response::render('public/404', ['pageTitle' => 'Halaman Tidak Ditemukan']);
        exit;
    }

    $meta = $saViews[$page];
    Response::render($meta['view'], [
        'pageTitle'   => $meta['title'],
        'activeMenu'  => $page,
        'currentUser' => $user,
        'loadChartJs' => $meta['loadChartJs'] ?? false,
    ], 'superadmin_layout');
    exit;
}

// =========================================================================
// 4. SCHOOL SUBFOLDER TENANT ROUTES: /{slug}/...
//    e.g. eduabsen.sekolah-indonesia.id/bina-insan-palu
// =========================================================================
$school = Tenant::findSchoolBySlug($seg0);
if (!$school) {
    http_response_code(404);
    Response::render('public/404', ['pageTitle' => 'Sekolah Tidak Ditemukan', 'requestedSlug' => $seg0]);
    exit;
}
Tenant::setCurrentSchool($school);

// 4a. Dynamic PWA Manifest per School using Superadmin Favicon!
if ($seg1 === 'manifest.json') {
    $saas = Tenant::saasSettings();
    $favPath = $saas['favicon_path'] ?? '';
    $favExt  = strtolower(pathinfo($favPath, PATHINFO_EXTENSION));
    $icons   = [];

    if ($favPath && file_exists(APP_ROOT . '/' . ltrim($favPath, '/'))) {
        $favUrl = app_url($favPath);
        $mimeMap = [
            'png'  => 'image/png',
            'svg'  => 'image/svg+xml',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'ico'  => 'image/x-icon',
        ];
        $mime = $mimeMap[$favExt] ?? 'image/png';
        $icons[] = ['src' => $favUrl, 'sizes' => '192x192', 'type' => $mime, 'purpose' => 'any maskable'];
        $icons[] = ['src' => $favUrl, 'sizes' => '512x512', 'type' => $mime, 'purpose' => 'any maskable'];
    }
    // Always append genuine PNG fallback icons for full Android/Chrome PWA installability
    $icons[] = ['src' => app_url('/assets/img/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'];
    $icons[] = ['src' => app_url('/assets/img/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'];

    header('Content-Type: application/manifest+json; charset=utf-8');
    echo json_encode([
        'name'             => $school['name'] . ' — ' . $saas['app_name'],
        'short_name'       => mb_substr($school['name'], 0, 18),
        'description'      => 'Aplikasi Absensi Mobile Pegawai ' . $school['name'],
        'start_url'        => Tenant::schoolLocalUrl($school['slug'], 'pegawai/dashboard'),
        'scope'            => Tenant::schoolLocalUrl($school['slug']) . '/',
        'display'          => 'standalone',
        'orientation'      => 'portrait',
        'background_color' => '#eff6ff',
        'theme_color'      => '#1d4ed8',
        'icons'            => $icons,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

// 4b. Dynamic PWA Service Worker per School
if ($seg1 === 'sw.js') {
    header('Content-Type: application/javascript; charset=utf-8');
    header('Service-Worker-Allowed: ' . Tenant::schoolLocalUrl($school['slug']) . '/');
    $cacheName = 'edupresensi-' . $school['slug'] . '-v' . APP_VERSION;
    $cssUrl = app_url('/assets/css/app.css');
    $modUrl = app_url('/assets/js/app-modals.js');
    $apiUrl = app_url('/assets/js/api-client.js');
    echo <<<JS
const CACHE_NAME = '{$cacheName}';
const STATIC_ASSETS = ['{$cssUrl}', '{$modUrl}', '{$apiUrl}'];
self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(CACHE_NAME).then((c) => c.addAll(STATIC_ASSETS)).catch(() => {}));
  self.skipWaiting();
});
self.addEventListener('activate', (e) => {
  e.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))));
  self.clients.claim();
});
self.addEventListener('fetch', (e) => {
  if (e.request.method !== 'GET') return;
  if (e.request.url.includes('/api/')) return;
  e.respondWith(fetch(e.request).catch(() => caches.match(e.request)));
});
JS;
    exit;
}

// 4c. School Logout
if ($seg1 === 'logout') {
    Auth::logout();
    Response::redirect(Tenant::schoolLocalUrl($school['slug']));
}

// Check active subscription before entering school portal
if (!Tenant::isSchoolActive($school)) {
    Response::render('public/school_suspended', ['pageTitle' => 'Langganan Tidak Aktif', 'school' => $school]);
    exit;
}

// 4d. School Subfolder Landing / Unified Login: /{slug}
if ($seg1 === '' || $seg1 === 'login') {
    $user = Auth::user();
    if ($user && (int)$user['school_id'] === (int)$school['id']) {
        if ($user['role'] === 'school_admin') {
            Response::redirect(Tenant::schoolLocalUrl($school['slug'], 'admin/dashboard'));
        } elseif ($user['role'] === 'teacher') {
            Response::redirect(Tenant::schoolLocalUrl($school['slug'], 'guru/dashboard'));
        } elseif ($user['role'] === 'employee') {
            Response::redirect(Tenant::schoolLocalUrl($school['slug'], 'pegawai/dashboard'));
        }
    }
    Response::render('public/school_login', [
        'pageTitle'      => 'Login — ' . $school['name'],
        'school'         => $school,
        'pwaManifestUrl' => Tenant::schoolLocalUrl($school['slug'], 'manifest.json'),
        'pwaSwUrl'       => Tenant::schoolLocalUrl($school['slug'], 'sw.js'),
    ]);
    exit;
}

// 4e. ADMIN SEKOLAH PORTAL: /{slug}/admin/...
if ($seg1 === 'admin') {
    $user = Auth::requireSchoolRole($school, ['school_admin'], false);
    $page = $seg2 === '' ? 'dashboard' : $seg2;

    if ($page === 'student-cards') {
        Tenant::requireFeature($school, 'student_management', false);
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        Response::render('school_admin/student_cards', [
            'pageTitle'   => 'Download Kartu QR Siswa (PNG)',
            'school'      => $school,
            'currentUser' => $user,
            'loadQrCode'  => true,
        ]);
        exit;
    }

    $adminViews = [
        'dashboard' => ['view' => 'school_admin/dashboard', 'title' => 'Dashboard Admin Sekolah', 'loadChartJs' => true],
        'offices'   => ['view' => 'school_admin/offices', 'title' => 'Kelola Kantor & Shift Kantor', 'loadLeaflet' => true],
        'shifts'    => ['view' => 'school_admin/shifts', 'title' => 'Kelola Shift & Jam Kerja'],
        'employees' => ['view' => 'school_admin/employees', 'title' => 'Data Pegawai, Shift & Koordinat', 'loadSheetJs' => true, 'loadLeaflet' => true],
        'students'  => ['view' => 'school_admin/students', 'title' => 'Data Siswa & Kartu QR', 'loadSheetJs' => true, 'loadQrCode' => true, 'feature' => 'student_management'],
        'reports'   => ['view' => 'school_admin/reports', 'title' => 'Laporan Absensi Pegawai', 'loadSheetJs' => true, 'feature' => 'employee_reports'],
        'teachers'  => ['view' => 'school_admin/teachers', 'title' => 'Akun Guru Absen Siswa', 'feature' => 'teacher_qr_scan'],
        'settings'  => ['view' => 'school_admin/settings', 'title' => 'Pengaturan Sekolah', 'loadLeaflet' => true],
    ];

    if (!isset($adminViews[$page])) {
        http_response_code(404);
        Response::render('public/404', ['pageTitle' => 'Halaman Tidak Ditemukan']);
        exit;
    }

    $meta = $adminViews[$page];
    if (!empty($meta['feature'])) {
        Tenant::requireFeature($school, $meta['feature'], false);
    }

    Response::render($meta['view'], [
        'pageTitle'   => $meta['title'],
        'activeMenu'  => $page,
        'school'      => $school,
        'currentUser' => $user,
        'loadChartJs' => $meta['loadChartJs'] ?? false,
        'loadSheetJs' => $meta['loadSheetJs'] ?? false,
        'loadLeaflet' => $meta['loadLeaflet'] ?? false,
        'loadQrCode'  => $meta['loadQrCode'] ?? false,
    ], 'admin_layout');
    exit;
}

// 4f. ADMIN GURU PORTAL: /{slug}/guru/...
if ($seg1 === 'guru') {
    $user = Auth::requireSchoolRole($school, ['teacher', 'school_admin'], false);
    Tenant::requireFeature($school, 'teacher_qr_scan', false);
    $page = $seg2 === '' ? 'dashboard' : $seg2;

    $guruViews = [
        'dashboard' => ['view' => 'teacher/dashboard', 'title' => 'Sesi Absen Siswa'],
        'scan'      => ['view' => 'teacher/scan', 'title' => 'Scan QR Code Siswa', 'loadQrScanner' => true],
        'reports'   => ['view' => 'teacher/reports', 'title' => 'Rekap & Laporan Siswa', 'loadSheetJs' => true],
    ];

    if (!isset($guruViews[$page])) {
        http_response_code(404);
        Response::render('public/404', ['pageTitle' => 'Halaman Tidak Ditemukan']);
        exit;
    }

    $meta = $guruViews[$page];
    Response::render($meta['view'], [
        'pageTitle'     => $meta['title'],
        'activeMenu'    => $page,
        'school'        => $school,
        'currentUser'   => $user,
        'loadQrScanner' => $meta['loadQrScanner'] ?? false,
        'loadSheetJs'   => $meta['loadSheetJs'] ?? false,
    ], 'teacher_layout');
    exit;
}

// 4g. PEGAWAI MOBILE PWA PORTAL: /{slug}/pegawai/...
if ($seg1 === 'pegawai') {
    $user = Auth::requireSchoolRole($school, ['employee', 'teacher', 'school_admin'], false);
    Tenant::requireFeature($school, 'employee_attendance', false);
    $page = $seg2 === '' ? 'dashboard' : $seg2;

    $empViews = [
        'dashboard'   => ['view' => 'employee/dashboard', 'title' => 'Beranda Pegawai'],
        'attendance'  => ['view' => 'employee/attendance', 'title' => 'Check-in & Check-out', 'loadLeaflet' => true],
        'corrections' => ['view' => 'employee/corrections', 'title' => 'Perbaikan Absen'],
        'account'     => ['view' => 'employee/account', 'title' => 'Pengaturan Akun'],
    ];

    if (!isset($empViews[$page])) {
        http_response_code(404);
        Response::render('public/404', ['pageTitle' => 'Halaman Tidak Ditemukan']);
        exit;
    }

    $meta = $empViews[$page];
    Response::render($meta['view'], [
        'pageTitle'      => $meta['title'],
        'activeMenu'     => $page,
        'school'         => $school,
        'currentUser'    => $user,
        'loadLeaflet'    => $meta['loadLeaflet'] ?? false,
        'pwaManifestUrl' => Tenant::schoolLocalUrl($school['slug'], 'manifest.json'),
        'pwaSwUrl'       => Tenant::schoolLocalUrl($school['slug'], 'sw.js'),
    ], 'employee_layout');
    exit;
}

http_response_code(404);
Response::render('public/404', ['pageTitle' => 'Halaman Tidak Ditemukan']);
