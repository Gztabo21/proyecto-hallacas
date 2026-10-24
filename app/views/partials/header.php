<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h(APP_NAME) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <?php $cssPath = PUBLIC_PATH . '/assets/css/app.css'; $cssVer = is_file($cssPath) ? (string)filemtime($cssPath) : '1'; ?>
  <link rel="stylesheet" href="<?= h(asset('assets/css/app.css')) ?>?v=<?= h($cssVer) ?>">
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
        <?php if (Auth::canAccessJornadasList()): ?>
        <li class="nav-item"><a class="nav-link" href="<?= url('jornadas') ?>">Jornadas</a></li>
        <?php endif; ?>
        <?php if (Auth::canAccessInventory()): ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Inventario</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= url('inventory') ?>">Catálogo y stock</a></li>
            <li><a class="dropdown-item" href="<?= url('inventory_purchases') ?>">Compras y donaciones</a></li>
            <li><a class="dropdown-item" href="<?= url('inventory_purchase') ?>">Registrar compra</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?= url('inventory_movements') ?>">Historial de movimientos</a></li>
            <li><a class="dropdown-item" href="<?= url('inventory_initial') ?>">Stock inicial</a></li>
            <?php if (Auth::canManageInventoryCatalog()): ?>
            <li><a class="dropdown-item" href="<?= url('inventory_import') ?>">Importar catálogo CSV</a></li>
            <?php endif; ?>
          </ul>
        </li>
        <?php endif; ?>
        <?php if (Auth::isAdmin()): ?>
        <li class="nav-item"><a class="nav-link" href="<?= url('missions') ?>">Misiones</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('users') ?>">Usuarios</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('coordinators') ?>">Coordinadores</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('role_permissions') ?>">Permisos</a></li>
        <?php endif; ?>
        <?php if (Auth::isAdmin() || Auth::isCoordinator()): ?>
        <li class="nav-item"><a class="nav-link" href="<?= url('members') ?>">Miembros</a></li>
        <?php endif; ?>
        <?php if (Auth::can('pagos', 'create') || Auth::can('pagos', 'edit') || Auth::isAdmin()): ?>
        <li class="nav-item"><a class="nav-link" href="<?= url('payments') ?>">Pagos</a></li>
        <?php endif; ?>
        <?php if (Auth::isAdmin() || Auth::isCoordinator()): ?>
        <li class="nav-item"><a class="nav-link" href="<?= url('reports') ?>">Reportes</a></li>
        <?php endif; ?>
        <?php else: ?>
        <li class="nav-item"><a class="nav-link" href="<?= url('login') ?>">Ingresar</a></li>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav">
        <?php if (Auth::check()): ?>
        <li class="nav-item"><span class="nav-link text-light small"><?= h(Auth::user()['name']) ?> · <?= h(role_label(Auth::role())) ?></span></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('logout') ?>">Salir</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<div class="container-fluid px-2 px-md-3 mt-3">
<?php if ($m = flash('success')): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?= h($m) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($m = flash('error')): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?= h($m) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
