<?php $slug = $school['slug']; ?>
<div class="space-y-4">
  <!-- Schedule & Geofence Card -->
  <div class="bg-white rounded-3xl p-4 shadow-sm border border-blue-100 space-y-3">
    <div class="flex items-center justify-between">
      <div>
        <div class="text-xs font-extrabold text-slate-800" id="attLocTitle">Memuat lokasi target...</div>
        <div class="text-[11px] text-slate-500" id="attShiftInfo">-</div>
      </div>
      <button type="button" onclick="refreshGps()" class="px-3 py-1.5 rounded-xl bg-blue-50 text-blue-700 text-xs font-bold">
        <i class="fa-solid fa-crosshairs mr-1"></i>GPS
      </button>
    </div>

    <div id="empAttMap" class="h-44 w-full rounded-2xl border border-slate-200 z-0"></div>

    <div id="gpsStatusBanner" class="rounded-2xl p-3 bg-slate-100 text-slate-600 text-xs font-bold flex items-center gap-2">
      <i class="fa-solid fa-spinner fa-spin"></i> Mendeteksi lokasi GPS Anda...
    </div>
  </div>

  <!-- Optional Selfie Capture -->
  <div class="bg-white rounded-3xl p-4 shadow-sm border border-slate-200/70 space-y-3">
    <div class="flex items-center justify-between">
      <div class="text-xs font-extrabold text-slate-800"><i class="fa-solid fa-camera text-blue-600 mr-1.5"></i>Foto Selfie (Opsional)</div>
      <button type="button" id="btnToggleSelfie" onclick="toggleSelfieCamera()" class="px-3 py-1.5 rounded-xl bg-blue-50 text-blue-700 text-xs font-bold">Buka Kamera</button>
    </div>
    <div id="selfieWrap" class="hidden space-y-2 text-center">
      <video id="selfieVideo" autoplay playsinline class="w-full h-48 object-cover rounded-2xl bg-slate-900"></video>
      <canvas id="selfieCanvas" class="hidden"></canvas>
      <img id="selfiePreview" class="hidden w-full h-48 object-cover rounded-2xl border border-blue-200">
      <button type="button" id="btnSnapSelfie" onclick="snapSelfie()" class="px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-bold">Ambil Foto</button>
    </div>
    <input type="text" id="attNote" placeholder="Catatan absen (opsional)..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs">
  </div>

  <!-- Action Buttons -->
  <div class="grid grid-cols-2 gap-3">
    <button type="button" id="btnCheckin" onclick="doAttendance('checkin')" class="py-4 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white font-extrabold text-sm shadow-lg shadow-emerald-600/25 flex flex-col items-center justify-center gap-1 transition disabled:opacity-40">
      <i class="fa-solid fa-right-to-bracket text-xl"></i>
      <span>CHECK-IN</span>
      <span id="btnInTime" class="text-[10px] font-semibold opacity-90">Absen Masuk</span>
    </button>

    <button type="button" id="btnCheckout" onclick="doAttendance('checkout')" class="py-4 rounded-2xl bg-gradient-to-br from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-extrabold text-sm shadow-lg shadow-blue-600/25 flex flex-col items-center justify-center gap-1 transition disabled:opacity-40">
      <i class="fa-solid fa-right-from-bracket text-xl"></i>
      <span>CHECK-OUT</span>
      <span id="btnOutTime" class="text-[10px] font-semibold opacity-90">Absen Pulang</span>
    </button>
  </div>
</div>

<script>
const SLUG = '<?= Security::e($slug) ?>';
let scheduleData = null;
let myLat = null, myLng = null, myDist = null;
let attMap = null, userMarker = null;
let selfieStream = null, selfieBase64 = '';

function haversine(lat1, lon1, lat2, lon2) {
  const R = 6371000;
  const dLat = (lat2 - lat1) * Math.PI / 180;
  const dLon = (lon2 - lon1) * Math.PI / 180;
  const a = Math.sin(dLat/2)**2 + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLon/2)**2;
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function loadAttendanceState() {
  ApiClient.get('<?= app_url('/api/employee/dashboard') ?>', { school_slug: SLUG }).then(res => {
    const d = res.data;
    scheduleData = d.schedule;
    $('#attLocTitle').text(scheduleData.location_name);
    $('#attShiftInfo').text(`${scheduleData.shift_name} (${scheduleData.start_time.substring(0,5)} - ${scheduleData.end_time.substring(0,5)}) • Maks ${scheduleData.target_radius}m`);

    if (d.today && d.today.checkin_time) {
      $('#btnCheckin').prop('disabled', true);
      $('#btnInTime').text('Masuk ' + d.today.checkin_time.substring(11,16));
    }
    if (!d.today || !d.today.checkin_time) {
      $('#btnCheckout').prop('disabled', true);
    } else if (d.today.checkout_time) {
      $('#btnCheckout').prop('disabled', true);
      $('#btnOutTime').text('Pulang ' + d.today.checkout_time.substring(11,16));
    }

    if (!attMap) {
      attMap = L.map('empAttMap', { zoomControl: false }).setView([scheduleData.target_lat, scheduleData.target_lng], 16);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(attMap);
      L.marker([scheduleData.target_lat, scheduleData.target_lng]).addTo(attMap).bindPopup(scheduleData.location_name);
      L.circle([scheduleData.target_lat, scheduleData.target_lng], { radius: scheduleData.target_radius, color: '#2563eb', fillOpacity: 0.15 }).addTo(attMap);
    }
    refreshGps();
  });
}

