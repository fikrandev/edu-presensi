<?php
$slug = $school['slug'];
$sessionId = (int)($_GET['session_id'] ?? 0);
?>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>

<div class="space-y-5">
  <!-- Session Picker Bar -->
  <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-[0_2px_12px_rgba(15,23,42,0.03)] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div class="flex-1">
      <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Pilih Sesi Absen Aktif</label>
      <select id="scanSessionSelect" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-800 bg-white outline-none focus:border-[#1E5BF0]"></select>
    </div>
    <div id="scanStatsPill" class="flex flex-wrap items-center gap-2 text-xs font-extrabold"></div>
  </div>

  <div class="grid lg:grid-cols-12 gap-5">
    <!-- FULL-FRAME LIVE CAMERA SCANNER (TANPA KOTAK PEMBATAS) -->
    <div class="lg:col-span-6 space-y-4">
      <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-[0_2px_12px_rgba(15,23,42,0.03)] space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div>
            <h2 class="font-extrabold text-slate-800 text-sm"><i class="fa-solid fa-camera text-[#1E5BF0] mr-1.5"></i>Kamera Scan QR Full Layar</h2>
            <p class="text-[11px] text-slate-400">Arahkan kartu QR ke mana saja di layar kamera — langsung terbaca otomatis.</p>
          </div>
          <div class="flex items-center gap-1.5">
            <button type="button" id="btnSwitchCam" onclick="switchCamera()" class="hidden px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold" title="Ganti Kamera Depan/Belakang">
              <i class="fa-solid fa-camera-rotate"></i>
            </button>
            <button type="button" id="btnStartCam" onclick="startFullScanner()" class="px-3.5 py-2 rounded-xl bg-[#1E5BF0] hover:bg-[#194ED3] text-white text-xs font-extrabold shadow-sm">
              <i class="fa-solid fa-play mr-1"></i>Buka Kamera
            </button>
            <button type="button" id="btnStopCam" onclick="stopFullScanner()" class="hidden px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-extrabold">
              <i class="fa-solid fa-stop mr-1"></i>Stop
            </button>
          </div>
        </div>

        <!-- Full Camera Viewport (No confining qrbox!) -->
        <div id="cameraContainer" class="relative w-full aspect-[4/3] sm:aspect-video rounded-2xl overflow-hidden bg-slate-950 flex items-center justify-center">
          <video id="qrVideo" autoplay playsinline muted class="hidden w-full h-full object-cover"></video>
          <canvas id="qrWorkCanvas" class="hidden"></canvas>

          <!-- Placeholder before camera starts -->
          <div id="camPlaceholder" class="text-center p-6 text-slate-400 space-y-3">
            <div class="w-14 h-14 rounded-2xl bg-white/10 text-blue-400 mx-auto flex items-center justify-center text-2xl">
              <i class="fa-solid fa-qrcode"></i>
            </div>
            <div class="text-xs font-semibold text-slate-300">Tekan tombol <b>Buka Kamera</b> untuk mulai scan QR seluruh layar</div>
          </div>

          <!-- Live Scanning Indicator Pill -->
          <div id="camLiveBadge" class="hidden absolute top-3 left-3 px-3 py-1.5 rounded-full bg-black/55 backdrop-blur text-white text-[11px] font-bold flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span id="camEngineLabel">Scanning Full Layar...</span>
          </div>

          <!-- Instant On-Camera Scan Toast Overlay -->
          <div id="camOverlayToast" class="hidden absolute inset-x-3 bottom-3 rounded-xl px-4 py-3 text-xs font-extrabold shadow-xl backdrop-blur transition"></div>
        </div>

        <!-- Last Scan Feedback Box -->
        <div id="scanFeedbackBox" class="hidden rounded-xl p-3.5 text-xs font-bold border"></div>

        <!-- Manual Input / USB Barcode Scanner -->
        <form id="manualScanForm" class="pt-2 border-t border-slate-100">
          <label class="block text-[11px] font-bold text-slate-500 mb-1.5">Input Manual Kode QR / NISN (juga mendukung USB Barcode Scanner)</label>
          <div class="flex gap-2">
            <input type="text" id="manualQrCode" placeholder="Ketik / tembak scanner NISN atau kode QR..." class="flex-1 px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono outline-none focus:border-[#1E5BF0]">
            <button type="submit" class="px-4 py-2.5 rounded-xl bg-[#1E5BF0] hover:bg-[#194ED3] text-white text-xs font-extrabold">Catat</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Live Class Attendance List -->
    <div class="lg:col-span-6 bg-white rounded-2xl border border-slate-200/80 shadow-[0_2px_12px_rgba(15,23,42,0.03)] overflow-hidden">
      <div class="p-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="font-extrabold text-slate-800 text-sm"><i class="fa-solid fa-users text-[#1E5BF0] mr-1.5"></i>Daftar Siswa Kelas</h2>
        <button type="button" onclick="loadSessionDetail()" class="text-xs font-bold text-[#1E5BF0] hover:underline"><i class="fa-solid fa-rotate mr-1"></i>Refresh</button>
      </div>
      <div class="overflow-x-auto max-h-[580px] overflow-y-auto">
        <table class="w-full text-xs">
          <thead class="bg-[#F8FAFC] text-slate-500 uppercase sticky top-0 border-b border-slate-100">
            <tr>
              <th class="text-left px-4 py-3">Siswa</th>
              <th class="text-center px-3 py-3">Waktu Scan</th>
              <th class="text-right px-4 py-3">Status</th>
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

// Full-frame scanner state
let mediaStream = null;
let scanLoopTimer = null;
let isScanning = false;
let isProcessingFrame = false;
let currentFacing = 'environment';
let nativeDetector = null;
const recentCodes = {}; // Per-code cooldown map (allows different students to scan immediately!)
let toastHideTimer = null;

// Initialize native hardware-accelerated BarcodeDetector if browser supports it
if ('BarcodeDetector' in window) {
  try {
    nativeDetector = new BarcodeDetector({ formats: ['qr_code', 'code_128', 'code_39', 'ean_13'] });
  } catch (e) {
    try { nativeDetector = new BarcodeDetector({ formats: ['qr_code'] }); } catch (e2) { nativeDetector = null; }
  }
}

function playBeep(success) {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.type = success ? 'sine' : 'sawtooth';
    osc.frequency.value = success ? 920 : 260;
    gain.gain.value = 0.18;
    osc.connect(gain); gain.connect(ctx.destination);
    osc.start();
    setTimeout(() => { osc.stop(); ctx.close(); }, success ? 130 : 260);
  } catch (e) {}
}

function showScanFeedback(ok, message) {
  const clsOk = 'bg-emerald-50 border-emerald-200 text-emerald-800';
  const clsErr = 'bg-rose-50 border-rose-200 text-rose-700';
  const icon = ok ? '<i class="fa-solid fa-circle-check mr-1.5"></i>' : '<i class="fa-solid fa-circle-xmark mr-1.5"></i>';

  $('#scanFeedbackBox')
    .removeClass('hidden ' + clsOk + ' ' + clsErr)
    .addClass(ok ? clsOk : clsErr)
    .html(icon + ApiClient.e(message));

  // Also show bold toast directly over the full camera screen
  $('#camOverlayToast')
    .removeClass('hidden bg-emerald-600/95 bg-rose-600/95 text-white')
    .addClass((ok ? 'bg-emerald-600/95' : 'bg-rose-600/95') + ' text-white')
    .html(icon + ApiClient.e(message));

  clearTimeout(toastHideTimer);
  toastHideTimer = setTimeout(() => $('#camOverlayToast').addClass('hidden'), 2600);
}

function loadSessionsDropdown() {
  ApiClient.get('<?= app_url('/api/teacher/sessions-list') ?>', { school_slug: SLUG }).then(res => {
    const list = res.data || [];
    if (!list.length) {
      $('#scanSessionSelect').html('<option value="0">Belum ada sesi — buat dulu di menu Sesi Absen</option>');
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
      <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700">Hadir: ${h}</span>
      <span class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700">Izin/Sakit: ${iz + sk}</span>
      <span class="px-2.5 py-1 rounded-lg bg-rose-50 text-rose-700">Belum/Alpha: ${al}</span>
    `);

    let html = '';
    recs.forEach(r => {
      const btn = (st, label, activeCls) => `<button type="button" onclick="setManualStatus(${r.record_id},'${st}')" class="px-2 py-1 rounded-lg text-[11px] font-bold transition ${r.status===st ? activeCls : 'bg-slate-100 text-slate-500 hover:bg-slate-200'}">${label}</button>`;
      html += `<tr class="hover:bg-slate-50/70">
        <td class="px-4 py-3"><div class="font-bold text-slate-800">${ApiClient.e(r.name)}</div><div class="text-[10px] font-mono text-slate-400">NISN: ${ApiClient.e(r.nisn)}</div></td>
        <td class="px-3 py-3 text-center font-mono text-slate-600">${r.scanned_at ? r.scanned_at.substring(11,19) : '-'} ${r.scan_method==='qr'?'<i class="fa-solid fa-qrcode text-[#1E5BF0] ml-1"></i>':''}</td>
        <td class="px-4 py-3 text-right">
          <div class="inline-flex gap-1">
            ${btn('hadir','H','bg-emerald-600 text-white')}
            ${btn('sakit','S','bg-purple-600 text-white')}
            ${btn('izin','I','bg-sky-600 text-white')}
            ${btn('alpha','A','bg-rose-600 text-white')}
          </div>
        </td>
      </tr>`;
    });
    $('#sessionStudentsBody').html(html);
  });
}

function submitScanCode(rawCode) {
  const code = String(rawCode || '').trim();
  if (!activeSessionId) {
    return showScanFeedback(false, 'Pilih sesi absen terlebih dahulu.');
  }
  if (!code) return;

  // Per-code 3-second cooldown so the same card isn't spammed, while other students can scan right away
  const now = Date.now();
  if (recentCodes[code] && (now - recentCodes[code]) < 3000) {
    return;
  }
  recentCodes[code] = now;

  ApiClient.post('<?= app_url('/api/teacher/scan-qr') ?>', {
    school_slug: SLUG,
    session_id: activeSessionId,
    code: code
  }, { loading: false, silentError: true })
  .then(res => {
    playBeep(true);
    showScanFeedback(true, res.message);
    loadSessionDetail();
  })
  .catch(err => {
    playBeep(false);
    showScanFeedback(false, err.message || 'Kartu QR gagal dibaca / tidak terdaftar.');
  });
}

async function startFullScanner() {
  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    return AppModal.showError('Browser tidak mendukung akses kamera. Pastikan halaman dibuka melalui HTTPS.');
  }
  stopFullScanner();

  const video = document.getElementById('qrVideo');
  try {
    try {
      mediaStream = await navigator.mediaDevices.getUserMedia({
        video: {
          facingMode: { ideal: currentFacing },
          width: { ideal: 1280 },
          height: { ideal: 720 }
        },
        audio: false
      });
    } catch (e1) {
      // Fallback for laptops/devices without facingMode constraint
      mediaStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
    }

    video.srcObject = mediaStream;
    await video.play();

    $('#camPlaceholder').addClass('hidden');
    $('#qrVideo, #camLiveBadge, #btnStopCam, #btnSwitchCam').removeClass('hidden');
    $('#btnStartCam').addClass('hidden');
    $('#camEngineLabel').text(nativeDetector ? 'Scan Full Layar (Cepat)' : 'Scan Full Layar Aktif');

    isScanning = true;
    scanLoopTimer = setInterval(scanFullFrame, 120); // ~8-10 full-frame scans/sec without freezing UI
  } catch (err) {
    AppModal.showError('Gagal membuka kamera: ' + (err.message || err) + '. Pastikan izin kamera diizinkan & memakai HTTPS.');
  }
}

async function scanFullFrame() {
  if (!isScanning || isProcessingFrame) return;
  const video = document.getElementById('qrVideo');
  if (!video || video.readyState < 2 || !video.videoWidth || !video.videoHeight) return;

  isProcessingFrame = true;
  try {
    // Engine 1: Hardware-accelerated BarcodeDetector on full video frame
    if (nativeDetector) {
      try {
        const codes = await nativeDetector.detect(video);
        if (codes && codes.length > 0 && codes[0].rawValue) {
          submitScanCode(codes[0].rawValue);
          isProcessingFrame = false;
          return;
        }
      } catch (e) {
        // Fall through to jsQR
      }
    }

    // Engine 2: Universal full-frame jsQR decoder (scans 100% of frame, NO crop box!)
    if (typeof jsQR === 'function') {
      const canvas = document.getElementById('qrWorkCanvas');
      const maxDim = 720;
      const scale = Math.min(1, maxDim / Math.max(video.videoWidth, video.videoHeight));
      const w = Math.floor(video.videoWidth * scale);
      const h = Math.floor(video.videoHeight * scale);
      if (canvas.width !== w || canvas.height !== h) {
        canvas.width = w;
        canvas.height = h;
      }
      const ctx = canvas.getContext('2d', { willReadFrequently: true });
      ctx.drawImage(video, 0, 0, w, h);
      const imgData = ctx.getImageData(0, 0, w, h);
      const qr = jsQR(imgData.data, w, h, { inversionAttempts: 'attemptBoth' });
      if (qr && qr.data) {
        submitScanCode(qr.data);
      }
    }
  } finally {
    isProcessingFrame = false;
  }
}

function stopFullScanner() {
  isScanning = false;
  if (scanLoopTimer) { clearInterval(scanLoopTimer); scanLoopTimer = null; }
  if (mediaStream) {
    mediaStream.getTracks().forEach(t => t.stop());
    mediaStream = null;
  }
  const video = document.getElementById('qrVideo');
  if (video) video.srcObject = null;
  $('#qrVideo, #camLiveBadge, #btnStopCam, #btnSwitchCam').addClass('hidden');
  $('#camPlaceholder, #btnStartCam').removeClass('hidden');
}

function switchCamera() {
  currentFacing = currentFacing === 'environment' ? 'user' : 'environment';
  startFullScanner();
}

$('#manualScanForm').on('submit', function (e) {
  e.preventDefault();
  const code = $('#manualQrCode').val().trim();
  if (!code) return;
  delete recentCodes[code];
  submitScanCode(code);
  $('#manualQrCode').val('').focus();
});

$('#scanSessionSelect').on('change', loadSessionDetail);
window.addEventListener('beforeunload', stopFullScanner);
$(loadSessionsDropdown);
</script>
