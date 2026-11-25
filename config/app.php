<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Makassar');

define('APP_ROOT', dirname(__DIR__));
define('UPLOAD_DIR', APP_ROOT . DIRECTORY_SEPARATOR . 'uploads');
define('APP_VERSION', '1.0.0');

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
 * Dynamically detects the base URL path (e.g. "/edu-presensi" on XAMPP or "" on root domain).
 */
function app_base_path(): string
{
    static $basePath = null;
    if ($basePath !== null) {
        return $basePath;
    }
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $dir = rtrim(dirname($scriptName), '/');
    if ($dir === '.' || $dir === '\\') {
        $dir = '';
    }
    $basePath = $dir;
    return $basePath;
}

function app_scheme(): string
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return 'https';
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        return 'https';
    }
    return 'http';
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
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return app_scheme() . '://' . $host . app_url($path);
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
