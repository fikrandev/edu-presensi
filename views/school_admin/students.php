<?php $slug = $school['slug']; ?>
<div class="space-y-5">
  <!-- Filter & Action Bar -->
  <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm flex flex-col lg:flex-row gap-3 justify-between">
    <div class="flex flex-1 gap-2">
      <div class="relative flex-1">
        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
        <input type="text" id="stuSearch" placeholder="Cari NISN, nama siswa, kelas..." class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:border-blue-500">
      </div>
      <select id="stuClassFilter" class="px-3 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700">
        <option value="">Semua Kelas</option>
      </select>
    </div>
    <div class="flex flex-wrap gap-2">
      <button type="button" onclick="openPrintCards()" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-extrabold shadow transition">
        <i class="fa-solid fa-id-card mr-1"></i> Generate &amp; Download Kartu QR (PNG)
      </button>
      <?php if (Tenant::hasFeature($school, 'excel_import')): ?>
      <button type="button" onclick="openImportStuModal()" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold shadow transition">
        <i class="fa-solid fa-file-excel mr-1"></i> Import Excel (.xlsx)
      </button>
      <?php endif; ?>
      <button type="button" onclick="openStuModal()" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-extrabold shadow transition">
        <i class="fa-solid fa-plus mr-1"></i> Tambah Siswa
      </button>
    </div>
  </div>

  <!-- Students Table -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-blue-50/80 text-blue-900 text-xs uppercase">
          <tr>
            <th class="text-left px-5 py-3.5">NISN & Nama Siswa</th>
            <th class="text-center px-4 py-3.5">Kelas</th>
            <th class="text-center px-4 py-3.5">L/P</th>
            <th class="text-left px-4 py-3.5">Wali Siswa</th>
            <th class="text-left px-4 py-3.5">Kode QR</th>
            <th class="text-right px-5 py-3.5">Aksi</th>
          </tr>
        </thead>
        <tbody id="stuTableBody" class="divide-y divide-slate-100"></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Tambah/Edit Siswa -->
<div id="stuModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
  <div class="modal-pop bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden">
    <div class="bg-blue-700 text-white px-6 py-4 flex items-center justify-between">
      <h3 id="stuModalTitle" class="font-extrabold text-base">Tambah Siswa</h3>
      <button type="button" onclick="$('#stuModal').addClass('hidden').removeClass('flex')" class="text-white/80"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form id="stuForm" class="p-6 space-y-4">
      <input type="hidden" id="stuId" value="0">
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">NISN / NIS *</label>
          <input type="text" id="stuNisn" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Kelas *</label>
          <input type="text" id="stuClass" required placeholder="misal: 6A / X-IPA-1" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1">Nama Lengkap Siswa *</label>
        <input type="text" id="stuName" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>
      <div class="grid grid-cols-3 gap-3">
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">L / P</label>
          <select id="stuGender" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
            <option value="L">Laki-laki</option>
            <option value="P">Perempuan</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Nama Wali</label>
          <input type="text" id="stuGuardian" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">HP Wali</label>
          <input type="text" id="stuGuardianPhone" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
      </div>
      <div class="flex justify-end gap-2 pt-2">
        <button type="button" onclick="$('#stuModal').addClass('hidden').removeClass('flex')" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-600">Batal</button>
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-bold shadow">Simpan Siswa</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Preview QR Tunggal -->
<div id="qrPreviewModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
  <div class="modal-pop bg-white rounded-3xl shadow-2xl max-w-xs w-full overflow-hidden text-center">
    <div class="bg-gradient-to-r from-blue-800 to-blue-600 text-white p-5">
      <div class="text-xs font-bold text-blue-200"><?= Security::e($school['name']) ?></div>
      <div id="qrStuName" class="text-base font-extrabold mt-0.5">-</div>
      <div id="qrStuMeta" class="text-xs text-blue-100">-</div>
    </div>
    <div class="p-6">
      <div id="qrCanvasBox" class="inline-block p-3 bg-white border-2 border-blue-100 rounded-2xl shadow-sm"></div>
      <div id="qrTokenLabel" class="font-mono text-xs font-bold text-blue-700 mt-3"></div>
      <div class="mt-5 flex gap-2">
        <button type="button" onclick="$('#qrPreviewModal').addClass('hidden').removeClass('flex')" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600">Tutup</button>
        <a id="qrSinglePrintLink" href="#" target="_blank" class="flex-1 py-2.5 rounded-xl bg-blue-600 text-white text-xs font-bold">Download Kartu PNG</a>
      </div>
    </div>
  </div>
</div>

