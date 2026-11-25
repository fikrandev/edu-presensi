<!-- Daftar Sekolah Berlangganan -->
<div class="space-y-5">
  <!-- Filter & Action Bar -->
  <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
    <div class="flex flex-1 gap-2">
      <div class="relative flex-1">
        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
        <input type="text" id="filterSearch" placeholder="Cari nama sekolah, subfolder slug, email..." class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-blue-500 outline-none">
      </div>
      <select id="filterStatus" class="px-3 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 outline-none">
        <option value="">Semua Status</option>
        <option value="active">Aktif</option>
        <option value="expiring">Jatuh Tempo ≤7 Hr</option>
        <option value="expired">Expired</option>
        <option value="suspended">Suspended</option>
      </select>
    </div>
    <button type="button" onclick="openSchoolModal()" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold shadow-md shadow-blue-600/25 transition shrink-0">
      <i class="fa-solid fa-plus mr-1.5"></i> Tambah Sekolah
    </button>
  </div>

  <!-- Schools Table / Cards -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-blue-50/80 text-blue-900 text-xs uppercase">
          <tr>
            <th class="text-left px-5 py-3.5">Sekolah & Subfolder Link</th>
            <th class="text-left px-4 py-3.5">Paket</th>
            <th class="text-left px-4 py-3.5">Masa Aktif</th>
            <th class="text-center px-4 py-3.5">Pegawai / Siswa</th>
            <th class="text-right px-5 py-3.5">Aksi & Perpanjangan</th>
          </tr>
        </thead>
        <tbody id="schoolsTableBody" class="divide-y divide-slate-100"></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Tambah / Edit Sekolah -->
