<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('UPLOAD_DIR', APP_ROOT . DIRECTORY_SEPARATOR . 'uploads');
define('APP_VERSION', '1.0.0');

/**
 * Lightweight native .env loader (no Composer required).
 * Reads APP_ROOT/.env if present without overriding real server environment variables.
 */
function load_env_file(string $envPath): void
{
    if (!is_file($envPath) || !is_readable($envPath)) {
        return;
    }
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val);
        if ($key === '') {
            continue;
        }
        if (
            (str_starts_with($val, '"') && str_ends_with($val, '"'))
            || (str_starts_with($val, "'") && str_ends_with($val, "'"))
        ) {
            $val = substr($val, 1, -1);
        }
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $val;
        }
        if (getenv($key) === false) {
            putenv("{$key}={$val}");
        }
    }
}

load_env_file(APP_ROOT . DIRECTORY_SEPARATOR . '.env');

function env(string $key, ?string $default = null): ?string
{
    if (array_key_exists($key, $_ENV)) {
        return (string)$_ENV[$key];
    }
    $val = getenv($key);
    if ($val !== false) {
        return (string)$val;
    }
    return $default;
}

date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Makassar') ?: 'Asia/Makassar');

// Reserved first-level URL segments that cannot be used as school subfolder slugs
define('RESERVED_SLUGS', [
    'superadmin', 'admin', 'guru', 'pegawai', 'api', 'assets', 'uploads',
    'config', 'core', 'database', 'views', 'cron', 'install', 'install.php',
    'manifest.json', 'sw.js', 'favicon.ico', 'robots.txt', 'login', 'logout'
]);

// Master SaaS Feature Registry (checked per Package in Superadmin)
define('SAAS_FEATURES', [
    'employee_attendance' => [
        'label' => 'Absensi Pegawai GPS & PWA Mobile',
        'desc'  => 'Check-in / Check-out GPS geofencing, foto selfie, dan aplikasi PWA mobile pegawai.'
    ],
    'custom_shift_location' => [
        'label' => 'Jadwal Shift & Koordinat Custom per Hari per Orang',
        'desc'  => 'Atur shift dan titik koordinat kantor berbeda untuk setiap pegawai dan setiap hari.'
    ],
    'excel_import' => [
        'label' => 'Import Data Excel (.xlsx)',
        'desc'  => 'Import massal data pegawai dan siswa menggunakan file Microsoft Excel (.xlsx).'
    ],
    'employee_reports' => [
        'label' => 'Laporan Log, Matrix Monitoring & Rekap Persentase',
        'desc'  => 'Laporan lengkap log harian, matrix kalender pengisian, rekapitulasi & persentase.'
    ],
    'attendance_correction' => [
        'label' => 'Pengajuan Perbaikan Absen Pegawai',
        'desc'  => 'Alur pengajuan koreksi kehadiran / izin / sakit pegawai dan persetujuan admin sekolah.'
    ],
    'student_management' => [
        'label' => 'Manajemen Data Siswa & Cetak Kartu QR Code',
        'desc'  => 'Kelola data siswa per kelas serta generate & cetak kartu pelajar QR Code siap scan.'
    ],
    'teacher_qr_scan' => [
        'label' => 'Portal Guru & Scan QR Absensi Siswa',
        'desc'  => 'Akun guru untuk membuat sesi absen kelas dan scan QR Code kartu siswa.'
    ],
    'teacher_reports' => [
        'label' => 'Rekapitulasi & Laporan Absensi Siswa',
        'desc'  => 'Laporan kehadiran siswa per sesi, kelas, dan periode dengan export Excel.'
    ],
    'wa_reminder' => [
        'label' => 'Notifikasi Pengingat WA Gateway',
        'desc'  => 'Pengingat otomatis tagihan langganan jatuh tempo H-3 dan Hari-H melalui WhatsApp.'
    ],
]);

