<!-- Daftar Paket Langganan + Feature Checkbox Matrix -->
<div class="space-y-5">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
    <div>
      <h2 class="font-extrabold text-slate-800">Manajemen Paket & Centang Fitur</h2>
      <p class="text-xs text-slate-500">Buat paket baru dan cukup centang fitur apa saja yang aktif untuk sekolah pelanggan.</p>
    </div>
    <button type="button" onclick="openPackageModal()" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold shadow-md shadow-blue-600/25 transition shrink-0">
      <i class="fa-solid fa-plus mr-1.5"></i> Buat Paket Baru
    </button>
  </div>

  <div id="packagesGrid" class="grid md:grid-cols-2 lg:grid-cols-3 gap-5"></div>
</div>

<!-- Modal Paket & Checklist Fitur -->
<div id="packageModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
  <div class="modal-pop bg-white rounded-2xl shadow-2xl max-w-2xl w-full my-auto overflow-hidden">
    <div class="bg-blue-700 text-white px-6 py-4 flex items-center justify-between">
      <h3 id="pkgModalTitle" class="font-extrabold text-base"><i class="fa-solid fa-cubes mr-2"></i>Buat Paket Baru</h3>
      <button type="button" onclick="$('#packageModal').addClass('hidden').removeClass('flex')" class="text-white/80 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
    </div>
    <form id="packageForm" class="p-6 space-y-4 max-h-[82vh] overflow-y-auto">
      <input type="hidden" id="pkgId" value="0">
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Nama Paket *</label>
          <input type="text" id="pkgName" required placeholder="misal: Paket Gold Sekolah" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:border-blue-500">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Deskripsi Singkat</label>
          <input type="text" id="pkgDesc" placeholder="Keterangan keunggulan paket" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:border-blue-500">
        </div>
      </div>

      <div class="grid grid-cols-3 gap-3">
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Harga 1 Bulan</label>
          <input type="number" id="pkgPrice1" required min="0" value="250000" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm font-bold">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Harga 6 Bulan</label>
          <input type="number" id="pkgPrice6" required min="0" value="1350000" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm font-bold">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Harga 1 Tahun</label>
          <input type="number" id="pkgPrice12" required min="0" value="2500000" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm font-bold">
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Maksimal Pegawai</label>
          <input type="number" id="pkgMaxEmp" min="1" value="100" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Maksimal Siswa</label>
          <input type="number" id="pkgMaxStu" min="1" value="1000" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
      </div>

      <!-- FEATURE CHECKBOX LIST -->
      <div class="bg-blue-50/70 border border-blue-200 rounded-2xl p-4">
        <div class="flex items-center justify-between mb-3">
          <div class="text-xs font-extrabold text-blue-900"><i class="fa-solid fa-list-check mr-1"></i> Centang Fitur yang Termasuk dalam Paket</div>
          <div class="space-x-2 text-[11px] font-bold">
            <button type="button" onclick="$('.pkgFeatureCheck').prop('checked', true)" class="text-blue-700 hover:underline">Pilih Semua</button>
            <button type="button" onclick="$('.pkgFeatureCheck').prop('checked', false)" class="text-slate-500 hover:underline">Kosongkan</button>
          </div>
        </div>
        <div id="featureCheckboxes" class="grid sm:grid-cols-2 gap-2.5"></div>
      </div>

      <div class="flex justify-end gap-2 pt-2">
        <button type="button" onclick="$('#packageModal').addClass('hidden').removeClass('flex')" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-600">Batal</button>
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold shadow-md">
          <i class="fa-solid fa-floppy-disk mr-1"></i> Simpan Paket
        </button>
      </div>
    </form>
  </div>
</div>

<script>
let pkgList = [];
let featureReg = {};

function loadPackages() {
  ApiClient.get('<?= app_url('/api/superadmin/packages-list') ?>').then(function (res) {
    pkgList = res.data.packages;
    featureReg = res.data.feature_registry;

    let html = '';
    pkgList.forEach(function (p) {
      let featsHtml = '';
      Object.keys(featureReg).forEach(function (k) {
        const has = p.features.includes(k);
        featsHtml += `<li class="flex items-start gap-2 text-xs ${has ? 'text-slate-700 font-medium' : 'text-slate-300 line-through'}">
          <i class="fa-solid ${has ? 'fa-circle-check text-blue-600' : 'fa-circle-xmark text-slate-300'} mt-0.5"></i>
          <span>${ApiClient.e(featureReg[k].label)}</span>
        </li>`;
      });

      html += `<div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-6 flex flex-col">
        <div class="flex items-start justify-between gap-2">
          <div>
            <h3 class="text-lg font-extrabold text-blue-900">${ApiClient.e(p.name)}</h3>
            <p class="text-xs text-slate-500 mt-0.5">${ApiClient.e(p.description || '')}</p>
          </div>
          <span class="px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 text-[11px] font-bold shrink-0">${p.schools_count} Sekolah</span>
        </div>

        <div class="mt-4 pb-4 border-b border-slate-100">
          <div class="text-2xl font-extrabold text-blue-700">${ApiClient.rupiah(p.price_monthly)} <span class="text-xs font-semibold text-slate-400">/bln</span></div>
          <div class="text-[11px] text-slate-500 mt-1">6 Bln: <b>${ApiClient.rupiah(p.price_6months)}</b> • 1 Thn: <b>${ApiClient.rupiah(p.price_yearly)}</b></div>
          <div class="text-[11px] text-slate-500 mt-0.5">Kuota: <b>${p.max_employees} Pegawai</b> & <b>${p.max_students} Siswa</b></div>
        </div>

        <ul class="mt-4 space-y-2 flex-1">${featsHtml}</ul>

        <div class="mt-5 pt-4 border-t border-slate-100 flex gap-2">
          <button type="button" onclick="openPackageModal(${p.id})" class="flex-1 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition">
            <i class="fa-solid fa-pen-to-square mr-1"></i> Edit & Atur Fitur
          </button>
          <button type="button" onclick="deletePackage(${p.id}, '${ApiClient.e(p.name)}')" class="px-3.5 py-2.5 rounded-xl bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold transition">
            <i class="fa-solid fa-trash"></i>
          </button>
        </div>
      </div>`;
    });
    $('#packagesGrid').html(html);
  });
}

