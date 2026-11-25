<!-- Superadmin Monitoring Dashboard (Clean Modern Style) -->
<div class="space-y-6">
  <!-- Section Header -->
  <div class="flex items-center justify-between gap-3">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-[#1E5BF0] text-white flex items-center justify-center shadow-md shadow-blue-600/20">
        <i class="fa-solid fa-chart-pie"></i>
      </div>
      <div>
        <h2 class="text-base font-extrabold text-slate-800">Monitoring Pelanggan &amp; Omset</h2>
        <p class="text-xs text-slate-400">Ringkasan kondisi sekolah berlangganan dan pendapatan SaaS</p>
      </div>
    </div>
    <a href="<?= app_url('/superadmin/schools') ?>" class="px-4 py-2 rounded-xl bg-[#1E5BF0] hover:bg-[#194ED3] text-white text-xs font-bold shadow-sm transition">
      <i class="fa-solid fa-plus mr-1"></i> Tambah Sekolah
    </a>
  </div>

  <!-- 4 Vibrant Gradient KPI Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="relative overflow-hidden rounded-2xl p-5 text-white bg-gradient-to-r from-[#2563EB] to-[#4F46E5] shadow-md shadow-blue-600/20">
      <div class="text-[10px] font-extrabold uppercase tracking-wider text-blue-100">SEKOLAH AKTIF</div>
      <div id="kpiActiveSchools" class="text-2xl lg:text-3xl font-extrabold mt-1.5">-</div>
      <div id="kpiTotalSchools" class="text-[11px] text-blue-100/90 mt-1">dari - terdaftar</div>
      <i class="fa-solid fa-school absolute -right-2 -bottom-2 text-6xl text-white/15"></i>
    </div>

    <div class="relative overflow-hidden rounded-2xl p-5 text-white bg-gradient-to-r from-[#EF233C] to-[#E11D48] shadow-md shadow-rose-600/20">
      <div class="text-[10px] font-extrabold uppercase tracking-wider text-rose-100">JATUH TEMPO ≤ 7 HARI</div>
      <div id="kpiExpiring" class="text-2xl lg:text-3xl font-extrabold mt-1.5">-</div>
      <div id="kpiExpired" class="text-[11px] text-rose-100/90 mt-1">- expired / suspend</div>
      <i class="fa-solid fa-bell absolute -right-2 -bottom-2 text-6xl text-white/15"></i>
    </div>

    <div class="relative overflow-hidden rounded-2xl p-5 text-white bg-gradient-to-r from-[#059669] to-[#0D9488] shadow-md shadow-emerald-600/20">
      <div class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-100">OMSET BULAN INI</div>
      <div id="kpiRevMonth" class="text-xl lg:text-2xl font-extrabold mt-1.5">-</div>
      <div id="kpiRevTotal" class="text-[11px] text-emerald-100/90 mt-1">Total: -</div>
      <i class="fa-solid fa-chart-line absolute -right-2 -bottom-2 text-6xl text-white/15"></i>
    </div>

    <div class="relative overflow-hidden rounded-2xl p-5 text-white bg-gradient-to-r from-[#F97316] to-[#EA580C] shadow-md shadow-orange-500/20">
      <div class="text-[10px] font-extrabold uppercase tracking-wider text-orange-100">TOTAL PEGAWAI AKTIF</div>
      <div id="kpiUsers" class="text-2xl lg:text-3xl font-extrabold mt-1.5">-</div>
      <div id="kpiStudents" class="text-[11px] text-orange-100/90 mt-1">- siswa terdaftar</div>
      <i class="fa-solid fa-users absolute -right-2 -bottom-2 text-6xl text-white/15"></i>
    </div>
  </div>

  <!-- Clean White List + Chart Grid -->
  <div class="grid lg:grid-cols-12 gap-5">
    <!-- Left Card: Expiring Schools List -->
    <div class="lg:col-span-5 bg-white rounded-2xl p-6 border border-slate-200/70 shadow-[0_2px_12px_rgba(15,23,42,0.03)] flex flex-col">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="font-bold text-slate-800 text-sm">Sekolah Mendekati Jatuh Tempo</h3>
          <p class="text-[11px] text-slate-400">Monitoring jatuh tempo &amp; pengingat WA</p>
        </div>
        <a href="<?= app_url('/superadmin/wa-gateway') ?>" class="text-xs font-bold text-[#1E5BF0] hover:underline">WA Gateway →</a>
      </div>
      <div id="expiringList" class="space-y-2.5 flex-1 overflow-y-auto max-h-72 text-sm">
        <div class="text-center text-slate-400 py-8 text-xs">Memuat data...</div>
      </div>
    </div>

    <!-- Right Card: Revenue Trend Chart -->
    <div class="lg:col-span-7 bg-white rounded-2xl p-6 border border-slate-200/70 shadow-[0_2px_12px_rgba(15,23,42,0.03)]">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="font-bold text-slate-800 text-sm">Trend Omset Langganan</h3>
          <p class="text-[11px] text-slate-400">Pendapatan pembayaran lunas 6 bulan terakhir</p>
        </div>
        <a href="<?= app_url('/superadmin/finance') ?>" class="text-xs font-bold text-[#1E5BF0] hover:underline">Detail Keuangan →</a>
      </div>
      <div class="h-64">
        <canvas id="saRevenueChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Recent Transactions Section -->
  <div class="space-y-3">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-sm">
        <i class="fa-solid fa-receipt text-sm"></i>
      </div>
      <div>
        <h3 class="text-sm font-extrabold text-slate-800">Transaksi Langganan Terbaru</h3>
        <p class="text-[11px] text-slate-400">Invoice pembayaran sekolah terbaru</p>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/70 shadow-[0_2px_12px_rgba(15,23,42,0.03)] overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-[#F8FAFC] text-slate-500 text-[11px] uppercase border-b border-slate-100">
            <tr>
              <th class="text-left px-5 py-3.5">Invoice</th>
              <th class="text-left px-5 py-3.5">Sekolah</th>
              <th class="text-left px-5 py-3.5">Paket / Durasi</th>
              <th class="text-right px-5 py-3.5">Nominal</th>
              <th class="text-center px-5 py-3.5">Status</th>
            </tr>
          </thead>
          <tbody id="recentTxBody" class="divide-y divide-slate-100"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
$(function () {
  let revChart = null;
  ApiClient.get('<?= app_url('/api/superadmin/dashboard') ?>').then(function (res) {
    const d = res.data;
    const s = d.stats;
    $('#kpiActiveSchools').text(s.active_schools);
    $('#kpiTotalSchools').text('dari ' + s.total_schools + ' sekolah terdaftar');
    $('#kpiExpiring').text(s.expiring_soon);
    $('#kpiExpired').text(s.expired_schools + ' expired / nonaktif');
    $('#kpiRevMonth').text(ApiClient.rupiah(s.revenue_month));
    $('#kpiRevTotal').text('Total: ' + ApiClient.rupiah(s.revenue_total));
    $('#kpiUsers').text(s.total_employees);
    $('#kpiStudents').text(s.total_students + ' siswa terdaftar');

    const ctx = document.getElementById('saRevenueChart');
    if (revChart) revChart.destroy();
    revChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: d.revenue_chart.map(x => x.label),
        datasets: [{
          label: 'Omset (Rp)',
          data: d.revenue_chart.map(x => x.amount),
          borderColor: '#1E5BF0',
          backgroundColor: 'rgba(30, 91, 240, 0.08)',
          borderWidth: 2.5,
          pointBackgroundColor: '#ffffff',
          pointBorderColor: '#1E5BF0',
          pointBorderWidth: 2.5,
          pointRadius: 4,
          fill: true,
          tension: 0.35
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 8 } } },
        scales: {
          x: { grid: { display: false } },
          y: { beginAtZero: true, grid: { color: '#F1F5F9' } }
        }
      }
    });

    if (!d.expiring_list.length) {
      $('#expiringList').html('<div class="text-center text-slate-400 py-10 text-xs"><i class="fa-solid fa-circle-check text-emerald-500 text-2xl mb-2 block"></i>Semua sekolah aktif dan aman.</div>');
    } else {
      let html = '';
      d.expiring_list.forEach(function (sc) {
        const dl = parseInt(sc.days_left, 10);
        const codeTag = dl < 0 ? 'EXP' : (dl === 0 ? 'H-0' : 'H-' + dl);
        const pillCls = dl <= 0 ? 'bg-rose-50 text-rose-600' : 'bg-[#EEF4FF] text-[#1E5BF0]';
        const rightColor = dl <= 0 ? 'text-rose-600' : 'text-emerald-600';
        html += `<div class="px-4 py-3 rounded-xl bg-[#F8FAFC] hover:bg-[#F1F6FF] flex items-center justify-between gap-3 transition">
          <div class="flex items-center gap-3 min-w-0">
            <span class="px-2 py-1 rounded-lg text-[10px] font-extrabold shrink-0 ${pillCls}">${codeTag}</span>
            <div class="min-w-0">
              <div class="font-semibold text-xs text-slate-800 truncate">${ApiClient.e(sc.name)}</div>
              <div class="text-[10px] text-slate-400">${ApiClient.e(sc.package_name || '-')} • ${ApiClient.e(sc.subscription_end || '-')}</div>
            </div>
          </div>
          <div class="text-xs font-bold ${rightColor} shrink-0">${ApiClient.rupiah(sc.price_monthly)}</div>
        </div>`;
      });
      $('#expiringList').html(html);
    }

    let txHtml = '';
    if (!d.recent_tx.length) {
      txHtml = '<tr><td colspan="5" class="text-center py-6 text-slate-400 text-xs">Belum ada transaksi.</td></tr>';
    } else {
      d.recent_tx.forEach(function (t) {
        const stBadge = t.status === 'paid'
          ? '<span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-600 text-[11px] font-bold">Lunas</span>'
          : (t.status === 'pending' ? '<span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-600 text-[11px] font-bold">Pending</span>' : '<span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 text-[11px] font-bold">Batal</span>');
        txHtml += `<tr class="hover:bg-slate-50/70">
          <td class="px-5 py-3.5 font-mono text-xs font-bold text-[#1E5BF0]">${ApiClient.e(t.invoice_number)}</td>
          <td class="px-5 py-3.5 font-semibold text-slate-800 text-xs">${ApiClient.e(t.school_name)}</td>
          <td class="px-5 py-3.5 text-xs text-slate-500">${ApiClient.e(t.package_name || '-')} (${t.duration_months} bln)</td>
          <td class="px-5 py-3.5 text-right font-bold text-emerald-600 text-xs">${ApiClient.rupiah(t.amount)}</td>
          <td class="px-5 py-3.5 text-center">${stBadge}</td>
        </tr>`;
      });
    }
    $('#recentTxBody').html(txHtml);
  });
});
</script>