<!-- Modal Import Excel Siswa (.XLSX ONLY) -->
<div id="importStuModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
  <div class="modal-pop bg-white rounded-2xl shadow-2xl max-w-2xl w-full my-auto overflow-hidden">
    <div class="bg-emerald-600 text-white px-6 py-4 flex items-center justify-between">
      <h3 class="font-extrabold text-base"><i class="fa-solid fa-file-excel mr-2"></i>Import Data Siswa (Excel .xlsx)</h3>
      <button type="button" onclick="$('#importStuModal').addClass('hidden').removeClass('flex')" class="text-white/80"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="p-6 space-y-4">
      <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-xs text-emerald-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <div class="font-extrabold">Hanya menerima file Microsoft Excel (.xlsx / .xls) — Bukan CSV!</div>
          <div class="mt-0.5">QR Code absen unik otomatis dibuatkan untuk setiap siswa yang diimpor.</div>
        </div>
        <a href="<?= app_url('/api/school_admin/students-template?school_slug=' . urlencode($slug)) ?>" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-bold text-xs whitespace-nowrap text-center">
          <i class="fa-solid fa-download mr-1"></i> Template .xlsx
        </a>
      </div>
      <input type="file" id="stuExcelInput" accept=".xlsx,.xls" class="block w-full text-sm text-slate-600 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:bg-blue-600 file:text-white file:font-bold border border-slate-200 rounded-xl p-2">
      <div id="stuExcelPreview" class="hidden max-h-52 overflow-auto border border-slate-200 rounded-xl text-xs"></div>
      <div class="flex justify-end gap-2">
        <button type="button" onclick="$('#importStuModal').addClass('hidden').removeClass('flex')" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-600">Batal</button>
        <button type="button" id="btnSubmitStuImport" disabled onclick="submitStuImport()" class="px-5 py-2.5 rounded-xl bg-emerald-600 disabled:opacity-40 text-white text-sm font-bold shadow">Proses Import</button>
      </div>
    </div>
  </div>
</div>

<script>
const SLUG = '<?= Security::e($slug) ?>';
let stuList = [];
let parsedStuRows = [];

function loadStudents() {
  const cls = $('#stuClassFilter').val();
  ApiClient.get('<?= app_url('/api/school_admin/students-list') ?>', {
    school_slug: SLUG,
    search: $('#stuSearch').val(),
    class_name: cls
  }).then(function (res) {
    stuList = res.data.students;
    const classes = res.data.classes || [];
    let opts = '<option value="">Semua Kelas</option>';
    classes.forEach(c => opts += `<option value="${ApiClient.e(c)}" ${c===cls?'selected':''}>Kelas ${ApiClient.e(c)}</option>`);
    $('#stuClassFilter').html(opts);

    if (!stuList.length) {
      $('#stuTableBody').html('<tr><td colspan="6" class="text-center py-10 text-slate-400">Belum ada data siswa.</td></tr>');
      return;
    }

    let html = '';
    stuList.forEach(s => {
      html += `<tr class="hover:bg-blue-50/30">
        <td class="px-5 py-3.5"><div class="font-extrabold text-slate-800">${ApiClient.e(s.name)}</div><div class="text-xs font-mono text-slate-500">NISN: ${ApiClient.e(s.nisn)}</div></td>
        <td class="px-4 py-3.5 text-center"><span class="px-2.5 py-1 rounded-lg bg-blue-100 text-blue-800 text-xs font-extrabold">${ApiClient.e(s.class_name)}</span></td>
        <td class="px-4 py-3.5 text-center text-xs font-bold">${ApiClient.e(s.gender)}</td>
        <td class="px-4 py-3.5 text-xs"><div class="font-semibold text-slate-700">${ApiClient.e(s.guardian_name || '-')}</div><div class="text-slate-400">${ApiClient.e(s.guardian_phone || '')}</div></td>
        <td class="px-4 py-3.5 font-mono text-xs text-blue-700 font-bold">${ApiClient.e(s.qr_token)}</td>
        <td class="px-5 py-3.5 text-right">
          <div class="inline-flex gap-1.5">
            <button type="button" onclick="showQrModal(${s.id})" class="px-2.5 py-1.5 rounded-lg bg-indigo-100 text-indigo-700 text-xs font-bold" title="Lihat QR"><i class="fa-solid fa-qrcode"></i></button>
            <button type="button" onclick="openStuModal(${s.id})" class="px-2.5 py-1.5 rounded-lg bg-blue-100 text-blue-700 text-xs font-bold"><i class="fa-solid fa-pen"></i></button>
            <button type="button" onclick="deleteStu(${s.id}, '${ApiClient.e(s.name)}')" class="px-2.5 py-1.5 rounded-lg bg-red-50 text-red-600 text-xs font-bold"><i class="fa-solid fa-trash"></i></button>
          </div>
        </td>
      </tr>`;
    });
    $('#stuTableBody').html(html);
  });
}

function openPrintCards() {
  const cls = $('#stuClassFilter').val();
  const url = '<?= Tenant::schoolLocalUrl($slug, 'admin/student-cards') ?>' + (cls ? '?class_name=' + encodeURIComponent(cls) : '');
  window.open(url, '_blank');
}

