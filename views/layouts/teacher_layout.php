<?php
require __DIR__ . '/head.php';
$logoUrl = Tenant::brandingAssetUrl($saas['logo_path'] ?? '', 'assets/img/default-logo.svg');
$slug = $school['slug'];
$activeMenu = $activeMenu ?? 'dashboard';
$menuItems = [
    ['key' => 'dashboard', 'label' => 'Sesi Absen',      'icon' => 'fa-calendar-plus', 'url' => Tenant::schoolLocalUrl($slug, 'guru/dashboard')],
    ['key' => 'scan',      'label' => 'Scan QR',         'icon' => 'fa-qrcode',        'url' => Tenant::schoolLocalUrl($slug, 'guru/scan')],
    ['key' => 'reports',   'label' => 'Rekap & Laporan', 'icon' => 'fa-file-lines',    'url' => Tenant::schoolLocalUrl($slug, 'guru/reports')],
];
$todayIdLabel = day_name_id((int)date('N')) . ', ' . format_date_id(date('Y-m-d'));
?>
<body class="bg-[#F7F9FC] text-slate-800 min-h-full pb-20 lg:pb-0 antialiased">
  <header class="sticky top-0 z-20 bg-white border-b border-slate-200/80">
    <div class="w-full px-4 lg:px-6 py-3 flex items-center justify-between gap-3">
      <div class="flex items-center gap-3 min-w-0">
        <img src="<?= Security::e($logoUrl) ?>" alt="Logo" class="h-8 w-auto shrink-0">
        <div class="hidden sm:block border-l border-slate-200 pl-3 min-w-0">
          <div class="text-xs font-extrabold text-slate-800 truncate"><?= Security::e($school['name']) ?></div>
          <div class="text-[11px] text-slate-400 truncate">Portal Guru • <?= Security::e($currentUser['name'] ?? '') ?></div>
        </div>
      </div>

      <nav class="hidden lg:flex items-center gap-1">
        <?php foreach ($menuItems as $m): $isAct = $activeMenu === $m['key']; ?>
          <a href="<?= Security::e($m['url']) ?>" class="px-4 py-2 rounded-xl text-xs font-bold transition <?= $isAct ? 'bg-[#EEF4FF] text-[#1E5BF0]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
            <i class="fa-solid <?= $m['icon'] ?> mr-1.5"></i><?= Security::e($m['label']) ?>
          </a>
        <?php endforeach; ?>
      </nav>

      <div class="flex items-center gap-2.5">
        <span class="hidden xl:inline text-xs text-slate-400"><?= Security::e($todayIdLabel) ?></span>
        <?php if (($currentUser['role'] ?? '') === 'school_admin'): ?>
          <a href="<?= Tenant::schoolLocalUrl($slug, 'admin/dashboard') ?>" class="px-3 py-1.5 rounded-full bg-[#EEF4FF] text-[#1E5BF0] hover:bg-blue-100 text-xs font-bold">
            <i class="fa-solid fa-arrow-left mr-1"></i>Admin
          </a>
        <?php endif; ?>
        <a href="<?= Tenant::schoolLocalUrl($slug, 'logout') ?>" class="w-9 h-9 rounded-xl bg-slate-50 hover:bg-red-50 text-slate-500 hover:text-red-600 flex items-center justify-center transition" title="Keluar">
          <i class="fa-solid fa-arrow-right-from-bracket text-sm"></i>
        </a>
      </div>
    </div>
  </header>

  <main class="w-full p-4 lg:p-6">
    <?= $content ?>
  </main>

  <!-- Mobile Bottom Nav for Guru -->
  <nav class="lg:hidden fixed bottom-0 inset-x-0 z-30 bg-white border-t border-slate-200 mobile-bottom-nav">
    <div class="grid grid-cols-3 max-w-md mx-auto">
      <?php foreach ($menuItems as $m): $isAct = $activeMenu === $m['key']; ?>
        <a href="<?= Security::e($m['url']) ?>" class="flex flex-col items-center justify-center py-2.5 <?= $isAct ? 'text-[#1E5BF0]' : 'text-slate-400' ?>">
          <div class="w-10 h-7 rounded-xl flex items-center justify-center <?= $isAct ? 'bg-[#EEF4FF]' : '' ?>">
            <i class="fa-solid <?= $m['icon'] ?>"></i>
          </div>
          <span class="text-[11px] font-bold mt-0.5"><?= Security::e($m['label']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </nav>

  <?php require __DIR__ . '/modals.php'; ?>
</body>
</html>
