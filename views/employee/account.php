<?php $slug = $school['slug']; ?>
<div class="space-y-4">
  <!-- Profile Form -->
  <form id="accProfileForm" enctype="multipart/form-data" class="bg-white rounded-3xl p-5 shadow-sm border border-blue-100 space-y-3.5">
    <input type="hidden" name="school_slug" value="<?= Security::e($slug) ?>">
    <h2 class="text-sm font-extrabold text-slate-800"><i class="fa-solid fa-user-pen text-blue-600 mr-1.5"></i>Profil Pegawai</h2>

    <div class="flex items-center gap-3">
      <div class="w-14 h-14 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center font-extrabold text-xl overflow-hidden shrink-0">
        <img id="accAvatarImg" src="" alt="" class="hidden w-full h-full object-cover">
        <span id="accAvatarInitial">P</span>
      </div>
      <div class="min-w-0 flex-1">
        <div id="accEmailLabel" class="text-xs font-bold text-slate-700 truncate">-</div>
        <div id="accPosLabel" class="text-[11px] text-slate-500">-</div>
        <input type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp" class="mt-1.5 block w-full text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 file:font-bold">
      </div>
    </div>

    <div>
      <label class="block text-[11px] font-bold text-slate-600 mb-1">Nama Lengkap</label>
      <input type="text" id="accName" name="name" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold">
    </div>
    <div>
      <label class="block text-[11px] font-bold text-slate-600 mb-1">No HP / WhatsApp</label>
      <input type="text" id="accPhone" name="phone" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs">
    </div>
    <button type="submit" class="w-full py-3 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow">
      <i class="fa-solid fa-floppy-disk mr-1"></i> Simpan Perubahan Profil
    </button>
  </form>

  <!-- Change Password -->
  <form id="accPassForm" class="bg-white rounded-3xl p-5 shadow-sm border border-slate-200/70 space-y-3">
    <h2 class="text-sm font-extrabold text-slate-800"><i class="fa-solid fa-key text-blue-600 mr-1.5"></i>Ubah Password</h2>
    <input type="password" id="oldPass" required placeholder="Password Lama" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs">
    <input type="password" id="newPass" required minlength="6" placeholder="Password Baru (minimal 6 karakter)" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs">
    <button type="submit" class="w-full py-2.5 rounded-2xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs">
      Perbarui Password
    </button>
  </form>

  <!-- Forever PWA Login Devices Security Panel -->
  <div class="bg-white rounded-3xl p-5 shadow-sm border border-emerald-200 space-y-3">
    <div class="flex items-start gap-2.5">
      <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0"><i class="fa-solid fa-shield-halved"></i></div>
      <div>
        <h2 class="text-sm font-extrabold text-slate-800">Keamanan Sesi Login Selamanya (PWA)</h2>
        <p class="text-[11px] text-slate-500">Perangkat di bawah terhubung dengan token rotasi aman (SHA-256). Anda dapat mencabut akses perangkat kapan saja.</p>
      </div>
    </div>
    <div id="devicesListWrap" class="space-y-2"></div>
  </div>

  <!-- Logout -->
  <button type="button" onclick="confirmLogout()" class="w-full py-3.5 rounded-2xl bg-red-50 hover:bg-red-100 border border-red-200 text-red-700 font-extrabold text-sm transition">
    <i class="fa-solid fa-right-from-bracket mr-1.5"></i> Keluar dari Aplikasi
  </button>
</div>

<script>
const SLUG = '<?= Security::e($slug) ?>';

function loadAccount() {
  ApiClient.get('<?= app_url('/api/employee/account-get') ?>', { school_slug: SLUG }).then(res => {
    const u = res.data.user;
    $('#accName').val(u.name);
    $('#accPhone').val(u.phone || '');
    $('#accEmailLabel').text(u.email + (u.nip_nik ? ' (' + u.nip_nik + ')' : ''));
    $('#accPosLabel').text(u.position_title || 'Pegawai');
    $('#accAvatarInitial').text((u.name || 'P').substring(0, 1).toUpperCase());
    if (u.avatar_url) {
      $('#accAvatarImg').attr('src', u.avatar_url).removeClass('hidden');
      $('#accAvatarInitial').addClass('hidden');
    }

    const devs = res.data.devices || [];
    if (!devs.length) {
      $('#devicesListWrap').html('<div class="text-xs text-slate-400">Belum ada sesi perangkat persisten tersimpan.</div>');
    } else {
      let html = '';
      devs.forEach(d => {
        html += `<div class="p-3 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between gap-2">
          <div class="min-w-0">
            <div class="text-xs font-bold text-slate-800 truncate">${ApiClient.e(d.device_name || 'Perangkat')} ${d.is_current ? '<span class="ml-1 px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700 text-[10px]">HP Ini</span>' : ''}</div>
            <div class="text-[10px] text-slate-500">IP: ${ApiClient.e(d.ip_address || '-')} • Aktif: ${ApiClient.e(d.last_used_at)}</div>
          </div>
          ${!d.is_current ? `<button type="button" onclick="revokeDevice(${d.id})" class="px-2.5 py-1 rounded-lg bg-red-100 text-red-700 text-[11px] font-bold shrink-0">Cabut</button>` : ''}
        </div>`;
      });
      $('#devicesListWrap').html(html);
    }
  });
}

$('#accProfileForm').on('submit', function (e) {
  e.preventDefault();
  ApiClient.upload('<?= app_url('/api/employee/account-update') ?>', new FormData(this), { loadingTitle: 'Memperbarui Profil...' })
    .then(res => AppModal.showUpdated(res.message, null, loadAccount)).catch(() => {});
});

$('#accPassForm').on('submit', function (e) {
  e.preventDefault();
  ApiClient.post('<?= app_url('/api/employee/password-update') ?>', {
    school_slug: SLUG,
    old_password: $('#oldPass').val(),
    new_password: $('#newPass').val()
  }, { loadingTitle: 'Mengubah Password...' })
  .then(res => {
    $('#accPassForm')[0].reset();
    AppModal.showUpdated(res.message);
  }).catch(() => {});
});

async function revokeDevice(tokenId) {
  const ok = await AppModal.confirm({ title: 'Cabut Sesi Perangkat?', message: 'Perangkat tersebut akan otomatis logout dan harus login ulang.', danger: true });
  if (!ok) return;
  ApiClient.post('<?= app_url('/api/employee/device-revoke') ?>', { school_slug: SLUG, token_id: tokenId })
    .then(res => AppModal.showUpdated(res.message, null, loadAccount)).catch(() => {});
}

async function confirmLogout() {
  const ok = await AppModal.confirm({ title: 'Keluar dari Akun?', message: 'Sesi login selamanya di perangkat ini akan dihapus sampai Anda login kembali.', danger: true, confirmText: 'Ya, Keluar' });
  if (!ok) return;
  window.location.href = '<?= Tenant::schoolLocalUrl($slug, 'logout') ?>';
}

$(loadAccount);
</script>
