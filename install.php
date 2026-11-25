<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Security.php';
require_once __DIR__ . '/database/seed.php';

$status = null;
$logs = [];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $cfg = Database::config();
        $pdoNoDb = Database::pdoWithoutDb();
        $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', $cfg['database']);
        $pdoNoDb->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $logs[] = "Database `{$dbName}` siap.";

        $pdo = Database::pdo();
        $schemaSql = file_get_contents(__DIR__ . '/database/schema.sql');
        if ($schemaSql === false) {
            throw new RuntimeException('File database/schema.sql tidak ditemukan.');
        }

        // Execute statements cleanly
        $pdo->exec($schemaSql);
        $logs[] = 'Seluruh tabel schema.sql berhasil dibuat / diverifikasi.';

        $seedLogs = run_saas_seed($pdo);
        $logs = array_merge($logs, $seedLogs ?: ['Data demo sudah tersedia.']);

        // Ensure uploads subdirectories exist
        foreach (['branding', 'attendance', 'corrections', 'avatars'] as $sub) {
            $dir = UPLOAD_DIR . DIRECTORY_SEPARATOR . $sub;
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        $logs[] = 'Direktori upload berhasil disiapkan.';
        $status = 'success';
    } catch (Throwable $e) {
        $status = 'error';
        $error = $e->getMessage();
    }
}
$cfg = Database::config();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Installer — EduPresensi SaaS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-950 via-blue-900 to-blue-800 min-h-screen flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl max-w-xl w-full p-8">
    <div class="flex items-center gap-3 mb-6">
      <div class="w-12 h-12 rounded-xl bg-blue-600 text-white flex items-center justify-center text-xl shadow-lg">
        <i class="fa-solid fa-database"></i>
      </div>
      <div>
        <h1 class="text-2xl font-bold text-slate-800">Installer EduPresensi SaaS</h1>
        <p class="text-sm text-slate-500">Setup Database MySQL & Data Demo Otomatis</p>
      </div>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6 text-sm text-blue-900 space-y-1">
      <div><strong>Host:</strong> <?= Security::e($cfg['host']) ?>:<?= Security::e($cfg['port']) ?></div>
      <div><strong>Database:</strong> <code><?= Security::e($cfg['database']) ?></code></div>
      <div><strong>User:</strong> <code><?= Security::e($cfg['username']) ?></code></div>
      <div class="text-xs text-blue-700 pt-1">Pastikan service Apache & MySQL di XAMPP sudah aktif sebelum menjalankan instalasi.</div>
    </div>

    <?php if ($status === 'success'): ?>
      <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mb-6 text-emerald-900">
        <div class="font-bold flex items-center gap-2 mb-2"><i class="fa-solid fa-circle-check text-emerald-600"></i> Instalasi Berhasil!</div>
        <ul class="text-sm list-disc pl-5 space-y-1">
          <?php foreach ($logs as $l): ?>
            <li><?= Security::e($l) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 mb-6 text-xs text-slate-700 space-y-1.5">
        <div class="font-bold text-slate-800 text-sm mb-1">Akun Demo Default (Password: <code>Admin123!</code>)</div>
        <div><strong>Superadmin:</strong> <code>superadmin@edupresensi.id</code></div>
        <div><strong>Admin Sekolah (Bina Insan Palu):</strong> <code>admin@binainsan.sch.id</code></div>
        <div><strong>Guru (Bina Insan Palu):</strong> <code>guru@binainsan.sch.id</code></div>
        <div><strong>Pegawai PWA (Bina Insan Palu):</strong> <code>pegawai@binainsan.sch.id</code></div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <a href="<?= app_url('/superadmin/login') ?>" class="text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-xl transition">
          <i class="fa-solid fa-shield-halved mr-1"></i> Login Superadmin
        </a>
        <a href="<?= app_url('/bina-insan-palu') ?>" class="text-center bg-slate-800 hover:bg-slate-900 text-white font-semibold py-3 rounded-xl transition">
          <i class="fa-solid fa-school mr-1"></i> Portal Bina Insan Palu
        </a>
      </div>
    <?php else: ?>
      <?php if ($status === 'error'): ?>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 text-red-800 text-sm">
          <div class="font-bold mb-1"><i class="fa-solid fa-triangle-exclamation mr-1"></i> Gagal Terhubung / Instalasi</div>
          <div><?= Security::e($error) ?></div>
        </div>
      <?php endif; ?>

      <form method="POST">
        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-blue-600/30 transition flex items-center justify-center gap-2">
          <i class="fa-solid fa-rocket"></i> Jalankan Instalasi Database & Seed Demo
        </button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
