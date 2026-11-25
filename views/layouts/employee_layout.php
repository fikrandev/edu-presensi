<?php
require __DIR__ . '/head.php';
$faviconUrl = Tenant::brandingAssetUrl($saas['favicon_path'] ?? '', 'assets/img/default-favicon.svg');
$slug = $school['slug'];
$activeMenu = $activeMenu ?? 'dashboard';
$menuItems = [
    ['key' => 'dashboard',   'label' => 'Beranda',   'icon' => 'fa-house',        'url' => Tenant::schoolLocalUrl($slug, 'pegawai/dashboard')],
    ['key' => 'attendance',  'label' => 'Absen GPS', 'icon' => 'fa-location-dot', 'url' => Tenant::schoolLocalUrl($slug, 'pegawai/attendance')],
    ['key' => 'corrections', 'label' => 'Perbaikan', 'icon' => 'fa-file-pen',     'url' => Tenant::schoolLocalUrl($slug, 'pegawai/corrections')],
    ['key' => 'account',     'label' => 'Akun',      'icon' => 'fa-user-gear',    'url' => Tenant::schoolLocalUrl($slug, 'pegawai/account')],
];
?>
<body class="bg-slate-100 text-slate-800 min-h-full antialiased">
  <div class="max-w-md mx-auto min-h-screen bg-[#F7F9FC] shadow-2xl flex flex-col relative pb-24">
    <!-- Clean Vibrant Blue PWA Mobile Top Header -->
    <header class="bg-gradient-to-r from-[#2563EB] to-[#1E5BF0] text-white px-5 pt-5 pb-6 rounded-b-[28px] shadow-md">
      <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
          <div class="w-10 h-10 rounded-2xl bg-white p-1.5 shadow-sm shrink-0 flex items-center justify-center">
            <img src="<?= Security::e($faviconUrl) ?>" alt="App Icon" class="w-full h-full object-contain">
          </div>
          <div class="min-w-0">
            <div class="text-[11px] text-blue-100 font-semibold truncate"><?= Security::e($school['name']) ?></div>
            <div class="text-base font-extrabold truncate"><?= Security::e($currentUser['name'] ?? 'Pegawai') ?></div>
          </div>
        </div>
        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-white/15 text-[10px] font-bold shrink-0" title="Sesi PWA aman aktif selamanya">
          <i class="fa-solid fa-shield-halved text-emerald-300"></i> PWA Aktif
        </span>
      </div>
    </header>

    <!-- PWA Install Prompt Banner -->
    <div id="pwaInstallBanner" class="hidden mx-4 -mt-3 mb-2 bg-white border border-blue-100 rounded-2xl p-3 shadow-sm flex items-center gap-3 z-10">
      <img src="<?= Security::e($faviconUrl) ?>" alt="Icon" class="w-10 h-10 rounded-xl border border-blue-50 p-1 shrink-0">
      <div class="flex-1 min-w-0">
        <div class="text-xs font-extrabold text-slate-800">Install Aplikasi Absensi</div>
        <div class="text-[11px] text-slate-400">Akses cepat &amp; login selamanya dari Layar Utama HP.</div>
      </div>
      <button id="pwaInstallBtn" type="button" class="px-3 py-1.5 rounded-xl bg-[#1E5BF0] text-white text-xs font-bold shrink-0">Install</button>
      <button id="pwaDismissBtn" type="button" class="text-slate-400 hover:text-slate-600 px-1"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <!-- Main Mobile Content -->
    <main class="flex-1 px-4 py-4">
      <?= $content ?>
    </main>

    <!-- Clean White Mobile PWA Bottom Navigation -->
    <nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md z-30 bg-white border-t border-slate-200 mobile-bottom-nav rounded-t-2xl">
      <div class="grid grid-cols-4">
        <?php foreach ($menuItems as $m): $isAct = $activeMenu === $m['key']; ?>
          <a href="<?= Security::e($m['url']) ?>" class="flex flex-col items-center justify-center py-2.5 transition <?= $isAct ? 'text-[#1E5BF0]' : 'text-slate-400 hover:text-[#1E5BF0]' ?>">
            <div class="w-10 h-7 rounded-xl flex items-center justify-center <?= $isAct ? 'bg-[#EEF4FF] text-[#1E5BF0]' : '' ?>">
              <i class="fa-solid <?= $m['icon'] ?> text-base"></i>
            </div>
            <span class="text-[11px] font-bold mt-0.5"><?= Security::e($m['label']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </nav>
  </div>

  <?php require __DIR__ . '/modals.php'; ?>
</body>
</html>