function showQrModal(id) {
  const s = stuList.find(x => parseInt(x.id,10) === parseInt(id,10));
  if (!s) return;
  $('#qrStuName').text(s.name);
  $('#qrStuMeta').text('NISN: ' + s.nisn + ' • Kelas ' + s.class_name);
  $('#qrTokenLabel').text(s.qr_token);
  $('#qrSinglePrintLink').attr('href', '<?= Tenant::schoolLocalUrl($slug, 'admin/student-cards') ?>?student_id=' + s.id);
  $('#qrCanvasBox').html('');
  new QRCode(document.getElementById('qrCanvasBox'), { text: s.qr_token, width: 170, height: 170, colorDark: '#1e3a8a', colorLight: '#ffffff' });
  $('#qrPreviewModal').removeClass('hidden').addClass('flex');
}

function openStuModal(id) {
  const s = id ? stuList.find(x => parseInt(x.id,10) === parseInt(id,10)) : null;
  if (s) {
    $('#stuModalTitle').text('Edit Data Siswa');
    $('#stuId').val(s.id);
    $('#stuNisn').val(s.nisn);
    $('#stuName').val(s.name);
    $('#stuClass').val(s.class_name);
    $('#stuGender').val(s.gender);
    $('#stuGuardian').val(s.guardian_name || '');
    $('#stuGuardianPhone').val(s.guardian_phone || '');
  } else {
    $('#stuModalTitle').text('Tambah Siswa Baru');
    $('#stuForm')[0].reset();
    $('#stuId').val(0);
  }
  $('#stuModal').removeClass('hidden').addClass('flex');
}

$('#stuForm').on('submit', function (e) {
  e.preventDefault();
  ApiClient.post('<?= app_url('/api/school_admin/student-save') ?>', {
    school_slug: SLUG,
    id: parseInt($('#stuId').val(), 10),
    nisn: $('#stuNisn').val().trim(),
    name: $('#stuName').val().trim(),
    class_name: $('#stuClass').val().trim(),
    gender: $('#stuGender').val(),
    guardian_name: $('#stuGuardian').val().trim(),
    guardian_phone: $('#stuGuardianPhone').val().trim()
  }, { loadingTitle: 'Menyimpan Siswa...' })
  .then(res => {
    $('#stuModal').addClass('hidden').removeClass('flex');
    res.mode === 'updated' ? AppModal.showUpdated(res.message, null, loadStudents) : AppModal.showSaved(res.message, null, loadStudents);
  }).catch(() => {});
});

async function deleteStu(id, name) {
  const ok = await AppModal.confirm({ title: 'Hapus Siswa?', message: `Hapus data siswa "${name}"?`, danger: true, confirmText: 'Ya, Hapus' });
  if (!ok) return;
  ApiClient.post('<?= app_url('/api/school_admin/student-delete') ?>', { school_slug: SLUG, id: id })
    .then(res => AppModal.showUpdated(res.message, 'Dihapus', loadStudents)).catch(() => {});
}

function openImportStuModal() {
  parsedStuRows = [];
  $('#stuExcelInput').val('');
  $('#stuExcelPreview').addClass('hidden').html('');
  $('#btnSubmitStuImport').prop('disabled', true);
  $('#importStuModal').removeClass('hidden').addClass('flex');
}

$('#stuExcelInput').on('change', function () {
  const file = this.files[0];
  if (!file) return;
  ExcelHelper.parseExcelFile(file).then(rows => {
    parsedStuRows = rows;
    const cols = Object.keys(rows[0]);
    let th = cols.map(c => `<th class="px-2 py-1.5 bg-blue-50 text-left">${ApiClient.e(c)}</th>`).join('');
    let tb = rows.slice(0, 8).map(r => '<tr>' + cols.map(c => `<td class="px-2 py-1 border-t">${ApiClient.e(r[c])}</td>`).join('') + '</tr>').join('');
    $('#stuExcelPreview').removeClass('hidden').html(`<div class="p-2 bg-emerald-50 font-bold text-emerald-800">Terbaca ${rows.length} siswa dari Excel (.xlsx):</div><table class="w-full"><thead><tr>${th}</tr></thead><tbody>${tb}</tbody></table>`);
    $('#btnSubmitStuImport').prop('disabled', false);
  }).catch(err => {
    $('#stuExcelInput').val('');
    AppModal.showError(err, 'Format File Tidak Valid');
  });
});

function submitStuImport() {
  ApiClient.post('<?= app_url('/api/school_admin/students-import') ?>', { school_slug: SLUG, rows: parsedStuRows }, { loadingTitle: 'Mengimpor Siswa dari Excel...' })
    .then(res => {
      $('#importStuModal').addClass('hidden').removeClass('flex');
      AppModal.showSaved(res.message, 'Import Siswa Berhasil!', loadStudents);
    }).catch(() => {});
}

$('#stuSearch').on('input', loadStudents);
$('#stuClassFilter').on('change', loadStudents);
$(loadStudents);
</script>
