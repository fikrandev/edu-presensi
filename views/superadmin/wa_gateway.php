<!-- WA Gateway LIVE API — Pengingat Jatuh Tempo H-3 & Hari-H (Tanpa Simulasi) -->
<div class="space-y-6">
  <!-- Live Connection Status Banner -->
  <div id="waReadyBanner" class="rounded-2xl p-4 border flex flex-wrap items-center justify-between gap-3 bg-white border-slate-200">
    <div class="flex items-center gap-3">
      <div id="waReadyIcon" class="w-10 h-10 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center text-lg">
        <i class="fa-brands fa-whatsapp"></i>
      </div>
      <div>
        <div id="waReadyTitle" class="text-sm font-extrabold text-slate-800">Memeriksa Status Live WA Gateway...</div>
        <div id="waReadyDesc" class="text-xs text-slate-500">-</div>
      </div>
    </div>
    <span id="waReadyPill" class="px-3 py-1 rounded-full text-xs font-extrabold bg-slate-100 text-slate-500">Checking...</span>
  </div>

  <div class="grid lg:grid-cols-3 gap-6">
    <!-- Config Form -->
    <form id="waConfigForm" class="lg:col-span-2 bg-white rounded-2xl p-6 border border-slate-200/80 shadow-[0_2px_12px_rgba(15,23,42,0.03)] space-y-4">
      <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
        <div>
          <h2 class="font-extrabold text-slate-800"><i class="fa-brands fa-whatsapp text-emerald-600 mr-2"></i>Konfigurasi Live WA Gateway</h2>
          <p class="text-xs text-slate-400">Pengiriman nyata via HTTP API provider (Fonnte, Wablas, Starsender, MPWA/Baileys, WAHA, WhaCenter).</p>
        </div>
        <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 cursor-pointer">
          <input type="checkbox" id="waEnabled" class="rounded text-emerald-600 w-4 h-4">
          <span class="text-xs font-extrabold text-emerald-800">Aktifkan Live WA Gateway</span>
        </label>
      </div>

      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Provider WA Gateway *</label>
          <select id="waProvider" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold bg-white outline-none focus:border-[#1E5BF0]">
            <option value="fonnte">Fonnte (api.fonnte.com)</option>
            <option value="wablas">Wablas (wablas.com)</option>
            <option value="starsender">Starsender (api.starsender.online)</option>
            <option value="mpwa">MPWA / M-Pedia / Baileys Self-Hosted</option>
            <option value="waha">WAHA (WhatsApp HTTP API)</option>
            <option value="whacenter">WhaCenter (app.whacenter.com)</option>
            <option value="generic">Generic REST JSON Webhook</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1" id="waSenderLabel">Nomor Pengirim / Session (Opsional)</label>
          <input type="text" id="waSender" placeholder="62812xxxx atau nama session" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:border-[#1E5BF0]">
        </div>
      </div>

      <div id="waProviderHint" class="rounded-xl bg-[#F8FBFF] border border-blue-100 p-3 text-[11px] text-blue-800"></div>

      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">API Endpoint URL *</label>
          <input type="url" id="waApiUrl" required placeholder="https://api.fonnte.com/send" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono outline-none focus:border-[#1E5BF0]">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1" id="waTokenLabel">API Token / Key *</label>
          <input type="text" id="waApiToken" placeholder="Masukkan token API asli dari provider" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono outline-none focus:border-[#1E5BF0]">
        </div>
      </div>

      <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 text-[11px] text-slate-600">
        Variabel template tersedia: <code>{nama_sekolah}</code>, <code>{paket}</code>, <code>{tanggal_expired}</code>, <code>{nominal}</code>, <code>{link_portal}</code>, <code>{app_name}</code>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1">Template Pengingat H-3 (3 Hari Sebelum Jatuh Tempo)</label>
        <textarea id="waTplH3" rows="4" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono outline-none focus:border-[#1E5BF0]"></textarea>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1">Template Pengingat Hari-H (Saat Jatuh Tempo)</label>
        <textarea id="waTplH0" rows="4" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono outline-none focus:border-[#1E5BF0]"></textarea>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1">Cron Job Otomatis Harian (H-3 &amp; H-0)</label>
        <div class="grid sm:grid-cols-3 gap-2">
          <input type="text" id="waCronSecret" class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono">
          <input type="text" id="waCronUrl" readonly class="sm:col-span-2 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs font-mono text-slate-600">
        </div>
      </div>

      <div class="flex justify-end pt-1">
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#1E5BF0] hover:bg-[#194ED3] text-white text-sm font-bold shadow-md shadow-blue-600/20">
          <i class="fa-solid fa-floppy-disk mr-1"></i> Simpan Konfigurasi Live WA
        </button>
      </div>
    </form>

    <!-- Action Panel: Run Due Reminders, Send to Specific School, & Live Test -->
    <div class="space-y-5">
      <!-- 1. Run H-3 & H-0 -->
      <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-[0_2px_12px_rgba(15,23,42,0.03)] space-y-3">
        <h3 class="font-extrabold text-slate-800 text-sm"><i class="fa-solid fa-bolt text-amber-500 mr-1.5"></i>Eksekusi Pengingat H-3 &amp; H-0</h3>
        <p class="text-xs text-slate-500">Kirim WhatsApp nyata ke seluruh sekolah yang jatuh tempo 3 hari lagi atau hari ini.</p>
        <div id="upcomingDueWrap" class="text-xs space-y-2"></div>
        <button type="button" onclick="runWaReminders()" class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold shadow transition">
          <i class="fa-brands fa-whatsapp mr-1"></i> Kirim Pengingat Jatuh Tempo Sekarang
        </button>
      </div>

      <!-- 2. Send Manual Billing Reminder to Specific School -->
      <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-[0_2px_12px_rgba(15,23,42,0.03)] space-y-3">
        <h3 class="font-extrabold text-slate-800 text-sm"><i class="fa-solid fa-school text-[#1E5BF0] mr-1.5"></i>Kirim Tagihan ke Sekolah Tertentu</h3>
        <select id="waManualSchool" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-bold bg-white"></select>
        <select id="waManualTpl" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white">
          <option value="h3">Gunakan Template H-3</option>
          <option value="h0">Gunakan Template Hari-H (Jatuh Tempo)</option>
        </select>
        <button type="button" onclick="sendManualSchoolWa()" class="w-full py-2.5 rounded-xl bg-[#1E5BF0] hover:bg-[#194ED3] text-white text-xs font-bold">
          <i class="fa-solid fa-paper-plane mr-1"></i> Kirim WA ke Sekolah Ini
        </button>
      </div>

      <!-- 3. Live Test Connection -->
      <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-[0_2px_12px_rgba(15,23,42,0.03)] space-y-3">
        <h3 class="font-extrabold text-slate-800 text-sm"><i class="fa-solid fa-vial-circle-check text-emerald-600 mr-1.5"></i>Tes Kirim Live WA</h3>
        <input type="text" id="testPhone" placeholder="No WA Anda (misal 0852xxxx)" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
        <textarea id="testMessage" rows="2" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs">Halo! Ini tes kirim LIVE WA Gateway dari EduPresensi SaaS.</textarea>
        <button type="button" onclick="sendTestWa()" class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold">
          Kirim Pesan Tes Nyata
        </button>
      </div>
    </div>
  </div>

  <!-- WA Delivery Logs (Showing Real Provider API Response) -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-[0_2px_12px_rgba(15,23,42,0.03)] overflow-hidden">
    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
      <div>
        <h2 class="font-extrabold text-slate-800">Riwayat Pengiriman &amp; Response Server WA Gateway</h2>
        <p class="text-[11px] text-slate-400">Menampilkan status asli dan balasan HTTP/JSON dari server provider WA Anda.</p>
      </div>
      <button type="button" onclick="loadWaConfig()" class="text-xs font-bold text-[#1E5BF0] hover:underline"><i class="fa-solid fa-rotate mr-1"></i>Refresh</button>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-xs">
        <thead class="bg-[#F8FAFC] text-slate-500 uppercase border-b border-slate-100">
          <tr>
            <th class="text-left px-5 py-3">Waktu</th>
            <th class="text-left px-4 py-3">Sekolah / Tujuan</th>
            <th class="text-center px-3 py-3">Tipe</th>
            <th class="text-left px-4 py-3">Response Provider Asli</th>
            <th class="text-center px-4 py-3">Status</th>
          </tr>
        </thead>
        <tbody id="waLogsBody" class="divide-y divide-slate-100"></tbody>
      </table>
    </div>
  </div>
</div>

<script>
const PROVIDER_META = {
  fonnte: {
    url: 'https://api.fonnte.com/send',
    tokenLabel: 'Fonnte Device Token *',
    senderLabel: 'Nomor Pengirim (Opsional)',
    hint: '<b>Fonnte:</b> Ambil Device Token dari dashboard <code>md.fonnte.com</code> → Menu Device → Token. Pastikan status device di Fonnte sudah <b>Connected</b>.'
  },
  wablas: {
    url: 'https://pati.wablas.com/api/send-message',
    tokenLabel: 'Wablas API Token (atau Token.SecretKey) *',
    senderLabel: 'Nomor Pengirim (Opsional)',
    hint: '<b>Wablas:</b> Ganti subdomain URL (misal <code>pati.wablas.com</code> / <code>kudus.wablas.com</code>) sesuai server akun Wablas Anda. Untuk Wablas v2 isi <code>TOKEN.SECRET_KEY</code>.'
  },
  starsender: {
    url: 'https://api.starsender.online/api/send',
    tokenLabel: 'Starsender Device API Key *',
    senderLabel: 'Nomor Pengirim (Opsional)',
    hint: '<b>Starsender:</b> Salin API Key device dari menu Device di dashboard Starsender.'
  },
  mpwa: {
    url: 'https://wa.domain-anda.com/send-message',
    tokenLabel: 'MPWA / Baileys API Key *',
    senderLabel: 'Nomor WA Pengirim (Wajib untuk MPWA) *',
    hint: '<b>MPWA / M-Pedia / Baileys:</b> Wajib mengisi Nomor Pengirim (format 628xxx) sesuai nomor device yang terscan QR di server MPWA Anda.'
  },
  waha: {
    url: 'http://localhost:3000/api/sendText',
    tokenLabel: 'WAHA X-Api-Key *',
    senderLabel: 'Nama Session WAHA (default: default)',
    hint: '<b>WAHA:</b> Isi API Key pada header <code>X-Api-Key</code> dan nama session WAHA (misal <code>default</code>).'
  },
  whacenter: {
    url: 'https://app.whacenter.com/api/send',
    tokenLabel: 'WhaCenter Device ID *',
    senderLabel: 'Nomor Pengirim (Opsional)',
    hint: '<b>WhaCenter:</b> Masukkan <code>device_id</code> dari dashboard app.whacenter.com.'
  },
  generic: {
    url: 'https://api.gateway-anda.com/send',
    tokenLabel: 'Bearer API Token *',
    senderLabel: 'Nomor Pengirim (Opsional)',
    hint: '<b>Generic REST JSON:</b> Mengirim POST JSON dengan header <code>Authorization: Bearer {token}</code> dan body <code>{api_key, sender, target, phone, number, message}</code>.'
  }
};

function updateProviderHint(autoFillUrl) {
  const p = $('#waProvider').val() || 'fonnte';
  const m = PROVIDER_META[p] || PROVIDER_META.generic;
  $('#waProviderHint').html('<i class="fa-solid fa-circle-info mr-1 text-[#1E5BF0]"></i> ' + m.hint);
  $('#waTokenLabel').text(m.tokenLabel);
  $('#waSenderLabel').text(m.senderLabel);
  if (autoFillUrl) {
    $('#waApiUrl').val(m.url);
  }
}

$('#waProvider').on('change', function () {
  updateProviderHint(true);
});

function loadWaConfig() {
  ApiClient.get('<?= app_url('/api/superadmin/wa-config-get') ?>').then(function (res) {
    const c = res.data.config;
    $('#waProvider').val(c.wa_provider || 'fonnte');
    $('#waApiUrl').val(c.wa_api_url || '');
    $('#waApiToken').val(c.wa_api_token || '');
    $('#waSender').val(c.wa_sender || '');
    $('#waEnabled').prop('checked', parseInt(c.wa_enabled, 10) === 1);
    $('#waTplH3').val(c.wa_template_h3 || '');
    $('#waTplH0').val(c.wa_template_h0 || '');
    $('#waCronSecret').val(c.cron_secret || '');
    $('#waCronUrl').val(c.cron_url || '');
    updateProviderHint(false);

    // Status Banner
    if (c.is_ready) {
      $('#waReadyBanner').removeClass('border-slate-200 border-amber-200 bg-amber-50/40').addClass('border-emerald-200 bg-emerald-50/50');
      $('#waReadyIcon').removeClass('bg-slate-100 text-slate-500 bg-amber-100 text-amber-600').addClass('bg-emerald-600 text-white');
      $('#waReadyTitle').text('Live WA Gateway AKTIF — Siap Kirim Nyata (' + (c.wa_provider || '').toUpperCase() + ')');
      $('#waReadyDesc').text('Endpoint aktif: ' + c.wa_api_url);
      $('#waReadyPill').removeClass().addClass('px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-600 text-white').text('LIVE API AKTIF');
    } else {
      $('#waReadyBanner').removeClass('border-slate-200 border-emerald-200 bg-emerald-50/50').addClass('border-amber-200 bg-amber-50/60');
      $('#waReadyIcon').removeClass('bg-slate-100 text-slate-500 bg-emerald-600 text-white').addClass('bg-amber-500 text-white');
      $('#waReadyTitle').text('Live WA Gateway Belum Aktif');
      $('#waReadyDesc').text(c.ready_reason || 'Lengkapi API URL, Token asli, dan aktifkan Live WA Gateway.');
      $('#waReadyPill').removeClass().addClass('px-3 py-1 rounded-full text-xs font-extrabold bg-amber-500 text-white').text('BELUM AKTIF');
    }

    // Schools dropdown for manual send
    const schools = res.data.schools || [];
    let scOpts = '<option value="">-- Pilih Sekolah --</option>';
    schools.forEach(s => {
      scOpts += `<option value="${s.id}">${ApiClient.e(s.name)} (${ApiClient.e(s.phone || 'Belum ada No WA')})</option>`;
    });
    $('#waManualSchool').html(scOpts);

    // Upcoming eligible schools
    const up = res.data.upcoming || [];
    if (!up.length) {
      $('#upcomingDueWrap').html('<div class="p-2.5 rounded-xl bg-slate-50 text-slate-500">Tidak ada sekolah tepat H-3 atau H-0 hari ini.</div>');
    } else {
      let uHtml = '';
      up.forEach(s => {
        const tag = parseInt(s.days_left, 10) === 3 ? 'H-3' : 'HARI-H';
        uHtml += `<div class="p-2.5 rounded-xl bg-amber-50 border border-amber-200 flex justify-between items-center">
          <div><div class="font-bold text-slate-800">${ApiClient.e(s.name)}</div><div class="text-[10px] text-slate-500">${ApiClient.e(s.phone || '-')} • Exp ${ApiClient.e(s.subscription_end)}</div></div>
          <span class="px-2 py-0.5 rounded-full bg-amber-600 text-white font-bold text-[10px]">${tag}</span>
        </div>`;
      });
      $('#upcomingDueWrap').html(uHtml);
    }

    // Logs
    const logs = res.data.logs || [];
    if (!logs.length) {
      $('#waLogsBody').html('<tr><td colspan="5" class="text-center py-8 text-slate-400">Belum ada riwayat pengiriman WA.</td></tr>');
    } else {
      let lHtml = '';
      logs.forEach(l => {
        const st = l.status === 'sent'
          ? '<span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-bold">Terkirim</span>'
          : '<span class="px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-700 font-bold">Gagal</span>';
        lHtml += `<tr class="hover:bg-slate-50/70">
          <td class="px-5 py-3 whitespace-nowrap text-slate-500 font-mono">${ApiClient.e(l.sent_at)}</td>
          <td class="px-4 py-3"><div class="font-bold text-slate-800">${ApiClient.e(l.school_name || 'Tes / Manual')}</div><div class="text-slate-500 font-mono">${ApiClient.e(l.phone)}</div></td>
          <td class="px-3 py-3 text-center uppercase font-bold text-[#1E5BF0]">${ApiClient.e(l.reminder_type)}</td>
          <td class="px-4 py-3 max-w-md"><div class="text-slate-700 break-words">${ApiClient.e(l.response_body || '-')}</div></td>
          <td class="px-4 py-3 text-center">${st}</td>
        </tr>`;
      });
      $('#waLogsBody').html(lHtml);
    }
  });
}

$('#waConfigForm').on('submit', function (e) {
  e.preventDefault();
  ApiClient.post('<?= app_url('/api/superadmin/wa-config-save') ?>', {
    wa_provider: $('#waProvider').val(),
    wa_api_url: $('#waApiUrl').val().trim(),
    wa_api_token: $('#waApiToken').val().trim(),
    wa_sender: $('#waSender').val().trim(),
    wa_enabled: $('#waEnabled').is(':checked'),
    wa_template_h3: $('#waTplH3').val(),
    wa_template_h0: $('#waTplH0').val(),
    cron_secret: $('#waCronSecret').val().trim()
  }, { loadingTitle: 'Menyimpan Konfigurasi Live WA...' })
  .then(res => AppModal.showUpdated(res.message, null, loadWaConfig)).catch(() => {});
});

async function runWaReminders() {
  const ok = await AppModal.confirm({
    title: 'Kirim Live WA Jatuh Tempo?',
    message: 'Sistem akan mengirim pesan WhatsApp nyata melalui API provider Anda ke sekolah yang jatuh tempo H-3 dan Hari-H.'
  });
  if (!ok) return;
  ApiClient.post('<?= app_url('/api/superadmin/wa-run-reminders') ?>', {}, { loadingTitle: 'Mengirim Live WA Pengingat...' })
    .then(res => AppModal.showSaved(res.message, 'Pengiriman Selesai', loadWaConfig)).catch(() => loadWaConfig());
}

function sendManualSchoolWa() {
  const sid = parseInt($('#waManualSchool').val(), 10);
  if (!sid) return AppModal.showError('Pilih sekolah tujuan terlebih dahulu.');
  ApiClient.post('<?= app_url('/api/superadmin/wa-send-school') ?>', {
    school_id: sid,
    template_type: $('#waManualTpl').val()
  }, { loadingTitle: 'Mengirim WA Tagihan...' })
  .then(res => AppModal.showSaved(res.message, 'WA Terkirim!', loadWaConfig)).catch(() => loadWaConfig());
}

function sendTestWa() {
  ApiClient.post('<?= app_url('/api/superadmin/wa-test') ?>', {
    phone: $('#testPhone').val().trim(),
    message: $('#testMessage').val().trim()
  }, { loadingTitle: 'Mengirim Live Tes WA...' })
  .then(res => AppModal.showSaved(res.message, 'Live WA Berhasil Terkirim!', loadWaConfig)).catch(() => loadWaConfig());
}

$(loadWaConfig);
</script>
