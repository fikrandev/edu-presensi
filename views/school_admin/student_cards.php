<?php
require dirname(__DIR__) . '/layouts/head.php';
$pdo = Database::pdo();
$schoolId = (int)$school['id'];
$className = trim((string)($_GET['class_name'] ?? ''));
$studentId = (int)($_GET['student_id'] ?? 0);

$sql = "SELECT * FROM students WHERE school_id = ? AND is_active = 1";
$params = [$schoolId];
if ($studentId > 0) {
    $sql .= " AND id = ?";
    $params[] = $studentId;
} elseif ($className !== '') {
    $sql .= " AND class_name = ?";
    $params[] = $className;
}
$sql .= " ORDER BY class_name ASC, name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

$logoUrl = Tenant::brandingAssetUrl($saas['logo_path'] ?? '', 'assets/img/default-logo.svg');
?>
<body class="bg-slate-100 p-4 sm:p-8">
  <div class="no-print max-w-5xl mx-auto mb-6 bg-white rounded-2xl p-4 shadow-sm border border-slate-200 flex flex-wrap items-center justify-between gap-3">
    <div>
      <h1 class="font-extrabold text-slate-800 text-lg">Cetak Kartu Absen QR Code Siswa</h1>
      <p class="text-xs text-slate-500"><?= Security::e($school['name']) ?> • Total <?= count($students) ?> kartu siap cetak</p>
    </div>
    <div class="flex gap-2">
      <button type="button" onclick="window.close()" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600">Tutup</button>
      <button type="button" onclick="window.print()" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-extrabold shadow">
        <i class="fa-solid fa-print mr-1.5"></i> Cetak Sekarang (A4)
      </button>
    </div>
  </div>

  <div class="max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-5 print-card-grid">
    <?php foreach ($students as $st): ?>
      <div class="student-qr-card bg-white rounded-2xl overflow-hidden shadow-md border-2 border-blue-700 flex flex-col">
        <div class="bg-gradient-to-r from-blue-900 via-blue-800 to-blue-600 text-white px-4 py-3 flex items-center justify-between gap-2">
          <div class="flex items-center gap-2.5 min-w-0">
            <div class="bg-white rounded-lg p-1 shrink-0">
              <img src="<?= Security::e($logoUrl) ?>" alt="Logo" class="h-6 w-auto">
            </div>
            <div class="min-w-0">
              <div class="text-xs font-extrabold uppercase tracking-wide truncate"><?= Security::e($school['name']) ?></div>
              <div class="text-[10px] text-blue-200">KARTU PRESENSI PELAJAR</div>
            </div>
          </div>
          <span class="px-2.5 py-1 rounded-lg bg-white/20 text-xs font-extrabold shrink-0"><?= Security::e($st['class_name']) ?></span>
        </div>

        <div class="p-4 flex items-center gap-4">
          <div class="qr-box p-2 bg-white border border-blue-200 rounded-xl shrink-0" data-token="<?= Security::e($st['qr_token']) ?>"></div>
          <div class="min-w-0 flex-1 space-y-1">
            <div class="text-sm font-extrabold text-slate-900 leading-tight"><?= Security::e($st['name']) ?></div>
            <div class="text-xs text-slate-600">NISN: <strong class="font-mono"><?= Security::e($st['nisn']) ?></strong></div>
            <div class="text-xs text-slate-600">Kelas: <strong><?= Security::e($st['class_name']) ?></strong> • <?= $st['gender'] === 'P' ? 'Perempuan' : 'Laki-laki' ?></div>
            <div class="pt-1.5 mt-1.5 border-t border-slate-100 text-[10px] font-mono text-blue-700 font-bold"><?= Security::e($st['qr_token']) ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <script>
    document.querySelectorAll('.qr-box').forEach(function (el) {
      new QRCode(el, {
        text: el.getAttribute('data-token'),
        width: 108,
        height: 108,
        colorDark: '#1e3a8a',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M
      });
    });
  </script>
</body>
</html>
