<?php $slug = $school['slug']; ?>
<div class="space-y-5">
  <!-- Banner Informasi Lokasi Global Sekolah -->
  <div class="bg-white rounded-2xl p-4 border border-blue-100 shadow-[0_2px_12px_rgba(15,23,42,0.03)] flex flex-col lg:flex-row lg:items-center justify-between gap-3">
    <div class="flex items-center gap-3 min-w-0">
      <div class="w-10 h-10 rounded-xl bg-[#EEF4FF] text-[#1E5BF0] flex items-center justify-center shrink-0">
        <i class="fa-solid fa-earth-asia text-base"></i>
      </div>
      <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
          <span class="text-xs font-extrabold text-slate-800">Lokasi Global Sekolah:</span>
          <span id="bannerLocName" class="text-xs font-bold text-[#1E5BF0] truncate">-</span>
          <span id="bannerLocMode" class="px-2 py-0.5 rounded-full bg-blue-50 text-[#1E5BF0] text-[10px] font-extrabold">-</span>
        </div>
        <div id="bannerLocCoords" class="text-[11px] font-mono text-slate-500 mt-0.5">Memuat koordinat global...</div>
      </div>
    </div>
    <div class="flex flex-wrap gap-2 shrink-0">
      <button type="button" onclick="applyGlobalToAllQuick()" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
        <i class="fa-solid fa-arrows-rotate mr-1"></i> Samakan Semua ke Global
      </button>
      <a href="<?= Tenant::schoolLocalUrl($slug, 'admin/settings') ?>" class="px-4 py-2 rounded-xl bg-[#1E5BF0] hover:bg-[#194ED3] text-white text-xs font-extrabold shadow-sm transition">
        <i class="fa-solid fa-location-crosshairs mr-1"></i> Atur Lat/Lng Global
      </a>
    </div>
  </div>

  <!-- Action Bar -->
  <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm flex flex-col lg:flex-row gap-3 items-stretch lg:items-center justify-between">
    <div class="relative flex-1">
      <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
      <input type="text" id="empSearch" placeholder="Cari NIP, nama, email, jabatan..." class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:border-blue-500">
    </div>
    <div class="flex flex-wrap gap-2">
      <a href="<?= Tenant::schoolLocalUrl($slug, 'admin/shifts') ?>" class="px-4 py-2.5 rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 text-xs font-extrabold transition inline-flex items-center">
        <i class="fa-solid fa-clock mr-1.5"></i> Kelola Shift &amp; Jam Kerja
      </a>
      <?php if (Tenant::hasFeature($school, 'excel_import')): ?>
      <button type="button" onclick="openImportEmpModal()" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold shadow transition">
        <i class="fa-solid fa-file-excel mr-1"></i> Import Excel (.xlsx)
      </button>
      <?php endif; ?>
      <button type="button" onclick="openEmpModal()" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-extrabold shadow transition">
        <i class="fa-solid fa-user-plus mr-1"></i> Tambah Pegawai
      </button>
    </div>
  </div>

  <!-- Employees Table -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-blue-50/80 text-blue-900 text-xs uppercase">
          <tr>
            <th class="text-left px-5 py-3.5">Pegawai</th>
            <th class="text-left px-4 py-3.5">Shift Default</th>
            <th class="text-left px-4 py-3.5">Koordinat & Custom Jadwal</th>
            <th class="text-center px-4 py-3.5">Status</th>
            <th class="text-right px-5 py-3.5">Aksi</th>
          </tr>
        </thead>
        <tbody id="empTableBody" class="divide-y divide-slate-100"></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Tambah / Edit Pegawai -->
