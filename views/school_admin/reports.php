<?php $slug = $school['slug']; ?>
<div class="space-y-5">
  <!-- Filter Header -->
  <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm flex flex-wrap items-center justify-between gap-3">
    <div class="flex flex-wrap items-center gap-2">
      <input type="month" id="repMonth" value="<?= date('Y-m') ?>" class="px-3 py-2 rounded-xl border border-slate-200 text-sm font-bold text-slate-700">
      <select id="repEmpFilter" class="px-3 py-2 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700">
        <option value="0">Semua Pegawai</option>
      </select>
    </div>
    <button type="button" onclick="exportSummaryExcel()" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold shadow">
      <i class="fa-solid fa-file-excel mr-1"></i> Export Rekap Excel (.xlsx)
    </button>
  </div>

  <!-- Tabs Navigation -->
  <div class="flex flex-wrap gap-2">
    <button type="button" data-tab="matrix" class="repTabBtn px-4 py-2.5 rounded-xl bg-blue-600 text-white text-xs font-extrabold shadow">
      <i class="fa-solid fa-table-cells mr-1"></i> Matrix Monitoring Pengisian
    </button>
    <button type="button" data-tab="summary" class="repTabBtn px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-600 text-xs font-extrabold">
      <i class="fa-solid fa-chart-pie mr-1"></i> Rekapitulasi & Persentase
    </button>
    <button type="button" data-tab="logs" class="repTabBtn px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-600 text-xs font-extrabold">
      <i class="fa-solid fa-list-ul mr-1"></i> Log Absen Harian
    </button>
    <button type="button" data-tab="corrections" class="repTabBtn px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-600 text-xs font-extrabold">
      <i class="fa-solid fa-file-signature mr-1"></i> Approval Perbaikan <span id="corrBadgeCount" class="ml-1 px-1.5 py-0.5 rounded-full bg-amber-500 text-white text-[10px]">0</span>
    </button>
  </div>

  <!-- TAB 1: MATRIX MONITORING PENGISIAN -->
  <div id="tab_matrix" class="repTabPanel bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="p-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2 text-xs">
      <div class="font-extrabold text-slate-800">Matrix Monitoring Pengisian Kehadiran Bulanan</div>
      <div class="flex flex-wrap gap-2 text-[11px] font-bold">
        <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-700">H = Hadir</span>
        <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800">T = Terlambat</span>
        <span class="px-2 py-0.5 rounded bg-sky-100 text-sky-700">I = Izin</span>
        <span class="px-2 py-0.5 rounded bg-purple-100 text-purple-700">S = Sakit</span>
        <span class="px-2 py-0.5 rounded bg-indigo-100 text-indigo-700">DL = Dinas Luar</span>
        <span class="px-2 py-0.5 rounded bg-red-50 text-red-500">- = Belum/Alpha</span>
        <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-400">L = Libur</span>
      </div>
    </div>
    <div class="overflow-x-auto">
      <table class="matrix-table w-full text-xs border-collapse">
        <thead id="matrixHead" class="bg-blue-50 text-blue-900"></thead>
        <tbody id="matrixBody" class="divide-y divide-slate-100"></tbody>
      </table>
    </div>
  </div>

  <!-- TAB 2: REKAPITULASI & PERSENTASE -->
  <div id="tab_summary" class="repTabPanel hidden bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-blue-50 text-blue-900 text-xs uppercase">
          <tr>
            <th class="text-left px-5 py-3.5">Pegawai</th>
            <th class="text-center px-3 py-3.5">Hari Kerja</th>
            <th class="text-center px-3 py-3.5">Hadir</th>
            <th class="text-center px-3 py-3.5">Terlambat</th>
            <th class="text-center px-3 py-3.5">Izin/Sakit</th>
            <th class="text-center px-3 py-3.5">Alpha</th>
            <th class="text-center px-3 py-3.5">Total Telat</th>
            <th class="text-left px-5 py-3.5 w-44">Persentase</th>
          </tr>
        </thead>
        <tbody id="summaryBody" class="divide-y divide-slate-100"></tbody>
      </table>
    </div>
  </div>

  <!-- TAB 3: LOG ABSEN -->
  <div id="tab_logs" class="repTabPanel hidden bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-xs">
        <thead class="bg-blue-50 text-blue-900 uppercase">
          <tr>
            <th class="text-left px-4 py-3">Tanggal</th>
            <th class="text-left px-4 py-3">Pegawai</th>
            <th class="text-left px-4 py-3">Shift</th>
            <th class="text-center px-3 py-3">Check-in</th>
            <th class="text-center px-3 py-3">Check-out</th>
            <th class="text-center px-3 py-3">Jarak GPS</th>
            <th class="text-center px-4 py-3">Status</th>
          </tr>
        </thead>
        <tbody id="logsBody" class="divide-y divide-slate-100"></tbody>
      </table>
    </div>
  </div>

  <!-- TAB 4: APPROVAL PERBAIKAN ABSEN -->
  <div id="tab_corrections" class="repTabPanel hidden bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-xs">
        <thead class="bg-blue-50 text-blue-900 uppercase">
          <tr>
            <th class="text-left px-4 py-3">Pegawai</th>
            <th class="text-left px-4 py-3">Tanggal & Jenis</th>
            <th class="text-left px-4 py-3">Usulan Jam & Alasan</th>
            <th class="text-center px-4 py-3">Status</th>
            <th class="text-right px-4 py-3">Keputusan</th>
          </tr>
        </thead>
        <tbody id="corrBody" class="divide-y divide-slate-100"></tbody>
      </table>
    </div>
  </div>
