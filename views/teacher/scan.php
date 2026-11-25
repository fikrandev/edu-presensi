<?php
$slug = $school['slug'];
$sessionId = (int)($_GET['session_id'] ?? 0);
?>
<div class="space-y-5">
  <!-- Session Picker Bar -->
  <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div class="flex-1">
      <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Pilih Sesi Absen Aktif</label>
      <select id="scanSessionSelect" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-800"></select>
    </div>
    <div id="scanStatsPill" class="flex items-center gap-2 text-xs font-extrabold"></div>
  </div>

  <div class="grid lg:grid-cols-12 gap-5">
    <!-- Live QR Scanner Column -->
    <div class="lg:col-span-5 space-y-4">
      <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="font-extrabold text-slate-800 text-sm"><i class="fa-solid fa-camera text-blue-600 mr-1.5"></i>Kamera Scanner QR Siswa</h2>
          <div class="flex gap-1.5">
            <button type="button" id="btnStartCam" onclick="startQrScanner()" class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-bold"><i class="fa-solid fa-play mr-1"></i>Mulai</button>
            <button type="button" id="btnStopCam" onclick="stopQrScanner()" class="hidden px-3 py-1.5 rounded-lg bg-red-600 text-white text-xs font-bold"><i class="fa-solid fa-stop mr-1"></i>Stop</button>
          </div>
        </div>

        <div id="qrReader" class="w-full rounded-2xl overflow-hidden bg-slate-900 min-h-[240px] flex items-center justify-center text-slate-400 text-xs">
          <span>Tekan tombol <b>Mulai</b> untuk mengaktifkan kamera scanner QR</span>
        </div>

        <!-- Last Scan Feedback Toast -->
        <div id="scanFeedbackBox" class="hidden rounded-xl p-3.5 text-xs font-bold border"></div>

        <!-- Manual Input Fallback -->
        <form id="manualScanForm" class="pt-2 border-t border-slate-100">
          <label class="block text-[11px] font-bold text-slate-500 mb-1">Atau Input Manual Kode QR / NISN (juga cocok untuk USB Barcode Scanner)</label>
          <div class="flex gap-2">
            <input type="text" id="manualQrCode" placeholder="Scan / ketik NISN atau kode QR..." class="flex-1 px-3 py-2.5 rounded-xl border border-slate-200 text-sm font-mono">
            <button type="submit" class="px-4 py-2.5 rounded-xl bg-blue-600 text-white text-xs font-extrabold">Catat</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Live Class Attendance List -->
    <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
      <div class="p-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="font-extrabold text-slate-800 text-sm"><i class="fa-solid fa-users text-blue-600 mr-1.5"></i>Daftar Siswa Kelas</h2>
        <button type="button" onclick="loadSessionDetail()" class="text-xs font-bold text-blue-600 hover:underline"><i class="fa-solid fa-rotate mr-1"></i>Refresh</button>
      </div>
      <div class="overflow-x-auto max-h-[540px] overflow-y-auto">
        <table class="w-full text-xs">
          <thead class="bg-blue-50 text-blue-900 uppercase sticky top-0">
            <tr>
              <th class="text-left px-4 py-3">Siswa</th>
              <th class="text-center px-3 py-3">Waktu Scan</th>
              <th class="text-right px-4 py-3">Status Kehadiran</th>
            </tr>
          </thead>
          <tbody id="sessionStudentsBody" class="divide-y divide-slate-100"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
const SLUG = '<?= Security::e($slug) ?>';
let activeSessionId = <?= $sessionId ?>;
let html5Qr = null;
let scanCooldown = false;

function playBeep(success) {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.type = success ? 'sine' : 'sawtooth';
    osc.frequency.value = success ? 880 : 280;
    gain.gain.value = 0.15;
    osc.connect(gain); gain.connect(ctx.destination);
    osc.start(); setTimeout(() => { osc.stop(); ctx.close(); }, success ? 140 : 280);
  } catch (e) {}
}

function loadSessionsDropdown() {
  ApiClient.get('<?= app_url('/api/teacher/sessions-list') ?>', { school_slug: SLUG }).then(res => {
    const list = res.data || [];
    if (!list.length) {
      $('#scanSessionSelect').html('<option value="0">Belum ada sesi — buat di menu Sesi Absen</option>');
      return;
    }
    if (!activeSessionId) activeSessionId = list[0].id;
    let opts = '';
    list.forEach(s => {
      opts += `<option value="${s.id}" ${parseInt(s.id,10)===parseInt(activeSessionId,10)?'selected':''}>[${s.status==='open'?'BUKA':'TUTUP'}] ${ApiClient.e(s.session_date)} — Kelas ${ApiClient.e(s.class_name)} (${ApiClient.e(s.session_title)})</option>`;
    });
    $('#scanSessionSelect').html(opts);
    loadSessionDetail();
  });
}

