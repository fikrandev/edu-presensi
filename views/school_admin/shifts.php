<?php $slug = $school['slug']; ?>
<div class="space-y-6">
  <!-- Section Header -->
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-[#1E5BF0] text-white flex items-center justify-center shadow-md shadow-blue-600/20">
        <i class="fa-solid fa-clock"></i>
      </div>
      <div>
        <h2 class="text-base font-extrabold text-slate-800">Kelola Shift, Jam Masuk, Jam Pulang &amp; Aturan Terlambat</h2>
        <p class="text-xs text-slate-400">Buat nama shift sesuai kategori pegawai (Guru, Staf TU, Satpam, Shift Jumat, dll.) lalu sesuaikan ke pegawai</p>
      </div>
    </div>
    <button type="button" onclick="openShiftModal()" class="px-5 py-2.5 rounded-xl bg-[#1E5BF0] hover:bg-[#194ED3] text-white text-xs font-extrabold shadow-md shadow-blue-600/20 transition">
      <i class="fa-solid fa-plus mr-1.5"></i> Buat Shift Baru
    </button>
  </div>

  <!-- 4 Summary Gradient Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="relative overflow-hidden rounded-2xl p-5 text-white bg-gradient-to-r from-[#2563EB] to-[#4F46E5] shadow-md shadow-blue-600/20">
      <div class="text-[10px] font-extrabold uppercase tracking-wider text-blue-100">TOTAL SHIFT AKTIF</div>
      <div id="kpiShiftTotal" class="text-2xl lg:text-3xl font-extrabold mt-1.5">-</div>
      <div class="text-[11px] text-blue-100/90 mt-1">Aturan jam kerja tersedia</div>
      <i class="fa-solid fa-layer-group absolute -right-2 -bottom-2 text-6xl text-white/15"></i>
    </div>

    <div class="relative overflow-hidden rounded-2xl p-5 text-white bg-gradient-to-r from-[#059669] to-[#0D9488] shadow-md shadow-emerald-600/20">
      <div class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-100">SHIFT DEFAULT SEKOLAH</div>
      <div id="kpiShiftDefault" class="text-lg lg:text-xl font-extrabold mt-1.5 truncate">-</div>
      <div id="kpiShiftDefaultTime" class="text-[11px] text-emerald-100/90 mt-1">-</div>
      <i class="fa-solid fa-star absolute -right-2 -bottom-2 text-6xl text-white/15"></i>
    </div>

    <div class="relative overflow-hidden rounded-2xl p-5 text-white bg-gradient-to-r from-[#F97316] to-[#EA580C] shadow-md shadow-orange-500/20">
      <div class="text-[10px] font-extrabold uppercase tracking-wider text-orange-100">PEGAWAI SUDAH DIATUR SHIFT</div>
      <div id="kpiEmpAssigned" class="text-2xl lg:text-3xl font-extrabold mt-1.5">-</div>
      <div id="kpiEmpTotal" class="text-[11px] text-orange-100/90 mt-1">dari - pegawai aktif</div>
      <i class="fa-solid fa-user-clock absolute -right-2 -bottom-2 text-6xl text-white/15"></i>
    </div>

    <div class="relative overflow-hidden rounded-2xl p-5 text-white bg-gradient-to-r from-[#EF233C] to-[#E11D48] shadow-md shadow-rose-600/20">
      <div class="text-[10px] font-extrabold uppercase tracking-wider text-rose-100">BELUM PUNYA SHIFT KHUSUS</div>
      <div id="kpiEmpUnassigned" class="text-2xl lg:text-3xl font-extrabold mt-1.5">-</div>
      <div class="text-[11px] text-rose-100/90 mt-1">Otomatis ikut shift default</div>
      <i class="fa-solid fa-users-slash absolute -right-2 -bottom-2 text-6xl text-white/15"></i>
    </div>
  </div>

  <!-- Tabs -->
  <div class="flex flex-wrap gap-2">
    <button type="button" data-tab="master" class="shTabBtn px-4 py-2.5 rounded-xl bg-[#1E5BF0] text-white text-xs font-extrabold shadow-sm">
      <i class="fa-solid fa-clock mr-1.5"></i> Daftar Shift &amp; Aturan Jam
    </button>
    <button type="button" data-tab="assign" class="shTabBtn px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-600 text-xs font-extrabold">
      <i class="fa-solid fa-users-gear mr-1.5"></i> Penugasan Shift ke Pegawai
    </button>
  </div>

  <!-- TAB 1: MASTER SHIFTS -->
  <div id="tab_master" class="shTabPanel bg-white rounded-2xl border border-slate-200/70 shadow-[0_2px_12px_rgba(15,23,42,0.03)] overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-[#F8FAFC] text-slate-500 text-[11px] uppercase border-b border-slate-100">
          <tr>
            <th class="text-left px-5 py-3.5">Nama Shift &amp; Kode</th>
            <th class="text-center px-4 py-3.5">Jam Masuk – Pulang</th>
            <th class="text-left px-4 py-3.5">Aturan Keterlambatan &amp; Jendela Absen</th>
            <th class="text-center px-4 py-3.5">Pegawai</th>
            <th class="text-right px-5 py-3.5">Aksi</th>
          </tr>
        </thead>
        <tbody id="shiftsTableBody" class="divide-y divide-slate-100"></tbody>
      </table>
    </div>
  </div>

  <!-- TAB 2: EMPLOYEE SHIFT ASSIGNMENT -->
  <div id="tab_assign" class="shTabPanel hidden bg-white rounded-2xl border border-slate-200/70 shadow-[0_2px_12px_rgba(15,23,42,0.03)] overflow-hidden">
    <div class="p-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
      <div class="relative flex-1 min-w-[220px] max-w-md">
        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
        <input type="text" id="assignEmpSearch" placeholder="Cari nama pegawai atau jabatan..." class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-xs outline-none focus:border-[#1E5BF0]">
      </div>
      <a href="<?= Tenant::schoolLocalUrl($slug, 'admin/employees') ?>" class="text-xs font-bold text-[#1E5BF0] hover:underline">
        <i class="fa-solid fa-calendar-week mr-1"></i> Atur Shift &amp; Koordinat Custom Per Hari (Senin–Minggu) →
      </a>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-[#F8FAFC] text-slate-500 text-[11px] uppercase border-b border-slate-100">
          <tr>
            <th class="text-left px-5 py-3.5">Pegawai</th>
            <th class="text-left px-4 py-3.5">Jabatan</th>
            <th class="text-left px-4 py-3.5">Shift Utama Pegawai</th>
            <th class="text-center px-4 py-3.5">Jadwal Custom Harian</th>
            <th class="text-right px-5 py-3.5">Simpan</th>
          </tr>
        </thead>
        <tbody id="assignEmpTableBody" class="divide-y divide-slate-100"></tbody>
      </table>
    </div>
  </div>
</div>

<!-- MODAL TAMBAH / EDIT SHIFT LENGKAP -->
<div id="shiftModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
  <div class="modal-pop bg-white rounded-2xl shadow-2xl max-w-2xl w-full my-auto overflow-hidden">
    <div class="bg-[#1E5BF0] text-white px-6 py-4 flex items-center justify-between">
      <h3 id="shiftModalTitle" class="font-extrabold text-base"><i class="fa-solid fa-clock mr-2"></i>Buat Shift Baru</h3>
      <button type="button" onclick="$('#shiftModal').addClass('hidden').removeClass('flex')" class="text-white/80 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
    </div>

    <form id="shiftFullForm" class="p-6 space-y-4 max-h-[84vh] overflow-y-auto">
      <input type="hidden" id="sfId" value="0">

      <!-- Nama, Kode & Warna -->
      <div class="grid sm:grid-cols-12 gap-3">
        <div class="sm:col-span-6">
          <label class="block text-xs font-bold text-slate-600 mb-1">Nama Shift *</label>
          <input type="text" id="sfName" required placeholder="misal: Shift Guru Pagi / Staf TU / Satpam Malam" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold focus:border-[#1E5BF0] outline-none">
        </div>
        <div class="sm:col-span-3">
          <label class="block text-xs font-bold text-slate-600 mb-1">Kode Shift</label>
          <input type="text" id="sfCode" maxlength="10" placeholder="PG / TU / JMT" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm font-mono uppercase focus:border-[#1E5BF0] outline-none">
        </div>
        <div class="sm:col-span-3">
          <label class="block text-xs font-bold text-slate-600 mb-1">Warna Badge</label>
          <div class="flex items-center gap-2">
            <input type="color" id="sfColor" value="#1E5BF0" class="w-11 h-10 rounded-lg border border-slate-200 cursor-pointer p-0.5">
            <div class="flex gap-1">
              <button type="button" onclick="$('#sfColor').val('#1E5BF0')" class="w-5 h-5 rounded-full bg-[#1E5BF0]"></button>
              <button type="button" onclick="$('#sfColor').val('#059669')" class="w-5 h-5 rounded-full bg-[#059669]"></button>
              <button type="button" onclick="$('#sfColor').val('#F97316')" class="w-5 h-5 rounded-full bg-[#F97316]"></button>
              <button type="button" onclick="$('#sfColor').val('#7C3AED')" class="w-5 h-5 rounded-full bg-[#7C3AED]"></button>
            </div>
          </div>
        </div>
      </div>

      <!-- Jam Masuk & Jam Pulang -->
      <div class="grid sm:grid-cols-2 gap-4 bg-[#F8FBFF] border border-blue-100 rounded-2xl p-4">
        <div>
          <label class="block text-xs font-extrabold text-blue-900 mb-1"><i class="fa-solid fa-right-to-bracket text-[#1E5BF0] mr-1"></i> Jam Masuk *</label>
          <input type="time" id="sfStart" required value="07:15" class="w-full px-3.5 py-2.5 rounded-xl border border-blue-200 bg-white text-base font-mono font-extrabold text-slate-800">
        </div>
        <div>
          <label class="block text-xs font-extrabold text-blue-900 mb-1"><i class="fa-solid fa-right-from-bracket text-emerald-600 mr-1"></i> Jam Pulang *</label>
          <input type="time" id="sfEnd" required value="15:00" class="w-full px-3.5 py-2.5 rounded-xl border border-blue-200 bg-white text-base font-mono font-extrabold text-slate-800">
        </div>
      </div>

      <!-- Aturan Keterlambatan & Jendela Absen -->
      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Buka Absen Sebelum (mnt)</label>
          <input type="number" id="sfOpenBefore" min="0" max="480" value="60" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm font-bold">
          <div class="text-[10px] text-slate-400 mt-0.5">0 = bebas kapan saja</div>
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Toleransi Terlambat (mnt)</label>
          <input type="number" id="sfTolerance" min="0" max="300" value="10" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-amber-700">
          <div class="text-[10px] text-slate-400 mt-0.5">Lewat ini = Terlambat</div>
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Batas Maks Telat (mnt)</label>
          <input type="number" id="sfMaxLate" min="0" max="720" value="0" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-rose-700">
          <div class="text-[10px] text-slate-400 mt-0.5">0 = tidak diblokir</div>
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Toleransi Pulang Awal (mnt)</label>
          <input type="number" id="sfEarlyOut" min="0" max="240" value="0" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm font-bold">
          <div class="text-[10px] text-slate-400 mt-0.5">Opsional</div>
        </div>
      </div>

      <!-- LIVE TIMELINE PREVIEW -->
      <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4">
        <div class="text-[11px] font-extrabold text-slate-600 uppercase mb-2.5"><i class="fa-solid fa-eye text-[#1E5BF0] mr-1"></i> Simulasi Aturan Waktu Shift Ini</div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center text-xs">
          <div class="bg-white rounded-xl p-2.5 border border-slate-100">
            <div class="text-[10px] text-slate-400 font-bold">Absen Dibuka</div>
            <div id="prevOpen" class="font-mono font-extrabold text-[#1E5BF0] text-sm mt-0.5">-</div>
          </div>
          <div class="bg-white rounded-xl p-2.5 border border-slate-100">
            <div class="text-[10px] text-slate-400 font-bold">Tepat Waktu s/d</div>
            <div id="prevOnTime" class="font-mono font-extrabold text-emerald-600 text-sm mt-0.5">-</div>
          </div>
          <div class="bg-white rounded-xl p-2.5 border border-slate-100">
            <div class="text-[10px] text-slate-400 font-bold">Mulai Terlambat</div>
            <div id="prevLate" class="font-mono font-extrabold text-amber-600 text-sm mt-0.5">-</div>
          </div>
          <div class="bg-white rounded-xl p-2.5 border border-slate-100">
            <div class="text-[10px] text-slate-400 font-bold">Batas / Pulang</div>
            <div id="prevEnd" class="font-mono font-extrabold text-slate-800 text-sm mt-0.5">-</div>
          </div>
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1">Keterangan / Peruntukan Shift (Opsional)</label>
        <input type="text" id="sfDesc" placeholder="misal: Khusus Guru Tetap hari Senin - Kamis" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>

      <div class="flex flex-wrap gap-5 pt-1">
        <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
          <input type="checkbox" id="sfDefault" class="rounded text-[#1E5BF0] w-4 h-4">
          <span>Jadikan Shift Default Sekolah</span>
        </label>
        <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
          <input type="checkbox" id="sfCrossDay" class="rounded text-[#1E5BF0] w-4 h-4">
          <span>Shift Lintas Hari (Malam)</span>
        </label>
        <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
          <input type="checkbox" id="sfActive" checked class="rounded text-[#1E5BF0] w-4 h-4">
          <span>Shift Aktif</span>
        </label>
      </div>

      <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
        <button type="button" onclick="$('#shiftModal').addClass('hidden').removeClass('flex')" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-600">Batal</button>
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#1E5BF0] hover:bg-[#194ED3] text-white text-sm font-extrabold shadow">
          <i class="fa-solid fa-floppy-disk mr-1"></i> Simpan Shift
        </button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL SESUAIKAN PEGAWAI KE SHIFT (BULK ASSIGN) -->
<div id="assignModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
  <div class="modal-pop bg-white rounded-2xl shadow-2xl max-w-lg w-full my-auto overflow-hidden">
    <div class="bg-[#1E5BF0] text-white px-6 py-4 flex items-center justify-between">
      <div>
        <h3 class="font-extrabold text-base"><i class="fa-solid fa-users mr-2"></i>Sesuaikan Pegawai Shift</h3>
        <div id="assignModalSub" class="text-xs text-blue-100 mt-0.5">-</div>
      </div>
      <button type="button" onclick="$('#assignModal').addClass('hidden').removeClass('flex')" class="text-white/80 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
    </div>
    <div class="p-5 space-y-4">
      <input type="hidden" id="assignShiftId">
      <div class="flex items-center justify-between gap-2">
        <input type="text" id="modalEmpFilter" placeholder="Filter nama / jabatan..." class="flex-1 px-3 py-2 rounded-xl border border-slate-200 text-xs outline-none focus:border-[#1E5BF0]">
        <button type="button" onclick="$('.assignEmpCheck:visible').prop('checked', true)" class="text-xs font-bold text-[#1E5BF0] hover:underline">Pilih Semua</button>
        <button type="button" onclick="$('.assignEmpCheck:visible').prop('checked', false)" class="text-xs font-bold text-slate-400 hover:underline">Reset</button>
      </div>

      <div id="assignChecklist" class="max-h-72 overflow-y-auto space-y-1.5 pr-1"></div>

      <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
        <button type="button" onclick="$('#assignModal').addClass('hidden').removeClass('flex')" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-600">Batal</button>
        <button type="button" onclick="submitShiftAssign()" class="px-5 py-2.5 rounded-xl bg-[#1E5BF0] hover:bg-[#194ED3] text-white text-sm font-extrabold shadow">
          <i class="fa-solid fa-check mr-1"></i> Simpan Penugasan
        </button>
      </div>
    </div>
  </div>
</div>

<script>
const SLUG = '<?= Security::e($slug) ?>';
let allShifts = [];
let allEmps = [];

function addMinutesToTime(hhmm, mins) {
  if (!hhmm || !hhmm.includes(':')) return '-';
  const parts = hhmm.split(':').map(Number);
  let total = parts[0] * 60 + parts[1] + parseInt(mins || 0, 10);
  total = ((total % 1440) + 1440) % 1440;
  const h = String(Math.floor(total / 60)).padStart(2, '0');
  const m = String(total % 60).padStart(2, '0');
  return `${h}:${m}`;
}

function updateTimelinePreview() {
  const st = $('#sfStart').val() || '07:15';
  const en = $('#sfEnd').val() || '15:00';
  const openB = parseInt($('#sfOpenBefore').val() || 0, 10);
  const tol = parseInt($('#sfTolerance').val() || 0, 10);
  const maxL = parseInt($('#sfMaxLate').val() || 0, 10);

  $('#prevOpen').text(openB > 0 ? addMinutesToTime(st, -openB) : 'Bebas');
  $('#prevOnTime').text(addMinutesToTime(st, tol));
  $('#prevLate').text(addMinutesToTime(st, tol + 1));
  $('#prevEnd').text(maxL > 0 ? (addMinutesToTime(st, maxL) + ' / ' + en) : en);
}

$('#sfStart, #sfEnd, #sfOpenBefore, #sfTolerance, #sfMaxLate').on('input change', updateTimelinePreview);

function loadShiftsPage() {
  ApiClient.get('<?= app_url('/api/school_admin/shifts-list') ?>', { school_slug: SLUG }).then(function (res) {
    allShifts = res.data.shifts || [];
    allEmps = res.data.employees || [];

    // Update KPI cards
    const activeShifts = allShifts.filter(s => parseInt(s.is_active ?? 1, 10) === 1);
    const defShift = allShifts.find(s => parseInt(s.is_default, 10) === 1) || allShifts[0];
    const assignedCnt = allEmps.filter(e => e.default_shift_id).length;

    $('#kpiShiftTotal').text(activeShifts.length);
    $('#kpiShiftDefault').text(defShift ? defShift.name : 'Belum Diatur');
    $('#kpiShiftDefaultTime').text(defShift ? `${defShift.start_time.substring(0,5)} – ${defShift.end_time.substring(0,5)} (Tol ${defShift.late_tolerance_minutes}m)` : '-');
    $('#kpiEmpAssigned').text(assignedCnt);
    $('#kpiEmpTotal').text('dari ' + allEmps.length + ' pegawai aktif');
    $('#kpiEmpUnassigned').text(Math.max(0, allEmps.length - assignedCnt));

    // Render Master Shifts table
    if (!allShifts.length) {
      $('#shiftsTableBody').html('<tr><td colspan="5" class="text-center py-10 text-slate-400">Belum ada shift. Klik "Buat Shift Baru" di atas.</td></tr>');
    } else {
      let html = '';
      allShifts.forEach(s => {
        const st = s.start_time.substring(0, 5);
        const en = s.end_time.substring(0, 5);
        const tol = parseInt(s.late_tolerance_minutes || 0, 10);
        const openB = parseInt(s.checkin_open_before ?? 60, 10);
        const maxL = parseInt(s.max_late_minutes || 0, 10);
        const color = s.color || '#1E5BF0';
        const onTimeUntil = addMinutesToTime(st, tol);
        const openAt = openB > 0 ? addMinutesToTime(st, -openB) : 'Bebas';

        html += `<tr class="hover:bg-slate-50/70">
          <td class="px-5 py-4">
            <div class="flex items-center gap-3">
              <span class="px-2.5 py-1.5 rounded-xl text-white text-[11px] font-extrabold font-mono shrink-0" style="background:${ApiClient.e(color)}">${ApiClient.e(s.code || 'SHF')}</span>
              <div>
                <div class="font-extrabold text-slate-800 flex flex-wrap items-center gap-1.5">
                  <span>${ApiClient.e(s.name)}</span>
                  ${parseInt(s.is_default,10)===1 ? '<span class="px-2 py-0.5 rounded-full bg-blue-50 text-[#1E5BF0] text-[10px] font-extrabold">Default</span>' : ''}
                  ${parseInt(s.is_cross_day||0,10)===1 ? '<span class="px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 text-[10px] font-bold">Lintas Hari</span>' : ''}
                  ${parseInt(s.is_active??1,10)===0 ? '<span class="px-2 py-0.5 rounded-full bg-slate-200 text-slate-600 text-[10px] font-bold">Nonaktif</span>' : ''}
                </div>
                <div class="text-[11px] text-slate-400 mt-0.5">${ApiClient.e(s.description || 'Shift kerja reguler')}</div>
              </div>
            </div>
          </td>
          <td class="px-4 py-4 text-center">
            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#F8FBFF] border border-blue-100 font-mono text-xs font-extrabold text-slate-800">
              <span class="text-[#1E5BF0]">${st}</span>
              <span class="text-slate-300">→</span>
              <span class="text-emerald-600">${en}</span>
            </div>
          </td>
          <td class="px-4 py-4 text-xs space-y-1">
            <div class="text-slate-600">Buka absen: <b class="font-mono text-[#1E5BF0]">${openAt}</b> <span class="text-slate-400">(${openB} mnt sblm)</span></div>
            <div class="text-slate-600">Toleransi telat: <b class="text-amber-600">${tol} menit</b> <span class="text-slate-400">(Tepat waktu s/d ${onTimeUntil})</span></div>
            <div class="text-slate-500 text-[11px]">Batas maks telat: <b>${maxL > 0 ? maxL + ' mnt (' + addMinutesToTime(st, maxL) + ')' : 'Tidak dibatasi'}</b></div>
          </td>
          <td class="px-4 py-4 text-center">
            <button type="button" onclick="openAssignModal(${s.id})" class="px-3 py-1.5 rounded-xl bg-[#EEF4FF] hover:bg-blue-100 text-[#1E5BF0] text-xs font-extrabold transition">
              <i class="fa-solid fa-users mr-1"></i> ${s.employee_count || 0} Pegawai
            </button>
          </td>
          <td class="px-5 py-4 text-right">
            <div class="inline-flex flex-wrap justify-end gap-1.5">
              ${parseInt(s.is_default,10)!==1 ? `<button type="button" onclick="setDefaultShift(${s.id}, '${ApiClient.e(s.name)}')" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-blue-50 text-slate-600 hover:text-[#1E5BF0] text-xs font-bold" title="Jadikan Default"><i class="fa-regular fa-star"></i></button>` : ''}
              <button type="button" onclick="openShiftModal(${s.id})" class="px-2.5 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-[#1E5BF0] text-xs font-bold" title="Edit Shift"><i class="fa-solid fa-pen"></i></button>
              <button type="button" onclick="deleteShift(${s.id}, '${ApiClient.e(s.name)}')" class="px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs font-bold" title="Hapus Shift"><i class="fa-solid fa-trash"></i></button>
            </div>
          </td>
        </tr>`;
      });
      $('#shiftsTableBody').html(html);
    }

    renderAssignEmpTable();
  });
}

function renderAssignEmpTable() {
  const q = ($('#assignEmpSearch').val() || '').toLowerCase().trim();
  const filtered = allEmps.filter(e => !q || (e.name || '').toLowerCase().includes(q) || (e.position_title || '').toLowerCase().includes(q) || (e.nip_nik || '').toLowerCase().includes(q));

  if (!filtered.length) {
    $('#assignEmpTableBody').html('<tr><td colspan="5" class="text-center py-8 text-slate-400 text-xs">Tidak ada pegawai ditemukan.</td></tr>');
    return;
  }

  let html = '';
  filtered.forEach(e => {
    let opts = '<option value="">-- Ikut Shift Default Sekolah --</option>';
    allShifts.forEach(s => {
      const sel = parseInt(e.default_shift_id || 0, 10) === parseInt(s.id, 10) ? 'selected' : '';
      opts += `<option value="${s.id}" ${sel}>[${ApiClient.e(s.code || 'SHF')}] ${ApiClient.e(s.name)} (${s.start_time.substring(0,5)}-${s.end_time.substring(0,5)})</option>`;
    });

    html += `<tr class="hover:bg-slate-50/70">
      <td class="px-5 py-3">
        <div class="font-bold text-slate-800 text-xs">${ApiClient.e(e.name)}</div>
        <div class="text-[11px] font-mono text-slate-400">${ApiClient.e(e.nip_nik || '-')}</div>
      </td>
      <td class="px-4 py-3 text-xs text-slate-500">${ApiClient.e(e.position_title || 'Pegawai')}</td>
      <td class="px-4 py-3">
        <select id="empShiftSel_${e.id}" class="w-full max-w-xs px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold bg-white outline-none focus:border-[#1E5BF0]">${opts}</select>
      </td>
      <td class="px-4 py-3 text-center">
        <span class="px-2.5 py-1 rounded-full text-[11px] font-bold ${e.custom_days_count > 0 ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-400'}">${e.custom_days_count} hari custom</span>
      </td>
      <td class="px-5 py-3 text-right">
        <button type="button" onclick="quickSaveEmpShift(${e.id})" class="px-3.5 py-2 rounded-xl bg-[#1E5BF0] hover:bg-[#194ED3] text-white text-xs font-bold">Simpan</button>
      </td>
    </tr>`;
  });
  $('#assignEmpTableBody').html(html);
}

function openShiftModal(id) {
  const s = id ? allShifts.find(x => parseInt(x.id, 10) === parseInt(id, 10)) : null;
  if (s) {
    $('#shiftModalTitle').html('<i class="fa-solid fa-pen mr-2"></i>Edit Pengaturan Shift');
    $('#sfId').val(s.id);
    $('#sfName').val(s.name);
    $('#sfCode').val(s.code || '');
    $('#sfColor').val(s.color || '#1E5BF0');
    $('#sfStart').val((s.start_time || '07:15').substring(0, 5));
    $('#sfEnd').val((s.end_time || '15:00').substring(0, 5));
    $('#sfOpenBefore').val(s.checkin_open_before ?? 60);
    $('#sfTolerance').val(s.late_tolerance_minutes ?? 10);
    $('#sfMaxLate').val(s.max_late_minutes ?? 0);
    $('#sfEarlyOut').val(s.early_checkout_tolerance ?? 0);
    $('#sfDesc').val(s.description || '');
    $('#sfDefault').prop('checked', parseInt(s.is_default, 10) === 1);
    $('#sfCrossDay').prop('checked', parseInt(s.is_cross_day || 0, 10) === 1);
    $('#sfActive').prop('checked', parseInt(s.is_active ?? 1, 10) === 1);
  } else {
    $('#shiftModalTitle').html('<i class="fa-solid fa-clock mr-2"></i>Buat Shift Baru');
    $('#shiftFullForm')[0].reset();
    $('#sfId').val(0);
    $('#sfColor').val('#1E5BF0');
    $('#sfStart').val('07:15');
    $('#sfEnd').val('15:00');
    $('#sfOpenBefore').val(60);
    $('#sfTolerance').val(10);
    $('#sfMaxLate').val(0);
    $('#sfEarlyOut').val(0);
    $('#sfActive').prop('checked', true);
  }
  updateTimelinePreview();
  $('#shiftModal').removeClass('hidden').addClass('flex');
}

$('#shiftFullForm').on('submit', function (e) {
  e.preventDefault();
  ApiClient.post('<?= app_url('/api/school_admin/shift-save') ?>', {
    school_slug: SLUG,
    id: parseInt($('#sfId').val(), 10),
    name: $('#sfName').val().trim(),
    code: $('#sfCode').val().trim(),
    color: $('#sfColor').val(),
    start_time: $('#sfStart').val(),
    end_time: $('#sfEnd').val(),
    checkin_open_before: parseInt($('#sfOpenBefore').val() || 0, 10),
    late_tolerance_minutes: parseInt($('#sfTolerance').val() || 0, 10),
    max_late_minutes: parseInt($('#sfMaxLate').val() || 0, 10),
    early_checkout_tolerance: parseInt($('#sfEarlyOut').val() || 0, 10),
    description: $('#sfDesc').val().trim(),
    is_default: $('#sfDefault').is(':checked'),
    is_cross_day: $('#sfCrossDay').is(':checked'),
    is_active: $('#sfActive').is(':checked')
  }, { loadingTitle: 'Menyimpan Shift...' })
  .then(res => {
    $('#shiftModal').addClass('hidden').removeClass('flex');
    res.mode === 'updated' ? AppModal.showUpdated(res.message, null, loadShiftsPage) : AppModal.showSaved(res.message, null, loadShiftsPage);
  }).catch(() => {});
});

async function setDefaultShift(id, name) {
  const ok = await AppModal.confirm({ title: 'Jadikan Shift Default?', message: `Jadikan "${name}" sebagai shift default sekolah?` });
  if (!ok) return;
  ApiClient.post('<?= app_url('/api/school_admin/shift-set-default') ?>', { school_slug: SLUG, id: id })
    .then(res => AppModal.showUpdated(res.message, null, loadShiftsPage)).catch(() => {});
}

async function deleteShift(id, name) {
  const ok = await AppModal.confirm({ title: 'Hapus Shift?', message: `Hapus shift "${name}"? Pegawai dengan shift ini akan beralih ke shift default.`, danger: true, confirmText: 'Ya, Hapus' });
  if (!ok) return;
  ApiClient.post('<?= app_url('/api/school_admin/shift-delete') ?>', { school_slug: SLUG, id: id })
    .then(res => AppModal.showUpdated(res.message, 'Shift Dihapus', loadShiftsPage)).catch(() => {});
}

function openAssignModal(shiftId) {
  const s = allShifts.find(x => parseInt(x.id, 10) === parseInt(shiftId, 10));
  if (!s) return;
  $('#assignShiftId').val(s.id);
  $('#assignModalSub').text(`${s.name} (${s.start_time.substring(0,5)} - ${s.end_time.substring(0,5)})`);
  $('#modalEmpFilter').val('');

  let html = '';
  allEmps.forEach(e => {
    const checked = parseInt(e.default_shift_id || 0, 10) === parseInt(s.id, 10) ? 'checked' : '';
    html += `<label class="assign-emp-row flex items-center justify-between gap-3 p-3 rounded-xl border border-slate-100 hover:bg-blue-50/40 cursor-pointer" data-search="${ApiClient.e((e.name + ' ' + (e.position_title || '')).toLowerCase())}">
      <div class="flex items-center gap-3 min-w-0">
        <input type="checkbox" class="assignEmpCheck rounded text-[#1E5BF0] w-4 h-4" value="${e.id}" ${checked}>
        <div class="min-w-0">
          <div class="text-xs font-bold text-slate-800 truncate">${ApiClient.e(e.name)}</div>
          <div class="text-[11px] text-slate-400 truncate">${ApiClient.e(e.position_title || 'Pegawai')} • Saat ini: ${ApiClient.e(e.shift_name || 'Default')}</div>
        </div>
      </div>
    </label>`;
  });
  $('#assignChecklist').html(html || '<div class="text-center py-6 text-xs text-slate-400">Belum ada data pegawai.</div>');
  $('#assignModal').removeClass('hidden').addClass('flex');
}

$('#modalEmpFilter').on('input', function () {
  const q = $(this).val().toLowerCase().trim();
  $('.assign-emp-row').each(function () {
    $(this).toggle(!q || String($(this).data('search')).includes(q));
  });
});

function submitShiftAssign() {
  const ids = [];
  $('.assignEmpCheck:checked').each(function () { ids.push(parseInt($(this).val(), 10)); });
  ApiClient.post('<?= app_url('/api/school_admin/shift-assign-employees') ?>', {
    school_slug: SLUG,
    shift_id: parseInt($('#assignShiftId').val(), 10),
    user_ids: ids,
    sync_mode: true
  }, { loadingTitle: 'Menyimpan Penugasan Shift...' })
  .then(res => {
    $('#assignModal').addClass('hidden').removeClass('flex');
    AppModal.showUpdated(res.message, 'Penugasan Disimpan!', loadShiftsPage);
  }).catch(() => {});
}

function quickSaveEmpShift(userId) {
  ApiClient.post('<?= app_url('/api/school_admin/employee-shift-quick') ?>', {
    school_slug: SLUG,
    user_id: userId,
    shift_id: $('#empShiftSel_' + userId).val()
  }, { loadingTitle: 'Memperbarui Shift Pegawai...' })
  .then(res => AppModal.showUpdated(res.message, null, loadShiftsPage)).catch(() => {});
}

$('.shTabBtn').on('click', function () {
  const tab = $(this).data('tab');
  $('.shTabBtn').removeClass('bg-[#1E5BF0] text-white shadow-sm').addClass('bg-white border border-slate-200 text-slate-600');
  $(this).removeClass('bg-white border border-slate-200 text-slate-600').addClass('bg-[#1E5BF0] text-white shadow-sm');
  $('.shTabPanel').addClass('hidden');
  $('#tab_' + tab).removeClass('hidden');
});

$('#assignEmpSearch').on('input', renderAssignEmpTable);
$(loadShiftsPage);
</script>