<div id="empModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
  <div class="modal-pop bg-white rounded-2xl shadow-2xl max-w-2xl w-full my-auto overflow-hidden">
    <div class="bg-blue-700 text-white px-6 py-4 flex items-center justify-between">
      <h3 id="empModalTitle" class="font-extrabold text-base">Tambah Pegawai</h3>
      <button type="button" onclick="$('#empModal').addClass('hidden').removeClass('flex')" class="text-white/80"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form id="empForm" class="p-6 space-y-4 max-h-[82vh] overflow-y-auto">
      <input type="hidden" id="empId" value="0">
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">NIP / NIK</label>
          <input type="text" id="empNip" placeholder="PEG-001" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Nama Lengkap *</label>
          <input type="text" id="empName" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
      </div>
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Email Login PWA *</label>
          <input type="email" id="empEmail" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">No HP / WA</label>
          <input type="text" id="empPhone" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
      </div>
      <div class="grid sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Jabatan</label>
          <input type="text" id="empPosition" placeholder="Guru / Staf TU" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Shift Default</label>
          <select id="empShift" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm"></select>
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Password <span class="text-[10px] text-slate-400">(kosong=tetap)</span></label>
          <input type="text" id="empPassword" placeholder="Pegawai123!" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
      </div>

      <div class="bg-[#F8FBFF] border border-blue-100 rounded-xl p-4 space-y-3">
        <div class="flex items-center justify-between gap-2">
          <div class="text-xs font-extrabold text-blue-900"><i class="fa-solid fa-building mr-1"></i> Lokasi Kantor / Koordinat Absen Pegawai</div>
          <a href="<?= Tenant::schoolLocalUrl($slug, 'admin/offices') ?>" class="text-[11px] font-bold text-[#1E5BF0] hover:underline">+ Kelola Kantor</a>
        </div>
        <div class="flex flex-wrap gap-3 text-xs">
          <label class="inline-flex items-center gap-1.5 font-bold text-slate-700 cursor-pointer">
            <input type="radio" name="empLocMode" value="global" checked class="text-[#1E5BF0]">
            <span>Global Sekolah</span>
          </label>
          <label class="inline-flex items-center gap-1.5 font-bold text-slate-700 cursor-pointer">
            <input type="radio" name="empLocMode" value="office" class="text-[#1E5BF0]">
            <span>Pilih Kantor (Master Kantor)</span>
          </label>
          <label class="inline-flex items-center gap-1.5 font-bold text-slate-700 cursor-pointer">
            <input type="radio" name="empLocMode" value="custom" class="text-[#1E5BF0]">
            <span>Koordinat Custom Manual</span>
          </label>
        </div>

        <div id="empOfficeSelectWrap" class="hidden pt-1 space-y-1.5">
          <label class="block text-[11px] font-bold text-blue-800">Pilih Kantor Penempatan Pegawai</label>
          <select id="empOfficeId" class="w-full px-3 py-2.5 rounded-lg border border-blue-200 text-xs font-bold bg-white"></select>
          <div id="empOfficeShiftHint" class="text-[11px] text-[#1E5BF0]"></div>
        </div>

        <div id="empCustomCoordFields" class="hidden grid grid-cols-3 gap-3 pt-1">
          <div>
            <label class="block text-[11px] font-bold text-blue-800 mb-1">Latitude Khusus</label>
            <input type="text" id="empLat" placeholder="-0.916500" class="w-full px-3 py-2 rounded-lg border border-blue-200 text-xs font-mono bg-white">
          </div>
          <div>
            <label class="block text-[11px] font-bold text-blue-800 mb-1">Longitude Khusus</label>
            <input type="text" id="empLng" placeholder="119.883200" class="w-full px-3 py-2 rounded-lg border border-blue-200 text-xs font-mono bg-white">
          </div>
          <div>
            <label class="block text-[11px] font-bold text-blue-800 mb-1">Radius (Meter)</label>
            <input type="number" id="empRadius" placeholder="200" class="w-full px-3 py-2 rounded-lg border border-blue-200 text-xs bg-white">
          </div>
        </div>
      </div>

      <div class="flex justify-end gap-2 pt-2">
        <button type="button" onclick="$('#empModal').addClass('hidden').removeClass('flex')" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-600">Batal</button>
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold shadow">Simpan Pegawai</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Custom Jadwal Shift & Koordinat Per Hari Per Orang -->
<div id="scheduleModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-3 overflow-y-auto">
  <div class="modal-pop bg-white rounded-2xl shadow-2xl max-w-4xl w-full my-auto overflow-hidden">
    <div class="bg-blue-800 text-white px-6 py-4 flex items-center justify-between">
      <div>
        <h3 class="font-extrabold text-base"><i class="fa-solid fa-calendar-week mr-2"></i>Jadwal Shift & Koordinat Custom Per Hari</h3>
        <div id="schedEmpTitle" class="text-xs text-blue-200 mt-0.5">-</div>
      </div>
      <button type="button" onclick="$('#scheduleModal').addClass('hidden').removeClass('flex')" class="text-white/80"><i class="fa-solid fa-xmark text-lg"></i></button>
    </div>
    <div class="p-5 max-h-[82vh] overflow-y-auto space-y-5">
      <input type="hidden" id="schedUserId">
      <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 text-xs text-blue-900 flex flex-wrap items-center justify-between gap-2">
        <span><i class="fa-solid fa-circle-info mr-1"></i> Klik peta di bawah untuk mengambil koordinat Latitude & Longitude dengan cepat, lalu klik tombol <b>"Tempel"</b> di baris hari yang diinginkan.</span>
        <span id="pickedCoordBadge" class="font-mono font-bold bg-white px-2.5 py-1 rounded-lg border border-blue-200">Klik peta untuk pilih titik</span>
      </div>

      <div id="schedMap" class="h-48 w-full rounded-xl border border-slate-200 z-0"></div>

      <!-- Weekly Schedule Table (Senin - Minggu) -->
      <div>
        <h4 class="text-xs font-extrabold text-slate-700 uppercase mb-2">Pola Mingguan (Senin – Minggu)</h4>
        <div class="overflow-x-auto border border-slate-200 rounded-xl">
          <table class="w-full text-xs">
            <thead class="bg-slate-50 text-slate-600">
              <tr>
                <th class="px-3 py-2 text-left">Hari</th>
                <th class="px-2 py-2 text-left">Kantor</th>
                <th class="px-2 py-2 text-left">Shift</th>
                <th class="px-2 py-2 text-center">Libur</th>
                <th class="px-2 py-2 text-left">Koordinat Custom (Opsional)</th>
                <th class="px-2 py-2 text-center">Peta</th>
              </tr>
            </thead>
            <tbody id="weeklySchedRows" class="divide-y divide-slate-100"></tbody>
          </table>
        </div>
      </div>

      <!-- Specific Date Override -->
      <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-3">
        <div class="text-xs font-extrabold text-slate-700">Tambah Override Tanggal Khusus (Opsional — misal Dinas Luar / Ujian Cabang)</div>
        <div class="grid sm:grid-cols-7 gap-2">
          <input type="date" id="ovDate" class="px-2.5 py-2 rounded-lg border border-slate-200 text-xs bg-white">
          <select id="ovOffice" class="px-2.5 py-2 rounded-lg border border-slate-200 text-xs bg-white"></select>
          <select id="ovShift" class="px-2.5 py-2 rounded-lg border border-slate-200 text-xs bg-white"></select>
          <input type="text" id="ovLocName" placeholder="Nama lokasi" class="px-2.5 py-2 rounded-lg border border-slate-200 text-xs bg-white">
          <input type="text" id="ovLat" placeholder="Lat" class="px-2.5 py-2 rounded-lg border border-slate-200 text-xs font-mono bg-white">
          <input type="text" id="ovLng" placeholder="Lng" class="px-2.5 py-2 rounded-lg border border-slate-200 text-xs font-mono bg-white">
          <input type="number" id="ovRad" placeholder="Rad (m)" class="px-2.5 py-2 rounded-lg border border-slate-200 text-xs bg-white">
        </div>
        <div id="overrideListWrap" class="space-y-1.5"></div>
      </div>

      <div class="flex justify-end gap-2">
        <button type="button" onclick="$('#scheduleModal').addClass('hidden').removeClass('flex')" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-600">Tutup</button>
        <button type="button" onclick="saveEmployeeSchedule()" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold shadow">
          <i class="fa-solid fa-floppy-disk mr-1"></i> Simpan Jadwal & Koordinat
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Import Excel Pegawai (.XLSX ONLY, BUKAN CSV) -->
<div id="importEmpModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
  <div class="modal-pop bg-white rounded-2xl shadow-2xl max-w-2xl w-full my-auto overflow-hidden">
    <div class="bg-emerald-600 text-white px-6 py-4 flex items-center justify-between">
      <h3 class="font-extrabold text-base"><i class="fa-solid fa-file-excel mr-2"></i>Import Data Pegawai (Excel .xlsx)</h3>
      <button type="button" onclick="$('#importEmpModal').addClass('hidden').removeClass('flex')" class="text-white/80"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="p-6 space-y-4">
      <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-xs text-emerald-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <div class="font-extrabold">Hanya menerima file Microsoft Excel (.xlsx / .xls) — Bukan CSV!</div>
          <div class="mt-0.5">Sekaligus dapat mengatur Shift default & Koordinat Kantor tiap pegawai langsung di file Excel.</div>
        </div>
        <a href="<?= app_url('/api/school_admin/employees-template?school_slug=' . urlencode($slug)) ?>" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-bold text-xs whitespace-nowrap text-center hover:bg-emerald-700">
          <i class="fa-solid fa-download mr-1"></i> Template .xlsx
        </a>
      </div>

      <input type="file" id="empExcelInput" accept=".xlsx,.xls" class="block w-full text-sm text-slate-600 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:bg-blue-600 file:text-white file:font-bold border border-slate-200 rounded-xl p-2">
      <div id="empExcelPreview" class="hidden max-h-52 overflow-auto border border-slate-200 rounded-xl text-xs"></div>

      <div class="flex justify-end gap-2">
        <button type="button" onclick="$('#importEmpModal').addClass('hidden').removeClass('flex')" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-600">Batal</button>
        <button type="button" id="btnSubmitEmpImport" disabled onclick="submitEmpImport()" class="px-5 py-2.5 rounded-xl bg-emerald-600 disabled:opacity-40 text-white text-sm font-bold shadow">
          <i class="fa-solid fa-upload mr-1"></i> Proses Import Excel
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Master Shift -->
<div id="shiftMasterModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
  <div class="modal-pop bg-white rounded-2xl shadow-2xl max-w-xl w-full my-auto overflow-hidden">
    <div class="bg-indigo-700 text-white px-6 py-4 flex items-center justify-between">
      <h3 class="font-extrabold text-base"><i class="fa-solid fa-clock mr-2"></i>Kelola Master Jadwal Shift</h3>
      <button type="button" onclick="$('#shiftMasterModal').addClass('hidden').removeClass('flex')" class="text-white/80"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="p-6 space-y-4">
      <form id="shiftForm" class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
        <input type="hidden" id="shId" value="0">
        <div class="grid sm:grid-cols-2 gap-3">
          <input type="text" id="shName" required placeholder="Nama Shift (misal: Shift Pagi)" class="px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white">
          <input type="number" id="shTol" value="10" min="0" placeholder="Toleransi Telat (menit)" class="px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white">
        </div>
        <div class="grid grid-cols-3 gap-3 items-center">
          <div><label class="text-[10px] font-bold text-slate-500">Jam Masuk</label><input type="time" id="shStart" value="07:15" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs bg-white"></div>
          <div><label class="text-[10px] font-bold text-slate-500">Jam Pulang</label><input type="time" id="shEnd" value="15:00" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs bg-white"></div>
          <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700 mt-4"><input type="checkbox" id="shDefault"> Default</label>
        </div>
        <button type="submit" class="w-full py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold">Simpan Shift</button>
      </form>
      <div id="shiftMasterList" class="space-y-2 max-h-56 overflow-y-auto"></div>
    </div>
  </div>
