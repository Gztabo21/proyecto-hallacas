<?php if (!pwa_is_enabled()) {
    return;
} ?>
  <meta name="theme-color" content="<?= h(pwa_theme_color()) ?>">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="<?= h(setting('app_name', APP_NAME)) ?>">
  <link rel="manifest" href="<?= h(asset('manifest.php')) ?>">
  <link rel="icon" href="<?= h(asset('assets/icons/icon.svg')) ?>" type="image/svg+xml">
  <link rel="apple-touch-icon" href="<?= h(asset('assets/icons/icon-192.png')) ?>">
