<?php
require __DIR__ . '/head.php';
$logoUrl = Tenant::brandingAssetUrl($saas['logo_path'] ?? '', 'assets/img/default-logo.svg');
$activeMenu = $activeMenu ?? 'dashboard';

$menuGroups = [
    'UTAMA' => [
        ['key' => 'dashboard',  'label' => 'Dashboard',      'short' => 'Home',     'icon' => 'fa-house',           'url' => app_url('/superadmin/dashboard')],
    ],
    'PELANGGAN SAAS' => [
        ['key' => 'schools',    'label' => 'Daftar Sekolah', 'short' => 'Sekolah',  'icon' => 'fa-school',          'url' => app_url('/superadmin/schools')],
        ['key' => 'packages',   'label' => 'Daftar Paket',   'short' => 'Paket',    'icon' => 'fa-cubes',           'url' => app_url('/superadmin/packages')],
    ],
    'KEUANGAN' => [
        ['key' => 'finance',    'label' => 'Keuangan & Omset','short' => 'Keuangan','icon' => 'fa-wallet',          'url' => app_url('/superadmin/finance')],
    ],
    'PREMIUM & PENGATURAN' => [
        ['key' => 'wa-gateway', 'label' => 'WA Gateway',     'short' => 'WA',       'icon' => 'fa-brands fa-whatsapp','url' => app_url('/superadmin/wa-gateway')],
        ['key' => 'branding',   'label' => 'Logo & Favicon', 'short' => 'Brand',    'icon' => 'fa-sliders',         'url' => app_url('/superadmin/branding')],
    ],
];

$flatMenu = [];
foreach ($menuGroups as $grp) {
    foreach ($grp as $it) {
        $flatMenu[] = $it;
    }
}
$todayIdLabel = day_name_id((int)date('N')) . ', ' . format_date_id(date('Y-m-d'));
?>
<body class="bg-[#F7F9FC] text-slate-800 min-h-full antialiased">
  <div class="flex min-h-screen">
    <!-- Minimalist Pure White Desktop Sidebar -->
    <aside class="hidden lg:flex lg:flex-col lg:w-[248px] bg-white border-r border-slate-200/80 fixed inset-y-0 left-0 z-30">
      <div class="px-6 py-5 flex items-center justify-center border-b border-slate-100">
        <a href="<?= app_url('/superadmin/dashboard') ?>">
          <img src="<?= Security::e($logoUrl) ?>" alt="Logo" class="h-9 w-auto object-contain max-w-[175px]">
        </a>
      </div>

      <nav class="flex-1 px-3 py-4 overflow-y-auto space-y-5">
        <?php foreach ($menuGroups as $groupLabel => $items): ?>
          <div>
            <?php if ($groupLabel !== 'UTAMA'): ?>
              <div class="px-3 mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400"><?= Security::e($groupLabel) ?></div>
            <?php endif; ?>
            <div class="space-y-1">
              <?php foreach ($items as $m):
                $isAct = $activeMenu === $m['key'];
                $iconClass = str_contains($m['icon'], 'fa-brands') ? $m['icon'] : 'fa-solid ' . $m['icon'];
              ?>
                <a href="<?= Security::e($m['url']) ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13px] transition relative <?= $isAct ? 'bg-[#EEF4FF] text-[#1E5BF0] font-bold' : 'text-slate-600 font-medium hover:bg-slate-50 hover:text-slate-900' ?>">
                  <?php if ($isAct): ?>
                    <span class="absolute right-0 top-2 bottom-2 w-1 rounded-l-full bg-[#1E5BF0]"></span>
                  <?php endif; ?>
                  <i class="<?= $iconClass ?> w-4 text-center text-sm <?= $isAct ? 'text-[#1E5BF0]' : 'text-slate-400' ?>"></i>
                  <span class="truncate"><?= Security::e($m['label']) ?></span>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </nav>

      <!-- Clean Sidebar User Footer -->
      <div class="p-4 border-t border-slate-100 flex items-center justify-between gap-2">
        <div class="flex items-center gap-2.5 min-w-0">
          <div class="w-9 h-9 rounded-full bg-[#EEF4FF] text-[#1E5BF0] flex items-center justify-center font-extrabold text-sm shrink-0">
            <?= strtoupper(substr($currentUser['name'] ?? 'S', 0, 1)) ?>
          </div>
          <div class="min-w-0">
            <div class="text-xs font-bold text-slate-800 truncate"><?= Security::e($currentUser['name'] ?? 'Superadmin') ?></div>
            <div class="text-[11px] font-semibold text-[#1E5BF0] truncate">Superadmin SaaS</div>
          </div>
        </div>
        <a href="<?= app_url('/superadmin/logout') ?>" title="Keluar" class="w-8 h-8 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 flex items-center justify-center transition shrink-0">
          <i class="fa-solid fa-arrow-right-from-bracket text-sm"></i>
        </a>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 lg:pl-[248px] flex flex-col min-w-0 pb-24 lg:pb-8">
      <!-- Clean White Top Header -->
      <header class="sticky top-0 z-20 bg-white border-b border-slate-200/70 px-4 lg:px-6 py-3.5 flex items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
          <img src="<?= Security::e($logoUrl) ?>" alt="Logo" class="h-7 w-auto lg:hidden">
          <h1 class="text-base lg:text-lg font-bold text-slate-800 truncate"><?= Security::e($pageTitle ?? 'Dashboard') ?></h1>
        </div>
        <div class="flex items-center gap-3">
          <span class="hidden md:inline text-xs font-medium text-slate-400"><?= Security::e($todayIdLabel) ?></span>
          <span class="hidden sm:inline-flex items-center px-3.5 py-1.5 rounded-full bg-[#1E5BF0] text-white text-[11px] font-bold shadow-sm">
            Superadmin SaaS
          </span>
          <a href="<?= app_url('/superadmin/logout') ?>" class="lg:hidden inline-flex items-center justify-center w-9 h-9 rounded-xl bg-red-50 text-red-600 text-xs">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
          </a>
        </div>
      </header>

      <main class="flex-1 p-4 lg:p-6 w-full">
        <?= $content ?>
      </main>
    </div>
  </div>

  <!-- RESPONSIVE MOBILE BOTTOM NAVIGATION (Superadmin) -->
  <nav class="lg:hidden fixed bottom-0 inset-x-0 z-30 bg-white border-t border-slate-200 mobile-bottom-nav">
    <div class="grid grid-cols-6 max-w-lg mx-auto">
      <?php foreach ($flatMenu as $m):
        $isAct = $activeMenu === $m['key'];
        $iconClass = str_contains($m['icon'], 'fa-brands') ? $m['icon'] : 'fa-solid ' . $m['icon'];
      ?>
        <a href="<?= Security::e($m['url']) ?>" class="flex flex-col items-center justify-center py-2.5 px-1 transition <?= $isAct ? 'text-[#1E5BF0]' : 'text-slate-400 hover:text-[#1E5BF0]' ?>">
          <div class="w-8 h-7 rounded-xl flex items-center justify-center <?= $isAct ? 'bg-[#EEF4FF] text-[#1E5BF0]' : '' ?>">
            <i class="<?= $iconClass ?> text-sm"></i>
          </div>
          <span class="text-[10px] font-bold mt-0.5 truncate"><?= Security::e($m['short']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </nav>

  <?php require __DIR__ . '/modals.php'; ?>
</body>
</html>