</div>

<script>
const SLUG = '<?= Security::e($slug) ?>';
let empList = [];
let shiftList = [];
let officeList = [];
let schoolOffice = { lat: -0.9165, lng: 119.8832, radius: 200 };
let parsedEmpRows = [];
let schedMapObj = null, schedMarker = null, pickedLat = null, pickedLng = null;

function renderEmpShiftDropdown(officeId, selectedShiftId) {
  const of = officeId ? officeList.find(o => parseInt(o.id, 10) === parseInt(officeId, 10)) : null;
  const officeShiftIds = of && Array.isArray(of.shift_ids) ? of.shift_ids.map(Number) : [];

  let shOpts = '<option value="">-- Ikut Shift Default Sekolah --</option>';
  if (officeShiftIds.length) {
    shOpts += '<optgroup label="Shift di Kantor Terpilih">';
    shiftList.filter(s => officeShiftIds.includes(parseInt(s.id, 10))).forEach(s => {
      shOpts += `<option value="${s.id}" ${parseInt(selectedShiftId||0,10)===parseInt(s.id,10)?'selected':''}>${ApiClient.e(s.name)} (${s.start_time.substring(0,5)}-${s.end_time.substring(0,5)})</option>`;
    });
    shOpts += '</optgroup>';
    const others = shiftList.filter(s => !officeShiftIds.includes(parseInt(s.id, 10)));
    if (others.length) {
      shOpts += '<optgroup label="Shift Lainnya">';
      others.forEach(s => {
        shOpts += `<option value="${s.id}" ${parseInt(selectedShiftId||0,10)===parseInt(s.id,10)?'selected':''}>${ApiClient.e(s.name)} (${s.start_time.substring(0,5)}-${s.end_time.substring(0,5)})</option>`;
      });
      shOpts += '</optgroup>';
    }
    $('#empOfficeShiftHint').text(`Kantor "${of.name}" memiliki ${officeShiftIds.length} shift terdaftar (ditampilkan paling atas di pilihan Shift).`);
  } else {
    shiftList.forEach(s => {
      shOpts += `<option value="${s.id}" ${parseInt(selectedShiftId||0,10)===parseInt(s.id,10)?'selected':''}>${ApiClient.e(s.name)} (${s.start_time.substring(0,5)}-${s.end_time.substring(0,5)})</option>`;
    });
    $('#empOfficeShiftHint').text(of ? `Kantor "${of.name}" memakai semua shift sekolah.` : '');
  }
  $('#empShift').html(shOpts);
}

