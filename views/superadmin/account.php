<!-- Pengaturan Akun & Ubah Password Superadmin -->
<div class="grid lg:grid-cols-2 gap-6">
  <!-- Profil & Username Superadmin -->
  <form id="saProfileForm" class="bg-white rounded-2xl p-6 sm:p-7 border border-slate-200/80 shadow-[0_2px_12px_rgba(15,23,42,0.03)] space-y-4">
    <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
      <div class="w-10 h-10 rounded-xl bg-[#1E5BF0] text-white flex items-center justify-center shadow-md shadow-blue-600/20">
        <i class="fa-solid fa-user-shield"></i>
      </div>
      <div>
        <h2 class="text-base font-extrabold text-slate-800">Profil &amp; Username Superadmin</h2>
        <p class="text-xs text-slate-400">Ubah username login dan identitas akun Superadmin Anda.</p>
      </div>
    </div>

    <div>
      <label class="block text-xs font-bold text-slate-600 mb-1">Username Login *</label>
      <input type="text" id="saAccUsername" required placeholder="superadmin" class="w-full px-3.5 py-2.5 rounded-xl bg-[#F8FBFF] border border-slate-200 focus:border-[#1E5BF0] focus:bg-white outline-none text-sm font-mono font-bold text-slate-800">
      <div class="text-[11px] text-slate-400 mt-1">Tanpa spasi (contoh: <code>superadmin</code>). Digunakan saat login ke Console Superadmin.</div>
    </div>

    <div>
      <label class="block text-xs font-bold text-slate-600 mb-1">Nama Tampilan *</label>
      <input type="text" id="saAccName" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:border-[#1E5BF0] outline-none text-sm font-semibold">
    </div>

    <div>
      <label class="block text-xs font-bold text-slate-600 mb-1">No HP / WhatsApp</label>
      <input type="text" id="saAccPhone" placeholder="0812xxxx" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:border-[#1E5BF0] outline-none text-sm">
    </div>

    <div class="pt-2 flex justify-end">
      <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#1E5BF0] hover:bg-[#194ED3] text-white text-xs font-extrabold shadow-md shadow-blue-600/20 transition">
        <i class="fa-solid fa-floppy-disk mr-1.5"></i> Simpan Profil &amp; Username
      </button>
    </div>
  </form>

  <!-- Ubah Password Superadmin -->
  <form id="saPasswordForm" class="bg-white rounded-2xl p-6 sm:p-7 border border-slate-200/80 shadow-[0_2px_12px_rgba(15,23,42,0.03)] space-y-4">
    <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
      <div class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center shadow-sm">
        <i class="fa-solid fa-key"></i>
      </div>
      <div>
        <h2 class="text-base font-extrabold text-slate-800">Ubah Password Superadmin</h2>
        <p class="text-xs text-slate-400">Gunakan password yang kuat minimal 6 karakter untuk keamanan platform SaaS.</p>
      </div>
    </div>

    <div>
      <label class="block text-xs font-bold text-slate-600 mb-1">Password Lama *</label>
      <div class="relative">
        <input type="password" id="saOldPass" required placeholder="Masukkan password saat ini" class="w-full px-3.5 pr-10 py-2.5 rounded-xl border border-slate-200 focus:border-[#1E5BF0] outline-none text-sm">
        <button type="button" onclick="togglePassField('#saOldPass')" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#1E5BF0]"><i class="fa-regular fa-eye"></i></button>
      </div>
    </div>

    <div>
      <label class="block text-xs font-bold text-slate-600 mb-1">Password Baru *</label>
      <div class="relative">
        <input type="password" id="saNewPass" required minlength="6" placeholder="Minimal 6 karakter" class="w-full px-3.5 pr-10 py-2.5 rounded-xl border border-slate-200 focus:border-[#1E5BF0] outline-none text-sm">
        <button type="button" onclick="togglePassField('#saNewPass')" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#1E5BF0]"><i class="fa-regular fa-eye"></i></button>
      </div>
    </div>

    <div>
      <label class="block text-xs font-bold text-slate-600 mb-1">Konfirmasi Password Baru *</label>
      <div class="relative">
        <input type="password" id="saConfirmPass" required minlength="6" placeholder="Ulangi password baru" class="w-full px-3.5 pr-10 py-2.5 rounded-xl border border-slate-200 focus:border-[#1E5BF0] outline-none text-sm">
        <button type="button" onclick="togglePassField('#saConfirmPass')" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#1E5BF0]"><i class="fa-regular fa-eye"></i></button>
      </div>
    </div>

    <div class="pt-2 flex justify-end">
      <button type="submit" class="px-6 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-extrabold shadow transition">
        <i class="fa-solid fa-lock mr-1.5"></i> Perbarui Password
      </button>
    </div>
  </form>
</div>

<script>
function togglePassField(sel) {
  const $i = $(sel);
  $i.attr('type', $i.attr('type') === 'password' ? 'text' : 'password');
}

function loadSaAccount() {
  ApiClient.get('<?= app_url('/api/superadmin/account-get') ?>').then(res => {
    const d = res.data;
    $('#saAccUsername').val(d.username || 'superadmin');
    $('#saAccName').val(d.name || '');
    $('#saAccPhone').val(d.phone || '');
  });
}

$('#saProfileForm').on('submit', function (e) {
  e.preventDefault();
  ApiClient.post('<?= app_url('/api/superadmin/account-update') ?>', {
    username: $('#saAccUsername').val().trim(),
    name: $('#saAccName').val().trim(),
    phone: $('#saAccPhone').val().trim()
  }, { loadingTitle: 'Menyimpan Profil Superadmin...' })
  .then(res => AppModal.showUpdated(res.message, 'Akun Diperbarui!', () => location.reload())).catch(() => {});
});

$('#saPasswordForm').on('submit', function (e) {
  e.preventDefault();
  const np = $('#saNewPass').val();
  const cp = $('#saConfirmPass').val();
  if (np !== cp) {
    return AppModal.showError('Konfirmasi password baru tidak sama dengan password baru.');
  }
  ApiClient.post('<?= app_url('/api/superadmin/password-update') ?>', {
    old_password: $('#saOldPass').val(),
    new_password: np,
    confirm_password: cp
  }, { loadingTitle: 'Memperbarui Password...' })
  .then(res => {
    $('#saPasswordForm')[0].reset();
    AppModal.showUpdated(res.message, 'Password Diperbarui!');
  }).catch(() => {});
});

$(loadSaAccount);
</script>