</div>

<script>
const SLUG = '<?= Security::e($slug) ?>';
let lastReportData = null;

function loadReports() {
  const curEmp = $('#repEmpFilter').val();
  ApiClient.get('<?= app_url('/api/school_admin/reports-data') ?>', {
    school_slug: SLUG,
    month: $('#repMonth').val(),
    user_id: curEmp
  }).then(function (res) {
    const d = res.data;
    lastReportData = d;

    if ($('#repEmpFilter option').length <= 1) {
      let opts = '<option value="0">Semua Pegawai</option>';
      d.employees.forEach(e => opts += `<option value="${e.id}">${ApiClient.e(e.name)}</option>`);
      $('#repEmpFilter').html(opts);
    }

    // 1. Render Matrix
    let headHtml = '<tr><th class="px-4 py-3 text-left min-w-[180px]">Pegawai</th>';
    for (let day = 1; day <= d.days_in_month; day++) {
      headHtml += `<th class="px-1.5 py-2 text-center w-8">${day}</th>`;
    }
    headHtml += '</tr>';
    $('#matrixHead').html(headHtml);

    const badgeStyle = {
      hadir: 'bg-emerald-100 text-emerald-700',
      terlambat: 'bg-amber-100 text-amber-800',
      izin: 'bg-sky-100 text-sky-700',
      sakit: 'bg-purple-100 text-purple-700',
      dinas_luar: 'bg-indigo-100 text-indigo-700',
      alpha: 'bg-red-100 text-red-700',
      empty: 'bg-red-50 text-red-400',
      libur: 'bg-slate-100 text-slate-400',
      future: 'text-slate-200'
    };

    let mHtml = '';
    d.matrix.forEach(row => {
      mHtml += `<tr><td class="px-4 py-2.5"><div class="font-bold text-slate-800 truncate max-w-[170px]">${ApiClient.e(row.name)}</div><div class="text-[10px] text-slate-400">${ApiClient.e(row.position || '')}</div></td>`;
      for (let day = 1; day <= d.days_in_month; day++) {
        const cell = row.days[day] || { code: '', status: 'future', in: '-', out: '-' };
        const cls = badgeStyle[cell.status] || '';
        const title = `Tgl ${day}: ${cell.status.toUpperCase()} (In: ${cell.in}, Out: ${cell.out})`;
        mHtml += `<td class="p-1 text-center" title="${title}"><span class="inline-flex items-center justify-center w-6 h-6 rounded-md font-extrabold text-[10px] ${cls}">${cell.code}</span></td>`;
      }
      mHtml += '</tr>';
    });
    $('#matrixBody').html(mHtml || '<tr><td colspan="32" class="text-center py-8 text-slate-400">Belum ada data pegawai.</td></tr>');

    // 2. Render Summary & Percentage
    let sHtml = '';
    d.summary.forEach(s => {
      const barColor = s.percentage >= 85 ? 'bg-emerald-500' : (s.percentage >= 65 ? 'bg-amber-500' : 'bg-red-500');
      sHtml += `<tr class="hover:bg-blue-50/30">
        <td class="px-5 py-3"><div class="font-bold text-slate-800">${ApiClient.e(s.name)}</div><div class="text-xs text-slate-500">${ApiClient.e(s.nip_nik || '-')} • ${ApiClient.e(s.position_title || '')}</div></td>
        <td class="px-3 py-3 text-center font-bold">${s.work_days}</td>
        <td class="px-3 py-3 text-center font-bold text-emerald-600">${s.hadir + s.dinas_luar}</td>
        <td class="px-3 py-3 text-center font-bold text-amber-600">${s.terlambat}</td>
        <td class="px-3 py-3 text-center font-bold text-sky-600">${s.izin + s.sakit}</td>
        <td class="px-3 py-3 text-center font-bold text-red-600">${s.alpha}</td>
        <td class="px-3 py-3 text-center text-xs text-slate-600">${s.late_minutes} mnt</td>
        <td class="px-5 py-3">
          <div class="flex items-center gap-2">
            <div class="flex-1 h-2.5 bg-slate-100 rounded-full overflow-hidden"><div class="h-full ${barColor} rounded-full" style="width:${s.percentage}%"></div></div>
            <span class="font-extrabold text-xs text-slate-800 w-11 text-right">${s.percentage}%</span>
          </div>
        </td>
      </tr>`;
    });
    $('#summaryBody').html(sHtml);

    // 3. Render Logs
    let lHtml = '';
    d.logs.forEach(l => {
      const gps = l.checkin_lat ? `<a href="https://maps.google.com/?q=${l.checkin_lat},${l.checkin_lng}" target="_blank" class="text-blue-600 hover:underline font-semibold">${Math.round(l.checkin_distance || 0)} m <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i></a>` : '-';
      lHtml += `<tr class="hover:bg-blue-50/30">
        <td class="px-4 py-3 font-mono font-bold text-slate-700">${ApiClient.e(l.attendance_date)}</td>
        <td class="px-4 py-3 font-bold text-slate-800">${ApiClient.e(l.employee_name)}</td>
        <td class="px-4 py-3 text-slate-600">${ApiClient.e(l.shift_name || '-')}</td>
        <td class="px-3 py-3 text-center font-mono font-bold text-blue-700">${l.checkin_time ? l.checkin_time.substring(11,16) : '-'}</td>
        <td class="px-3 py-3 text-center font-mono font-bold text-slate-700">${l.checkout_time ? l.checkout_time.substring(11,16) : '-'}</td>
        <td class="px-3 py-3 text-center">${gps}</td>
        <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded-full font-bold uppercase text-[10px] ${badgeStyle[l.status] || ''}">${ApiClient.e(l.status)}</span></td>
      </tr>`;
    });
    $('#logsBody').html(lHtml || '<tr><td colspan="7" class="text-center py-8 text-slate-400">Belum ada log absen pada bulan ini.</td></tr>');

    // 4. Render Corrections
    const pendingCnt = (d.corrections || []).filter(c => c.status === 'pending').length;
    $('#corrBadgeCount').text(pendingCnt);
    let cHtml = '';
    (d.corrections || []).forEach(c => {
      const st = c.status === 'pending'
        ? '<span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-bold">Menunggu</span>'
        : (c.status === 'approved' ? '<span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-bold">Disetujui</span>' : '<span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 font-bold">Ditolak</span>');
      const actions = c.status === 'pending'
        ? `<div class="inline-flex gap-1.5">
            <button type="button" onclick="reviewCorrection(${c.id},'approved')" class="px-2.5 py-1 rounded-lg bg-emerald-600 text-white font-bold">Setujui</button>
            <button type="button" onclick="reviewCorrection(${c.id},'rejected')" class="px-2.5 py-1 rounded-lg bg-red-600 text-white font-bold">Tolak</button>
          </div>`
        : `<span class="text-slate-400">${ApiClient.e(c.admin_note || 'Selesai')}</span>`;
      cHtml += `<tr>
        <td class="px-4 py-3"><div class="font-bold text-slate-800">${ApiClient.e(c.employee_name)}</div><div class="text-[10px] text-slate-400">${ApiClient.e(c.position_title || '')}</div></td>
        <td class="px-4 py-3"><div class="font-bold text-blue-700">${ApiClient.e(c.attendance_date)}</div><div class="uppercase text-[10px] font-bold text-slate-500">${ApiClient.e(c.correction_type.replace('_',' '))}</div></td>
        <td class="px-4 py-3 max-w-xs"><div class="font-mono text-[11px] text-slate-700">In: ${c.requested_checkin || '-'} | Out: ${c.requested_checkout || '-'}</div><div class="text-slate-600 mt-0.5">${ApiClient.e(c.reason)}</div></td>
        <td class="px-4 py-3 text-center">${st}</td>
        <td class="px-4 py-3 text-right">${actions}</td>
      </tr>`;
    });
    $('#corrBody').html(cHtml || '<tr><td colspan="5" class="text-center py-8 text-slate-400">Belum ada pengajuan perbaikan absen.</td></tr>');
  });
}

