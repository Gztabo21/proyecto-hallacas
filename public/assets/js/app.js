document.addEventListener('DOMContentLoaded', function () {
  if (!('serviceWorker' in navigator) || !document.querySelector('link[rel="manifest"]')) {
    return;
  }
  var manifestHref = document.querySelector('link[rel="manifest"]').href;
  var swUrl = manifestHref.replace(/manifest\.php(\?.*)?$/i, 'sw.js');
  navigator.serviceWorker.register(swUrl).catch(function () {});
});
