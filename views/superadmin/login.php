<?php
require dirname(__DIR__) . '/layouts/head.php';
$logoUrl = Tenant::brandingAssetUrl($saas['logo_path'] ?? '', 'assets/img/default-logo.svg');

// Prepare package showcase items for left panel tabs
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
            'features' => [
                'Absensi Pegawai GPS & PWA Mobile',
                'Import Data Excel (.xlsx)',
                'Laporan Log, Matrix & Rekap Persentase',
            ],
        ],
        [
            'name'     => 'Paket Professional',
            'short'    => 'Pro',
            'price'    => 'Rp 350.000 / bln',
            'features' => [
                'Custom Shift & Koordinat per Hari per Orang',
                'Data Siswa & Cetak Kartu QR Code',
                'Portal Guru & Scan QR Absensi Siswa',
            ],
        ],
        [
            'name'     => 'Paket Enterprise',
            'short'    => 'Enterprise',
            'price'    => 'Rp 650.000 / bln',
            'features' => [
                'Semua Fitur Absensi Pegawai & Siswa Lengkap',
                'Notifikasi Pengingat Tagihan WA Gateway',
                'Kuota Besar hingga 1.000 Pegawai & 5.000 Siswa',
            ],
        ],
    ];
}
?>
<body class="bg-white text-slate-800 min-h-screen antialiased">
  <div class="min-h-screen grid lg:grid-cols-2">
    <!-- LEFT WHITE PANEL: Brand, Tagline, Security & Interactive Package Pills -->
    <div class="bg-white flex flex-col justify-center px-8 sm:px-14 lg:px-20 py-12 order-2 lg:order-1">
      <div class="max-w-lg w-full mx-auto lg:mx-0">
        <!-- Brand Logo -->
        <a href="<?= app_url('/') ?>" class="inline-block mb-10">
          <img src="<?= Security::e($logoUrl) ?>" alt="Logo" class="h-10 w-auto object-contain">
        </a>

        <!-- Headline & Subtitle -->
        <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
          <?= Security::e($saas['app_name']) ?>
        </h1>
        <p class="text-slate-500 text-base sm:text-lg mt-3 leading-relaxed">
          Transformasi digital ekosistem absensi pegawai &amp; siswa sekolah menjadi lebih modern dan terukur.
        </p>

        <!-- Security Trust Item -->
        <div class="mt-8 flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-full bg-blue-50 text-[#1E5BF0] flex items-center justify-center shrink-0">
            <i class="fa-solid fa-shield-halved text-base"></i>
          </div>
          <div>
            <div class="text-sm font-bold text-slate-800">Keamanan Sistem Terproteksi</div>
            <div class="text-xs text-slate-400">Data terenkripsi aman, isolasi multi-tenant &amp; proteksi maksimal.</div>
          </div>
        </div>

        <!-- Interactive Package Pills -->
        <div class="mt-10">
          <div class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400 mb-3">
            PILIH PAKET UNGGULAN:
          </div>
          <div class="flex flex-wrap gap-2 mb-4" id="saPkgTabs">
            <?php foreach ($pkgTabs as $idx => $pt): ?>
              <button type="button" data-idx="<?= $idx ?>" class="pkg-tab-btn px-4 py-1.5 rounded-full text-xs font-bold border transition <?= $idx === 0 ? 'bg-blue-50 border-blue-200 text-[#1E5BF0]' : 'bg-slate-50 border-slate-100 text-slate-500 hover:bg-slate-100' ?>">
                <?= Security::e($pt['short']) ?>
              </button>
            <?php endforeach; ?>
          </div>

          <div class="rounded-2xl border border-blue-100/90 bg-[#F8FBFF] p-5">
            <div class="flex items-center justify-between gap-2 mb-3">
              <div id="pkgCardTitle" class="text-sm font-extrabold text-[#1E40AF]"><?= Security::e($pkgTabs[0]['name']) ?></div>
              <span id="pkgCardPrice" class="text-xs font-bold text-[#1E5BF0]"><?= Security::e($pkgTabs[0]['price']) ?></span>
            </div>
            <ul id="pkgCardFeatures" class="space-y-2 text-xs text-[#2563EB] font-medium">
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

    <!-- RIGHT VIBRANT ROYAL BLUE PANEL: Minimalist Floating Login Card -->
    <div class="relative overflow-hidden bg-gradient-to-br from-[#2563EB] via-[#1E5BF0] to-[#1D4ED8] flex items-center justify-center p-6 sm:p-10 order-1 lg:order-2">
      <!-- Subtle soft glow accents -->
      <div class="pointer-events-none absolute -bottom-24 -left-24 w-80 h-80 rounded-full bg-white/10 blur-3xl"></div>
      <div class="pointer-events-none absolute -top-24 -right-24 w-72 h-72 rounded-full bg-sky-300/15 blur-3xl"></div>

      <div class="relative z-10 bg-white rounded-[28px] shadow-[0_25px_60px_-15px_rgba(15,23,42,0.3)] w-full max-w-[410px] p-8 sm:p-10">
        <div class="mb-7">
          <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Selamat Datang</h2>
          <p class="text-xs text-slate-400 mt-1">Masuk untuk mengelola Console Superadmin SaaS</p>
        </div>

        <form id="saLoginForm" class="space-y-5">
          <div>
            <label class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 mb-2">EMAIL SUPERADMIN</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"><i class="fa-regular fa-user"></i></span>
              <input type="email" id="saEmail" required value="superadmin@edupresensi.id" placeholder="superadmin@edupresensi.id" class="w-full pl-11 pr-4 py-3.5 rounded-xl bg-[#EDF3FF] border border-transparent focus:border-[#2563EB] focus:bg-white outline-none text-sm font-semibold text-slate-800 transition">
            </div>
          </div>

          <div>
            <label class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 mb-2">PASSWORD</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"><i class="fa-solid fa-lock"></i></span>
              <input type="password" id="saPassword" required placeholder="••••••••••" class="w-full pl-11 pr-11 py-3.5 rounded-xl bg-[#EDF3FF] border border-transparent focus:border-[#2563EB] focus:bg-white outline-none text-sm font-semibold text-slate-800 transition">
              <button type="button" onclick="const i=$('#saPassword');i.attr('type', i.attr('type')==='password'?'text':'password');" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#1E5BF0]">
                <i class="fa-regular fa-eye"></i>
              </button>
            </div>
          </div>

          <div class="flex justify-end text-xs">
            <button type="button" onclick="$('#saEmail').val('superadmin@edupresensi.id');$('#saPassword').val('Admin123!');" class="text-[#1E5BF0] font-bold hover:underline">
              Isi Demo
            </button>
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
    </div>
  </div>

  <?php require dirname(__DIR__) . '/layouts/modals.php'; ?>
  <script>
    const saPkgData = <?= json_encode($pkgTabs, JSON_UNESCAPED_UNICODE) ?>;

    $('.pkg-tab-btn').on('click', function () {
      const idx = parseInt($(this).data('idx'), 10);
      const item = saPkgData[idx];
      if (!item) return;

      $('.pkg-tab-btn')
        .removeClass('bg-blue-50 border-blue-200 text-[#1E5BF0]')
        .addClass('bg-slate-50 border-slate-100 text-slate-500');
      $(this)
        .removeClass('bg-slate-50 border-slate-100 text-slate-500')
        .addClass('bg-blue-50 border-blue-200 text-[#1E5BF0]');

      $('#pkgCardTitle').text(item.name);
      $('#pkgCardPrice').text(item.price);
      let html = '';
      (item.features || []).forEach(f => {
        html += `<li class="flex items-center gap-2"><i class="fa-solid fa-check text-[11px]"></i><span>${ApiClient.e(f)}</span></li>`;
      });
      $('#pkgCardFeatures').html(html);
    });

    $('#saLoginForm').on('submit', function (e) {
      e.preventDefault();
      ApiClient.post('<?= app_url('/api/auth/superadmin-login') ?>', {
        email: $('#saEmail').val().trim(),
        password: $('#saPassword').val(),
        remember: $('#saRemember').is(':checked')
      }, { loadingTitle: 'Memverifikasi Akun...', loadingSub: 'Menghubungkan ke Console Superadmin...' })
      .then(function (res) {
        AppModal.showSaved(res.message, 'Login Berhasil!', function () {
          window.location.href = res.redirect;
        });
      }).catch(function () {});
    });
  </script>
</body>
</html>
