<?php
$saas = $saas ?? Tenant::saasSettings();
$faviconUrl = Tenant::brandingAssetUrl($saas['favicon_path'] ?? '', 'assets/img/default-favicon.svg');
$docTitle   = ($pageTitle ?? $saas['app_name']) . ' — ' . $saas['app_name'];
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#1d4ed8">
  <meta name="csrf-token" content="<?= Security::e($csrfToken ?? Security::csrfToken()) ?>">
  <?php if (!empty($pwaManifestUrl)): ?>
    <link rel="manifest" href="<?= Security::e($pwaManifestUrl) ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= Security::e($school['name'] ?? $saas['app_name']) ?>">
    <link rel="apple-touch-icon" href="<?= Security::e($faviconUrl) ?>">
  <?php endif; ?>
  <?php if (!empty($pwaSwUrl)): ?>
    <meta name="pwa-sw-url" content="<?= Security::e($pwaSwUrl) ?>">
  <?php endif; ?>

  <title><?= Security::e($docTitle) ?></title>
  <link rel="icon" href="<?= Security::e($faviconUrl) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: {
              50: '#eff6ff',
              100: '#dbeafe',
              200: '#bfdbfe',
              500: '#3b82f6',
              600: '#2563eb',
              700: '#1d4ed8',
              800: '#1e40af',
              900: '#1e3a8a',
              950: '#172554'
            }
          }
        }
      }
    };
  </script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="<?= app_url('/assets/css/app.css') ?>?v=<?= APP_VERSION ?>">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <?php if (!empty($loadChartJs)): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
  <?php endif; ?>
  <?php if (!empty($loadSheetJs)): ?>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script src="<?= app_url('/assets/js/xlsx-import.js') ?>?v=<?= APP_VERSION ?>"></script>
  <?php endif; ?>
  <?php if (!empty($loadLeaflet)): ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <?php endif; ?>
  <?php if (!empty($loadQrCode)): ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <?php endif; ?>
  <?php if (!empty($loadQrScanner)): ?>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
  <?php endif; ?>
</head>
