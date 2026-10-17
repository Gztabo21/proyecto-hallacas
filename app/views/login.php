<?php include BASE_PATH . '/app/views/partials/header_login.php'; ?>
<div class="card login-card shadow-sm border-0">
  <div class="card-body p-4 p-sm-5">
    <div class="text-center mb-4">
      <h1 class="h4 login-card-title mb-2">Iniciar sesión</h1>
      <p class="text-muted small mb-0">Ingresa con tu correo y contraseña asignados</p>
    </div>
    <form method="post" action="<?= url('login') ?>">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <div class="mb-3">
        <label class="form-label" for="login-email">Correo</label>
        <input type="email" name="email" id="login-email" class="form-control" autocomplete="username" required autofocus>
      </div>
      <div class="mb-4">
        <label class="form-label" for="login-password">Contraseña</label>
        <input type="password" name="password" id="login-password" class="form-control" autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Entrar</button>
    </form>
  </div>
</div>
<?php include BASE_PATH . '/app/views/partials/footer_login.php'; ?>