function loadEmployees() {
  ApiClient.get('<?= app_url('/api/school_admin/employees-list') ?>', { school_slug: SLUG, search: $('#empSearch').val() }).then(function (res) {
    empList = res.data.employees;
    shiftList = res.data.shifts;
    officeList = res.data.offices || [];
    schoolOffice = res.data.office;

    // Update Global Location Banner
    $('#bannerLocName').text(schoolOffice.location_name || 'Lokasi Global');
    $('#bannerLocMode').text(schoolOffice.force_global_location ? 'Global Penuh (Paksa)' : 'Global + Bisa Custom');
    $('#bannerLocCoords').text(`Lat: ${Number(schoolOffice.lat).toFixed(6)}  •  Lng: ${Number(schoolOffice.lng).toFixed(6)}  •  Radius: ${schoolOffice.radius} meter`);

    let ofOpts = '<option value="">-- Pilih Kantor --</option>';
    officeList.forEach(o => ofOpts += `<option value="${o.id}">[${ApiClient.e(o.code || 'KTR')}] ${ApiClient.e(o.name)} (Radius ${o.radius}m)</option>`);
    $('#empOfficeId').html(ofOpts);
    $('#ovOffice').html('<option value="">Default Pegawai</option>' + officeList.map(o => `<option value="${o.id}">${ApiClient.e(o.name)}</option>`).join(''));

    let shOpts = '<option value="">-- Ikut Shift Default Sekolah --</option>';
    shiftList.forEach(s => shOpts += `<option value="${s.id}">${ApiClient.e(s.name)} (${s.start_time.substring(0,5)}-${s.end_time.substring(0,5)})</option>`);
    $('#empShift, #ovShift').html(shOpts);

    if (!empList.length) {
      $('#empTableBody').html('<tr><td colspan="5" class="text-center py-10 text-slate-400">Belum ada data pegawai. Tambah manual atau Import Excel (.xlsx).</td></tr>');
      return;
    }

    let html = '';
    empList.forEach(function (e) {
      let locBadge = '';
      if (schoolOffice.force_global_location) {
        locBadge = `<span class="px-2 py-0.5 rounded-full bg-blue-50 text-[#1E5BF0] text-[10px] font-bold">Global Penuh</span> <span class="font-mono text-[11px] text-slate-400">${Number(schoolOffice.lat).toFixed(4)}, ${Number(schoolOffice.lng).toFixed(4)}</span>`;
      } else if (e.default_lat && e.default_lng) {
        locBadge = `<span class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-[10px] font-bold">Custom Manual</span> <span class="font-mono text-[11px] text-slate-600">${Number(e.default_lat).toFixed(4)}, ${Number(e.default_lng).toFixed(4)} (${e.default_radius || schoolOffice.radius}m)</span>`;
      } else if (e.default_office_id && e.office_name) {
        locBadge = `<span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold"><i class="fa-solid fa-building mr-0.5"></i> ${ApiClient.e(e.office_name)}</span> <span class="font-mono text-[11px] text-slate-400">(${e.office_radius_ref || schoolOffice.radius}m)</span>`;
      } else {
        locBadge = `<span class="px-2 py-0.5 rounded-full bg-blue-50 text-[#1E5BF0] text-[10px] font-bold">Global Sekolah</span> <span class="font-mono text-[11px] text-slate-400">${Number(schoolOffice.lat).toFixed(4)}, ${Number(schoolOffice.lng).toFixed(4)} (${schoolOffice.radius}m)</span>`;
      }
      html += `<tr class="hover:bg-blue-50/30">
        <td class="px-5 py-3.5">
          <div class="font-extrabold text-slate-800">${ApiClient.e(e.name)}</div>
          <div class="text-xs text-slate-500">${ApiClient.e(e.nip_nik || '-')} • ${ApiClient.e(e.position_title || 'Pegawai')}</div>
          <div class="text-[11px] text-blue-600">${ApiClient.e(e.email)}</div>
        </td>
        <td class="px-4 py-3.5 text-xs">
          <div class="font-bold text-slate-700">${ApiClient.e(e.shift_name || 'Default Sekolah')}</div>
          <div class="text-slate-400">${e.start_time ? e.start_time.substring(0,5) + ' - ' + e.end_time.substring(0,5) : '-'}</div>
        </td>
        <td class="px-4 py-3.5 text-xs">
          <div>${locBadge}</div>
          <div class="mt-1">
            <span class="px-2 py-0.5 rounded-full ${e.custom_schedule_count > 0 ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-500'} text-[10px] font-bold">
              ${e.custom_schedule_count} aturan harian custom
            </span>
          </div>
        </td>
        <td class="px-4 py-3.5 text-center">
          <span class="px-2.5 py-0.5 rounded-full text-xs font-bold ${parseInt(e.is_active,10)===1 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-500'}">
            ${parseInt(e.is_active,10)===1 ? 'Aktif' : 'Nonaktif'}
          </span>
        </td>
        <td class="px-5 py-3.5 text-right">
          <div class="inline-flex gap-1.5">
            <button type="button" onclick="openScheduleModal(${e.id})" class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold" title="Atur Shift & Koordinat Per Hari">
              <i class="fa-solid fa-calendar-day mr-1"></i>Shift/Lokasi
            </button>
            <button type="button" onclick="openEmpModal(${e.id})" class="px-2.5 py-1.5 rounded-lg bg-blue-100 text-blue-700 text-xs font-bold"><i class="fa-solid fa-pen"></i></button>
            <button type="button" onclick="deleteEmp(${e.id}, '${ApiClient.e(e.name)}')" class="px-2.5 py-1.5 rounded-lg bg-red-50 text-red-600 text-xs font-bold"><i class="fa-solid fa-trash"></i></button>
          </div>
        </td>
      </tr>`;
    });
    $('#empTableBody').html(html);
  });
}

