/**
 * Central jQuery AJAX API Client with automatic CSRF header & Custom Loading Modal integration.
 */
window.ApiClient = (function ($) {
  'use strict';

  function getCsrf() {
    return $('meta[name="csrf-token"]').attr('content') || '';
  }

  function request(opts) {
    const useLoading = opts.loading !== false;
    if (useLoading) {
      AppModal.showLoading(opts.loadingTitle || 'Memproses...', opts.loadingSub || 'Mohon tunggu sebentar...');
    }

    const ajaxOpts = {
      url: opts.url,
      method: opts.method || 'POST',
      headers: Object.assign({ 'X-CSRF-Token': getCsrf() }, opts.headers || {}),
      dataType: 'json'
    };

    if (opts.formData) {
      ajaxOpts.data = opts.formData;
      ajaxOpts.processData = false;
      ajaxOpts.contentType = false;
    } else if (ajaxOpts.method.toUpperCase() === 'GET') {
      ajaxOpts.data = opts.data || {};
    } else {
      ajaxOpts.contentType = 'application/json; charset=utf-8';
      ajaxOpts.data = JSON.stringify(opts.data || {});
    }

    return new Promise(function (resolve, reject) {
      $.ajax(ajaxOpts)
        .done(function (res) {
          if (useLoading) AppModal.hideLoading();
          if (res && res.success) {
            resolve(res);
          } else {
            const msg = (res && res.message) ? res.message : 'Permintaan gagal diproses.';
            if (opts.silentError !== true) AppModal.showError(msg);
            reject(res || { message: msg });
          }
        })
        .fail(function (xhr) {
          if (useLoading) AppModal.hideLoading();
          let msg = 'Terjadi kesalahan koneksi ke server.';
          if (xhr.responseJSON && xhr.responseJSON.message) {
            msg = xhr.responseJSON.message;
          } else if (xhr.status === 419) {
            msg = 'Token keamanan kadaluarsa. Muat ulang halaman.';
          } else if (xhr.status === 401) {
            msg = 'Sesi berakhir. Silakan login kembali.';
          }
          if (opts.silentError !== true) AppModal.showError(msg);
          reject({ status: xhr.status, message: msg });
        });
    });
  }

  function escapeHtml(str) {
    return String(str == null ? '' : str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function formatRupiah(num) {
    const n = Number(num || 0);
    return 'Rp ' + n.toLocaleString('id-ID');
  }

  return {
    get: function (url, data, opts) {
      return request(Object.assign({ url: url, method: 'GET', data: data, loading: false }, opts || {}));
    },
    post: function (url, data, opts) {
      return request(Object.assign({ url: url, method: 'POST', data: data }, opts || {}));
    },
    upload: function (url, formData, opts) {
      return request(Object.assign({ url: url, method: 'POST', formData: formData }, opts || {}));
    },
    e: escapeHtml,
    rupiah: formatRupiah
  };
})(jQuery);
