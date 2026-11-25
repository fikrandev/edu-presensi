<?php $slug = $school['slug']; ?>
<div class="space-y-5">
  <!-- Filter Bar -->
  <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm flex flex-wrap items-center justify-between gap-3">
    <div class="flex flex-wrap items-center gap-2">
      <select id="trClass" class="px-3 py-2 rounded-xl border border-slate-200 text-sm font-bold text-slate-700"><option value="">Semua Kelas</option></select>
      <input type="date" id="trStart" value="<?= date('Y-m-01') ?>" class="px-3 py-2 rounded-xl border border-slate-200 text-sm">
      <span class="text-xs text-slate-400">s/d</span>
      <input type="date" id="trEnd" value="<?= date('Y-m-d') ?>" class="px-3 py-2 rounded-xl border border-slate-200 text-sm">
    </div>
    <button type="button" onclick="exportStudentRecap()" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold shadow">
      <i class="fa-solid fa-file-excel mr-1"></i> Export Rekap Excel (.xlsx)
    </button>
  </div>

  <!-- Recap Table -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="p-4 border-b border-slate-100">
      <h2 class="font-extrabold text-slate-800">Rekapitulasi Kehadiran Siswa</h2>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-blue-50 text-blue-900 text-xs uppercase">
          <tr>
            <th class="text-left px-5 py-3">Siswa</th>
            <th class="text-center px-3 py-3">Kelas</th>
            <th class="text-center px-3 py-3">Sesi</th>
            <th class="text-center px-3 py-3">Hadir</th>
            <th class="text-center px-3 py-3">Sakit</th>
            <th class="text-center px-3 py-3">Izin</th>
            <th class="text-center px-3 py-3">Alpha</th>
            <th class="text-left px-5 py-3 w-40">Persentase</th>
          </tr>
        </thead>
        <tbody id="trRecapBody" class="divide-y divide-slate-100"></tbody>
      </table>
    </div>
  </div>
</div>

<script>
const SLUG = '<?= Security::e($slug) ?>';
let studentRecapRows = [];

function loadTeacherReports() {
  const cls = $('#trClass').val();
  ApiClient.get('<?= app_url('/api/teacher/reports') ?>', {
    school_slug: SLUG,
    class_name: cls,
    start_date: $('#trStart').val(),
    end_date: $('#trEnd').val()
  }).then(res => {
    const classes = res.data.classes || [];
    let opts = '<option value="">Semua Kelas</option>';
    classes.forEach(c => opts += `<option value="${ApiClient.e(c)}" ${c===cls?'selected':''}>Kelas ${ApiClient.e(c)}</option>`);
    $('#trClass').html(opts);

    studentRecapRows = res.data.recap || [];
    if (!studentRecapRows.length) {
      $('#trRecapBody').html('<tr><td colspan="8" class="text-center py-8 text-slate-400">Belum ada data rekap siswa.</td></tr>');
      return;
    }
    let html = '';
    studentRecapRows.forEach(r => {
      const color = r.percentage >= 85 ? 'bg-emerald-500' : (r.percentage >= 65 ? 'bg-amber-500' : 'bg-red-500');
      html += `<tr class="hover:bg-blue-50/30">
        <td class="px-5 py-3"><div class="font-bold text-slate-800">${ApiClient.e(r.name)}</div><div class="text-xs font-mono text-slate-400">${ApiClient.e(r.nisn)}</div></td>
        <td class="px-3 py-3 text-center"><span class="px-2 py-0.5 rounded bg-blue-100 text-blue-800 text-xs font-bold">${ApiClient.e(r.class_name)}</span></td>
        <td class="px-3 py-3 text-center font-bold">${r.total_sessions}</td>
        <td class="px-3 py-3 text-center font-bold text-emerald-600">${r.hadir}</td>
        <td class="px-3 py-3 text-center font-bold text-purple-600">${r.sakit}</td>
        <td class="px-3 py-3 text-center font-bold text-sky-600">${r.izin}</td>
        <td class="px-3 py-3 text-center font-bold text-red-600">${r.alpha}</td>
        <td class="px-5 py-3">
          <div class="flex items-center gap-2">
            <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden"><div class="h-full ${color}" style="width:${r.percentage}%"></div></div>
            <span class="text-xs font-extrabold w-10 text-right">${r.percentage}%</span>
          </div>
        </td>
      </tr>`;
    });
    $('#trRecapBody').html(html);
  });
}

function exportStudentRecap() {
  if (!studentRecapRows.length) return AppModal.showError('Belum ada data untuk diexport.');
  const rows = studentRecapRows.map(r => ({
    'NISN': r.nisn,
    'Nama Siswa': r.name,
    'Kelas': r.class_name,
    'Total Sesi': r.total_sessions,
    'Hadir': r.hadir,
    'Sakit': r.sakit,
    'Izin': r.izin,
    'Alpha': r.alpha,
    'Persentase (%)': r.percentage + '%'
  }));
  ExcelHelper.exportToExcel(rows, 'Rekap_Siswa_' + SLUG + '.xlsx', 'Rekap Siswa');
}

$('#trClass, #trStart, #trEnd').on('change', loadTeacherReports);
$(loadTeacherReports);
</script>
