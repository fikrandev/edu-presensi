<?php $slug = $school['slug']; ?>
<div class="w-full space-y-6">
  <!-- CARD 1: PENGATURAN LATITUDE & LONGITUDE GLOBAL -->
  <div class="bg-white rounded-2xl p-6 sm:p-7 border border-slate-200/80 shadow-[0_2px_12px_rgba(15,23,42,0.03)] space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-[#1E5BF0] text-white flex items-center justify-center shadow-md shadow-blue-600/20">
          <i class="fa-solid fa-earth-asia"></i>
        </div>
        <div>
          <h2 class="text-base font-extrabold text-slate-800">Pengaturan Latitude &amp; Longitude Global</h2>
          <p class="text-xs text-slate-400">Semua pegawai otomatis mengikuti titik koordinat global ini — tinggal menyesuaikan pegawai/hari tertentu jika perlu.</p>
        </div>
      </div>
      <div class="flex flex-wrap items-center gap-2 text-xs font-bold">
        <span class="px-3 py-1.5 rounded-full bg-blue-50 text-[#1E5BF0]"><i class="fa-solid fa-users mr-1"></i> <span id="statGlobalEmp">0</span> Ikut Global</span>
        <span class="px-3 py-1.5 rounded-full bg-amber-50 text-amber-700"><i class="fa-solid fa-sliders mr-1"></i> <span id="statCustomEmp">0</span> Custom</span>
      </div>
    </div>

    <!-- Mode Pemilihan Global -->
    <div class="grid md:grid-cols-2 gap-3">
      <label class="flex items-start gap-3 p-4 rounded-2xl border-2 border-blue-200 bg-[#F8FBFF] cursor-pointer" id="modeCardFlex">
        <input type="radio" name="globalMode" value="0" checked class="mt-1 text-[#1E5BF0] focus:ring-[#1E5BF0]">
        <div>
          <div class="text-xs font-extrabold text-slate-800">Global + Bisa Disesuaikan per Orang/Hari <span class="ml-1 px-1.5 py-0.5 rounded bg-blue-100 text-[#1E5BF0] text-[10px]">Rekomendasi</span></div>
          <div class="text-[11px] text-slate-500 mt-0.5">Semua pegawai otomatis mengikuti koordinat Global di bawah, kecuali pegawai/hari yang diatur custom di menu Data Pegawai.</div>
        </div>
      </label>

      <label class="flex items-start gap-3 p-4 rounded-2xl border-2 border-slate-200 bg-white cursor-pointer" id="modeCardForce">
        <input type="radio" name="globalMode" value="1" class="mt-1 text-[#1E5BF0] focus:ring-[#1E5BF0]">
        <div>
          <div class="text-xs font-extrabold text-slate-800">Global Penuh (Paksa Semua Pegawai)</div>
          <div class="text-[11px] text-slate-500 mt-0.5">100% seluruh pegawai wajib absen di titik Global ini (mengabaikan sementara seluruh koordinat custom pribadi/harian).</div>
        </div>
      </label>
    </div>

    <!-- Quick Paste Google Maps / GPS Button -->
    <div class="grid lg:grid-cols-12 gap-3 items-end">
      <div class="lg:col-span-5">
        <label class="block text-xs font-bold text-slate-600 mb-1">Nama Lokasi Global</label>
        <input type="text" id="setLocName" placeholder="misal: Kampus Utama SDIT Bina Insan Palu" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold focus:border-[#1E5BF0] outline-none">
      </div>
      <div class="lg:col-span-5">
        <label class="block text-xs font-bold text-slate-600 mb-1">Tempel Koordinat / Link Google Maps (Cepat)</label>
        <div class="flex gap-2">
          <input type="text" id="pasteMapsInput" placeholder="-0.916500, 119.883200 atau URL Google Maps..." class="flex-1 px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-mono outline-none focus:border-[#1E5BF0]">
          <button type="button" onclick="parsePastedCoords()" class="px-3.5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold shrink-0">Ambil</button>
        </div>
      </div>
      <div class="lg:col-span-2">
        <button type="button" onclick="useCurrentGps()" class="w-full px-3 py-2.5 rounded-xl bg-[#EEF4FF] hover:bg-blue-100 text-[#1E5BF0] text-xs font-extrabold transition">
          <i class="fa-solid fa-crosshairs mr-1"></i> GPS Saya
        </button>
      </div>
    </div>

    <!-- Interactive Map -->
    <div id="officeMap" class="h-72 w-full rounded-2xl border border-slate-200 z-0"></div>

    <!-- Lat / Lng / Radius Inputs -->
    <div class="grid sm:grid-cols-3 gap-4">
      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1">Latitude Global *</label>
        <input type="text" id="setLat" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono font-bold bg-[#F8FBFF] focus:bg-white focus:border-[#1E5BF0] outline-none">
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1">Longitude Global *</label>
        <input type="text" id="setLng" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono font-bold bg-[#F8FBFF] focus:bg-white focus:border-[#1E5BF0] outline-none">
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1">Radius Geofence Global (Meter) *</label>
        <input type="number" id="setRadius" min="20" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-bold bg-[#F8FBFF] focus:bg-white focus:border-[#1E5BF0] outline-none">
      </div>
    </div>

    <!-- Sync Actions -->
    <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-100">
      <div class="flex flex-wrap items-center gap-4 text-xs">
        <label class="inline-flex items-center gap-2 font-bold text-slate-700 cursor-pointer">
          <input type="checkbox" id="chkApplyAllEmp" class="rounded text-[#1E5BF0] w-4 h-4">
          <span>Samakan seluruh pegawai ke Lokasi Global ini</span>
        </label>
        <label class="inline-flex items-center gap-2 font-semibold text-slate-500 cursor-pointer">
          <input type="checkbox" id="chkResetDailyLoc" class="rounded text-[#1E5BF0] w-4 h-4">
          <span>Reset juga koordinat custom harian</span>
        </label>
      </div>
      <button type="button" onclick="saveGlobalLocationOnly()" class="px-6 py-3 rounded-xl bg-[#1E5BF0] hover:bg-[#194ED3] text-white text-xs font-extrabold shadow-md shadow-blue-600/20 transition">
        <i class="fa-solid fa-floppy-disk mr-1.5"></i> Simpan Lokasi Global
      </button>
    </div>
  </div>

  <!-- CARD 2: PROFIL & IDENTITAS SEKOLAH -->
  <form id="schoolSettingsForm" class="bg-white rounded-2xl p-6 sm:p-7 border border-slate-200/80 shadow-[0_2px_12px_rgba(15,23,42,0.03)] space-y-5">
    <div class="border-b border-slate-100 pb-4 flex flex-wrap items-center justify-between gap-2">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-sm">
          <i class="fa-solid fa-school"></i>
        </div>
        <div>
          <h2 class="text-base font-extrabold text-slate-800">Profil &amp; Nama Sekolah</h2>
          <p class="text-xs text-slate-400">Identitas sekolah yang tampil di portal login, laporan, dan kartu QR siswa.</p>
        </div>
      </div>
      <div class="px-3 py-1.5 rounded-xl bg-blue-50 text-[#1E5BF0] text-xs font-bold">
        Portal: <span id="setDisplayUrl" class="font-mono">-</span>
      </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1">Nama Sekolah *</label>
        <input type="text" id="setName" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-bold">
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1">NPSN</label>
        <input type="text" id="setNpsn" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1">Kepala Sekolah</label>
        <input type="text" id="setPrincipal" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1">Email Sekolah</label>
        <input type="email" id="setEmail" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1">No Telepon / WA</label>
        <input type="text" id="setPhone" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
      </div>
    </div>

    <div>
      <label class="block text-xs font-bold text-slate-600 mb-1">Alamat Lengkap</label>
      <textarea id="setAddress" rows="2" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm"></textarea>
    </div>

    <div class="flex justify-end pt-2">
      <button type="submit" class="px-6 py-3 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-extrabold shadow transition">
        <i class="fa-solid fa-floppy-disk mr-1.5"></i> Simpan Profil Sekolah
      </button>
    </div>
  </form>
</div>

<script>
const SLUG = '<?= Security::e($slug) ?>';
let officeMap = null, officeMarker = null, officeCircle = null;

function updateModeCardStyles() {
  const isForce = $('input[name="globalMode"]:checked').val() === '1';
  $('#modeCardFlex').toggleClass('border-blue-200 bg-[#F8FBFF]', !isForce).toggleClass('border-slate-200 bg-white', isForce);
  $('#modeCardForce').toggleClass('border-blue-200 bg-[#F8FBFF]', isForce).toggleClass('border-slate-200 bg-white', !isForce);
}
$('input[name="globalMode"]').on('change', updateModeCardStyles);

function updateMapVisual(lat, lng, radius) {
  if (!officeMap) {
    officeMap = L.map('officeMap').setView([lat, lng], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OSM' }).addTo(officeMap);
    officeMarker = L.marker([lat, lng], { draggable: true }).addTo(officeMap);
    officeCircle = L.circle([lat, lng], { radius: radius, color: '#1E5BF0', fillOpacity: 0.14 }).addTo(officeMap);

    officeMap.on('click', function (e) { setCoords(e.latlng.lat, e.latlng.lng); });
    officeMarker.on('dragend', function () {
      const p = officeMarker.getLatLng();
      setCoords(p.lat, p.lng);
    });
  } else {
    officeMap.setView([lat, lng], 16);
    officeMarker.setLatLng([lat, lng]);
    officeCircle.setLatLng([lat, lng]).setRadius(radius);
  }
}

function setCoords(lat, lng) {
  $('#setLat').val(Number(lat).toFixed(7));
  $('#setLng').val(Number(lng).toFixed(7));
  const rad = parseInt($('#setRadius').val(), 10) || 150;
  updateMapVisual(Number(lat), Number(lng), rad);
}

function parsePastedCoords() {
  const txt = $('#pasteMapsInput').val().trim();
  if (!txt) return AppModal.showError('Tempel koordinat (contoh: -0.9165, 119.8832) atau link Google Maps terlebih dahulu.');
  // Match @lat,lng or plain lat, lng
  const m = txt.match(/(-?\d{1,2}\.\d+)\s*,\s*(-?\d{1,3}\.\d+)/);
  if (!m) return AppModal.showError('Format koordinat tidak dikenali. Contoh valid: -0.916500, 119.883200');
  setCoords(parseFloat(m[1]), parseFloat(m[2]));
  AppModal.showSaved(`Koordinat terbaca: ${m[1]}, ${m[2]}`, 'Koordinat Diterapkan ke Peta!');
}

function useCurrentGps() {
  if (!navigator.geolocation) return AppModal.showError('Browser tidak mendukung GPS.');
  AppModal.showLoading('Mendeteksi GPS...', 'Mengambil koordinat lokasi saat ini...');
  navigator.geolocation.getCurrentPosition(
    pos => { AppModal.hideLoading(); setCoords(pos.coords.latitude, pos.coords.longitude); },
    () => { AppModal.hideLoading(); AppModal.showError('Gagal mendapatkan lokasi GPS. Izinkan akses lokasi pada browser.'); },
    { enableHighAccuracy: true, timeout: 10000 }
  );
}

$('#setLat, #setLng').on('change', function () {
  const lat = parseFloat($('#setLat').val()), lng = parseFloat($('#setLng').val());
  if (!isNaN(lat) && !isNaN(lng)) updateMapVisual(lat, lng, parseInt($('#setRadius').val(), 10) || 150);
});
$('#setRadius').on('input', function () {
  if (officeCircle) officeCircle.setRadius(parseInt($(this).val(), 10) || 150);
});

function loadSettings() {
  ApiClient.get('<?= app_url('/api/school_admin/settings-get') ?>', { school_slug: SLUG }).then(function (res) {
    const d = res.data;
    $('#setDisplayUrl').text(d.display_url);
    $('#setName').val(d.name);
    $('#setNpsn').val(d.npsn || '');
    $('#setPrincipal').val(d.principal_name || '');
    $('#setEmail').val(d.email || '');
    $('#setPhone').val(d.phone || '');
    $('#setAddress').val(d.address || '');
    $('#setLocName').val(d.office_location_name || d.name);
    $('#setLat').val(d.office_lat);
    $('#setLng').val(d.office_lng);
    $('#setRadius').val(d.office_radius);
    $(`input[name="globalMode"][value="${d.force_global_location ? '1' : '0'}"]`).prop('checked', true);
    updateModeCardStyles();
    if (d.employee_stats) {
      $('#statGlobalEmp').text(d.employee_stats.global);
      $('#statCustomEmp').text(d.employee_stats.custom);
    }
    updateMapVisual(d.office_lat, d.office_lng, d.office_radius);
  });
}

async function saveGlobalLocationOnly() {
  const applyAll = $('#chkApplyAllEmp').is(':checked');
  const resetDaily = $('#chkResetDailyLoc').is(':checked');
  if (applyAll || resetDaily) {
    const ok = await AppModal.confirm({
      title: 'Terapkan Lokasi Global ke Semua Pegawai?',
      message: 'Koordinat khusus pegawai akan di-reset agar seluruh pegawai langsung mengikuti Latitude & Longitude Global ini.'
    });
    if (!ok) return;
  }

  ApiClient.post('<?= app_url('/api/school_admin/global-location-save') ?>', {
    school_slug: SLUG,
    office_location_name: $('#setLocName').val().trim(),
    office_lat: parseFloat($('#setLat').val()),
    office_lng: parseFloat($('#setLng').val()),
    office_radius: parseInt($('#setRadius').val(), 10),
    force_global_location: $('input[name="globalMode"]:checked').val() === '1',
    apply_to_all: applyAll,
    reset_daily_locations: resetDaily
  }, { loadingTitle: 'Menyimpan Lokasi Global...' })
  .then(res => {
    $('#chkApplyAllEmp, #chkResetDailyLoc').prop('checked', false);
    AppModal.showUpdated(res.message, 'Lokasi Global Diperbarui!', loadSettings);
  }).catch(() => {});
}

$(function () {
  loadSettings();
  $('#schoolSettingsForm').on('submit', function (e) {
    e.preventDefault();
    ApiClient.post('<?= app_url('/api/school_admin/settings-save') ?>', {
      school_slug: SLUG,
      name: $('#setName').val().trim(),
      npsn: $('#setNpsn').val().trim(),
      principal_name: $('#setPrincipal').val().trim(),
      email: $('#setEmail').val().trim(),
      phone: $('#setPhone').val().trim(),
      address: $('#setAddress').val().trim(),
      office_location_name: $('#setLocName').val().trim(),
      office_lat: parseFloat($('#setLat').val()),
      office_lng: parseFloat($('#setLng').val()),
      office_radius: parseInt($('#setRadius').val(), 10),
      force_global_location: $('input[name="globalMode"]:checked').val() === '1'
    }, { loadingTitle: 'Menyimpan Profil Sekolah...' })
    .then(res => AppModal.showUpdated(res.message, 'Profil Diperbarui!', () => location.reload())).catch(() => {});
  });
});
</script>