<div id="schoolModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
  <div class="modal-pop bg-white rounded-2xl shadow-2xl max-w-2xl w-full my-auto overflow-hidden">
    <div class="bg-blue-700 text-white px-6 py-4 flex items-center justify-between">
      <h3 id="schoolModalTitle" class="font-extrabold text-base"><i class="fa-solid fa-school mr-2"></i>Tambah Sekolah Berlangganan</h3>
      <button type="button" onclick="closeSchoolModal()" class="text-white/80 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
    </div>
    <form id="schoolForm" class="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
      <input type="hidden" id="scId" value="0">
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Nama Sekolah *</label>
          <input type="text" id="scName" required placeholder="misal: SDIT Bina Insan Palu" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-blue-500 outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Subfolder Slug *</label>
          <input type="text" id="scSlug" required placeholder="bina-insan-palu" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono focus:border-blue-500 outline-none">
          <div class="text-[11px] text-blue-600 mt-1 truncate">URL: <strong id="slugPreview">eduabsen.sekolah-indonesia.id/...</strong></div>
        </div>
      </div>

      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Paket Langganan *</label>
          <select id="scPackage" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm outline-none"></select>
        </div>
        <div id="scDurationWrap">
          <label class="block text-xs font-bold text-slate-600 mb-1">Durasi Awal Berlangganan *</label>
          <select id="scDuration" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm outline-none">
            <option value="1">Bulanan (1 Bulan)</option>
            <option value="6">6 Bulan (Semester)</option>
            <option value="12">Setahun (12 Bulan)</option>
          </select>
        </div>
      </div>

      <div class="grid sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">No WhatsApp Sekolah *</label>
          <input type="text" id="scPhone" placeholder="0852xxxx" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Email Sekolah</label>
          <input type="email" id="scEmail" placeholder="info@sekolah.sch.id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Status</label>
          <select id="scStatus" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm outline-none">
            <option value="active">Aktif</option>
            <option value="trial">Trial</option>
            <option value="expired">Expired</option>
            <option value="suspended">Suspended</option>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1">Alamat Sekolah</label>
        <input type="text" id="scAddress" placeholder="Alamat lengkap sekolah" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm outline-none">
      </div>

      <div id="scAdminCredsWrap" class="bg-blue-50 border border-blue-200 rounded-xl p-4 space-y-3">
        <div class="text-xs font-extrabold text-blue-900"><i class="fa-solid fa-user-shield mr-1"></i> Akun Admin Sekolah Otomatis</div>
        <div class="grid sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-[11px] font-bold text-blue-800 mb-1">Email Login Admin Sekolah</label>
            <input type="email" id="scAdminEmail" placeholder="admin@sekolah.sch.id" class="w-full px-3 py-2 rounded-lg border border-blue-200 text-sm bg-white">
          </div>
          <div>
            <label class="block text-[11px] font-bold text-blue-800 mb-1">Password Awal</label>
            <input type="text" id="scAdminPass" value="Admin123!" class="w-full px-3 py-2 rounded-lg border border-blue-200 text-sm bg-white">
          </div>
        </div>
      </div>

      <div class="flex justify-end gap-2 pt-2">
        <button type="button" onclick="closeSchoolModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-600">Batal</button>
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold shadow-md">
          <i class="fa-solid fa-floppy-disk mr-1"></i> Simpan Sekolah
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Perpanjang Masa Aktif (1 Bulan / 6 Bulan / 1 Tahun) -->
<div id="extendModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
  <div class="modal-pop bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
    <div class="bg-emerald-600 text-white px-6 py-4 flex items-center justify-between">
      <h3 class="font-extrabold text-base"><i class="fa-solid fa-calendar-plus mr-2"></i>Perpanjang Langganan</h3>
      <button type="button" onclick="$('#extendModal').addClass('hidden').removeClass('flex')" class="text-white/80 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form id="extendForm" class="p-6 space-y-4">
      <input type="hidden" id="extSchoolId">
      <div class="bg-slate-50 rounded-xl p-3 text-xs">
        <div class="font-extrabold text-slate-800 text-sm" id="extSchoolName">-</div>
        <div class="text-slate-500 mt-0.5">Paket: <strong id="extPackageName">-</strong> • Berakhir: <strong id="extCurrentEnd">-</strong></div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-600 mb-2">Pilih Tambahan Durasi</label>
        <div class="grid grid-cols-3 gap-2">
          <button type="button" data-dur="1" class="extDurBtn p-3 rounded-xl border-2 border-blue-600 bg-blue-50 text-center">
            <div class="text-sm font-extrabold text-blue-800">1 Bulan</div>
            <div id="extPrice1" class="text-[11px] text-blue-600 font-bold mt-0.5">-</div>
          </button>
          <button type="button" data-dur="6" class="extDurBtn p-3 rounded-xl border-2 border-slate-200 text-center hover:border-blue-400">
            <div class="text-sm font-extrabold text-slate-800">6 Bulan</div>
            <div id="extPrice6" class="text-[11px] text-slate-500 font-bold mt-0.5">-</div>
          </button>
          <button type="button" data-dur="12" class="extDurBtn p-3 rounded-xl border-2 border-slate-200 text-center hover:border-blue-400">
            <div class="text-sm font-extrabold text-slate-800">1 Tahun</div>
            <div id="extPrice12" class="text-[11px] text-slate-500 font-bold mt-0.5">-</div>
          </button>
        </div>
        <input type="hidden" id="extDuration" value="1">
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Nominal Bayar (Rp)</label>
          <input type="number" id="extAmount" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm font-bold">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Metode Bayar</label>
          <select id="extMethod" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
            <option>Transfer Bank</option>
            <option>QRIS</option>
            <option>Tunai</option>
            <option>Virtual Account</option>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1">Catatan (Opsional)</label>
        <input type="text" id="extNotes" placeholder="Bukti transfer / keterangan..." class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>

      <div class="flex justify-end gap-2 pt-2">
        <button type="button" onclick="$('#extendModal').addClass('hidden').removeClass('flex')" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-600">Batal</button>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold shadow">
          <i class="fa-solid fa-check mr-1"></i> Aktifkan Perpanjangan
        </button>
      </div>
    </form>
  </div>
</div>

<script>
let allSchools = [];
let allPackages = [];
let displayDomain = 'eduabsen.sekolah-indonesia.id';
let localBaseUrl = '<?= app_url('/') ?>';
let activeExtendSchool = null;

