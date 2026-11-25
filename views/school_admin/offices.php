<?php $slug = $school['slug']; ?>
<div class="space-y-6">
  <!-- Section Header -->
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-[#1E5BF0] text-white flex items-center justify-center shadow-md shadow-blue-600/20">
        <i class="fa-solid fa-building"></i>
      </div>
      <div>
        <h2 class="text-base font-extrabold text-slate-800">Kelola Kantor / Lokasi &amp; Shift di Dalam Kantor</h2>
        <p class="text-xs text-slate-400">Tambah cabang/gedung kantor, masukkan shift-shift yang berlaku di dalamnya, lalu sesuaikan ke pegawai.</p>
      </div>
    </div>
    <div class="flex flex-wrap gap-2">
      <a href="<?= Tenant::schoolLocalUrl($slug, 'admin/shifts') ?>" class="px-4 py-2.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-extrabold transition">
        <i class="fa-solid fa-clock text-[#1E5BF0] mr-1"></i> Kelola Master Shift
      </a>
      <button type="button" onclick="openOfficeModal()" class="px-5 py-2.5 rounded-xl bg-[#1E5BF0] hover:bg-[#194ED3] text-white text-xs font-extrabold shadow-md shadow-blue-600/20 transition">
        <i class="fa-solid fa-plus mr-1.5"></i> Tambah Kantor Baru
      </button>
    </div>
  </div>

  <!-- 4 Summary Gradient Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="relative overflow-hidden rounded-2xl p-5 text-white bg-gradient-to-r from-[#2563EB] to-[#4F46E5] shadow-md shadow-blue-600/20">
      <div class="text-[10px] font-extrabold uppercase tracking-wider text-blue-100">TOTAL KANTOR / LOKASI</div>
      <div id="kpiOfTotal" class="text-2xl lg:text-3xl font-extrabold mt-1.5">-</div>
      <div class="text-[11px] text-blue-100/90 mt-1">Titik absensi kantor terdaftar</div>
      <i class="fa-solid fa-building absolute -right-2 -bottom-2 text-6xl text-white/15"></i>
    </div>

    <div class="relative overflow-hidden rounded-2xl p-5 text-white bg-gradient-to-r from-[#059669] to-[#0D9488] shadow-md shadow-emerald-600/20">
      <div class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-100">KANTOR UTAMA (GLOBAL)</div>
      <div id="kpiOfDefault" class="text-base lg:text-lg font-extrabold mt-1.5 truncate">-</div>
      <div id="kpiOfDefaultRad" class="text-[11px] text-emerald-100/90 mt-1">-</div>
      <i class="fa-solid fa-location-dot absolute -right-2 -bottom-2 text-6xl text-white/15"></i>
    </div>

    <div class="relative overflow-hidden rounded-2xl p-5 text-white bg-gradient-to-r from-[#F97316] to-[#EA580C] shadow-md shadow-orange-500/20">
      <div class="text-[10px] font-extrabold uppercase tracking-wider text-orange-100">TOTAL SHIFT TERSEDIA</div>
      <div id="kpiOfShifts" class="text-2xl lg:text-3xl font-extrabold mt-1.5">-</div>
      <div class="text-[11px] text-orange-100/90 mt-1">Bisa dimasukkan ke tiap kantor</div>
      <i class="fa-solid fa-clock absolute -right-2 -bottom-2 text-6xl text-white/15"></i>
    </div>

    <div class="relative overflow-hidden rounded-2xl p-5 text-white bg-gradient-to-r from-[#EF233C] to-[#E11D48] shadow-md shadow-rose-600/20">
      <div class="text-[10px] font-extrabold uppercase tracking-wider text-rose-100">PEGAWAI TERDAFTAR DI KANTOR</div>
      <div id="kpiOfAssigned" class="text-2xl lg:text-3xl font-extrabold mt-1.5">-</div>
      <div id="kpiOfTotalEmp" class="text-[11px] text-rose-100/90 mt-1">dari - pegawai aktif</div>
      <i class="fa-solid fa-users absolute -right-2 -bottom-2 text-6xl text-white/15"></i>
    </div>
  </div>

  <!-- Offices Cards Grid -->
  <div id="officesGrid" class="grid md:grid-cols-2 xl:grid-cols-3 gap-5"></div>
</div>

<!-- MODAL TAMBAH / EDIT KANTOR + PILIH SHIFT DI DALAM KANTOR -->
<div id="officeModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
  <div class="modal-pop bg-white rounded-2xl shadow-2xl max-w-3xl w-full my-auto overflow-hidden">
    <div class="bg-[#1E5BF0] text-white px-6 py-4 flex items-center justify-between">
      <h3 id="officeModalTitle" class="font-extrabold text-base"><i class="fa-solid fa-building mr-2"></i>Tambah Kantor Baru</h3>
      <button type="button" onclick="$('#officeModal').addClass('hidden').removeClass('flex')" class="text-white/80 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
    </div>

    <form id="officeForm" class="p-6 space-y-4 max-h-[84vh] overflow-y-auto">
      <input type="hidden" id="ofId" value="0">

      <div class="grid sm:grid-cols-12 gap-3">
        <div class="sm:col-span-3">
          <label class="block text-xs font-bold text-slate-600 mb-1">Kode Kantor</label>
          <input type="text" id="ofCode" maxlength="12" placeholder="UTM / SD / SMP" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm font-mono uppercase focus:border-[#1E5BF0] outline-none">
        </div>
        <div class="sm:col-span-9">
          <label class="block text-xs font-bold text-slate-600 mb-1">Nama Kantor / Gedung / Cabang *</label>
          <input type="text" id="ofName" required placeholder="misal: Kampus Utama / Unit SMPIT / Kantor Yayasan" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold focus:border-[#1E5BF0] outline-none">
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1">Alamat Kantor (Opsional)</label>
        <input type="text" id="ofAddress" placeholder="Alamat lengkap kantor / unit" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:border-[#1E5BF0]">
      </div>

      <!-- Coordinate Picker with Paste Google Maps + Map -->
      <div class="bg-[#F8FBFF] border border-blue-100 rounded-2xl p-4 space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div class="text-xs font-extrabold text-blue-900"><i class="fa-solid fa-map-location-dot text-[#1E5BF0] mr-1"></i> Titik Lokasi &amp; Radius Kantor</div>
          <div class="flex flex-wrap gap-1.5">
            <input type="text" id="ofPasteMaps" placeholder="Paste -0.9165, 119.8832 / Link Maps" class="px-2.5 py-1.5 rounded-lg border border-blue-200 bg-white text-[11px] font-mono w-56">
            <button type="button" onclick="parseOfficePaste()" class="px-2.5 py-1.5 rounded-lg bg-slate-800 text-white text-[11px] font-bold">Ambil</button>
            <button type="button" onclick="useOfficeGps()" class="px-2.5 py-1.5 rounded-lg bg-[#1E5BF0] text-white text-[11px] font-bold"><i class="fa-solid fa-crosshairs mr-1"></i>GPS</button>
          </div>
        </div>

        <div id="officeModalMap" class="h-48 w-full rounded-xl border border-blue-100 z-0"></div>

        <div class="grid grid-cols-3 gap-3">
          <div>
            <label class="block text-[11px] font-bold text-blue-900 mb-1">Latitude *</label>
            <input type="text" id="ofLat" required class="w-full px-3 py-2 rounded-lg border border-blue-200 bg-white text-xs font-mono font-bold">
          </div>
          <div>
            <label class="block text-[11px] font-bold text-blue-900 mb-1">Longitude *</label>
            <input type="text" id="ofLng" required class="w-full px-3 py-2 rounded-lg border border-blue-200 bg-white text-xs font-mono font-bold">
          </div>
          <div>
            <label class="block text-[11px] font-bold text-blue-900 mb-1">Radius (Meter) *</label>
            <input type="number" id="ofRadius" min="20" value="150" required class="w-full px-3 py-2 rounded-lg border border-blue-200 bg-white text-xs font-bold">
          </div>
        </div>
      </div>

      <!-- MASUKKAN SHIFT-SHIFT DI DALAM KANTOR INI -->
      <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div>
            <div class="text-xs font-extrabold text-slate-800"><i class="fa-solid fa-clock text-[#1E5BF0] mr-1"></i> Shift yang Berlaku di Kantor Ini</div>
            <div class="text-[11px] text-slate-400">Centang shift mana saja yang dimasukkan ke dalam kantor ini (bisa lebih dari satu).</div>
          </div>
          <div class="flex items-center gap-2 text-[11px] font-bold">
            <button type="button" onclick="$('.ofShiftCheck').prop('checked', true)" class="text-[#1E5BF0] hover:underline">Pilih Semua</button>
            <button type="button" onclick="$('#quickShiftBox').toggleClass('hidden')" class="px-2.5 py-1 rounded-lg bg-[#EEF4FF] text-[#1E5BF0] hover:bg-blue-100">+ Buat Shift Cepat</button>
          </div>
        </div>

        <!-- Inline Quick Create Shift -->
        <div id="quickShiftBox" class="hidden bg-white border border-blue-200 rounded-xl p-3 space-y-2">
          <div class="text-[11px] font-extrabold text-[#1E5BF0]">Buat Shift Baru &amp; Langsung Masukkan ke Kantor Ini</div>
          <div class="grid sm:grid-cols-5 gap-2">
            <input type="text" id="qsName" placeholder="Nama Shift (misal: Pagi TK)" class="sm:col-span-2 px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs">
            <input type="time" id="qsStart" value="07:30" class="px-2 py-1.5 rounded-lg border border-slate-200 text-xs font-mono">
            <input type="time" id="qsEnd" value="13:00" class="px-2 py-1.5 rounded-lg border border-slate-200 text-xs font-mono">
            <button type="button" onclick="createQuickShiftForOffice()" class="px-3 py-1.5 rounded-lg bg-[#1E5BF0] text-white text-xs font-bold">Tambah</button>
          </div>
        </div>

        <div id="officeShiftsChecklist" class="grid sm:grid-cols-2 gap-2"></div>
      </div>

      <div class="flex flex-wrap gap-5 pt-1">
        <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
          <input type="checkbox" id="ofDefault" class="rounded text-[#1E5BF0] w-4 h-4">
          <span>Jadikan Kantor Utama (Sinkron Koordinat Global Sekolah)</span>
        </label>
        <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
          <input type="checkbox" id="ofActive" checked class="rounded text-[#1E5BF0] w-4 h-4">
          <span>Kantor Aktif</span>
        </label>
      </div>

      <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
        <button type="button" onclick="$('#officeModal').addClass('hidden').removeClass('flex')" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-600">Batal</button>
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#1E5BF0] hover:bg-[#194ED3] text-white text-sm font-extrabold shadow">
          <i class="fa-solid fa-floppy-disk mr-1"></i> Simpan Kantor &amp; Shift
        </button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL ATUR PEGAWAI KANTOR (BULK ASSIGN + OPTIONAL SHIFT) -->
<div id="officeAssignModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
  <div class="modal-pop bg-white rounded-2xl shadow-2xl max-w-lg w-full my-auto overflow-hidden">
    <div class="bg-[#1E5BF0] text-white px-6 py-4 flex items-center justify-between">
      <div>
        <h3 class="font-extrabold text-base"><i class="fa-solid fa-users mr-2"></i>Atur Pegawai di Kantor Ini</h3>
        <div id="oaSubtitle" class="text-xs text-blue-100 mt-0.5">-</div>
      </div>
      <button type="button" onclick="$('#officeAssignModal').addClass('hidden').removeClass('flex')" class="text-white/80 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
    </div>
    <div class="p-5 space-y-4">
      <input type="hidden" id="oaOfficeId">

      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1">Sekaligus Tetapkan Shift untuk Pegawai Terpilih (Opsional)</label>
        <select id="oaShiftSelect" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold bg-white"></select>
      </div>

      <div class="flex items-center justify-between gap-2">
        <input type="text" id="oaEmpSearch" placeholder="Cari nama / jabatan pegawai..." class="flex-1 px-3 py-2 rounded-xl border border-slate-200 text-xs outline-none focus:border-[#1E5BF0]">
        <button type="button" onclick="$('.oaEmpCheck:visible').prop('checked', true)" class="text-xs font-bold text-[#1E5BF0] hover:underline">Pilih Semua</button>
        <button type="button" onclick="$('.oaEmpCheck:visible').prop('checked', false)" class="text-xs font-bold text-slate-400 hover:underline">Reset</button>
      </div>

      <div id="oaEmpList" class="max-h-64 overflow-y-auto space-y-1.5 pr-1"></div>

      <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
        <button type="button" onclick="$('#officeAssignModal').addClass('hidden').removeClass('flex')" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-600">Batal</button>
        <button type="button" onclick="submitOfficeAssign()" class="px-5 py-2.5 rounded-xl bg-[#1E5BF0] hover:bg-[#194ED3] text-white text-sm font-extrabold shadow">
          <i class="fa-solid fa-check mr-1"></i> Simpan Pegawai Kantor
        </button>
      </div>
    </div>
  </div>
</div>

<script>
const SLUG = '<?= Security::e($slug) ?>';
let officesData = [];
let shiftsMaster = [];
let employeesMaster = [];
let ofMap = null, ofMarker = null, ofCircle = null;

function loadOffices() {
  ApiClient.get('<?= app_url('/api/school_admin/offices-list') ?>', { school_slug: SLUG }).then(res => {
    officesData = res.data.offices || [];
    shiftsMaster = res.data.shifts || [];
    employeesMaster = res.data.employees || [];

    const activeOffices = officesData.filter(o => parseInt(o.is_active, 10) === 1);
    const defOf = officesData.find(o => parseInt(o.is_default, 10) === 1) || officesData[0];
    const assignedEmps = employeesMaster.filter(e => e.default_office_id).length;

    $('#kpiOfTotal').text(activeOffices.length);
    $('#kpiOfDefault').text(defOf ? defOf.name : '-');
    $('#kpiOfDefaultRad').text(defOf ? `Radius ${defOf.radius}m • ${Number(defOf.latitude).toFixed(4)}, ${Number(defOf.longitude).toFixed(4)}` : '-');
    $('#kpiOfShifts').text(shiftsMaster.length);
    $('#kpiOfAssigned').text(assignedEmps);
    $('#kpiOfTotalEmp').text('dari ' + employeesMaster.length + ' pegawai aktif');

    if (!officesData.length) {
      $('#officesGrid').html('<div class="col-span-full bg-white rounded-2xl p-10 text-center text-slate-400 border border-slate-200">Belum ada kantor. Klik "Tambah Kantor Baru".</div>');
      return;
    }

    let html = '';
    officesData.forEach(of => {
      let shiftsBadges = '';
      if (of.shifts && of.shifts.length) {
        of.shifts.forEach(s => {
          const c = s.color || '#1E5BF0';
          shiftsBadges += `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold text-white shadow-sm" style="background:${ApiClient.e(c)}">
            <span>${ApiClient.e(s.name)}</span>
            <span class="opacity-85 font-mono text-[10px]">${s.start_time.substring(0,5)}-${s.end_time.substring(0,5)}</span>
          </span>`;
        });
      } else {
        shiftsBadges = '<span class="text-[11px] text-slate-400 italic">Belum ada shift dimasukkan — klik Edit untuk memasukkan shift</span>';
      }

      html += `<div class="bg-white rounded-2xl border border-slate-200/80 shadow-[0_2px_12px_rgba(15,23,42,0.03)] p-5 flex flex-col justify-between gap-4">
        <div class="space-y-3">
          <div class="flex items-start justify-between gap-2">
            <div class="flex items-center gap-3 min-w-0">
              <span class="px-2.5 py-1.5 rounded-xl bg-[#EEF4FF] text-[#1E5BF0] font-mono text-xs font-extrabold shrink-0">${ApiClient.e(of.code || 'KTR')}</span>
              <div class="min-w-0">
                <div class="font-extrabold text-slate-800 text-sm truncate">${ApiClient.e(of.name)}</div>
                <div class="text-[11px] text-slate-400 truncate">${ApiClient.e(of.address || 'Alamat belum diisi')}</div>
              </div>
            </div>
            ${parseInt(of.is_default,10)===1 ? '<span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600 text-[10px] font-extrabold shrink-0">Utama</span>' : ''}
          </div>

          <div class="rounded-xl bg-[#F8FAFC] border border-slate-100 p-3 text-xs flex items-center justify-between gap-2">
            <div class="font-mono text-[11px] text-slate-600 truncate">
              <i class="fa-solid fa-location-dot text-[#1E5BF0] mr-1"></i>${Number(of.latitude).toFixed(5)}, ${Number(of.longitude).toFixed(5)}
            </div>
            <span class="px-2 py-0.5 rounded-md bg-blue-50 text-[#1E5BF0] font-bold text-[10px] shrink-0">Radius ${of.radius}m</span>
          </div>

          <div>
            <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-2">Shift di Dalam Kantor (${of.shifts ? of.shifts.length : 0}):</div>
            <div class="flex flex-wrap gap-1.5">${shiftsBadges}</div>
          </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
          <button type="button" onclick="openOfficeAssignModal(${of.id})" class="px-3 py-2 rounded-xl bg-[#EEF4FF] hover:bg-blue-100 text-[#1E5BF0] text-xs font-extrabold transition">
            <i class="fa-solid fa-users mr-1"></i> ${of.employee_count || 0} Pegawai
          </button>
          <div class="flex gap-1.5">
            <button type="button" onclick="openOfficeModal(${of.id})" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-[#1E5BF0] text-xs font-bold transition">
              <i class="fa-solid fa-pen mr-1"></i>Edit &amp; Shift
            </button>
            <button type="button" onclick="deleteOffice(${of.id}, '${ApiClient.e(of.name)}')" class="px-2.5 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs font-bold transition">
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
        </div>
      </div>`;
    });
    $('#officesGrid').html(html);
  });
}

function renderOfficeShiftChecklist(selectedIds) {
  let html = '';
  shiftsMaster.forEach(s => {
    const checked = selectedIds.includes(parseInt(s.id, 10)) ? 'checked' : '';
    const color = s.color || '#1E5BF0';
    html += `<label class="flex items-center gap-2.5 p-2.5 rounded-xl bg-white border border-slate-200 hover:border-[#1E5BF0] cursor-pointer transition">
      <input type="checkbox" class="ofShiftCheck rounded text-[#1E5BF0] w-4 h-4" value="${s.id}" ${checked}>
      <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:${ApiClient.e(color)}"></span>
      <div class="min-w-0 flex-1">
        <div class="text-xs font-bold text-slate-800 truncate">${ApiClient.e(s.name)}</div>
        <div class="text-[10px] font-mono text-slate-400">${s.start_time.substring(0,5)} – ${s.end_time.substring(0,5)} (Tol ${s.late_tolerance_minutes}m)</div>
      </div>
    </label>`;
  });
  $('#officeShiftsChecklist').html(html || '<div class="col-span-full text-xs text-slate-400">Belum ada shift. Gunakan "+ Buat Shift Cepat" di atas.</div>');
}

function updateOfMap(lat, lng, rad) {
  if (!ofMap) {
    ofMap = L.map('officeModalMap').setView([lat, lng], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OSM' }).addTo(ofMap);
    ofMarker = L.marker([lat, lng], { draggable: true }).addTo(ofMap);
    ofCircle = L.circle([lat, lng], { radius: rad, color: '#1E5BF0', fillOpacity: 0.14 }).addTo(ofMap);
    ofMap.on('click', e => setOfCoords(e.latlng.lat, e.latlng.lng));
    ofMarker.on('dragend', () => { const p = ofMarker.getLatLng(); setOfCoords(p.lat, p.lng); });
  } else {
    ofMap.invalidateSize();
    ofMap.setView([lat, lng], 16);
    ofMarker.setLatLng([lat, lng]);
    ofCircle.setLatLng([lat, lng]).setRadius(rad);
  }
}

function setOfCoords(lat, lng) {
  $('#ofLat').val(Number(lat).toFixed(7));
  $('#ofLng').val(Number(lng).toFixed(7));
  updateOfMap(Number(lat), Number(lng), parseInt($('#ofRadius').val(), 10) || 150);
}

function parseOfficePaste() {
  const txt = $('#ofPasteMaps').val().trim();
  const m = txt.match(/(-?\d{1,2}\.\d+)\s*,\s*(-?\d{1,3}\.\d+)/);
  if (!m) return AppModal.showError('Format tidak dikenali. Contoh: -0.916500, 119.883200');
  setOfCoords(parseFloat(m[1]), parseFloat(m[2]));
}

function useOfficeGps() {
  if (!navigator.geolocation) return AppModal.showError('Browser tidak mendukung GPS.');
  AppModal.showLoading('Mendeteksi GPS...');
  navigator.geolocation.getCurrentPosition(
    pos => { AppModal.hideLoading(); setOfCoords(pos.coords.latitude, pos.coords.longitude); },
    () => { AppModal.hideLoading(); AppModal.showError('Gagal mengambil lokasi GPS.'); },
    { enableHighAccuracy: true, timeout: 10000 }
  );
}

$('#ofRadius').on('input', function () {
  if (ofCircle) ofCircle.setRadius(parseInt($(this).val(), 10) || 150);
});

function openOfficeModal(id) {
  const of = id ? officesData.find(x => parseInt(x.id, 10) === parseInt(id, 10)) : null;
  const fallback = officesData[0] || { latitude: -0.9165, longitude: 119.8832, radius: 150 };

  $('#quickShiftBox').addClass('hidden');
  $('#ofPasteMaps').val('');
  let lat, lng, rad, selShifts;

  if (of) {
    $('#officeModalTitle').html('<i class="fa-solid fa-pen mr-2"></i>Edit Kantor &amp; Shift');
    $('#ofId').val(of.id);
    $('#ofCode').val(of.code || '');
    $('#ofName').val(of.name);
    $('#ofAddress').val(of.address || '');
    lat = Number(of.latitude); lng = Number(of.longitude); rad = parseInt(of.radius, 10) || 150;
    $('#ofDefault').prop('checked', parseInt(of.is_default, 10) === 1);
    $('#ofActive').prop('checked', parseInt(of.is_active, 10) === 1);
    selShifts = of.shift_ids || [];
  } else {
    $('#officeModalTitle').html('<i class="fa-solid fa-building mr-2"></i>Tambah Kantor Baru');
    $('#officeForm')[0].reset();
    $('#ofId').val(0);
    lat = Number(fallback.latitude); lng = Number(fallback.longitude); rad = parseInt(fallback.radius, 10) || 150;
    $('#ofActive').prop('checked', true);
    selShifts = shiftsMaster.map(s => parseInt(s.id, 10));
  }

  $('#ofLat').val(lat.toFixed(7));
  $('#ofLng').val(lng.toFixed(7));
  $('#ofRadius').val(rad);
  renderOfficeShiftChecklist(selShifts);
  $('#officeModal').removeClass('hidden').addClass('flex');
  setTimeout(() => updateOfMap(lat, lng, rad), 180);
}

function createQuickShiftForOffice() {
  const name = $('#qsName').val().trim();
  if (!name) return AppModal.showError('Masukkan nama shift terlebih dahulu.');
  const currentlyChecked = [];
  $('.ofShiftCheck:checked').each(function () { currentlyChecked.push(parseInt($(this).val(), 10)); });

  ApiClient.post('<?= app_url('/api/school_admin/shift-save') ?>', {
    school_slug: SLUG,
    id: 0,
    name: name,
    start_time: $('#qsStart').val() || '07:15',
    end_time: $('#qsEnd').val() || '15:00',
    late_tolerance_minutes: 10
  }, { loadingTitle: 'Membuat Shift Cepat...' })
  .then(() => {
    $('#qsName').val('');
    $('#quickShiftBox').addClass('hidden');
    return ApiClient.get('<?= app_url('/api/school_admin/offices-list') ?>', { school_slug: SLUG });
  })
  .then(res => {
    shiftsMaster = res.data.shifts || [];
    const newest = shiftsMaster.find(s => s.name === name);
    if (newest) currentlyChecked.push(parseInt(newest.id, 10));
    renderOfficeShiftChecklist(currentlyChecked);
    AppModal.showSaved(`Shift "${name}" berhasil dibuat & langsung dicentang untuk kantor ini!`);
  }).catch(() => {});
}

$('#officeForm').on('submit', function (e) {
  e.preventDefault();
  const shiftIds = [];
  $('.ofShiftCheck:checked').each(function () { shiftIds.push(parseInt($(this).val(), 10)); });

  ApiClient.post('<?= app_url('/api/school_admin/office-save') ?>', {
    school_slug: SLUG,
    id: parseInt($('#ofId').val(), 10),
    code: $('#ofCode').val().trim(),
    name: $('#ofName').val().trim(),
    address: $('#ofAddress').val().trim(),
    latitude: parseFloat($('#ofLat').val()),
    longitude: parseFloat($('#ofLng').val()),
    radius: parseInt($('#ofRadius').val(), 10),
    is_default: $('#ofDefault').is(':checked'),
    is_active: $('#ofActive').is(':checked'),
    shift_ids: shiftIds
  }, { loadingTitle: 'Menyimpan Kantor & Shift...' })
  .then(res => {
    $('#officeModal').addClass('hidden').removeClass('flex');
    res.mode === 'updated' ? AppModal.showUpdated(res.message, null, loadOffices) : AppModal.showSaved(res.message, null, loadOffices);
  }).catch(() => {});
});

function openOfficeAssignModal(officeId) {
  const of = officesData.find(x => parseInt(x.id, 10) === parseInt(officeId, 10));
  if (!of) return;
  $('#oaOfficeId').val(of.id);
  $('#oaSubtitle').text(`${of.name} • Radius ${of.radius}m`);
  $('#oaEmpSearch').val('');

  const availableShifts = (of.shifts && of.shifts.length) ? of.shifts : shiftsMaster;
  let shOpts = '<option value="">-- Biarkan shift masing-masing pegawai --</option>';
  availableShifts.forEach(s => {
    shOpts += `<option value="${s.id}">${ApiClient.e(s.name)} (${s.start_time.substring(0,5)} – ${s.end_time.substring(0,5)})</option>`;
  });
  $('#oaShiftSelect').html(shOpts);

  let html = '';
  employeesMaster.forEach(e => {
    const checked = parseInt(e.default_office_id || 0, 10) === parseInt(of.id, 10) ? 'checked' : '';
    html += `<label class="oa-emp-row flex items-center justify-between gap-3 p-3 rounded-xl border border-slate-100 hover:bg-blue-50/40 cursor-pointer" data-search="${ApiClient.e((e.name + ' ' + (e.position_title || '')).toLowerCase())}">
      <div class="flex items-center gap-3 min-w-0">
        <input type="checkbox" class="oaEmpCheck rounded text-[#1E5BF0] w-4 h-4" value="${e.id}" ${checked}>
        <div class="min-w-0">
          <div class="text-xs font-bold text-slate-800 truncate">${ApiClient.e(e.name)}</div>
          <div class="text-[11px] text-slate-400 truncate">${ApiClient.e(e.position_title || 'Pegawai')} • Kantor: ${ApiClient.e(e.office_name || 'Global Sekolah')} • Shift: ${ApiClient.e(e.shift_name || 'Default')}</div>
        </div>
      </div>
    </label>`;
  });
  $('#oaEmpList').html(html || '<div class="text-center py-6 text-xs text-slate-400">Belum ada pegawai.</div>');
  $('#officeAssignModal').removeClass('hidden').addClass('flex');
}

$('#oaEmpSearch').on('input', function () {
  const q = $(this).val().toLowerCase().trim();
  $('.oa-emp-row').each(function () {
    $(this).toggle(!q || String($(this).data('search')).includes(q));
  });
});

function submitOfficeAssign() {
  const userIds = [];
  $('.oaEmpCheck:checked').each(function () { userIds.push(parseInt($(this).val(), 10)); });
  ApiClient.post('<?= app_url('/api/school_admin/office-assign-employees') ?>', {
    school_slug: SLUG,
    office_id: parseInt($('#oaOfficeId').val(), 10),
    shift_id: $('#oaShiftSelect').val(),
    user_ids: userIds
  }, { loadingTitle: 'Menyimpan Penempatan Pegawai...' })
  .then(res => {
    $('#officeAssignModal').addClass('hidden').removeClass('flex');
    AppModal.showUpdated(res.message, 'Berhasil Disimpan!', loadOffices);
  }).catch(() => {});
}

async function deleteOffice(id, name) {
  const ok = await AppModal.confirm({
    title: 'Hapus Kantor?',
    message: `Hapus kantor "${name}"? Pegawai yang ditempatkan di kantor ini akan kembali mengikuti Lokasi Global Sekolah.`,
    danger: true,
    confirmText: 'Ya, Hapus'
  });
  if (!ok) return;
  ApiClient.post('<?= app_url('/api/school_admin/office-delete') ?>', { school_slug: SLUG, id: id })
    .then(res => AppModal.showUpdated(res.message, 'Kantor Dihapus', loadOffices)).catch(() => {});
}

$(loadOffices);
</script>