function toggleEmpLocMode() {
  const mode = $('input[name="empLocMode"]:checked').val();
  $('#empOfficeSelectWrap').toggleClass('hidden', mode !== 'office');
  $('#empCustomCoordFields').toggleClass('hidden', mode !== 'custom');
  if (mode === 'office') {
    renderEmpShiftDropdown($('#empOfficeId').val(), $('#empShift').val());
  } else {
    renderEmpShiftDropdown(null, $('#empShift').val());
  }
}
$('input[name="empLocMode"]').on('change', toggleEmpLocMode);
$('#empOfficeId').on('change', function () {
  renderEmpShiftDropdown($(this).val(), $('#empShift').val());
});

async function applyGlobalToAllQuick() {
  const ok = await AppModal.confirm({
    title: 'Samakan Semua Pegawai ke Lokasi Global?',
    message: `Semua pegawai akan otomatis mengikuti titik Global (${Number(schoolOffice.lat).toFixed(5)}, ${Number(schoolOffice.lng).toFixed(5)}, radius ${schoolOffice.radius}m). Lanjutkan?`,
    confirmText: 'Ya, Samakan Semua'
  });
  if (!ok) return;
  ApiClient.post('<?= app_url('/api/school_admin/global-location-save') ?>', {
    school_slug: SLUG,
    office_location_name: schoolOffice.location_name,
    office_lat: schoolOffice.lat,
    office_lng: schoolOffice.lng,
    office_radius: schoolOffice.radius,
    force_global_location: !!schoolOffice.force_global_location,
    apply_to_all: true
  }, { loadingTitle: 'Menyinkronkan Lokasi Global...' })
  .then(res => AppModal.showUpdated(res.message, 'Berhasil Disamakan!', loadEmployees)).catch(() => {});
}

