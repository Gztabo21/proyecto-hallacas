  </div>
</main>
<footer class="login-footer mt-auto py-4">
  <div class="container text-center">
    <p class="login-footer-title mb-1">&copy; <?= (int)date('Y') ?> <?= h(APP_NAME) ?></p>
    <p class="login-footer-text mb-2">Gestión de jornadas, distribución y pagos de hallacas</p>
    <p class="login-footer-credit mb-0">
      Foto de fondo:
      <a href="https://www.pexels.com/es-es/foto/manos-cuaderno-boligrafo-trabajando-8296968/" target="_blank" rel="noopener noreferrer">Pexels</a>
    </p>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= h(asset('assets/js/app.js')) ?>"></script>
</body>
</html>
