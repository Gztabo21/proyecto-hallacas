<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ingresar — <?= h(APP_NAME) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="login-page d-flex flex-column min-vh-100">
<header class="login-top py-3">
  <div class="container text-center">
    <a class="login-brand text-decoration-none" href="<?= url('login') ?>"><?= h(APP_NAME) ?></a>
  </div>
</header>
<main class="login-main flex-grow-1 d-flex align-items-center justify-content-center px-3 py-4">
  <div class="w-100" style="max-width: 420px;">
<?php if ($m = flash('success')): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?= h($m) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($m = flash('error')): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?= h($m) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