function openEmpModal(id) {
  const emp = id ? empList.find(x => parseInt(x.id,10) === parseInt(id,10)) : null;
  if (emp) {
    $('#empModalTitle').text('Edit Data Pegawai');
    $('#empId').val(emp.id);
    $('#empNip').val(emp.nip_nik || '');
    $('#empName').val(emp.name);
    $('#empEmail').val(emp.email);
    $('#empPhone').val(emp.phone || '');
    $('#empPosition').val(emp.position_title || '');
    $('#empOfficeId').val(emp.default_office_id || '');
    $('#empLat').val(emp.default_lat || '');
    $('#empLng').val(emp.default_lng || '');
    $('#empRadius').val(emp.default_radius || '');
    $('#empPassword').val('');

    let mode = 'global';
    if (emp.default_lat && emp.default_lng) mode = 'custom';
    else if (emp.default_office_id) mode = 'office';
    $(`input[name="empLocMode"][value="${mode}"]`).prop('checked', true);
    renderEmpShiftDropdown(mode === 'office' ? emp.default_office_id : null, emp.default_shift_id || '');
  } else {
    $('#empModalTitle').text('Tambah Pegawai Baru');
    $('#empForm')[0].reset();
    $('#empId').val(0);
    $('#empPassword').val('Pegawai123!');
    $('input[name="empLocMode"][value="global"]').prop('checked', true);
    renderEmpShiftDropdown(null, '');
  }
  toggleEmpLocMode();
  $('#empModal').removeClass('hidden').addClass('flex');
}

$('#empForm').on('submit', function (e) {
  e.preventDefault();
  const mode = $('input[name="empLocMode"]:checked').val();
  ApiClient.post('<?= app_url('/api/school_admin/employee-save') ?>', {
    school_slug: SLUG,
    id: parseInt($('#empId').val(), 10),
    nip_nik: $('#empNip').val().trim(),
    name: $('#empName').val().trim(),
    email: $('#empEmail').val().trim(),
    phone: $('#empPhone').val().trim(),
    position_title: $('#empPosition').val().trim(),
    default_office_id: mode === 'office' ? $('#empOfficeId').val() : '',
    default_shift_id: $('#empShift').val(),
    default_lat: mode === 'custom' ? $('#empLat').val().trim() : '',
    default_lng: mode === 'custom' ? $('#empLng').val().trim() : '',
    default_radius: mode === 'custom' ? $('#empRadius').val().trim() : '',
    password: $('#empPassword').val()
  }, { loadingTitle: 'Menyimpan Data Pegawai...' })
  .then(res => {
    $('#empModal').addClass('hidden').removeClass('flex');
    res.mode === 'updated' ? AppModal.showUpdated(res.message, null, loadEmployees) : AppModal.showSaved(res.message, null, loadEmployees);
  }).catch(() => {});
});

async function deleteEmp(id, name) {
  const ok = await AppModal.confirm({ title: 'Hapus Pegawai?', message: `Hapus data pegawai "${name}"?`, danger: true, confirmText: 'Ya, Hapus' });
  if (!ok) return;
  ApiClient.post('<?= app_url('/api/school_admin/employee-delete') ?>', { school_slug: SLUG, id: id })
    .then(res => AppModal.showUpdated(res.message, 'Dihapus', loadEmployees)).catch(() => {});
}

// Custom Schedule & Coordinates Per Day Per Employee
const DAY_NAMES = {1:'Senin',2:'Selasa',3:'Rabu',4:'Kamis',5:'Jumat',6:'Sabtu',7:'Minggu'};

