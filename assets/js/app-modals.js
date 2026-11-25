/**
 * EduPresensi Custom Modal Suite
 * - Modal Loading
 * - Modal Konfirmasi (Promise-based)
 * - Modal Data Tersimpan
 * - Modal Data Terupdate
 * - Modal Peringatan / Error
 */
window.AppModal = (function ($) {
  'use strict';

  let confirmResolve = null;

  function showLoading(title, subtitle) {
    $('#appModalLoadingTitle').text(title || 'Memproses Data...');
    $('#appModalLoadingSub').text(subtitle || 'Mohon tunggu sebentar, jangan tutup halaman.');
    $('#appModalLoading').removeClass('hidden').addClass('flex');
  }

  function hideLoading() {
    $('#appModalLoading').addClass('hidden').removeClass('flex');
  }

  function showSaved(message, title, onClose) {
    hideLoading();
    $('#appModalSavedTitle').text(title || 'Data Tersimpan!');
    $('#appModalSavedMessage').text(message || 'Data baru telah berhasil disimpan ke sistem.');
    const $modal = $('#appModalSaved');
    $modal.removeClass('hidden').addClass('flex');
    $('#appModalSavedBtn').off('click').on('click', function () {
      $modal.addClass('hidden').removeClass('flex');
      if (typeof onClose === 'function') onClose();
    });
  }

  function showUpdated(message, title, onClose) {
    hideLoading();
    $('#appModalUpdatedTitle').text(title || 'Data Berhasil Diperbarui!');
    $('#appModalUpdatedMessage').text(message || 'Perubahan data telah berhasil disimpan.');
    const $modal = $('#appModalUpdated');
    $modal.removeClass('hidden').addClass('flex');
    $('#appModalUpdatedBtn').off('click').on('click', function () {
      $modal.addClass('hidden').removeClass('flex');
      if (typeof onClose === 'function') onClose();
    });
  }

  function showError(message, title, onClose) {
    hideLoading();
    $('#appModalErrorTitle').text(title || 'Terjadi Kesalahan');
    $('#appModalErrorMessage').text(message || 'Permintaan tidak dapat diproses. Silakan coba lagi.');
    const $modal = $('#appModalError');
    $modal.removeClass('hidden').addClass('flex');
    $('#appModalErrorBtn').off('click').on('click', function () {
      $modal.addClass('hidden').removeClass('flex');
      if (typeof onClose === 'function') onClose();
    });
  }

  function confirm(options) {
    const opts = Object.assign({
      title: 'Konfirmasi Tindakan',
      message: 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
      confirmText: 'Ya, Lanjutkan',
      cancelText: 'Batal',
      danger: false
    }, options || {});

    $('#appModalConfirmTitle').text(opts.title);
    $('#appModalConfirmMessage').text(opts.message);
    $('#appModalConfirmOk').text(opts.confirmText);
    $('#appModalConfirmCancel').text(opts.cancelText);

    const $okBtn = $('#appModalConfirmOk');
    const $iconWrap = $('#appModalConfirmIconWrap');
    if (opts.danger) {
      $okBtn.removeClass('bg-blue-600 hover:bg-blue-700').addClass('bg-red-600 hover:bg-red-700');
      $iconWrap.removeClass('bg-blue-100 text-blue-600').addClass('bg-red-100 text-red-600');
    } else {
      $okBtn.removeClass('bg-red-600 hover:bg-red-700').addClass('bg-blue-600 hover:bg-blue-700');
      $iconWrap.removeClass('bg-red-100 text-red-600').addClass('bg-blue-100 text-blue-600');
    }

    const $modal = $('#appModalConfirm');
    $modal.removeClass('hidden').addClass('flex');

    return new Promise(function (resolve) {
      confirmResolve = resolve;
      $('#appModalConfirmOk').off('click').on('click', function () {
        $modal.addClass('hidden').removeClass('flex');
        if (confirmResolve) confirmResolve(true);
        confirmResolve = null;
      });
      $('#appModalConfirmCancel, #appModalConfirmBackdrop').off('click').on('click', function () {
        $modal.addClass('hidden').removeClass('flex');
        if (confirmResolve) confirmResolve(false);
        confirmResolve = null;
      });
    });
  }

  return {
    showLoading: showLoading,
    hideLoading: hideLoading,
    showSaved: showSaved,
    showUpdated: showUpdated,
    showError: showError,
    confirm: confirm
  };
})(jQuery);
