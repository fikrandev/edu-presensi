<?php
require dirname(__DIR__) . '/layouts/head.php';
$logoUrl = Tenant::brandingAssetUrl($saas['logo_path'] ?? '', 'assets/img/default-logo.svg');

$pkgTabs = [];
if (!empty($packages)) {
    foreach ($packages as $p) {
        $featKeys = Tenant::decodeFeatures($p['features_json'] ?? null);
        $featLabels = [];
        foreach ($featKeys as $fk) {
            if (isset(SAAS_FEATURES[$fk]['label'])) {
                $featLabels[] = SAAS_FEATURES[$fk]['label'];
            }
        }
        $pkgTabs[] = [
            'name'     => $p['name'],
            'short'    => explode(' ', trim($p['name']))[0],
            'price'    => format_rupiah($p['price_monthly']) . ' / bln',
            'features' => array_slice($featLabels, 0, 4),
        ];
    }
}
if (empty($pkgTabs)) {
    $pkgTabs = [
        [
            'name'     => 'Paket Starter',
            'short'    => 'Starter',
            'price'    => 'Rp 150.000 / bln',
            'features' => ['Absensi Pegawai GPS & PWA Mobile', 'Import Data Excel (.xlsx)', 'Laporan Matrix & Rekap Persentase'],
        ],
        [
            'name'     => 'Paket Professional',
            'short'    => 'Pro',
            'price'    => 'Rp 350.000 / bln',
            'features' => ['Custom Shift & Koordinat per Hari per Orang', 'Data Siswa & Cetak Kartu QR Code', 'Portal Guru & Scan QR Absensi Siswa'],
        ],
        [
            'name'     => 'Paket Enterprise',
            'short'    => 'Enterprise',
            'price'    => 'Rp 650.000 / bln',
            'features' => ['Semua Fitur Pegawai & Siswa Lengkap', 'Pengingat Tagihan WA Gateway H-3 & H-0', 'Kuota Besar Yayasan'],
        ],
    ];
}
?>
<body class="bg-white text-slate-800 min-h-screen antialiased">
  <div class="min-h-screen grid lg:grid-cols-2">
    <!-- LEFT WHITE PANEL -->
    <div class="bg-white flex flex-col justify-center px-8 sm:px-14 lg:px-20 py-12 order-2 lg:order-1">
      <div class="max-w-lg w-full mx-auto lg:mx-0">
        <div class="flex items-center justify-between gap-3 mb-10">
          <img src="<?= Security::e($logoUrl) ?>" alt="Logo" class="h-10 w-auto object-contain">
          <?php if (!$dbReady): ?>
            <a href="<?= app_url('/install.php') ?>" class="px-3 py-1.5 rounded-full bg-amber-100 text-amber-800 text-xs font-bold">
              <i class="fa-solid fa-database mr-1"></i> Setup Database
            </a>
          <?php endif; ?>
        </div>

        <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
          <?= Security::e($saas['app_name']) ?>
        </h1>
        <p class="text-slate-500 text-base sm:text-lg mt-3 leading-relaxed">
          Transformasi digital ekosistem absensi pegawai &amp; siswa sekolah menjadi lebih mudah, akurat, dan modern.
        </p>

        <div class="mt-8 flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-full bg-blue-50 text-[#1E5BF0] flex items-center justify-center shrink-0">
            <i class="fa-solid fa-shield-halved text-base"></i>
          </div>
          <div>
            <div class="text-sm font-bold text-slate-800">Multi-Tenant Subfolder &amp; Keamanan Tinggi</div>
            <div class="text-xs text-slate-400">Setiap sekolah mendapat URL khusus seperti <code><?= Security::e($saas['display_domain']) ?>/bina-insan-palu</code>.</div>
          </div>
        </div>

        <!-- Package Tabs -->
        <div class="mt-10">
          <div class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400 mb-3">
            PILIH PAKET UNGGULAN:
          </div>
          <div class="flex flex-wrap gap-2 mb-4">
            <?php foreach ($pkgTabs as $idx => $pt): ?>
              <button type="button" data-idx="<?= $idx ?>" class="lp-pkg-btn px-4 py-1.5 rounded-full text-xs font-bold border transition <?= $idx === 0 ? 'bg-blue-50 border-blue-200 text-[#1E5BF0]' : 'bg-slate-50 border-slate-100 text-slate-500 hover:bg-slate-100' ?>">
                <?= Security::e($pt['short']) ?>
              </button>
            <?php endforeach; ?>
          </div>
          <div class="rounded-2xl border border-blue-100/90 bg-[#F8FBFF] p-5">
            <div class="flex items-center justify-between gap-2 mb-3">
              <div id="lpPkgTitle" class="text-sm font-extrabold text-[#1E40AF]"><?= Security::e($pkgTabs[0]['name']) ?></div>
              <span id="lpPkgPrice" class="text-xs font-bold text-[#1E5BF0]"><?= Security::e($pkgTabs[0]['price']) ?></span>
            </div>
            <ul id="lpPkgFeats" class="space-y-2 text-xs text-[#2563EB] font-medium">
              <?php foreach ($pkgTabs[0]['features'] as $f): ?>
                <li class="flex items-center gap-2">
                  <i class="fa-solid fa-check text-[11px]"></i>
                  <span><?= Security::e($f) ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- RIGHT VIBRANT ROYAL BLUE PANEL: Portal Access Card -->
    <div class="relative overflow-hidden bg-gradient-to-br from-[#2563EB] via-[#1E5BF0] to-[#1D4ED8] flex items-center justify-center p-6 sm:p-10 order-1 lg:order-2">
      <div class="pointer-events-none absolute -bottom-24 -left-24 w-80 h-80 rounded-full bg-white/10 blur-3xl"></div>
      <div class="pointer-events-none absolute -top-24 -right-24 w-72 h-72 rounded-full bg-sky-300/15 blur-3xl"></div>

      <div class="relative z-10 bg-white rounded-[28px] shadow-[0_25px_60px_-15px_rgba(15,23,42,0.3)] w-full max-w-[420px] p-8 sm:p-10">
        <div class="mb-6">
          <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Pilih Portal Masuk</h2>
          <p class="text-xs text-slate-400 mt-1">Masuk ke portal sekolah Anda atau Console Superadmin</p>
        </div>

        <?php if (!$dbReady): ?>
          <a href="<?= app_url('/install.php') ?>" class="block text-center w-full py-3.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs uppercase tracking-wider shadow mb-5">
            <i class="fa-solid fa-database mr-1.5"></i> Jalankan Installer Database Dulu
          </a>
        <?php endif; ?>

        <!-- Quick Jump by School Slug -->
        <form id="slugJumpForm" class="space-y-4 mb-6">
          <div>
            <label class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 mb-2">KODE SUBFOLDER SEKOLAH</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"><i class="fa-solid fa-school"></i></span>
              <input type="text" id="jumpSlugInput" value="bina-insan-palu" placeholder="contoh: bina-insan-palu" class="w-full pl-11 pr-4 py-3.5 rounded-xl bg-[#EDF3FF] border border-transparent focus:border-[#2563EB] focus:bg-white outline-none text-sm font-semibold text-slate-800 transition">
            </div>
          </div>
          <button type="submit" class="w-full py-3.5 rounded-xl bg-[#1E5BF0] hover:bg-[#194ED3] text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-blue-600/30 transition flex items-center justify-center gap-2">
            <span>BUKA PORTAL SEKOLAH</span>
            <i class="fa-solid fa-arrow-right text-[11px]"></i>
          </button>
        </form>

        <?php if (!empty($demoSchools)): ?>
          <div class="border-t border-slate-100 pt-5 space-y-2 mb-5">
            <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">SEKOLAH TERDAFTAR</div>
            <?php foreach ($demoSchools as $ds): ?>
              <a href="<?= Tenant::schoolLocalUrl($ds['slug']) ?>" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-slate-50 hover:bg-blue-50 text-xs transition">
                <div class="min-w-0">
                  <div class="font-bold text-slate-800 truncate"><?= Security::e($ds['name']) ?></div>
                  <div class="text-[11px] text-[#1E5BF0] truncate">/<?= Security::e($ds['slug']) ?></div>
                </div>
                <i class="fa-solid fa-chevron-right text-slate-300 text-[10px]"></i>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <a href="<?= app_url('/superadmin/login') ?>" class="block text-center w-full py-3 rounded-xl border border-slate-200 hover:border-blue-300 hover:bg-blue-50/50 text-slate-700 font-bold text-xs transition">
          <i class="fa-solid fa-shield-halved text-[#1E5BF0] mr-1.5"></i> Login Superadmin SaaS
        </a>

        <div class="mt-6 text-center text-[10px] font-bold uppercase tracking-widest text-slate-400">
          &copy; <?= date('Y') ?> <?= Security::e(strtoupper($saas['app_name'])) ?> SAAS PLATFORM
        </div>
      </div>
    </div>
  </div>

  <?php require dirname(__DIR__) . '/layouts/modals.php'; ?>
  <script>
    const lpPkgData = <?= json_encode($pkgTabs, JSON_UNESCAPED_UNICODE) ?>;
    $('.lp-pkg-btn').on('click', function () {
      const idx = parseInt($(this).data('idx'), 10);
      const item = lpPkgData[idx];
      if (!item) return;
      $('.lp-pkg-btn').removeClass('bg-blue-50 border-blue-200 text-[#1E5BF0]').addClass('bg-slate-50 border-slate-100 text-slate-500');
      $(this).removeClass('bg-slate-50 border-slate-100 text-slate-500').addClass('bg-blue-50 border-blue-200 text-[#1E5BF0]');
      $('#lpPkgTitle').text(item.name);
      $('#lpPkgPrice').text(item.price);
      $('#lpPkgFeats').html((item.features || []).map(f => `<li class="flex items-center gap-2"><i class="fa-solid fa-check text-[11px]"></i><span>${ApiClient.e(f)}</span></li>`).join(''));
    });

    $('#slugJumpForm').on('submit', function (e) {
      e.preventDefault();
      const slug = $('#jumpSlugInput').val().trim().toLowerCase().replace(/[^a-z0-9-]/g, '');
      if (!slug) return AppModal.showError('Masukkan kode subfolder sekolah terlebih dahulu.');
      window.location.href = '<?= rtrim(app_url('/'), '/') ?>/' + slug;
    });
  </script>
</body>
</html>
