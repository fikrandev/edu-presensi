<?php
require dirname(__DIR__) . '/layouts/head.php';
$logoUrl    = Tenant::brandingAssetUrl($saas['logo_path'] ?? '', 'assets/img/default-logo.svg');
$faviconUrl = Tenant::brandingAssetUrl($saas['favicon_path'] ?? '', 'assets/img/default-favicon.svg');

$roleTabs = [
    [
        'key'      => 'admin',
        'short'    => 'Admin Sekolah',
        'title'    => 'Portal Admin Sekolah',
        'demo'     => 'admin@binainsan.sch.id',
        'features' => [
            'Monitoring Kehadiran Pegawai & Siswa Real-time',
            'Import Excel (.xlsx), Custom Shift & Koordinat Harian',
            'Laporan Matrix, Rekap Persentase & Cetak Kartu QR',
        ],
    ],
    [
        'key'      => 'guru',
        'short'    => 'Admin Guru',
        'title'    => 'Portal Guru & Scan QR Siswa',
        'demo'     => 'guru@binainsan.sch.id',
        'features' => [
            'Buat Sesi Absen & Tarik Data Kelas Otomatis',
            'Scan QR Code Kartu Siswa via Kamera HP / Laptop',
            'Rekapitulasi & Export Laporan Kehadiran Siswa',
        ],
    ],
    [
        'key'      => 'pegawai',
        'short'    => 'Pegawai PWA',
        'title'    => 'Aplikasi Mobile PWA Pegawai',
        'demo'     => 'pegawai@binainsan.sch.id',
        'features' => [
            'Check-in & Check-out GPS Geofence Sesuai Shift',
            'Pengajuan Perbaikan Absen, Izin & Sakit Online',
            'Sesi Login Selamanya Aman dengan Token Rotasi PWA',
        ],
    ],
];
?>
<body class="bg-white text-slate-800 min-h-screen antialiased">
  <div class="min-h-screen grid lg:grid-cols-2">
    <!-- LEFT WHITE PANEL -->
    <div class="bg-white flex flex-col justify-center px-8 sm:px-14 lg:px-20 py-12 order-2 lg:order-1">
      <div class="max-w-lg w-full mx-auto lg:mx-0">
        <!-- Brand Logo -->
        <div class="flex items-center justify-between gap-3 mb-10">
          <img src="<?= Security::e($logoUrl) ?>" alt="Logo" class="h-10 w-auto object-contain">
          <span class="px-3 py-1 rounded-full bg-blue-50 text-[#1E5BF0] text-[11px] font-bold">
            <?= Security::e($school['package_name'] ?? 'Aktif') ?>
          </span>
        </div>

        <!-- School Name & Description -->
        <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight leading-tight">
          <?= Security::e($school['name']) ?>
        </h1>
        <p class="text-slate-500 text-base sm:text-lg mt-3 leading-relaxed">
          Portal manajemen absensi digital pegawai &amp; siswa terpadu berbasis GPS dan QR Code.
        </p>

        <!-- Security Badge -->
        <div class="mt-8 flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-full bg-blue-50 text-[#1E5BF0] flex items-center justify-center shrink-0">
            <i class="fa-solid fa-shield-halved text-base"></i>
          </div>
          <div>
            <div class="text-sm font-bold text-slate-800">Keamanan &amp; Sesi PWA Selamanya</div>
            <div class="text-xs text-slate-400">Data terenkripsi aman dan terproteksi maksimal untuk <?= Security::e(Tenant::schoolDisplayUrl($school['slug'])) ?>.</div>
          </div>
        </div>

        <!-- Portal Role Tabs -->
        <div class="mt-10">
          <div class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400 mb-3">
            AKSES PORTAL TERSEDIA:
          </div>
          <div class="flex flex-wrap gap-2 mb-4">
            <?php foreach ($roleTabs as $idx => $rt): ?>
              <button type="button" data-idx="<?= $idx ?>" class="role-tab-btn px-4 py-1.5 rounded-full text-xs font-bold border transition <?= $idx === 0 ? 'bg-blue-50 border-blue-200 text-[#1E5BF0]' : 'bg-slate-50 border-slate-100 text-slate-500 hover:bg-slate-100' ?>">
                <?= Security::e($rt['short']) ?>
              </button>
            <?php endforeach; ?>
          </div>

          <div class="rounded-2xl border border-blue-100/90 bg-[#F8FBFF] p-5">
            <div class="flex items-center justify-between gap-2 mb-3">
              <div id="roleCardTitle" class="text-sm font-extrabold text-[#1E40AF]"><?= Security::e($roleTabs[0]['title']) ?></div>
              <?php if ($school['slug'] === 'bina-insan-palu'): ?>
                <button type="button" id="roleDemoFillBtn" onclick="fillDemoRole(0)" class="text-[11px] font-bold text-[#1E5BF0] hover:underline">
                  Isi Akun Demo →
                </button>
              <?php endif; ?>
            </div>
            <ul id="roleCardFeatures" class="space-y-2 text-xs text-[#2563EB] font-medium">
              <?php foreach ($roleTabs[0]['features'] as $f): ?>
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

    <!-- RIGHT VIBRANT ROYAL BLUE PANEL -->
    <div class="relative overflow-hidden bg-gradient-to-br from-[#2563EB] via-[#1E5BF0] to-[#1D4ED8] flex flex-col items-center justify-center p-6 sm:p-10 order-1 lg:order-2">
      <div class="pointer-events-none absolute -bottom-24 -left-24 w-80 h-80 rounded-full bg-white/10 blur-3xl"></div>
      <div class="pointer-events-none absolute -top-24 -right-24 w-72 h-72 rounded-full bg-sky-300/15 blur-3xl"></div>

      <div class="relative z-10 bg-white rounded-[28px] shadow-[0_25px_60px_-15px_rgba(15,23,42,0.3)] w-full max-w-[410px] p-8 sm:p-10">
        <div class="mb-7">
          <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Selamat Datang</h2>
          <p class="text-xs text-slate-400 mt-1">Masuk untuk mengelola Dashboard &amp; Absensi Sekolah</p>
        </div>

        <form id="schoolLoginForm" class="space-y-5">
          <input type="hidden" name="school_slug" value="<?= Security::e($school['slug']) ?>">

          <div>
            <label class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 mb-2">EMAIL / NIP</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"><i class="fa-regular fa-user"></i></span>
              <input type="text" id="loginIdentifier" required autocomplete="username" placeholder="Masukkan email atau NIP" class="w-full pl-11 pr-4 py-3.5 rounded-xl bg-[#EDF3FF] border border-transparent focus:border-[#2563EB] focus:bg-white outline-none text-sm font-semibold text-slate-800 transition">
            </div>
          </div>

          <div>
            <label class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 mb-2">PASSWORD</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"><i class="fa-solid fa-lock"></i></span>
              <input type="password" id="loginPassword" required autocomplete="current-password" placeholder="••••••••••" class="w-full pl-11 pr-11 py-3.5 rounded-xl bg-[#EDF3FF] border border-transparent focus:border-[#2563EB] focus:bg-white outline-none text-sm font-semibold text-slate-800 transition">
              <button type="button" onclick="const i=$('#loginPassword');i.attr('type', i.attr('type')==='password'?'text':'password');" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#1E5BF0]">
                <i class="fa-regular fa-eye"></i>
              </button>
            </div>
          </div>

          <button type="submit" class="w-full py-3.5 rounded-xl bg-[#1E5BF0] hover:bg-[#194ED3] text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-blue-600/30 transition flex items-center justify-center gap-2">
            <span>MASUK SEKARANG</span>
            <i class="fa-solid fa-arrow-right text-[11px]"></i>
          </button>
        </form>

        <div class="mt-7 text-center text-[10px] font-bold uppercase tracking-widest text-slate-400">
          &copy; <?= date('Y') ?> <?= Security::e(strtoupper($saas['app_name'])) ?> SAAS PLATFORM
        </div>
      </div>

      <!-- Optional PWA Install Bar -->
      <div id="pwaInstallBanner" class="hidden relative z-10 mt-4 w-full max-w-[410px] bg-white/15 backdrop-blur border border-white/25 rounded-2xl p-3 text-white flex items-center gap-3">
        <img src="<?= Security::e($faviconUrl) ?>" alt="Icon" class="w-9 h-9 rounded-xl bg-white p-1 shrink-0">
        <div class="flex-1 min-w-0">
          <div class="text-xs font-bold">Install Aplikasi Mobile PWA</div>
          <div class="text-[11px] text-blue-100">Akses absensi langsung dari Layar Utama HP.</div>
        </div>
        <button id="pwaInstallBtn" type="button" class="px-3 py-1.5 rounded-lg bg-white text-[#1E5BF0] text-xs font-extrabold shrink-0">Install</button>
        <button id="pwaDismissBtn" type="button" class="text-white/70 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
      </div>
    </div>
  </div>

  <?php require dirname(__DIR__) . '/layouts/modals.php'; ?>
  <script>
    const roleTabsData = <?= json_encode($roleTabs, JSON_UNESCAPED_UNICODE) ?>;

    function fillDemoRole(idx) {
      const item = roleTabsData[idx];
      if (!item) return;
      $('#loginIdentifier').val(item.demo);
      $('#loginPassword').val('Admin123!');
    }

    $('.role-tab-btn').on('click', function () {
      const idx = parseInt($(this).data('idx'), 10);
      const item = roleTabsData[idx];
      if (!item) return;

      $('.role-tab-btn')
        .removeClass('bg-blue-50 border-blue-200 text-[#1E5BF0]')
        .addClass('bg-slate-50 border-slate-100 text-slate-500');
      $(this)
        .removeClass('bg-slate-50 border-slate-100 text-slate-500')
        .addClass('bg-blue-50 border-blue-200 text-[#1E5BF0]');

      $('#roleCardTitle').text(item.title);
      $('#roleDemoFillBtn').attr('onclick', 'fillDemoRole(' + idx + ')');
      let html = '';
      item.features.forEach(f => {
        html += `<li class="flex items-center gap-2"><i class="fa-solid fa-check text-[11px]"></i><span>${ApiClient.e(f)}</span></li>`;
      });
      $('#roleCardFeatures').html(html);
    });

    $('#schoolLoginForm').on('submit', function (e) {
      e.preventDefault();
      ApiClient.post('<?= app_url('/api/auth/school-login') ?>', {
        school_slug: '<?= Security::e($school['slug']) ?>',
        identifier: $('#loginIdentifier').val().trim(),
        password: $('#loginPassword').val(),
        remember_forever: $('#loginRememberForever').is(':checked')
      }, { loadingTitle: 'Memverifikasi Akun...', loadingSub: 'Menyiapkan sesi aman Anda...' })
      .then(function (res) {
        AppModal.showSaved(res.message, 'Login Berhasil!', function () {
          window.location.href = res.redirect;
        });
      }).catch(function () {});
    });
  </script>
</body>
</html>