function loadSessionDetail() {
  activeSessionId = parseInt($('#scanSessionSelect').val(), 10);
  if (!activeSessionId) return;
  ApiClient.get('<?= app_url('/api/teacher/session-detail') ?>', { school_slug: SLUG, session_id: activeSessionId }).then(res => {
    const recs = res.data.records || [];
    let h = 0, sk = 0, iz = 0, al = 0;
    recs.forEach(r => { if(r.status==='hadir') h++; else if(r.status==='sakit') sk++; else if(r.status==='izin') iz++; else al++; });
    $('#scanStatsPill').html(`
      <span class="px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-700">Hadir: ${h}</span>
      <span class="px-2.5 py-1 rounded-lg bg-amber-100 text-amber-800">Izin/Sakit: ${iz + sk}</span>
      <span class="px-2.5 py-1 rounded-lg bg-red-100 text-red-700">Belum/Alpha: ${al}</span>
    `);

    let html = '';
    recs.forEach(r => {
      const btn = (st, label, activeCls) => `<button type="button" onclick="setManualStatus(${r.record_id},'${st}')" class="px-2 py-1 rounded-lg text-[11px] font-bold transition ${r.status===st ? activeCls : 'bg-slate-100 text-slate-500 hover:bg-slate-200'}">${label}</button>`;
      html += `<tr>
        <td class="px-4 py-3"><div class="font-bold text-slate-800">${ApiClient.e(r.name)}</div><div class="text-[10px] font-mono text-slate-400">NISN: ${ApiClient.e(r.nisn)}</div></td>
        <td class="px-3 py-3 text-center font-mono text-slate-600">${r.scanned_at ? r.scanned_at.substring(11,19) : '-'} ${r.scan_method==='qr'?'<i class="fa-solid fa-qrcode text-blue-600 ml-1"></i>':''}</td>
        <td class="px-4 py-3 text-right">
          <div class="inline-flex gap-1">
            ${btn('hadir','H','bg-emerald-600 text-white')}
            ${btn('sakit','S','bg-purple-600 text-white')}
            ${btn('izin','I','bg-sky-600 text-white')}
            ${btn('alpha','A','bg-red-600 text-white')}
          </div>
        </td>
      </tr>`;
    });
    $('#sessionStudentsBody').html(html);
  });
}

function submitScanCode(code) {
  if (!activeSessionId || !code) return;
  ApiClient.post('<?= app_url('/api/teacher/scan-qr') ?>', {
    school_slug: SLUG,
    session_id: activeSessionId,
    code: code
  }, { loading: false, silentError: true })
  .then(res => {
    playBeep(true);
    $('#scanFeedbackBox').removeClass('hidden bg-red-50 border-red-200 text-red-700').addClass('bg-emerald-50 border-emerald-200 text-emerald-800')
      .html(`<i class="fa-solid fa-circle-check mr-1.5"></i> ${ApiClient.e(res.message)}`);
    loadSessionDetail();
  })
  .catch(err => {
    playBeep(false);
    $('#scanFeedbackBox').removeClass('hidden bg-emerald-50 border-emerald-200 text-emerald-800').addClass('bg-red-50 border-red-200 text-red-700')
      .html(`<i class="fa-solid fa-circle-xmark mr-1.5"></i> ${ApiClient.e(err.message || 'Gagal scan QR')}`);
  });
}

function startQrScanner() {
  if (typeof Html5Qrcode === 'undefined') return AppModal.showError('Library kamera QR belum termuat.');
  $('#qrReader').html('');
  html5Qr = new Html5Qrcode('qrReader');
  html5Qr.start(
    { facingMode: 'environment' },
    { fps: 10, qrbox: { width: 220, height: 220 } },
    function (decodedText) {
      if (scanCooldown) return;
      scanCooldown = true;
      submitScanCode(decodedText.trim());
      setTimeout(() => { scanCooldown = false; }, 1600);
    },
    function () {}
  ).then(() => {
    $('#btnStartCam').addClass('hidden');
    $('#btnStopCam').removeClass('hidden');
  }).catch(err => {
    AppModal.showError('Tidak dapat mengakses kamera: ' + err + '. Gunakan input manual atau pastikan akses via HTTPS/localhost.');
  });
}

function stopQrScanner() {
  if (html5Qr) {
    html5Qr.stop().then(() => {
      html5Qr.clear();
      $('#btnStopCam').addClass('hidden');
      $('#btnStartCam').removeClass('hidden');
    }).catch(() => {});
  }
}

function setManualStatus(recordId, status) {
  ApiClient.post('<?= app_url('/api/teacher/record-status') ?>', {
    school_slug: SLUG,
    record_id: recordId,
    status: status
  }, { loading: false }).then(() => loadSessionDetail()).catch(() => {});
}

$('#manualScanForm').on('submit', function (e) {
  e.preventDefault();
  const code = $('#manualQrCode').val().trim();
  if (!code) return;
  submitScanCode(code);
  $('#manualQrCode').val('').focus();
});

$('#scanSessionSelect').on('change', loadSessionDetail);
$(loadSessionsDropdown);
</script>
