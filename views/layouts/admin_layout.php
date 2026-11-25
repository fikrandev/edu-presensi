<?php
require __DIR__ . '/head.php';
$logoUrl = Tenant::brandingAssetUrl($saas['logo_path'] ?? '', 'assets/img/default-logo.svg');
$slug = $school['slug'];
$activeMenu = $activeMenu ?? 'dashboard';

$menuGroups = [
    'UTAMA' => [
        ['key' => 'dashboard', 'label' => 'Dashboard',       'icon' => 'fa-house',           'url' => Tenant::schoolLocalUrl($slug, 'admin/dashboard'), 'show' => true],
    ],
    'DATA MASTER' => [
        ['key' => 'offices',   'label' => 'Kelola Kantor',   'icon' => 'fa-building',        'url' => Tenant::schoolLocalUrl($slug, 'admin/offices'),   'show' => true],
        ['key' => 'shifts',    'label' => 'Kelola Shift',    'icon' => 'fa-clock',           'url' => Tenant::schoolLocalUrl($slug, 'admin/shifts'),    'show' => true],
        ['key' => 'employees', 'label' => 'Data Pegawai',    'icon' => 'fa-users',           'url' => Tenant::schoolLocalUrl($slug, 'admin/employees'), 'show' => true],
        ['key' => 'students',  'label' => 'Data Siswa & QR', 'icon' => 'fa-user-graduate',   'url' => Tenant::schoolLocalUrl($slug, 'admin/students'),  'show' => Tenant::hasFeature($school, 'student_management')],
        ['key' => 'teachers',  'label' => 'Akun Guru',       'icon' => 'fa-chalkboard-user', 'url' => Tenant::schoolLocalUrl($slug, 'admin/teachers'),  'show' => Tenant::hasFeature($school, 'teacher_qr_scan')],
    ],
    'ABSENSI & LAPORAN' => [
        ['key' => 'reports',   'label' => 'Laporan Absensi', 'icon' => 'fa-chart-column',    'url' => Tenant::schoolLocalUrl($slug, 'admin/reports'),   'show' => Tenant::hasFeature($school, 'employee_reports')],
        ['key' => 'guru_link', 'label' => 'Portal Scan Guru','icon' => 'fa-qrcode',          'url' => Tenant::schoolLocalUrl($slug, 'guru/dashboard'),  'show' => Tenant::hasFeature($school, 'teacher_qr_scan')],
    ],
    'PENGATURAN' => [
        ['key' => 'settings',  'label' => 'Pengaturan',      'icon' => 'fa-gear',            'url' => Tenant::schoolLocalUrl($slug, 'admin/settings'),  'show' => true],
    ],
];
$todayIdLabel = day_name_id((int)date('N')) . ', ' . format_date_id(date('Y-m-d'));
?>
<body class="bg-[#F7F9FC] text-slate-800 min-h-full antialiased">
  <div class="flex min-h-screen">
    <!-- Minimalist Pure White Sidebar -->
    <aside id="adminSidebar" class="fixed inset-y-0 left-0 z-40 w-[248px] bg-white border-r border-slate-200/80 transform -translate-x-full lg:translate-x-0 transition-transform duration-200 flex flex-col">
      <div class="px-6 py-5 flex flex-col items-center border-b border-slate-100">
        <img src="<?= Security::e($logoUrl) ?>" alt="Logo" class="h-9 w-auto object-contain max-w-[175px]">
        <div class="text-[11px] font-bold text-slate-500 mt-1.5 truncate max-w-full"><?= Security::e($school['name']) ?></div>
      </div>

      <nav class="flex-1 px-3 py-4 overflow-y-auto space-y-5">
        <?php foreach ($menuGroups as $groupLabel => $items):
          $visibleItems = array_filter($items, static fn($i) => $i['show']);
          if (empty($visibleItems)) continue;
        ?>
          <div>
            <?php if ($groupLabel !== 'UTAMA'): ?>
              <div class="px-3 mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400"><?= Security::e($groupLabel) ?></div>
            <?php endif; ?>
            <div class="space-y-1">
              <?php foreach ($visibleItems as $m): $isAct = $activeMenu === $m['key']; ?>
                <a href="<?= Security::e($m['url']) ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13px] transition relative <?= $isAct ? 'bg-[#EEF4FF] text-[#1E5BF0] font-bold' : 'text-slate-600 font-medium hover:bg-slate-50 hover:text-slate-900' ?>">
                  <?php if ($isAct): ?>
                    <span class="absolute right-0 top-2 bottom-2 w-1 rounded-l-full bg-[#1E5BF0]"></span>
                  <?php endif; ?>
                  <i class="fa-solid <?= $m['icon'] ?> w-4 text-center text-sm <?= $isAct ? 'text-[#1E5BF0]' : 'text-slate-400' ?>"></i>
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
            <?= strtoupper(substr($currentUser['name'] ?? 'A', 0, 1)) ?>
          </div>
          <div class="min-w-0">
            <div class="text-xs font-bold text-slate-800 truncate"><?= Security::e($currentUser['name'] ?? 'Admin') ?></div>
            <div class="text-[11px] font-semibold text-[#1E5BF0] truncate"><?= Security::e($school['package_name'] ?? 'Aktif') ?></div>
          </div>
        </div>
        <a href="<?= Tenant::schoolLocalUrl($slug, 'logout') ?>" title="Keluar" class="w-8 h-8 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 flex items-center justify-center transition shrink-0">
          <i class="fa-solid fa-arrow-right-from-bracket text-sm"></i>
        </a>
      </div>
    </aside>

    <div id="adminBackdrop" class="fixed inset-0 bg-slate-900/40 z-30 hidden lg:hidden" onclick="$('#adminSidebar').addClass('-translate-x-full');$(this).addClass('hidden');"></div>

    <!-- Main Area -->
    <div class="flex-1 lg:pl-[248px] flex flex-col min-w-0">
      <header class="sticky top-0 z-20 bg-white border-b border-slate-200/70 px-4 lg:px-6 py-3.5 flex items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
          <button type="button" onclick="$('#adminSidebar').removeClass('-translate-x-full');$('#adminBackdrop').removeClass('hidden');" class="lg:hidden w-9 h-9 rounded-xl bg-[#EEF4FF] text-[#1E5BF0] flex items-center justify-center">
            <i class="fa-solid fa-bars"></i>
          </button>
          <h1 class="text-base lg:text-lg font-bold text-slate-800 truncate"><?= Security::e($pageTitle ?? 'Dashboard') ?></h1>
        </div>
        <div class="flex items-center gap-3">
          <span class="hidden md:inline text-xs font-medium text-slate-400"><?= Security::e($todayIdLabel) ?></span>
          <span class="inline-flex items-center px-3.5 py-1.5 rounded-full bg-[#1E5BF0] text-white text-[11px] font-bold shadow-sm truncate max-w-[180px]">
            <?= Security::e($school['package_name'] ?? 'Aktif') ?>
          </span>
        </div>
      </header>

      <main class="flex-1 p-4 lg:p-6 w-full">
        <?= $content ?>
      </main>
    </div>
  </div>
  <?php require __DIR__ . '/modals.php'; ?>
</body>
</html>