function refreshGps() {
  if (!navigator.geolocation) {
    $('#gpsStatusBanner').removeClass().addClass('rounded-2xl p-3 bg-red-50 text-red-700 text-xs font-bold').text('Perangkat tidak mendukung GPS.');
    return;
  }
  navigator.geolocation.getCurrentPosition(
    pos => {
      myLat = pos.coords.latitude;
      myLng = pos.coords.longitude;
      myDist = Math.round(haversine(myLat, myLng, scheduleData.target_lat, scheduleData.target_lng));
      const inRange = myDist <= scheduleData.target_radius;

      if (attMap) {
        if (!userMarker) userMarker = L.circleMarker([myLat, myLng], { radius: 8, color: '#059669', fillColor: '#10b981', fillOpacity: 1 }).addTo(attMap);
        else userMarker.setLatLng([myLat, myLng]);
        attMap.panTo([myLat, myLng]);
      }

      if (inRange) {
        $('#gpsStatusBanner').removeClass().addClass('rounded-2xl p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-2')
          .html(`<i class="fa-solid fa-circle-check text-emerald-600"></i> Dalam jangkauan! Jarak: ${myDist} m (Batas ${scheduleData.target_radius} m)`);
      } else {
        $('#gpsStatusBanner').removeClass().addClass('rounded-2xl p-3 bg-amber-50 border border-amber-200 text-amber-800 text-xs font-bold flex items-center gap-2')
          .html(`<i class="fa-solid fa-triangle-exclamation text-amber-600"></i> Di luar radius! Jarak Anda ${myDist} m (Maks ${scheduleData.target_radius} m)`);
      }
    },
    () => {
      $('#gpsStatusBanner').removeClass().addClass('rounded-2xl p-3 bg-red-50 border border-red-200 text-red-700 text-xs font-bold flex items-center gap-2')
        .html('<i class="fa-solid fa-location-crosshairs"></i> Izin lokasi ditolak / GPS mati. Aktifkan GPS lalu tekan tombol GPS.');
    },
    { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
  );
}

async function toggleSelfieCamera() {
  if (selfieStream) {
    selfieStream.getTracks().forEach(t => t.stop());
    selfieStream = null;
    $('#selfieWrap').addClass('hidden');
    $('#btnToggleSelfie').text('Buka Kamera');
    return;
  }
  try {
    selfieStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
    document.getElementById('selfieVideo').srcObject = selfieStream;
    $('#selfieWrap').removeClass('hidden');
    $('#selfiePreview').addClass('hidden');
    $('#selfieVideo').removeClass('hidden');
    $('#btnToggleSelfie').text('Tutup Kamera');
  } catch (e) {
    AppModal.showError('Tidak dapat membuka kamera depan: ' + e.message);
  }
}

function snapSelfie() {
  const video = document.getElementById('selfieVideo');
  const canvas = document.getElementById('selfieCanvas');
  canvas.width = 480;
  canvas.height = 360;
  canvas.getContext('2d').drawImage(video, 0, 0, 480, 360);
  selfieBase64 = canvas.toDataURL('image/jpeg', 0.72);
  $('#selfiePreview').attr('src', selfieBase64).removeClass('hidden');
  $('#selfieVideo').addClass('hidden');
  if (selfieStream) { selfieStream.getTracks().forEach(t => t.stop()); selfieStream = null; }
}

async function doAttendance(type) {
  if (myLat === null || myLng === null) {
    return AppModal.showError('Lokasi GPS belum terdeteksi. Aktifkan GPS dan tekan tombol GPS di atas.');
  }
  if (type === 'checkout') {
    const ok = await AppModal.confirm({
      title: 'Konfirmasi Check-out?',
      message: `Kirim absen PULANG sekarang dari jarak ${myDist} meter?`,
      confirmText: 'Ya, Check-out'
    });
    if (!ok) return;
  }

  ApiClient.post('<?= app_url('/api/employee/') ?>' + type, {
    school_slug: SLUG,
    lat: myLat,
    lng: myLng,
    note: $('#attNote').val().trim(),
    photo: selfieBase64
  }, { loadingTitle: type === 'checkin' ? 'Mencatat Check-in...' : 'Mencatat Check-out...' })
  .then(res => {
    const cb = () => window.location.href = '<?= Tenant::schoolLocalUrl($slug, 'pegawai/dashboard') ?>';
    type === 'checkin' ? AppModal.showSaved(res.message, 'Check-in Tercatat!', cb) : AppModal.showUpdated(res.message, 'Check-out Tercatat!', cb);
  }).catch(() => {});
}

$(loadAttendanceState);
</script>