function openScheduleModal(userId) {
  $('#schedUserId').val(userId);
  ApiClient.get('<?= app_url('/api/school_admin/employee-schedule-get') ?>', { school_slug: SLUG, user_id: userId }).then(function (res) {
    const emp = res.data.employee;
    const weeklyMap = {};
    (res.data.weekly || []).forEach(w => weeklyMap[parseInt(w.day_of_week, 10)] = w);

    $('#schedEmpTitle').text(emp.name + ' (' + (emp.nip_nik || '-') + ')');
    let rowsHtml = '';
    for (let d = 1; d <= 7; d++) {
      const r = weeklyMap[d] || {};
      let ofOpts = '<option value="">Default</option>';
      officeList.forEach(o => ofOpts += `<option value="${o.id}" ${parseInt(r.office_id||0,10)===parseInt(o.id,10)?'selected':''}>${ApiClient.e(o.name)}</option>`);

      let shOpts = '<option value="">Default</option>';
      shiftList.forEach(s => shOpts += `<option value="${s.id}" ${parseInt(r.shift_id||0,10)===parseInt(s.id,10)?'selected':''}>${ApiClient.e(s.name)}</option>`);

      rowsHtml += `<tr data-dow="${d}">
        <td class="px-3 py-2 font-bold text-slate-700">${DAY_NAMES[d]}</td>
        <td class="px-2 py-2"><select class="wOffice w-36 px-2 py-1.5 rounded-lg border border-slate-200">${ofOpts}</select></td>
        <td class="px-2 py-2"><select class="wShift w-36 px-2 py-1.5 rounded-lg border border-slate-200">${shOpts}</select></td>
        <td class="px-2 py-2 text-center"><input type="checkbox" class="wOff rounded text-blue-600" ${parseInt(r.is_day_off,10)===1?'checked':''}></td>
        <td class="px-2 py-2">
          <div class="flex gap-1">
            <input type="text" class="wLocName w-28 px-1.5 py-1.5 rounded-lg border border-slate-200 text-[11px]" placeholder="Nama custom" value="${ApiClient.e(r.location_name || '')}">
            <input type="text" class="wLat w-20 px-1.5 py-1.5 rounded-lg border border-slate-200 font-mono text-[11px]" placeholder="Lat" value="${r.custom_lat || ''}">
            <input type="text" class="wLng w-20 px-1.5 py-1.5 rounded-lg border border-slate-200 font-mono text-[11px]" placeholder="Lng" value="${r.custom_lng || ''}">
            <input type="number" class="wRad w-14 px-1.5 py-1.5 rounded-lg border border-slate-200 text-[11px]" placeholder="m" value="${r.custom_radius || ''}">
          </div>
        </td>
        <td class="px-2 py-2 text-center">
          <button type="button" onclick="applyPickedToRow(${d})" class="px-2 py-1 rounded-lg bg-blue-100 text-blue-700 font-bold hover:bg-blue-200">Tempel</button>
        </td>
      </tr>`;
    }
    $('#weeklySchedRows').html(rowsHtml);

    // Overrides list
    let ovHtml = '';
    (res.data.overrides || []).forEach(o => {
      const locInfo = o.office_name ? ('Kantor: ' + o.office_name) : (o.location_name || 'Custom');
      ovHtml += `<div class="flex items-center justify-between bg-white px-3 py-2 rounded-lg border border-slate-200 text-xs">
        <span><b>${ApiClient.e(o.specific_date)}</b> • ${ApiClient.e(locInfo)} • ${ApiClient.e(o.shift_name || 'Default')} ${o.custom_lat ? '(' + o.custom_lat + ', ' + o.custom_lng + ')' : ''}</span>
        <button type="button" onclick="delOverride(${o.id})" class="text-red-500 font-bold"><i class="fa-solid fa-trash"></i></button>
      </div>`;
    });
    $('#overrideListWrap').html(ovHtml);

    $('#scheduleModal').removeClass('hidden').addClass('flex');
    setTimeout(initSchedMap, 180);
  });
}

function initSchedMap() {
  if (schedMapObj) {
    schedMapObj.invalidateSize();
    return;
  }
  schedMapObj = L.map('schedMap').setView([schoolOffice.lat, schoolOffice.lng], 15);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OSM' }).addTo(schedMapObj);
  schedMarker = L.marker([schoolOffice.lat, schoolOffice.lng]).addTo(schedMapObj);
  pickedLat = schoolOffice.lat; pickedLng = schoolOffice.lng;
  $('#pickedCoordBadge').text(pickedLat.toFixed(6) + ', ' + pickedLng.toFixed(6));

  schedMapObj.on('click', function (e) {
    pickedLat = e.latlng.lat;
    pickedLng = e.latlng.lng;
    schedMarker.setLatLng([pickedLat, pickedLng]);
    $('#pickedCoordBadge').text(pickedLat.toFixed(6) + ', ' + pickedLng.toFixed(6));
    $('#ovLat').val(pickedLat.toFixed(6));
    $('#ovLng').val(pickedLng.toFixed(6));
  });
}

function applyPickedToRow(dow) {
  if (pickedLat === null) return;
  const $tr = $(`#weeklySchedRows tr[data-dow="${dow}"]`);
  $tr.find('.wLat').val(pickedLat.toFixed(6));
  $tr.find('.wLng').val(pickedLng.toFixed(6));
  if (!$tr.find('.wRad').val()) $tr.find('.wRad').val(schoolOffice.radius);
}

function saveEmployeeSchedule() {
  const weekly = [];
  $('#weeklySchedRows tr').each(function () {
    weekly.push({
      day_of_week: parseInt($(this).data('dow'), 10),
      office_id: $(this).find('.wOffice').val(),
      shift_id: $(this).find('.wShift').val(),
      is_day_off: $(this).find('.wOff').is(':checked'),
      location_name: $(this).find('.wLocName').val().trim(),
      custom_lat: $(this).find('.wLat').val().trim(),
      custom_lng: $(this).find('.wLng').val().trim(),
      custom_radius: $(this).find('.wRad').val().trim()
    });
  });

  const override = $('#ovDate').val() ? {
    specific_date: $('#ovDate').val(),
    office_id: $('#ovOffice').val(),
    shift_id: $('#ovShift').val(),
    location_name: $('#ovLocName').val().trim(),
    custom_lat: $('#ovLat').val().trim(),
    custom_lng: $('#ovLng').val().trim(),
    custom_radius: $('#ovRad').val().trim()
  } : null;

  ApiClient.post('<?= app_url('/api/school_admin/employee-schedule-save') ?>', {
    school_slug: SLUG,
    user_id: parseInt($('#schedUserId').val(), 10),
    weekly: weekly,
    override: override
  }, { loadingTitle: 'Menyimpan Jadwal & Koordinat...' })
  .then(res => {
    $('#scheduleModal').addClass('hidden').removeClass('flex');
    AppModal.showUpdated(res.message, null, loadEmployees);
  }).catch(() => {});
}

