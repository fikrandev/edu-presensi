<?php $slug = $school['slug']; ?>
<div class="space-y-6">
  <!-- Create Session Card -->
  <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-sm">
    <div class="flex items-center gap-3 mb-4">
      <div class="w-11 h-11 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center text-lg">
        <i class="fa-solid fa-calendar-plus"></i>
      </div>
      <div>
        <h2 class="font-extrabold text-slate-800 text-base">Buat Sesi Absen Baru (Tarik Data Kelas)</h2>
        <p class="text-xs text-slate-500">Pilih kelas — seluruh daftar siswa di kelas tersebut akan otomatis ditarik ke sesi absensi QR.</p>
      </div>
    </div>

    <form id="createSessionForm" class="grid sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1">Pilih Kelas *</label>
        <select id="sessClass" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm font-bold bg-white"></select>
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1">Mata Pelajaran *</label>
        <input type="text" id="sessSubject" required value="<?= Security::e($currentUser['subject_name'] ?? 'Tematik / Umum') ?>" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1">Judul Sesi</label>
        <input type="text" id="sessTitle" placeholder="Otomatis jika kosong" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1">Tanggal</label>
        <input type="date" id="sessDate" value="<?= date('Y-m-d') ?>" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>
      <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-extrabold shadow-md shadow-blue-600/25 transition">
        <i class="fa-solid fa-wand-magic-sparkles mr-1"></i> Buat & Tarik Siswa
      </button>
    </form>
  </div>

  <!-- Sessions List -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
      <h2 class="font-extrabold text-slate-800">Daftar Sesi Absensi Siswa</h2>
      <span class="text-xs text-slate-500">Klik <b>Scan QR</b> untuk mulai memindai kartu siswa</span>
    </div>
    <div id="sessionsListWrap" class="divide-y divide-slate-100"></div>
  </div>
</div>

<script>
const SLUG = '<?= Security::e($slug) ?>';

function loadTeacherDashboard() {
  ApiClient.get('<?= app_url('/api/teacher/classes') ?>', { school_slug: SLUG }).then(res => {
    const cls = res.data || [];
    if (!cls.length) {
      $('#sessClass').html('<option value="">Belum ada data siswa di sekolah</option>');
    } else {
      let opts = '<option value="">-- Pilih Kelas --</option>';
      cls.forEach(c => opts += `<option value="${ApiClient.e(c.class_name)}">Kelas ${ApiClient.e(c.class_name)} (${c.student_count} siswa)</option>`);
      $('#sessClass').html(opts);
    }
  });

  ApiClient.get('<?= app_url('/api/teacher/sessions-list') ?>', { school_slug: SLUG }).then(res => {
    const list = res.data || [];
    if (!list.length) {
      $('#sessionsListWrap').html('<div class="p-10 text-center text-slate-400 text-sm">Belum ada sesi absensi. Buat sesi pertama di atas!</div>');
      return;
    }
    let html = '';
    list.forEach(s => {
      const isOpen = s.status === 'open';
      const pct = s.total_students > 0 ? Math.round((s.hadir_count / s.total_students) * 100) : 0;
      const scanUrl = '<?= Tenant::schoolLocalUrl($slug, 'guru/scan') ?>?session_id=' + s.id;
      html += `<div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-blue-50/30">
        <div class="min-w-0 flex-1">
          <div class="flex flex-wrap items-center gap-2">
            <span class="px-2.5 py-0.5 rounded-lg bg-blue-100 text-blue-800 text-xs font-extrabold">Kelas ${ApiClient.e(s.class_name)}</span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold ${isOpen ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600'}">${isOpen ? 'Sesi Dibuka' : 'Sesi Ditutup'}</span>
            <span class="text-xs text-slate-400 font-mono">${ApiClient.e(s.session_date)}</span>
          </div>
          <div class="font-extrabold text-slate-800 mt-1.5">${ApiClient.e(s.session_title)}</div>
          <div class="text-xs text-slate-500 mt-0.5">Mapel: <b>${ApiClient.e(s.subject_name)}</b> • Hadir: <b class="text-emerald-600">${s.hadir_count}/${s.total_students} (${pct}%)</b> • Sakit: ${s.sakit_count} • Izin: ${s.izin_count} • Alpha: ${s.alpha_count}</div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
          <a href="${scanUrl}" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-extrabold shadow">
            <i class="fa-solid fa-qrcode mr-1"></i> Scan QR
          </a>
          <button type="button" onclick="toggleSession(${s.id}, '${isOpen ? 'closed' : 'open'}')" class="px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-100">
            ${isOpen ? 'Tutup' : 'Buka'}
          </button>
        </div>
      </div>`;
    });
    $('#sessionsListWrap').html(html);
  });
}

$('#createSessionForm').on('submit', function (e) {
  e.preventDefault();
  ApiClient.post('<?= app_url('/api/teacher/session-create') ?>', {
    school_slug: SLUG,
    class_name: $('#sessClass').val(),
    subject_name: $('#sessSubject').val().trim(),
    session_title: $('#sessTitle').val().trim(),
    session_date: $('#sessDate').val()
  }, { loadingTitle: 'Menarik Data Kelas...' })
  .then(res => {
    AppModal.showSaved(res.message, 'Sesi Absen Siap!', function () {
      window.location.href = '<?= Tenant::schoolLocalUrl($slug, 'guru/scan') ?>?session_id=' + res.data.session_id;
    });
  }).catch(() => {});
});

async function toggleSession(id, nextStatus) {
  const ok = await AppModal.confirm({
    title: nextStatus === 'closed' ? 'Tutup Sesi Absen?' : 'Buka Kembali Sesi?',
    message: nextStatus === 'closed' ? 'Scan QR tidak dapat dilakukan selama sesi ditutup.' : 'Sesi akan dibuka kembali untuk scan QR.'
  });
  if (!ok) return;
  ApiClient.post('<?= app_url('/api/teacher/session-toggle') ?>', { school_slug: SLUG, session_id: id, status: nextStatus })
    .then(res => AppModal.showUpdated(res.message, null, loadTeacherDashboard)).catch(() => {});
}

$(loadTeacherDashboard);
</script>
