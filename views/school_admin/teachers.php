<?php $slug = $school['slug']; ?>
<div class="space-y-5">
  <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
      <h2 class="font-extrabold text-slate-800">Daftar Akun Guru (Absensi Siswa QR Code)</h2>
      <p class="text-xs text-slate-500">Guru yang didaftarkan dapat login di portal sekolah untuk membuat sesi absen kelas dan melakukan scan QR siswa.</p>
    </div>
    <button type="button" onclick="openTeacherModal()" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-extrabold shadow shrink-0">
      <i class="fa-solid fa-user-plus mr-1"></i> Buat Akun Guru
    </button>
  </div>

  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-blue-50/80 text-blue-900 text-xs uppercase">
          <tr>
            <th class="text-left px-5 py-3.5">Nama Guru & Email</th>
            <th class="text-left px-4 py-3.5">Mata Pelajaran</th>
            <th class="text-left px-4 py-3.5">Kelas Diampu</th>
            <th class="text-center px-4 py-3.5">Status</th>
            <th class="text-right px-5 py-3.5">Aksi</th>
          </tr>
        </thead>
        <tbody id="teachersTableBody" class="divide-y divide-slate-100"></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Tambah/Edit Guru -->
<div id="teacherModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
  <div class="modal-pop bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden">
    <div class="bg-blue-700 text-white px-6 py-4 flex items-center justify-between">
      <h3 id="tModalTitle" class="font-extrabold text-base">Buat Akun Guru</h3>
      <button type="button" onclick="$('#teacherModal').addClass('hidden').removeClass('flex')" class="text-white/80"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form id="teacherForm" class="p-6 space-y-4">
      <input type="hidden" id="tId" value="0">
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">NIP / Kode Guru</label>
          <input type="text" id="tNip" placeholder="GRU-001" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Nama Lengkap Guru *</label>
          <input type="text" id="tName" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Email Login *</label>
          <input type="email" id="tEmail" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Password <span class="text-[10px] text-slate-400">(kosong=tetap)</span></label>
          <input type="text" id="tPassword" placeholder="Guru123!" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Mata Pelajaran</label>
          <input type="text" id="tSubject" placeholder="Matematika / Tematik" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">No HP / WA</label>
          <input type="text" id="tPhone" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1">Kelas Diampu (pisahkan koma, misal: 6A,6B)</label>
        <input type="text" id="tClasses" placeholder="6A,6B,5A" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>
      <div class="flex justify-end gap-2 pt-2">
        <button type="button" onclick="$('#teacherModal').addClass('hidden').removeClass('flex')" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-600">Batal</button>
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-bold shadow">Simpan Akun Guru</button>
      </div>
    </form>
  </div>
</div>

<script>
const SLUG = '<?= Security::e($slug) ?>';
let teachers = [];

function loadTeachers() {
  ApiClient.get('<?= app_url('/api/school_admin/teachers-list') ?>', { school_slug: SLUG }).then(function (res) {
    teachers = res.data.teachers;
    if (!teachers.length) {
      $('#teachersTableBody').html('<tr><td colspan="5" class="text-center py-10 text-slate-400">Belum ada akun guru terdaftar.</td></tr>');
      return;
    }
    let html = '';
    teachers.forEach(t => {
      html += `<tr class="hover:bg-blue-50/30">
        <td class="px-5 py-3.5"><div class="font-extrabold text-slate-800">${ApiClient.e(t.name)}</div><div class="text-xs text-blue-600">${ApiClient.e(t.email)}</div><div class="text-[11px] text-slate-400">${ApiClient.e(t.nip_nik || '-')} • ${ApiClient.e(t.phone || '-')}</div></td>
        <td class="px-4 py-3.5 text-xs font-semibold text-slate-700">${ApiClient.e(t.subject_name || '-')}</td>
        <td class="px-4 py-3.5 text-xs"><span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 font-bold">${ApiClient.e(t.assigned_classes || 'Semua Kelas')}</span></td>
        <td class="px-4 py-3.5 text-center"><span class="px-2.5 py-0.5 rounded-full text-xs font-bold ${parseInt(t.is_active,10)===1?'bg-emerald-100 text-emerald-700':'bg-slate-200 text-slate-500'}">${parseInt(t.is_active,10)===1?'Aktif':'Nonaktif'}</span></td>
        <td class="px-5 py-3.5 text-right">
          <button type="button" onclick="openTeacherModal(${t.id})" class="px-2.5 py-1.5 rounded-lg bg-blue-100 text-blue-700 text-xs font-bold mr-1"><i class="fa-solid fa-pen"></i></button>
          <button type="button" onclick="deleteTeacher(${t.id}, '${ApiClient.e(t.name)}')" class="px-2.5 py-1.5 rounded-lg bg-red-50 text-red-600 text-xs font-bold"><i class="fa-solid fa-trash"></i></button>
        </td>
      </tr>`;
    });
    $('#teachersTableBody').html(html);
  });
}

function openTeacherModal(id) {
  const t = id ? teachers.find(x => parseInt(x.id,10) === parseInt(id,10)) : null;
  if (t) {
    $('#tModalTitle').text('Edit Akun Guru');
    $('#tId').val(t.id);
    $('#tNip').val(t.nip_nik || '');
    $('#tName').val(t.name);
    $('#tEmail').val(t.email);
    $('#tPhone').val(t.phone || '');
    $('#tSubject').val(t.subject_name || '');
    $('#tClasses').val(t.assigned_classes || '');
    $('#tPassword').val('');
  } else {
    $('#tModalTitle').text('Buat Akun Guru Baru');
    $('#teacherForm')[0].reset();
    $('#tId').val(0);
    $('#tPassword').val('Guru123!');
  }
  $('#teacherModal').removeClass('hidden').addClass('flex');
}

$('#teacherForm').on('submit', function (e) {
  e.preventDefault();
  ApiClient.post('<?= app_url('/api/school_admin/teacher-save') ?>', {
    school_slug: SLUG,
    id: parseInt($('#tId').val(), 10),
    nip_nik: $('#tNip').val().trim(),
    name: $('#tName').val().trim(),
    email: $('#tEmail').val().trim(),
    phone: $('#tPhone').val().trim(),
    subject_name: $('#tSubject').val().trim(),
    assigned_classes: $('#tClasses').val().trim(),
    password: $('#tPassword').val()
  }, { loadingTitle: 'Menyimpan Akun Guru...' })
  .then(res => {
    $('#teacherModal').addClass('hidden').removeClass('flex');
    res.mode === 'updated' ? AppModal.showUpdated(res.message, null, loadTeachers) : AppModal.showSaved(res.message, null, loadTeachers);
  }).catch(() => {});
});

async function deleteTeacher(id, name) {
  const ok = await AppModal.confirm({ title: 'Hapus Akun Guru?', message: `Hapus akun guru "${name}"?`, danger: true, confirmText: 'Ya, Hapus' });
  if (!ok) return;
  ApiClient.post('<?= app_url('/api/school_admin/teacher-delete') ?>', { school_slug: SLUG, id: id })
    .then(res => AppModal.showUpdated(res.message, 'Dihapus', loadTeachers)).catch(() => {});
}

$(loadTeachers);
</script>