async function reviewCorrection(id, decision) {
  const ok = await AppModal.confirm({
    title: decision === 'approved' ? 'Setujui Pengajuan Perbaikan?' : 'Tolak Pengajuan Perbaikan?',
    message: decision === 'approved' ? 'Log absen pegawai akan otomatis diperbarui sesuai usulan ini.' : 'Pengajuan akan ditandai ditolak.',
    danger: decision === 'rejected'
  });
  if (!ok) return;
  ApiClient.post('<?= app_url('/api/school_admin/correction-review') ?>', { school_slug: SLUG, id: id, status: decision })
    .then(res => AppModal.showUpdated(res.message, null, loadReports)).catch(() => {});
}

function exportSummaryExcel() {
  if (!lastReportData || !lastReportData.summary.length) {
    return AppModal.showError('Belum ada data rekap untuk diexport.');
  }
  const rows = lastReportData.summary.map(s => ({
    'NIP': s.nip_nik || '-',
    'Nama Pegawai': s.name,
    'Jabatan': s.position_title || '-',
    'Hari Kerja': s.work_days,
    'Hadir': s.hadir,
    'Terlambat': s.terlambat,
    'Dinas Luar': s.dinas_luar,
    'Izin': s.izin,
    'Sakit': s.sakit,
    'Alpha': s.alpha,
    'Total Menit Terlambat': s.late_minutes,
    'Persentase Kehadiran (%)': s.percentage + '%'
  }));
  ExcelHelper.exportToExcel(rows, 'Rekap_Absensi_Pegawai_' + SLUG + '_' + $('#repMonth').val() + '.xlsx', 'Rekap Pegawai');
}

$('.repTabBtn').on('click', function () {
  const tab = $(this).data('tab');
  $('.repTabBtn').removeClass('bg-blue-600 text-white shadow').addClass('bg-white border border-slate-200 text-slate-600');
  $(this).removeClass('bg-white border border-slate-200 text-slate-600').addClass('bg-blue-600 text-white shadow');
  $('.repTabPanel').addClass('hidden');
  $('#tab_' + tab).removeClass('hidden');
});

$('#repMonth, #repEmpFilter').on('change', loadReports);
$(loadReports);
</script>
