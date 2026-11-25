<!-- Keuangan, Omset & Harga Paket -->
<div class="space-y-6">
  <!-- Summary Cards -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-gradient-to-br from-blue-700 to-blue-800 text-white rounded-2xl p-5 shadow-lg">
      <div class="text-xs font-semibold text-blue-200">Total Omset Lunas</div>
      <div id="finTotal" class="text-xl lg:text-2xl font-extrabold mt-2">-</div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
      <div class="text-xs font-bold text-slate-500">Omset Bulan Ini</div>
      <div id="finMonth" class="text-xl lg:text-2xl font-extrabold text-blue-700 mt-2">-</div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
      <div class="text-xs font-bold text-slate-500">Tagihan Pending</div>
      <div id="finPending" class="text-xl lg:text-2xl font-extrabold text-amber-600 mt-2">-</div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
      <div class="text-xs font-bold text-slate-500">Transaksi Lunas</div>
      <div id="finCount" class="text-xl lg:text-2xl font-extrabold text-emerald-600 mt-2">-</div>
    </div>
  </div>

  <!-- Quick Package Price Matrix -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-100">
      <h2 class="font-extrabold text-slate-800"><i class="fa-solid fa-tags text-blue-600 mr-2"></i>Daftar Harga Paket Langganan</h2>
      <p class="text-xs text-slate-500">Ubah harga paket Bulanan, 6 Bulan, atau Tahunan langsung dari sini.</p>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-blue-50/70 text-blue-900 text-xs uppercase">
          <tr>
            <th class="text-left px-5 py-3">Nama Paket</th>
            <th class="text-left px-4 py-3">Harga 1 Bulan</th>
            <th class="text-left px-4 py-3">Harga 6 Bulan</th>
            <th class="text-left px-4 py-3">Harga 1 Tahun</th>
            <th class="text-right px-5 py-3">Simpan</th>
          </tr>
        </thead>
        <tbody id="priceMatrixBody" class="divide-y divide-slate-100"></tbody>
      </table>
    </div>
  </div>

  <!-- Transactions History -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="font-extrabold text-slate-800">Riwayat Pembayaran & Invoice</h2>
      </div>
      <div class="flex items-center gap-2">
        <input type="month" id="finMonthFilter" class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold">
        <button type="button" onclick="openTxModal()" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow">
          <i class="fa-solid fa-plus mr-1"></i> Catat Pembayaran
        </button>
      </div>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
          <tr>
            <th class="text-left px-5 py-3">Invoice</th>
            <th class="text-left px-4 py-3">Sekolah</th>
            <th class="text-left px-4 py-3">Paket & Periode</th>
            <th class="text-right px-4 py-3">Nominal</th>
            <th class="text-center px-4 py-3">Status</th>
            <th class="text-right px-5 py-3">Aksi</th>
          </tr>
        </thead>
        <tbody id="txTableBody" class="divide-y divide-slate-100"></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Catat Transaksi -->
<div id="txModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
  <div class="modal-pop bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
    <div class="bg-blue-700 text-white px-6 py-4 flex items-center justify-between">
      <h3 class="font-extrabold text-base">Catat Transaksi Keuangan</h3>
      <button type="button" onclick="$('#txModal').addClass('hidden').removeClass('flex')" class="text-white/80"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form id="txForm" class="p-6 space-y-4">
      <input type="hidden" id="txId" value="0">
      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1">Sekolah</label>
        <select id="txSchool" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm"></select>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Durasi (Bulan)</label>
          <select id="txDuration" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
            <option value="1">1 Bulan</option>
            <option value="6">6 Bulan</option>
            <option value="12">12 Bulan</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Nominal (Rp)</label>
          <input type="number" id="txAmount" required min="0" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm font-bold">
        </div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Metode</label>
          <input type="text" id="txMethod" value="Transfer Bank" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Status</label>
          <select id="txStatus" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
            <option value="paid">Lunas</option>
            <option value="pending">Pending</option>
            <option value="cancelled">Dibatalkan</option>
          </select>
        </div>
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1">Catatan</label>
        <input type="text" id="txNotes" placeholder="Keterangan invoice..." class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>
      <div class="flex justify-end gap-2">
        <button type="button" onclick="$('#txModal').addClass('hidden').removeClass('flex')" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-600">Batal</button>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-bold">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script>
let finSchools = [];