function loadSchools() {
  ApiClient.get('<?= app_url('/api/superadmin/schools-list') ?>', {
    search: $('#filterSearch').val(),
    status: $('#filterStatus').val()
  }).then(function (res) {
    allSchools = res.data.schools;
    allPackages = res.data.packages;
    displayDomain = res.data.display_domain;
    localBaseUrl = res.data.base_url.replace(/\/$/, '');

    let pkgOpts = '';
    allPackages.forEach(p => {
      pkgOpts += `<option value="${p.id}">${ApiClient.e(p.name)} (${ApiClient.rupiah(p.price_monthly)}/bln)</option>`;
    });
    $('#scPackage').html(pkgOpts);

    if (!allSchools.length) {
      $('#schoolsTableBody').html('<tr><td colspan="5" class="text-center py-10 text-slate-400">Belum ada sekolah terdaftar.</td></tr>');
      return;
    }

    let html = '';
    allSchools.forEach(function (sc) {
      const dl = parseInt(sc.days_left, 10);
      let statusBadge = '<span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[11px] font-bold">Aktif</span>';
      if (sc.subscription_status === 'expired' || dl < 0) statusBadge = '<span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[11px] font-bold">Expired</span>';
      else if (sc.subscription_status === 'suspended') statusBadge = '<span class="px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 text-[11px] font-bold">Suspended</span>';
      else if (dl <= 7) statusBadge = `<span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[11px] font-bold">H-${dl}</span>`;

      const pubLink = displayDomain + '/' + sc.slug;
      const localLink = localBaseUrl + '/' + sc.slug;

      html += `<tr class="hover:bg-blue-50/30">
        <td class="px-5 py-4">
          <div class="font-extrabold text-slate-800">${ApiClient.e(sc.name)}</div>
          <div class="text-xs text-blue-600 font-semibold flex items-center gap-2 mt-0.5">
            <a href="${ApiClient.e(localLink)}" target="_blank" class="hover:underline truncate max-w-[260px]">${ApiClient.e(pubLink)}</a>
            <button type="button" onclick="copyText('https://${ApiClient.e(pubLink)}')" class="text-slate-400 hover:text-blue-600" title="Salin link"><i class="fa-regular fa-copy"></i></button>
          </div>
          <div class="text-[11px] text-slate-500 mt-0.5"><i class="fa-brands fa-whatsapp text-emerald-600"></i> ${ApiClient.e(sc.phone || '-')}</div>
        </td>
        <td class="px-4 py-4">
          <div class="font-bold text-slate-700 text-xs">${ApiClient.e(sc.package_name || '-')}</div>
          <div class="text-[11px] text-slate-500">${ApiClient.rupiah(sc.price_monthly)}/bln</div>
        </td>
        <td class="px-4 py-4">
          <div>${statusBadge}</div>
          <div class="text-xs text-slate-600 mt-1 font-medium">${ApiClient.e(sc.subscription_end || '-')}</div>
        </td>
        <td class="px-4 py-4 text-center text-xs font-bold text-slate-700">
          <span class="px-2 py-1 rounded-lg bg-blue-50 text-blue-700">${sc.emp_count} Peg</span>
          <span class="px-2 py-1 rounded-lg bg-sky-50 text-sky-700 ml-1">${sc.stu_count} Sis</span>
        </td>
        <td class="px-5 py-4 text-right">
          <div class="inline-flex flex-wrap justify-end gap-1.5">
            <button type="button" onclick="openExtendModal(${sc.id})" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold">
              <i class="fa-solid fa-calendar-plus mr-1"></i>+1/6/12 Bln
            </button>
            <button type="button" onclick="openSchoolModal(${sc.id})" class="px-2.5 py-1.5 rounded-lg bg-blue-100 text-blue-700 hover:bg-blue-200 text-xs font-bold"><i class="fa-solid fa-pen"></i></button>
            <button type="button" onclick="deleteSchool(${sc.id}, '${ApiClient.e(sc.name)}')" class="px-2.5 py-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 text-xs font-bold"><i class="fa-solid fa-trash"></i></button>
          </div>
        </td>
      </tr>`;
    });
    $('#schoolsTableBody').html(html);
  });
}

function copyText(txt) {
  navigator.clipboard.writeText(txt).then(() => AppModal.showSaved('Link subfolder sekolah disalin: ' + txt, 'Link Disalin!'));
}

function openSchoolModal(id) {
  const sc = id ? allSchools.find(x => parseInt(x.id, 10) === parseInt(id, 10)) : null;
  if (sc) {
    $('#schoolModalTitle').html('<i class="fa-solid fa-pen mr-2"></i>Edit Data Sekolah');
    $('#scId').val(sc.id);
    $('#scName').val(sc.name);
    $('#scSlug').val(sc.slug);
    $('#scPackage').val(sc.package_id);
    $('#scPhone').val(sc.phone || '');
    $('#scEmail').val(sc.email || '');
    $('#scAddress').val(sc.address || '');
    $('#scStatus').val(sc.subscription_status);
    $('#scDurationWrap, #scAdminCredsWrap').addClass('hidden');
  } else {
    $('#schoolModalTitle').html('<i class="fa-solid fa-school mr-2"></i>Tambah Sekolah Berlangganan');
    $('#schoolForm')[0].reset();
    $('#scId').val(0);
    $('#scAdminPass').val('Admin123!');
    $('#scDurationWrap, #scAdminCredsWrap').removeClass('hidden');
  }
  updateSlugPreview();
  $('#schoolModal').removeClass('hidden').addClass('flex');
}

function closeSchoolModal() {
  $('#schoolModal').addClass('hidden').removeClass('flex');
}

function updateSlugPreview() {
  const slug = $('#scSlug').val().trim() || 'nama-sekolah';
  $('#slugPreview').text(displayDomain + '/' + slug);
}

