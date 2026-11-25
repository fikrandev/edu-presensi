<?php require dirname(__DIR__) . '/layouts/head.php'; ?>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
  <div class="bg-white rounded-3xl shadow-xl max-w-md w-full p-8 text-center border border-amber-200">
    <div class="w-16 h-16 rounded-2xl bg-amber-100 text-amber-600 mx-auto mb-4 flex items-center justify-center text-3xl">
      <i class="fa-solid fa-clock-rotate-left"></i>
    </div>
    <h1 class="text-xl font-extrabold text-slate-800">Masa Aktif Langganan Berakhir</h1>
    <p class="text-sm text-slate-600 mt-2 leading-relaxed">
      Layanan portal absensi untuk <strong><?= Security::e($school['name'] ?? 'Sekolah') ?></strong> saat ini berstatus
      <span class="font-bold text-amber-700 uppercase"><?= Security::e($school['subscription_status'] ?? 'expired') ?></span>
      (berakhir <?= format_date_id($school['subscription_end'] ?? null) ?>).
    </p>
    <p class="text-xs text-slate-500 mt-3">Silakan hubungi tim Superadmin <?= Security::e($saas['app_name']) ?> untuk melakukan perpanjangan paket langganan.</p>
    <a href="<?= app_url('/') ?>" class="mt-6 inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-blue-600 text-white font-bold text-sm hover:bg-blue-700 transition">
      <i class="fa-solid fa-house"></i> Kembali ke Beranda
    </a>
  </div>
  <?php require dirname(__DIR__) . '/layouts/modals.php'; ?>
</body>
</html>