/**
 * Automatically detects the base URL path for ANY hosting environment:
 * - Root domain (e.g. https://eduabsen.sekolah-indonesia.id/) -> ""
 * - Subfolder (e.g. http://localhost/edu-presensi/ or https://domain.com/absen/) -> "/edu-presensi" or "/absen"
 * Works reliably from index.php, install.php, cron/wa_reminder.php, cPanel, CyberPanel, Nginx, and XAMPP.
 */
function app_base_path(): string
{
    static $basePath = null;
    if ($basePath !== null) {
        return $basePath;
    }

    // 1. Optional explicit override via .env (leave blank for full auto-detect)
    $envBase = env('APP_BASE_PATH', '');
    if ($envBase !== null && trim($envBase) !== '') {
        $clean = '/' . trim(str_replace('\\', '/', trim($envBase)), '/');
        $basePath = ($clean === '/') ? '' : $clean;
        return $basePath;
    }

    // 2. Primary auto-detection: compare APP_ROOT against DOCUMENT_ROOT
    $docRootReal = !empty($_SERVER['DOCUMENT_ROOT']) ? realpath((string)$_SERVER['DOCUMENT_ROOT']) : false;
    $appRootReal = realpath(APP_ROOT);
    if ($docRootReal && $appRootReal) {
        $docNorm = rtrim(str_replace('\\', '/', $docRootReal), '/');
        $appNorm = rtrim(str_replace('\\', '/', $appRootReal), '/');

        // Case-insensitive prefix check on Windows, case-sensitive on Linux
        $matches = (DIRECTORY_SEPARATOR === '\\')
            ? (stripos($appNorm, $docNorm) === 0)
            : str_starts_with($appNorm, $docNorm);

        if ($matches) {
            $rel = substr($appNorm, strlen($docNorm));
            $rel = '/' . trim($rel, '/');
            $basePath = ($rel === '/') ? '' : $rel;
            return $basePath;
        }
    }

    // 3. Fallback via SCRIPT_NAME (handles symlinked hostings & cron/ subdir cleanly)
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $dir = rtrim(dirname($scriptName), '/');
    if (str_ends_with($dir, '/cron')) {
        $dir = substr($dir, 0, -5);
    }
    if ($dir === '.' || $dir === '\\' || $dir === '/') {
        $dir = '';
    }
    $basePath = $dir;
    return $basePath;
}

/**
 * Accurately detects HTTP vs HTTPS behind Apache, Nginx, cPanel, Load Balancers, and Cloudflare.
 */
function app_scheme(): string
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return 'https';
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $proto = strtolower(trim(explode(',', (string)$_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
        if ($proto === 'https') {
            return 'https';
        }
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') {
        return 'https';
    }
    if (!empty($_SERVER['HTTP_CF_VISITOR']) && str_contains(strtolower((string)$_SERVER['HTTP_CF_VISITOR']), '"https"')) {
        return 'https';
    }
    if ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443) {
        return 'https';
    }
    return 'http';
}

function app_host(): string
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
        return trim(explode(',', (string)$_SERVER['HTTP_X_FORWARDED_HOST'])[0]);
    }
    return $_SERVER['HTTP_HOST'] ?? 'localhost';
}

function app_url(string $path = ''): string
{
    $base = app_base_path();
    $cleanPath = '/' . ltrim($path, '/');
    if ($cleanPath === '/') {
        return $base !== '' ? $base . '/' : '/';
    }
    return $base . $cleanPath;
}

function app_full_url(string $path = ''): string
{
    return app_scheme() . '://' . app_host() . app_url($path);
}

function format_rupiah(float|int|string $amount): string
{
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}

function format_date_id(?string $date, bool $withTime = false): string
{
    if (!$date) {
        return '-';
    }
    $ts = strtotime($date);
    if (!$ts) {
        return $date;
    }
    $months = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];
    $m = (int)date('n', $ts);
    $formatted = date('d', $ts) . ' ' . ($months[$m] ?? date('M', $ts)) . ' ' . date('Y', $ts);
    if ($withTime) {
        $formatted .= ' ' . date('H:i', $ts);
    }
    return $formatted;
}

function day_name_id(int $dayOfWeek): string
{
    $days = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];
    return $days[$dayOfWeek] ?? '-';
}