function renderFeatureCheckboxes(selected) {
  let html = '';
  Object.keys(featureReg).forEach(function (k) {
    const f = featureReg[k];
    const checked = selected.includes(k) ? 'checked' : '';
    html += `<label class="flex items-start gap-2.5 bg-white p-3 rounded-xl border border-blue-100 hover:border-blue-400 cursor-pointer transition">
      <input type="checkbox" class="pkgFeatureCheck mt-0.5 rounded text-blue-600 focus:ring-blue-500" value="${ApiClient.e(k)}" ${checked}>
      <div>
        <div class="text-xs font-bold text-slate-800 leading-snug">${ApiClient.e(f.label)}</div>
        <div class="text-[10px] text-slate-500 mt-0.5 leading-tight">${ApiClient.e(f.desc)}</div>
      </div>
    </label>`;
  });
  $('#featureCheckboxes').html(html);
}

function openPackageModal(id) {
  const pkg = id ? pkgList.find(x => parseInt(x.id, 10) === parseInt(id, 10)) : null;
  if (pkg) {
    $('#pkgModalTitle').html('<i class="fa-solid fa-pen mr-2"></i>Edit Paket & Checklist Fitur');
    $('#pkgId').val(pkg.id);
    $('#pkgName').val(pkg.name);
    $('#pkgDesc').val(pkg.description || '');
    $('#pkgPrice1').val(pkg.price_monthly);
    $('#pkgPrice6').val(pkg.price_6months);
    $('#pkgPrice12').val(pkg.price_yearly);
    $('#pkgMaxEmp').val(pkg.max_employees);
    $('#pkgMaxStu').val(pkg.max_students);
    renderFeatureCheckboxes(pkg.features || []);
  } else {
    $('#pkgModalTitle').html('<i class="fa-solid fa-cubes mr-2"></i>Buat Paket Baru');
    $('#packageForm')[0].reset();
    $('#pkgId').val(0);
    renderFeatureCheckboxes(Object.keys(featureReg));
  }
  $('#packageModal').removeClass('hidden').addClass('flex');
}

$('#packageForm').on('submit', function (e) {
  e.preventDefault();
  const checkedFeatures = [];
  $('.pkgFeatureCheck:checked').each(function () { checkedFeatures.push($(this).val()); });

  ApiClient.post('<?= app_url('/api/superadmin/package-save') ?>', {
    id: parseInt($('#pkgId').val(), 10),
    name: $('#pkgName').val().trim(),
    description: $('#pkgDesc').val().trim(),
    price_monthly: parseFloat($('#pkgPrice1').val()),
    price_6months: parseFloat($('#pkgPrice6').val()),
    price_yearly: parseFloat($('#pkgPrice12').val()),
    max_employees: parseInt($('#pkgMaxEmp').val(), 10),
    max_students: parseInt($('#pkgMaxStu').val(), 10),
    features: checkedFeatures
  }, { loadingTitle: 'Menyimpan Paket...' })
  .then(function (res) {
    $('#packageModal').addClass('hidden').removeClass('flex');
    const cb = () => loadPackages();
    res.mode === 'updated' ? AppModal.showUpdated(res.message, null, cb) : AppModal.showSaved(res.message, null, cb);
  }).catch(() => {});
});

async function deletePackage(id, name) {
  const ok = await AppModal.confirm({
    title: 'Hapus Paket?',
    message: `Apakah Anda yakin ingin menghapus paket "${name}"?`,
    confirmText: 'Ya, Hapus',
    danger: true
  });
  if (!ok) return;
  ApiClient.post('<?= app_url('/api/superadmin/package-delete') ?>', { id: id })
    .then(res => AppModal.showUpdated(res.message, 'Paket Dihapus', () => loadPackages())).catch(() => {});
}

$(loadPackages);
</script>
