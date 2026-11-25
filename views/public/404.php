<?php require dirname(__DIR__) . '/layouts/head.php'; ?>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
  <div class="bg-white rounded-3xl shadow-xl max-w-md w-full p-8 text-center border border-blue-100">
    <div class="w-16 h-16 rounded-2xl bg-blue-100 text-blue-600 mx-auto mb-4 flex items-center justify-center text-3xl">
      <i class="fa-solid fa-compass"></i>
    </div>
    <h1 class="text-2xl font-extrabold text-slate-800">404 — Halaman Tidak Ditemukan</h1>
    <p class="text-sm text-slate-500 mt-2">
      <?php if (!empty($requestedSlug)): ?>
        Sekolah dengan alamat subfolder <code>/<?= Security::e($requestedSlug) ?></code> belum terdaftar.
      <?php else: ?>
        Halaman yang Anda cari tidak tersedia atau telah dipindahkan.
      <?php endif; ?>
    </p>
    <a href="<?= app_url('/') ?>" class="mt-6 inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm transition">
      <i class="fa-solid fa-arrow-left"></i> Kembali ke Beranda
    </a>
  </div>
  <?php require dirname(__DIR__) . '/layouts/modals.php'; ?>
</body>
</html>