function loadFinance() {
  ApiClient.get('<?= app_url('/api/superadmin/finance-summary') ?>', { month: $('#finMonthFilter').val() }).then(function (res) {
    const d = res.data;
    finSchools = d.schools;
    $('#finTotal').text(ApiClient.rupiah(d.summary.total_omset));
    $('#finMonth').text(ApiClient.rupiah(d.summary.month_omset));
    $('#finPending').text(ApiClient.rupiah(d.summary.pending_sum));
    $('#finCount').text(d.summary.paid_count + ' Invoice');

    let scOpts = '';
    finSchools.forEach(s => scOpts += `<option value="${s.id}" data-pkg="${s.package_id || ''}">${ApiClient.e(s.name)}</option>`);
    $('#txSchool').html(scOpts);

    let pHtml = '';
    d.packages.forEach(function (p) {
      pHtml += `<tr>
        <td class="px-5 py-3 font-bold text-slate-800">${ApiClient.e(p.name)}</td>
        <td class="px-4 py-3"><input type="number" id="pm_${p.id}" value="${p.price_monthly}" class="w-32 px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-bold"></td>
        <td class="px-4 py-3"><input type="number" id="p6_${p.id}" value="${p.price_6months}" class="w-32 px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-bold"></td>
        <td class="px-4 py-3"><input type="number" id="p12_${p.id}" value="${p.price_yearly}" class="w-32 px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-bold"></td>
        <td class="px-5 py-3 text-right">
          <button type="button" onclick="savePkgPrice(${p.id})" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold">Update</button>
        </td>
      </tr>`;
    });
    $('#priceMatrixBody').html(pHtml);

    let tHtml = '';
    if (!d.transactions.length) {
      tHtml = '<tr><td colspan="6" class="text-center py-8 text-slate-400 text-xs">Tidak ada transaksi pada periode ini.</td></tr>';
    } else {
      d.transactions.forEach(function (t) {
        const badge = t.status === 'paid'
          ? '<span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">Lunas</span>'
          : (t.status === 'pending' ? '<span class="px-2.5 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-bold">Pending</span>' : '<span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 text-xs font-bold">Batal</span>');
        tHtml += `<tr class="hover:bg-blue-50/30">
          <td class="px-5 py-3.5 font-mono text-xs font-bold text-blue-700">${ApiClient.e(t.invoice_number)}<div class="text-[10px] font-sans text-slate-400">${ApiClient.e(t.payment_method)}</div></td>
          <td class="px-4 py-3.5 font-bold text-slate-800">${ApiClient.e(t.school_name)}</td>
          <td class="px-4 py-3.5 text-xs text-slate-600">${ApiClient.e(t.package_name || '-')} (${t.duration_months} bln)<div class="text-[10px] text-slate-400">${ApiClient.e(t.notes || '')}</div></td>
          <td class="px-4 py-3.5 text-right font-extrabold text-slate-800">${ApiClient.rupiah(t.amount)}</td>
          <td class="px-4 py-3.5 text-center">${badge}</td>
          <td class="px-5 py-3.5 text-right">
            ${t.status === 'pending' ? `<button type="button" onclick="markPaid(${t.id}, ${t.amount}, '${ApiClient.e(t.payment_method)}')" class="px-2.5 py-1 rounded-lg bg-emerald-600 text-white text-xs font-bold">Tandai Lunas</button>` : '<span class="text-xs text-slate-400">-</span>'}
          </td>
        </tr>`;
      });
    }
    $('#txTableBody').html(tHtml);
  });
}

function savePkgPrice(id) {
  ApiClient.post('<?= app_url('/api/superadmin/package-price-quick') ?>', {
    id: id,
    price_monthly: parseFloat($('#pm_' + id).val()),
    price_6months: parseFloat($('#p6_' + id).val()),
    price_yearly: parseFloat($('#p12_' + id).val())
  }, { loadingTitle: 'Memperbarui Harga...' })
  .then(res => AppModal.showUpdated(res.message, null, loadFinance)).catch(() => {});
}

function openTxModal() {
  $('#txForm')[0].reset();
  $('#txId').val(0);
  $('#txModal').removeClass('hidden').addClass('flex');
}

$('#txForm').on('submit', function (e) {
  e.preventDefault();
  ApiClient.post('<?= app_url('/api/superadmin/transaction-save') ?>', {
    id: parseInt($('#txId').val(), 10),
    school_id: parseInt($('#txSchool').val(), 10),
    package_id: parseInt($('#txSchool option:selected').data('pkg') || 0, 10),
    duration_months: parseInt($('#txDuration').val(), 10),
    amount: parseFloat($('#txAmount').val()),
    payment_method: $('#txMethod').val().trim(),
    status: $('#txStatus').val(),
    notes: $('#txNotes').val().trim()
  }, { loadingTitle: 'Menyimpan Transaksi...' })
  .then(res => {
    $('#txModal').addClass('hidden').removeClass('flex');
    AppModal.showSaved(res.message, null, loadFinance);
  }).catch(() => {});
});

async function markPaid(id, amount, method) {
  const ok = await AppModal.confirm({ title: 'Verifikasi Lunas?', message: 'Tandai transaksi ini sebagai LUNAS?' });
  if (!ok) return;
  ApiClient.post('<?= app_url('/api/superadmin/transaction-save') ?>', { id: id, status: 'paid', amount: amount, payment_method: method })
    .then(res => AppModal.showUpdated(res.message, null, loadFinance)).catch(() => {});
}

$('#finMonthFilter').on('change', loadFinance);
$(loadFinance);
</script>
