<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h(APP_NAME) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?= url('dashboard') ?>"><?= h(APP_NAME) ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto">
        <?php if (Auth::check()): ?>
        <li class="nav-item"><a class="nav-link" href="<?= url('dashboard') ?>">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('missions') ?>">Misiones</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('users') ?>">Usuarios</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('jornadas') ?>">Jornadas</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('reports') ?>">Reportes</a></li>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav">
        <?php if (Auth::check()): ?>
        <li class="nav-item"><span class="nav-link text-light"><?= h(Auth::user()['name']) ?> (<?= h(Auth::role()) ?>)</span></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('logout') ?>">Salir</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<div class="container-fluid mt-3">
<?php if ($m = flash('success')): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?= h($m) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($m = flash('error')): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?= h($m) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