function delOverride(id) {
  ApiClient.post('<?= app_url('/api/school_admin/employee-schedule-delete-override') ?>', { school_slug: SLUG, id: id })
    .then(() => openScheduleModal($('#schedUserId').val())).catch(() => {});
}

// Import Excel Pegawai (.xlsx ONLY)
function openImportEmpModal() {
  parsedEmpRows = [];
  $('#empExcelInput').val('');
  $('#empExcelPreview').addClass('hidden').html('');
  $('#btnSubmitEmpImport').prop('disabled', true);
  $('#importEmpModal').removeClass('hidden').addClass('flex');
}

$('#empExcelInput').on('change', function () {
  const file = this.files[0];
  if (!file) return;
  ExcelHelper.parseExcelFile(file).then(function (rows) {
    parsedEmpRows = rows;
    const cols = Object.keys(rows[0]);
    let th = cols.map(c => `<th class="px-2 py-1.5 bg-blue-50 text-left">${ApiClient.e(c)}</th>`).join('');
    let tb = rows.slice(0, 8).map(r => '<tr>' + cols.map(c => `<td class="px-2 py-1 border-t">${ApiClient.e(r[c])}</td>`).join('') + '</tr>').join('');
    $('#empExcelPreview').removeClass('hidden').html(`<div class="p-2 bg-emerald-50 font-bold text-emerald-800">Terbaca ${rows.length} baris dari Excel (.xlsx) — Preview 8 baris pertama:</div><table class="w-full"><thead><tr>${th}</tr></thead><tbody>${tb}</tbody></table>`);
    $('#btnSubmitEmpImport').prop('disabled', false);
  }).catch(function (err) {
    $('#empExcelInput').val('');
    AppModal.showError(err, 'Format File Tidak Valid');
  });
});

function submitEmpImport() {
  ApiClient.post('<?= app_url('/api/school_admin/employees-import') ?>', {
    school_slug: SLUG,
    rows: parsedEmpRows
  }, { loadingTitle: 'Mengimpor Pegawai dari Excel...' })
  .then(res => {
    $('#importEmpModal').addClass('hidden').removeClass('flex');
    AppModal.showSaved(res.message, 'Import Excel Berhasil!', loadEmployees);
  }).catch(() => {});
}

// Shift Master
function openShiftMasterModal() {
  renderShiftMaster();
  $('#shiftMasterModal').removeClass('hidden').addClass('flex');
}

function renderShiftMaster() {
  let html = '';
  shiftList.forEach(s => {
    html += `<div class="flex items-center justify-between p-3 rounded-xl border border-slate-200 bg-white">
      <div>
        <div class="font-bold text-xs text-slate-800">${ApiClient.e(s.name)} ${parseInt(s.is_default,10)===1?'<span class="px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 text-[10px]">Default</span>':''}</div>
        <div class="text-[11px] text-slate-500">${s.start_time.substring(0,5)} - ${s.end_time.substring(0,5)} (Toleransi ${s.late_tolerance_minutes} mnt)</div>
      </div>
      <button type="button" onclick="deleteShift(${s.id})" class="text-red-500 hover:text-red-700 text-xs px-2"><i class="fa-solid fa-trash"></i></button>
    </div>`;
  });
  $('#shiftMasterList').html(html);
}

$('#shiftForm').on('submit', function (e) {
  e.preventDefault();
  ApiClient.post('<?= app_url('/api/school_admin/shift-save') ?>', {
    school_slug: SLUG,
    id: parseInt($('#shId').val(), 10),
    name: $('#shName').val().trim(),
    start_time: $('#shStart').val(),
    end_time: $('#shEnd').val(),
    late_tolerance_minutes: parseInt($('#shTol').val(), 10),
    is_default: $('#shDefault').is(':checked')
  }).then(res => {
    $('#shiftForm')[0].reset();
    loadEmployees();
    setTimeout(renderShiftMaster, 350);
    AppModal.showSaved(res.message);
  }).catch(() => {});
});

async function deleteShift(id) {
  const ok = await AppModal.confirm({ title: 'Hapus Shift?', message: 'Hapus jadwal shift ini?', danger: true });
  if (!ok) return;
  ApiClient.post('<?= app_url('/api/school_admin/shift-delete') ?>', { school_slug: SLUG, id: id })
    .then(() => { loadEmployees(); setTimeout(renderShiftMaster, 350); }).catch(() => {});
}

$('#empSearch').on('input', loadEmployees);
$(loadEmployees);
</script>
