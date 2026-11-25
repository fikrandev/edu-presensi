<!-- 1. CUSTOM MODAL LOADING -->
<div id="appModalLoading" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
  <div class="modal-pop bg-white rounded-2xl shadow-2xl max-w-xs w-full p-6 text-center border border-blue-100">
    <div class="relative w-16 h-16 mx-auto mb-4">
      <div class="absolute inset-0 rounded-full border-4 border-blue-100"></div>
      <div class="absolute inset-0 rounded-full border-4 border-blue-600 border-t-transparent animate-spin"></div>
      <div class="absolute inset-0 flex items-center justify-center text-blue-600 text-lg">
        <i class="fa-solid fa-bolt"></i>
      </div>
    </div>
    <h3 id="appModalLoadingTitle" class="text-base font-bold text-slate-800">Memproses Data...</h3>
    <p id="appModalLoadingSub" class="text-xs text-slate-500 mt-1">Mohon tunggu sebentar...</p>
  </div>
</div>

<!-- 2. CUSTOM MODAL KONFIRMASI -->
<div id="appModalConfirm" class="fixed inset-0 z-[95] hidden items-center justify-center p-4">
  <div id="appModalConfirmBackdrop" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
  <div class="modal-pop relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 border border-slate-100">
    <div class="flex items-start gap-4">
      <div id="appModalConfirmIconWrap" class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl shrink-0">
        <i class="fa-solid fa-circle-question"></i>
      </div>
      <div class="flex-1">
        <h3 id="appModalConfirmTitle" class="text-lg font-bold text-slate-800">Konfirmasi Tindakan</h3>
        <p id="appModalConfirmMessage" class="text-sm text-slate-600 mt-1 leading-relaxed"></p>
      </div>
    </div>
    <div class="flex items-center justify-end gap-2.5 mt-6">
      <button id="appModalConfirmCancel" type="button" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold text-sm transition">
        Batal
      </button>
      <button id="appModalConfirmOk" type="button" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-md shadow-blue-600/20 transition">
        Ya, Lanjutkan
      </button>
    </div>
  </div>
</div>

<!-- 3. CUSTOM MODAL DATA TERSIMPAN -->
<div id="appModalSaved" class="fixed inset-0 z-[96] hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
  <div class="modal-pop bg-white rounded-2xl shadow-2xl max-w-sm w-full p-7 text-center border border-emerald-100">
    <div class="icon-bounce w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 mx-auto mb-4 flex items-center justify-center text-3xl">
      <i class="fa-solid fa-circle-check"></i>
    </div>
    <h3 id="appModalSavedTitle" class="text-xl font-extrabold text-slate-800">Data Tersimpan!</h3>
    <p id="appModalSavedMessage" class="text-sm text-slate-600 mt-2 leading-relaxed"></p>
    <button id="appModalSavedBtn" type="button" class="mt-6 w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-lg shadow-blue-600/25 transition">
      <i class="fa-solid fa-check mr-1"></i> Mengerti
    </button>
  </div>
</div>

<!-- 4. CUSTOM MODAL DATA TERUPDATE -->
<div id="appModalUpdated" class="fixed inset-0 z-[96] hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
  <div class="modal-pop bg-white rounded-2xl shadow-2xl max-w-sm w-full p-7 text-center border border-blue-100">
    <div class="icon-bounce w-16 h-16 rounded-full bg-blue-100 text-blue-600 mx-auto mb-4 flex items-center justify-center text-3xl">
      <i class="fa-solid fa-arrows-rotate"></i>
    </div>
    <h3 id="appModalUpdatedTitle" class="text-xl font-extrabold text-slate-800">Data Diperbarui!</h3>
    <p id="appModalUpdatedMessage" class="text-sm text-slate-600 mt-2 leading-relaxed"></p>
    <button id="appModalUpdatedBtn" type="button" class="mt-6 w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-lg shadow-blue-600/25 transition">
      <i class="fa-solid fa-thumbs-up mr-1"></i> Selesai
    </button>
  </div>
</div>

<!-- 5. CUSTOM MODAL ERROR / PERINGATAN -->
<div id="appModalError" class="fixed inset-0 z-[97] hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
  <div class="modal-pop bg-white rounded-2xl shadow-2xl max-w-sm w-full p-7 text-center border border-red-100">
    <div class="icon-bounce w-16 h-16 rounded-full bg-red-100 text-red-600 mx-auto mb-4 flex items-center justify-center text-3xl">
      <i class="fa-solid fa-triangle-exclamation"></i>
    </div>
    <h3 id="appModalErrorTitle" class="text-xl font-extrabold text-slate-800">Perhatian</h3>
    <p id="appModalErrorMessage" class="text-sm text-slate-600 mt-2 leading-relaxed"></p>
    <button id="appModalErrorBtn" type="button" class="mt-6 w-full py-3 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-sm transition">
      Tutup
    </button>
  </div>
</div>

<script src="<?= app_url('/assets/js/app-modals.js') ?>?v=<?= APP_VERSION ?>"></script>
<script src="<?= app_url('/assets/js/api-client.js') ?>?v=<?= APP_VERSION ?>"></script>
<?php if (!empty($pwaSwUrl)): ?>
  <script src="<?= app_url('/assets/js/pwa-register.js') ?>?v=<?= APP_VERSION ?>"></script>
<?php endif; ?>
