<?php $slug = $school['slug']; ?>
<div class="space-y-6">
  <!-- Section Header -->
  <div class="flex items-center justify-between gap-3">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-[#1E5BF0] text-white flex items-center justify-center shadow-md shadow-blue-600/20">
        <i class="fa-solid fa-clipboard-user"></i>
      </div>
      <div>
        <h2 class="text-base font-extrabold text-slate-800">Ringkasan Absensi Hari Ini</h2>
        <p class="text-xs text-slate-400">Monitoring kehadiran pegawai &amp; siswa <?= Security::e($school['name']) ?></p>
      </div>
    </div>
    <a href="<?= Tenant::schoolLocalUrl($slug, 'admin/reports') ?>" class="px-4 py-2 rounded-xl bg-[#1E5BF0] hover:bg-[#194ED3] text-white text-xs font-bold shadow-sm transition">
      <i class="fa-solid fa-chart-column mr-1"></i> Laporan Matrix
    </a>
  </div>

  <!-- 4 Vibrant Gradient Stat Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="relative overflow-hidden rounded-2xl p-5 text-white bg-gradient-to-r from-[#2563EB] to-[#4F46E5] shadow-md shadow-blue-600/20">
      <div class="text-[10px] font-extrabold uppercase tracking-wider text-blue-100">PEGAWAI HADIR HARI INI</div>
      <div id="saEmpPresent" class="text-2xl lg:text-3xl font-extrabold mt-1.5">-</div>
      <div id="saEmpMeta" class="text-[11px] text-blue-100/90 mt-1">dari - pegawai</div>
      <i class="fa-solid fa-user-check absolute -right-2 -bottom-2 text-6xl text-white/15"></i>
    </div>

    <div class="relative overflow-hidden rounded-2xl p-5 text-white bg-gradient-to-r from-[#EF233C] to-[#E11D48] shadow-md shadow-rose-600/20">
      <div class="text-[10px] font-extrabold uppercase tracking-wider text-rose-100">TERLAMBAT / BELUM ABSEN</div>
      <div class="text-2xl lg:text-3xl font-extrabold mt-1.5"><span id="saEmpLate">-</span> / <span id="saEmpNotYet">-</span></div>
      <div id="saEmpLeave" class="text-[11px] text-rose-100/90 mt-1">Izin/Sakit: -</div>
      <i class="fa-solid fa-clock absolute -right-2 -bottom-2 text-6xl text-white/15"></i>
    </div>

    <div class="relative overflow-hidden rounded-2xl p-5 text-white bg-gradient-to-r from-[#059669] to-[#0D9488] shadow-md shadow-emerald-600/20">
      <div class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-100">SISWA HADIR (QR SCAN)</div>
      <div id="saStuPresent" class="text-2xl lg:text-3xl font-extrabold mt-1.5">-</div>
      <div id="saStuMeta" class="text-[11px] text-emerald-100/90 mt-1">Total siswa: -</div>
      <i class="fa-solid fa-qrcode absolute -right-2 -bottom-2 text-6xl text-white/15"></i>
    </div>

    <div class="relative overflow-hidden rounded-2xl p-5 text-white bg-gradient-to-r from-[#F97316] to-[#EA580C] shadow-md shadow-orange-500/20">
      <div class="text-[10px] font-extrabold uppercase tracking-wider text-orange-100">PERBAIKAN MENUNGGU</div>
      <div id="saPendingCorr" class="text-2xl lg:text-3xl font-extrabold mt-1.5">-</div>
      <a href="<?= Tenant::schoolLocalUrl($slug, 'admin/reports') ?>" class="text-[11px] text-orange-50 hover:underline mt-1 inline-block font-semibold">Tinjau pengajuan →</a>
      <i class="fa-solid fa-file-pen absolute -right-2 -bottom-2 text-6xl text-white/15"></i>
    </div>
  </div>

  <!-- Left List + Right Trend Chart -->
  <div class="grid lg:grid-cols-12 gap-5">
    <div class="lg:col-span-5 bg-white rounded-2xl p-6 border border-slate-200/70 shadow-[0_2px_12px_rgba(15,23,42,0.03)] flex flex-col">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="font-bold text-slate-800 text-sm">Pengajuan Perbaikan Absen</h3>
          <p class="text-[11px] text-slate-400">Menunggu persetujuan admin sekolah</p>
        </div>
        <a href="<?= Tenant::schoolLocalUrl($slug, 'admin/reports') ?>" class="text-xs font-bold text-[#1E5BF0] hover:underline">Semua →</a>
      </div>
      <div id="dashPendingList" class="space-y-2.5 flex-1 overflow-y-auto max-h-64 text-xs"></div>
    </div>

    <div class="lg:col-span-7 bg-white rounded-2xl p-6 border border-slate-200/70 shadow-[0_2px_12px_rgba(15,23,42,0.03)]">
      <div class="mb-4">
        <h3 class="font-bold text-slate-800 text-sm">Trend Kehadiran 7 Hari Terakhir</h3>
        <p class="text-[11px] text-slate-400">Perbandingan jumlah kehadiran Pegawai &amp; Siswa</p>
      </div>
      <div class="h-64"><canvas id="schoolTrendChart"></canvas></div>
    </div>
  </div>

  <!-- Latest Employee Attendance Today -->
  <div class="space-y-3">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-sm">
        <i class="fa-solid fa-clock-rotate-left text-sm"></i>
      </div>
      <div>
        <h3 class="text-sm font-extrabold text-slate-800">Aktivitas Absensi Pegawai Hari Ini</h3>
        <p class="text-[11px] text-slate-400">Log check-in dan check-out pegawai real-time</p>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/70 shadow-[0_2px_12px_rgba(15,23,42,0.03)] overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-[#F8FAFC] text-slate-500 text-[11px] uppercase border-b border-slate-100">
            <tr>
              <th class="text-left px-5 py-3.5">Pegawai</th>
              <th class="text-left px-4 py-3.5">Shift</th>
              <th class="text-center px-4 py-3.5">Masuk</th>
              <th class="text-center px-4 py-3.5">Pulang</th>
              <th class="text-center px-4 py-3.5">Jarak GPS</th>
              <th class="text-center px-5 py-3.5">Status</th>
            </tr>
          </thead>
          <tbody id="dashTodayLogs" class="divide-y divide-slate-100"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
$(function () {
  let trendChart = null;
  ApiClient.get('<?= app_url('/api/school_admin/dashboard') ?>', { school_slug: '<?= Security::e($slug) ?>' }).then(function (res) {
    const d = res.data;
    $('#saEmpPresent').text(d.emp_today.present);
    $('#saEmpMeta').text('dari ' + d.totals.employees + ' pegawai aktif');
    $('#saEmpLate').text(d.emp_today.terlambat);
    $('#saEmpNotYet').text(d.emp_today.not_yet);
    $('#saEmpLeave').text('Izin: ' + d.emp_today.izin + ' • Sakit: ' + d.emp_today.sakit);
    $('#saStuPresent').text(d.stu_today.hadir);
    $('#saStuMeta').text('Total siswa: ' + d.totals.students + ' • Guru: ' + d.totals.teachers);
    $('#saPendingCorr').text(d.pending_corrections.length);

    const ctx = document.getElementById('schoolTrendChart');
    if (trendChart) trendChart.destroy();
    trendChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: d.trend.map(t => t.date),
        datasets: [
          { label: 'Pegawai Hadir', data: d.trend.map(t => t.emp), borderColor: '#1E5BF0', backgroundColor: 'rgba(30,91,240,0.08)', borderWidth: 2.5, pointBackgroundColor: '#fff', pointBorderColor: '#1E5BF0', pointBorderWidth: 2, fill: true, tension: 0.35 },
          { label: 'Siswa Hadir', data: d.trend.map(t => t.stu), borderColor: '#059669', backgroundColor: 'rgba(5,150,105,0.06)', borderWidth: 2.5, pointBackgroundColor: '#fff', pointBorderColor: '#059669', pointBorderWidth: 2, fill: true, tension: 0.35 }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 8 } } },
        scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: '#F1F5F9' } } }
      }
    });

    if (!d.pending_corrections.length) {
      $('#dashPendingList').html('<div class="text-center py-10 text-slate-400"><i class="fa-solid fa-circle-check text-emerald-500 text-2xl mb-2 block"></i>Tidak ada pengajuan menunggu.</div>');
    } else {
      let ph = '';
      d.pending_corrections.forEach(c => {
        ph += `<div class="px-4 py-3 rounded-xl bg-[#F8FAFC] flex items-center justify-between gap-3">
          <div class="min-w-0">
            <div class="font-bold text-slate-800 truncate">${ApiClient.e(c.employee_name)}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">${ApiClient.e(c.attendance_date)} • ${ApiClient.e(c.reason)}</div>
          </div>
          <span class="px-2 py-1 rounded-lg bg-[#EEF4FF] text-[#1E5BF0] text-[10px] font-bold uppercase shrink-0">${ApiClient.e(c.correction_type.replace('_',' '))}</span>
        </div>`;
      });
      $('#dashPendingList').html(ph);
    }

    if (!d.latest_logs.length) {
      $('#dashTodayLogs').html('<tr><td colspan="6" class="text-center py-8 text-slate-400 text-xs">Belum ada pegawai yang check-in hari ini.</td></tr>');
    } else {
      let lh = '';
      d.latest_logs.forEach(l => {
        const badge = l.status === 'hadir'
          ? '<span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-600 text-[11px] font-bold">Hadir</span>'
          : (l.status === 'terlambat' ? '<span class="px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-600 text-[11px] font-bold">Terlambat</span>' : `<span class="px-2.5 py-0.5 rounded-full bg-blue-50 text-[#1E5BF0] text-[11px] font-bold uppercase">${ApiClient.e(l.status)}</span>`);
        lh += `<tr class="hover:bg-slate-50/70">
          <td class="px-5 py-3"><div class="font-bold text-slate-800 text-xs">${ApiClient.e(l.employee_name)}</div><div class="text-[11px] text-slate-400">${ApiClient.e(l.position_title || '-')}</div></td>
          <td class="px-4 py-3 text-xs text-slate-500">${ApiClient.e(l.shift_name || '-')}</td>
          <td class="px-4 py-3 text-center font-mono text-xs font-bold text-[#1E5BF0]">${l.checkin_time ? l.checkin_time.substring(11,16) : '-'}</td>
          <td class="px-4 py-3 text-center font-mono text-xs font-bold text-slate-700">${l.checkout_time ? l.checkout_time.substring(11,16) : '-'}</td>
          <td class="px-4 py-3 text-center text-xs text-slate-500">${l.checkin_distance != null ? Math.round(l.checkin_distance) + ' m' : '-'}</td>
          <td class="px-5 py-3 text-center">${badge}</td>
        </tr>`;
      });
      $('#dashTodayLogs').html(lh);
    }
  });
});
</script>
