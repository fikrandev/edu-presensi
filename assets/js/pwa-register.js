/**
 * EduPresensi Mobile PWA Installer & Service Worker Registration
 */
(function () {
  'use strict';

  const swUrl = document.querySelector('meta[name="pwa-sw-url"]')?.getAttribute('content');
  if ('serviceWorker' in navigator && swUrl) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register(swUrl).catch(function () {});
    });
  }

  let deferredPrompt = null;
  const $banner = document.getElementById('pwaInstallBanner');
  const $btnInstall = document.getElementById('pwaInstallBtn');
  const $btnDismiss = document.getElementById('pwaDismissBtn');

  const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
  const isIos = /iphone|ipad|ipod/i.test(window.navigator.userAgent);

  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredPrompt = e;
    if ($banner && !isStandalone && !localStorage.getItem('pwa_banner_dismissed')) {
      $banner.classList.remove('hidden');
    }
  });

  if ($btnInstall) {
    $btnInstall.addEventListener('click', async function () {
      if (deferredPrompt) {
        deferredPrompt.prompt();
        await deferredPrompt.userChoice;
        deferredPrompt = null;
        if ($banner) $banner.classList.add('hidden');
      } else if (isIos) {
        AppModal.showSaved(
          'Di Safari iOS, tekan tombol Bagikan (ikon kotak dengan panah ke atas), lalu pilih "Tambah ke Layar Utama" (Add to Home Screen).',
          'Cara Install di iPhone / iPad'
        );
      } else {
        AppModal.showSaved(
          'Buka menu browser (titik tiga di pojok kanan atas) lalu pilih "Install Aplikasi" atau "Tambahkan ke Layar Utama".',
          'Install Aplikasi PWA'
        );
      }
    });
  }

  if ($btnDismiss && $banner) {
    $btnDismiss.addEventListener('click', function () {
      $banner.classList.add('hidden');
      localStorage.setItem('pwa_banner_dismissed', '1');
    });
  }

  if (isIos && !isStandalone && $banner && !localStorage.getItem('pwa_banner_dismissed')) {
    $banner.classList.remove('hidden');
  }
})();
