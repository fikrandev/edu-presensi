<!-- Pengaturan Logo & Favicon Global SaaS + PWA Installer Icon -->
<div class="w-full">
  <form id="brandingForm" enctype="multipart/form-data" class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/80 shadow-sm space-y-6">
    <div class="border-b border-slate-100 pb-4">
      <h2 class="text-lg font-extrabold text-slate-800"><i class="fa-solid fa-palette text-blue-600 mr-2"></i>Branding Aplikasi SaaS & Icon PWA</h2>
      <p class="text-xs text-slate-500 mt-1">Logo & Favicon yang diatur di sini otomatis dipakai di seluruh portal SaaS dan menjadi <strong>Icon Installer PWA Mobile Pegawai</strong>.</p>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1">Nama Aplikasi SaaS *</label>
        <input type="text" id="brAppName" name="app_name" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-bold">
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1">Domain Utama Subfolder</label>
        <input type="text" id="brDomain" name="display_domain" placeholder="eduabsen.sekolah-indonesia.id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono">
      </div>
    </div>

    <div>
      <label class="block text-xs font-bold text-slate-600 mb-1">Tagline Aplikasi</label>
      <input type="text" id="brTagline" name="tagline" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
    </div>

    <div class="grid sm:grid-cols-2 gap-5">
      <!-- Logo Upload -->
      <div class="border-2 border-dashed border-blue-200 rounded-2xl p-5 bg-blue-50/40 text-center">
        <div class="text-xs font-extrabold text-blue-900 mb-3">Logo Utama Aplikasi</div>
        <div class="bg-white rounded-xl p-3 border border-slate-200 inline-flex items-center justify-center mb-3 min-h-[72px] min-w-[160px]">
          <img id="previewLogo" src="" alt="Logo Preview" class="max-h-14 w-auto object-contain">
        </div>
        <input type="file" name="logo" id="inputLogo" accept=".png,.jpg,.jpeg,.webp,.svg" class="block w-full text-xs text-slate-500 file:mr-2 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white file:font-bold">
        <div class="text-[10px] text-slate-500 mt-1.5">Format: PNG, SVG, JPG, WEBP (Maks 2MB)</div>
      </div>

      <!-- Favicon / PWA Icon Upload -->
      <div class="border-2 border-dashed border-blue-200 rounded-2xl p-5 bg-blue-50/40 text-center">
        <div class="text-xs font-extrabold text-blue-900 mb-3">Favicon & Icon PWA Pegawai</div>
        <div class="bg-white rounded-2xl p-3 border border-slate-200 inline-flex items-center justify-center mb-3 w-20 h-20 shadow-sm">
          <img id="previewFavicon" src="" alt="Favicon Preview" class="w-14 h-14 object-contain">
        </div>
        <input type="file" name="favicon" id="inputFavicon" accept=".png,.ico,.svg,.webp,.jpg,.jpeg" class="block w-full text-xs text-slate-500 file:mr-2 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white file:font-bold">
        <div class="text-[10px] text-slate-500 mt-1.5">Disarankan PNG kotak (512x512) untuk icon PWA terbaik.</div>
      </div>
    </div>

    <div class="flex justify-end pt-2">
      <button type="submit" class="px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-extrabold shadow-lg shadow-blue-600/25 transition">
        <i class="fa-solid fa-floppy-disk mr-1.5"></i> Simpan Pengaturan Branding
      </button>
    </div>
  </form>
</div>

<script>
function loadBranding() {
  ApiClient.get('<?= app_url('/api/superadmin/branding-get') ?>').then(function (res) {
    const d = res.data;
    $('#brAppName').val(d.app_name);
    $('#brTagline').val(d.tagline);
    $('#brDomain').val(d.display_domain);
    $('#previewLogo').attr('src', d.logo_url + '?t=' + Date.now());
    $('#previewFavicon').attr('src', d.favicon_url + '?t=' + Date.now());
  });
}

$('#inputLogo').on('change', function () {
  if (this.files[0]) $('#previewLogo').attr('src', URL.createObjectURL(this.files[0]));
});
$('#inputFavicon').on('change', function () {
  if (this.files[0]) $('#previewFavicon').attr('src', URL.createObjectURL(this.files[0]));
});

$('#brandingForm').on('submit', function (e) {
  e.preventDefault();
  const fd = new FormData(this);
  ApiClient.upload('<?= app_url('/api/superadmin/branding-save') ?>', fd, { loadingTitle: 'Mengunggah Branding SaaS...' })
    .then(function (res) {
      AppModal.showUpdated(res.message, 'Branding Diperbarui!', () => location.reload());
    }).catch(() => {});
});

$(loadBranding);
</script>
