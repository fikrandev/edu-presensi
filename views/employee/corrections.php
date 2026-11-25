<?php $slug = $school['slug']; ?>
<div class="space-y-4">
  <!-- Form Pengajuan Perbaikan Absen -->
  <form id="corrForm" enctype="multipart/form-data" class="bg-white rounded-3xl p-5 shadow-sm border border-blue-100 space-y-3.5">
    <input type="hidden" name="school_slug" value="<?= Security::e($slug) ?>">
    <div>
      <h2 class="text-sm font-extrabold text-slate-800"><i class="fa-solid fa-file-pen text-blue-600 mr-1.5"></i>Ajukan Perbaikan Absen / Izin</h2>
      <p class="text-[11px] text-slate-500">Pengajuan akan ditinjau dan disetujui oleh Admin Sekolah.</p>
    </div>

    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-[11px] font-bold text-slate-600 mb-1">Tanggal *</label>
        <input type="date" name="attendance_date" required value="<?= date('Y-m-d') ?>" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-bold">
      </div>
      <div>
        <label class="block text-[11px] font-bold text-slate-600 mb-1">Jenis Pengajuan *</label>
        <select name="correction_type" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-bold">
          <option value="lupa_checkin">Lupa Check-in</option>
          <option value="lupa_checkout">Lupa Check-out</option>
          <option value="koreksi_jam">Koreksi Jam</option>
          <option value="izin">Izin</option>
          <option value="sakit">Sakit</option>
          <option value="dinas_luar">Dinas Luar</option>
        </select>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-[11px] font-bold text-slate-600 mb-1">Jam Masuk Usulan</label>
        <input type="time" name="requested_checkin" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs">
      </div>
      <div>
        <label class="block text-[11px] font-bold text-slate-600 mb-1">Jam Pulang Usulan</label>
        <input type="time" name="requested_checkout" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs">
      </div>
    </div>

    <div>
      <label class="block text-[11px] font-bold text-slate-600 mb-1">Alasan / Keterangan *</label>
      <textarea name="reason" required rows="3" placeholder="Jelaskan alasan perbaikan absen atau izin..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs"></textarea>
    </div>

    <div>
      <label class="block text-[11px] font-bold text-slate-600 mb-1">Lampiran Bukti Foto (Opsional)</label>
      <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp" class="block w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 file:font-bold">
    </div>

    <button type="submit" class="w-full py-3 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-sm shadow-md shadow-blue-600/20">
      <i class="fa-solid fa-paper-plane mr-1.5"></i> Kirim Pengajuan
    </button>
  </form>

  <!-- History -->
  <div class="bg-white rounded-3xl p-5 shadow-sm border border-slate-200/70">
    <h2 class="text-sm font-extrabold text-slate-800 mb-3">Riwayat Pengajuan Saya</h2>
    <div id="empCorrList" class="space-y-2.5"></div>
  </div>
</div>

<script>
const SLUG = '<?= Security::e($slug) ?>';

function loadMyCorrections() {
  ApiClient.get('<?= app_url('/api/employee/corrections-list') ?>', { school_slug: SLUG }).then(res => {
    const list = res.data || [];
    if (!list.length) {
      $('#empCorrList').html('<div class="text-center py-6 text-xs text-slate-400">Belum ada pengajuan perbaikan absen.</div>');
      return;
    }
    let html = '';
    list.forEach(c => {
      const badge = c.status === 'pending'
        ? '<span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold">Menunggu</span>'
        : (c.status === 'approved' ? '<span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-bold">Disetujui</span>' : '<span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-bold">Ditolak</span>');
      html += `<div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 space-y-1">
        <div class="flex items-center justify-between">
          <span class="text-xs font-extrabold text-blue-800 uppercase">${ApiClient.e(c.correction_type.replace('_',' '))}</span>
          ${badge}
        </div>
        <div class="text-[11px] font-mono text-slate-500">Tanggal: ${ApiClient.e(c.attendance_date)}</div>
        <div class="text-xs text-slate-700">${ApiClient.e(c.reason)}</div>
        ${c.admin_note ? `<div class="text-[11px] text-slate-500 italic">Catatan Admin: ${ApiClient.e(c.admin_note)}</div>` : ''}
      </div>`;
    });
    $('#empCorrList').html(html);
  });
}

$('#corrForm').on('submit', function (e) {
  e.preventDefault();
  const fd = new FormData(this);
  ApiClient.upload('<?= app_url('/api/employee/correction-create') ?>', fd, { loadingTitle: 'Mengirim Pengajuan...' })
    .then(res => {
      $('#corrForm')[0].reset();
      AppModal.showSaved(res.message, 'Pengajuan Terkirim!', loadMyCorrections);
    }).catch(() => {});
});

$(loadMyCorrections);
</script>
