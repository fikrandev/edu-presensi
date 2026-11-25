<?php $slug = $school['slug']; ?>
<div class="space-y-4">
  <!-- Today Status Hero Card -->
  <div class="bg-white rounded-3xl p-5 shadow-sm border border-blue-100">
    <div class="flex items-center justify-between">
      <div>
        <div id="empDateLabel" class="text-xs font-bold text-blue-600">-</div>
        <div id="empLiveClock" class="text-3xl font-extrabold text-slate-900 font-mono tracking-tight mt-0.5">--:--:--</div>
      </div>
      <div class="text-right">
        <div id="empShiftBadge" class="inline-block px-2.5 py-1 rounded-xl bg-blue-50 text-blue-700 text-[11px] font-extrabold">-</div>
        <div id="empShiftTime" class="text-[11px] text-slate-500 mt-1 font-semibold">-</div>
      </div>
    </div>

    <div class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-2 gap-3">
      <div class="bg-emerald-50/70 rounded-2xl p-3 text-center border border-emerald-100">
        <div class="text-[11px] font-bold text-emerald-700">Check-in</div>
        <div id="empTodayIn" class="text-lg font-extrabold text-slate-800 font-mono mt-0.5">--:--</div>
      </div>
      <div class="bg-blue-50/70 rounded-2xl p-3 text-center border border-blue-100">
        <div class="text-[11px] font-bold text-blue-700">Check-out</div>
        <div id="empTodayOut" class="text-lg font-extrabold text-slate-800 font-mono mt-0.5">--:--</div>
      </div>
    </div>

    <div class="mt-3 text-[11px] text-slate-500 flex items-center gap-1.5">
      <i class="fa-solid fa-location-dot text-blue-600"></i>
      <span id="empLocationTarget">Lokasi target hari ini: -</span>
    </div>

    <a href="<?= Tenant::schoolLocalUrl($slug, 'pegawai/attendance') ?>" class="mt-4 w-full py-3.5 rounded-2xl bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-extrabold text-sm shadow-lg shadow-blue-600/25 flex items-center justify-center gap-2 transition">
      <i class="fa-solid fa-fingerprint text-base"></i> Buka Check-in / Check-out GPS
    </a>
  </div>

  <!-- Monthly Personal Stats -->
  <div class="bg-white rounded-3xl p-5 shadow-sm border border-slate-200/70">
    <div class="flex items-center justify-between mb-3">
      <h2 class="text-sm font-extrabold text-slate-800">Kehadiran Pribadi Bulan Ini</h2>
      <span id="empMonthPct" class="px-2.5 py-1 rounded-full bg-blue-100 text-blue-800 text-xs font-extrabold">0%</span>
    </div>
    <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden mb-4">
      <div id="empMonthBar" class="h-full bg-blue-600 rounded-full transition-all duration-500" style="width:0%"></div>
    </div>
    <div class="grid grid-cols-4 gap-2 text-center">
      <div class="bg-emerald-50 rounded-2xl p-2.5">
        <div id="stHadir" class="text-lg font-extrabold text-emerald-700">0</div>
        <div class="text-[10px] font-bold text-slate-500">Hadir</div>
      </div>
      <div class="bg-amber-50 rounded-2xl p-2.5">
        <div id="stTelat" class="text-lg font-extrabold text-amber-600">0</div>
        <div class="text-[10px] font-bold text-slate-500">Telat</div>
      </div>
      <div class="bg-sky-50 rounded-2xl p-2.5">
        <div id="stIzin" class="text-lg font-extrabold text-sky-700">0</div>
        <div class="text-[10px] font-bold text-slate-500">Izin/Skt</div>
      </div>
      <div class="bg-indigo-50 rounded-2xl p-2.5">
        <div id="stCorr" class="text-lg font-extrabold text-indigo-700">0</div>
        <div class="text-[10px] font-bold text-slate-500">Pending</div>
      </div>
    </div>
  </div>

  <!-- Personal Attendance History -->
  <div class="bg-white rounded-3xl p-5 shadow-sm border border-slate-200/70">
    <h2 class="text-sm font-extrabold text-slate-800 mb-3">Riwayat Kehadiran Terbaru</h2>
    <div id="empHistoryList" class="space-y-2.5"></div>
  </div>
</div>

<script>
const SLUG = '<?= Security::e($slug) ?>';

function tickClock() {
  const now = new Date();
  $('#empLiveClock').text(now.toLocaleTimeString('id-ID', { hour12: false }));
}
setInterval(tickClock, 1000);
tickClock();

$(function () {
  ApiClient.get('<?= app_url('/api/employee/dashboard') ?>', { school_slug: SLUG }).then(res => {
    const d = res.data;
    $('#empDateLabel').text(d.schedule.day_name + ', ' + d.server_date_id);
    $('#empShiftBadge').text(d.schedule.is_day_off ? 'Hari Libur' : d.schedule.shift_name);
    $('#empShiftTime').text(d.schedule.start_time.substring(0,5) + ' - ' + d.schedule.end_time.substring(0,5));
    $('#empLocationTarget').text('Lokasi target: ' + d.schedule.location_name + ' (Radius ' + d.schedule.target_radius + 'm)');

    if (d.today) {
      $('#empTodayIn').text(d.today.checkin_time ? d.today.checkin_time.substring(11,16) : '--:--');
      $('#empTodayOut').text(d.today.checkout_time ? d.today.checkout_time.substring(11,16) : '--:--');
    }

    const m = d.monthly;
    $('#empMonthPct').text(m.percentage + '%');
    $('#empMonthBar').css('width', m.percentage + '%');
    $('#stHadir').text(m.hadir + m.dinas_luar);
    $('#stTelat').text(m.terlambat);
    $('#stIzin').text(m.izin + m.sakit);
    $('#stCorr').text(d.pending_corrections);

    if (!d.history.length) {
      $('#empHistoryList').html('<div class="text-center py-6 text-xs text-slate-400">Belum ada riwayat absensi.</div>');
    } else {
      let html = '';
      d.history.forEach(h => {
        const badge = h.status === 'hadir'
          ? '<span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-bold">Hadir</span>'
          : (h.status === 'terlambat' ? '<span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold">Telat ' + h.late_minutes + 'm</span>' : `<span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-[10px] font-bold uppercase">${ApiClient.e(h.status)}</span>`);
        html += `<div class="flex items-center justify-between p-3 rounded-2xl bg-slate-50 border border-slate-100">
          <div>
            <div class="text-xs font-extrabold text-slate-800">${ApiClient.e(h.attendance_date)}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">${ApiClient.e(h.shift_name || '-')}</div>
          </div>
          <div class="text-right">
            <div class="font-mono text-xs font-bold text-blue-700">${h.checkin_time ? h.checkin_time.substring(11,16) : '--:--'} / ${h.checkout_time ? h.checkout_time.substring(11,16) : '--:--'}</div>
            <div class="mt-0.5">${badge}</div>
          </div>
        </div>`;
      });
      $('#empHistoryList').html(html);
    }
  });
});
</script>
