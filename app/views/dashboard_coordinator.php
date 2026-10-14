<?php include BASE_PATH . '/app/views/partials/header.php'; ?>
<h2>Dashboard del coordinador — <?= h($mission['name'] ?? '') ?></h2>

<div class="row mb-3">
  <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><h6>Asignadas (jornadas)</h6><h3><?= (int)$assigned ?></h3></div></div></div>
  <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><h6>Entregadas a miembros</h6><h3><?= (int)$entregadas ?></h3></div></div></div>
  <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><h6>Canceladas (confirmadas)</h6><h3><?= (int)$canceladas ?></h3></div></div></div>
  <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><h6>Por cancelar (hallacas)</h6><h3><?= (int)$debe ?></h3></div></div></div>
</div>
<div class="row mb-3">
  <div class="col-md-4"><div class="card shadow-sm"><div class="card-body"><h6>Total por cobrar</h6><h3><?= money($totalMoney) ?></h3></div></div></div>
  <div class="col-md-4"><div class="card shadow-sm"><div class="card-body"><h6>Cobrado (confirmado)</h6><h3><?= money($cobrado) ?></h3></div></div></div>
  <div class="col-md-4"><div class="card shadow-sm"><div class="card-body"><h6>Pendiente por cobrar</h6><h3><?= money($pendMoney) ?></h3></div></div></div>
</div>

<div class="d-flex gap-2 mb-3">
  <a class="btn btn-primary" href="<?= url('jornadas') ?>">Jornadas y distribución</a>
  <a class="btn btn-success" href="<?= url('payments') ?>">Registrar pagos</a>
</div>

<h5>Pagos pendientes de confirmación</h5>
<?php if (empty($pagosPendientes)): ?>
<div class="alert alert-success">No hay pagos pendientes.</div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-sm table-striped">
 <thead><tr><th>Fecha</th><th>Jornada</th><th>Miembro</th><th>Hallacas</th><th>Monto</th><th>Estado</th></tr></thead>
 <tbody>
 <?php foreach ($pagosPendientes as $p): ?>
 <tr>
   <td><?= h($p['fecha']) ?></td>
   <td><?= h($p['jornada_name']) ?></td>
   <td><?= h($p['member_name']) ?></td>
   <td><?= (int)$p['hallacas'] ?></td>
   <td><?= money($p['monto']) ?></td>
   <td><?= status_badge('pendiente') ?> <small>por confirmar</small></td>
 </tr>
 <?php endforeach; ?>
 </tbody>
</table>
</div>
<?php endif; ?>

<h5 class="mt-4">Mis entregas por miembro</h5>
<div class="table-responsive">
<table class="table table-sm table-striped">
 <thead><tr><th>Jornada</th><th>Fecha</th><th>Miembro</th><th>Entreg.</th><th>Pagadas</th><th>Pend.</th><th>Estado</th><th>Monto pagado</th></tr></thead>
 <tbody>
 <?php foreach ($entregas as $e): ?>
 <tr>
   <td><?= h($e['jornada_name']) ?></td>
   <td><?= h($e['fecha']) ?></td>
   <td><?= h($e['member_name']) ?></td>
   <td><?= (int)$e['hallacas_entregadas'] ?></td>
   <td><?= (int)$e['hallacas_pagadas'] ?></td>
   <td><?= (int)$e['hallacas_entregadas']-(int)$e['hallacas_pagadas'] ?></td>
   <td><?= status_badge($e['status']) ?></td>
   <td><?= money($e['monto_pagado']) ?></td>
 </tr>
 <?php endforeach; ?>
 </tbody>
</table>
</div>

<h5 class="mt-4">Mis jornadas</h5>
<div class="table-responsive">
<table class="table table-sm">
 <thead><tr><th>Nombre</th><th>Fecha</th><th>Estado</th><th>Acciones</th></tr></thead>
 <tbody>
 <?php foreach ($jornadas as $j): ?>
 <tr>
   <td><?= h($j['name']) ?></td>
   <td><?= h($j['fecha']) ?></td>
   <td><?= status_badge($j['status']) ?></td>
   <td><a class="btn btn-sm btn-outline-primary" href="<?= url('jornada_show',['id'=>$j['id']]) ?>">Abrir</a></td>
 </tr>
 <?php endforeach; ?>
 </tbody>
</table>
</div>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