$('#scName').on('input', function () {
  if (parseInt($('#scId').val(), 10) === 0) {
    const slug = $(this).val().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    $('#scSlug').val(slug);
    updateSlugPreview();
  }
});
$('#scSlug').on('input', updateSlugPreview);

$('#schoolForm').on('submit', function (e) {
  e.preventDefault();
  const payload = {
    id: parseInt($('#scId').val(), 10),
    name: $('#scName').val().trim(),
    slug: $('#scSlug').val().trim(),
    package_id: parseInt($('#scPackage').val(), 10),
    duration_months: parseInt($('#scDuration').val(), 10),
    phone: $('#scPhone').val().trim(),
    email: $('#scEmail').val().trim(),
    address: $('#scAddress').val().trim(),
    subscription_status: $('#scStatus').val(),
    admin_email: $('#scAdminEmail').val().trim(),
    admin_password: $('#scAdminPass').val()
  };
  ApiClient.post('<?= app_url('/api/superadmin/school-save') ?>', payload, { loadingTitle: 'Menyimpan Sekolah...' })
    .then(function (res) {
      closeSchoolModal();
      const cb = () => loadSchools();
      res.mode === 'updated' ? AppModal.showUpdated(res.message, null, cb) : AppModal.showSaved(res.message, null, cb);
    }).catch(function () {});
});

function openExtendModal(schoolId) {
  activeExtendSchool = allSchools.find(x => parseInt(x.id, 10) === parseInt(schoolId, 10));
  if (!activeExtendSchool) return;
  $('#extSchoolId').val(activeExtendSchool.id);
  $('#extSchoolName').text(activeExtendSchool.name);
  $('#extPackageName').text(activeExtendSchool.package_name || '-');
  $('#extCurrentEnd').text(activeExtendSchool.subscription_end || '-');
  $('#extPrice1').text(ApiClient.rupiah(activeExtendSchool.price_monthly));
  $('#extPrice6').text(ApiClient.rupiah(activeExtendSchool.price_6months));
  $('#extPrice12').text(ApiClient.rupiah(activeExtendSchool.price_yearly));
  selectExtendDur(1);
  $('#extendModal').removeClass('hidden').addClass('flex');
}

function selectExtendDur(dur) {
  $('#extDuration').val(dur);
  $('.extDurBtn').removeClass('border-blue-600 bg-blue-50').addClass('border-slate-200');
  $(`.extDurBtn[data-dur="${dur}"]`).removeClass('border-slate-200').addClass('border-blue-600 bg-blue-50');
  if (activeExtendSchool) {
    const p = dur === 12 ? activeExtendSchool.price_yearly : (dur === 6 ? activeExtendSchool.price_6months : activeExtendSchool.price_monthly);
    $('#extAmount').val(p || 0);
  }
}

$(document).on('click', '.extDurBtn', function () {
  selectExtendDur(parseInt($(this).data('dur'), 10));
});

$('#extendForm').on('submit', async function (e) {
  e.preventDefault();
  const dur = parseInt($('#extDuration').val(), 10);
  const ok = await AppModal.confirm({
    title: 'Konfirmasi Perpanjangan',
    message: `Tambahkan masa aktif ${dur} bulan untuk ${activeExtendSchool.name} dengan nominal ${ApiClient.rupiah($('#extAmount').val())}?`,
    confirmText: 'Ya, Perpanjang'
  });
  if (!ok) return;

  ApiClient.post('<?= app_url('/api/superadmin/school-extend') ?>', {
    school_id: parseInt($('#extSchoolId').val(), 10),
    duration_months: dur,
    amount: parseFloat($('#extAmount').val()),
    payment_method: $('#extMethod').val(),
    notes: $('#extNotes').val().trim()
  }, { loadingTitle: 'Memproses Perpanjangan...' })
  .then(function (res) {
    $('#extendModal').addClass('hidden').removeClass('flex');
    AppModal.showUpdated(res.message, 'Perpanjangan Berhasil!', () => loadSchools());
  }).catch(function () {});
});

async function deleteSchool(id, name) {
  const ok = await AppModal.confirm({
    title: 'Hapus Sekolah Permanen?',
    message: `Seluruh data pegawai, siswa, dan absensi milik "${name}" akan dihapus permanen. Lanjutkan?`,
    confirmText: 'Ya, Hapus Permanen',
    danger: true
  });
  if (!ok) return;
  ApiClient.post('<?= app_url('/api/superadmin/school-delete') ?>', { school_id: id }, { loadingTitle: 'Menghapus Sekolah...' })
    .then(res => AppModal.showUpdated(res.message, 'Sekolah Dihapus', () => loadSchools())).catch(() => {});
}

$('#filterSearch').on('input', loadSchools);
$('#filterStatus').on('change', loadSchools);
$(loadSchools);
</script>
