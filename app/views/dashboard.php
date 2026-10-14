<?php include BASE_PATH . '/app/views/partials/header.php'; ?>
<h2>Dashboard</h2>
<div class="row mb-3">
  <div class="col-md-3"><div class="card"><div class="card-body"><h6>Misiones activas</h6><h3><?= (int)$stats['misiones'] ?></h3></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><h6>Jornadas</h6><h3><?= (int)$stats['jornadas'] ?></h3></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><h6>Miembros activos</h6><h3><?= (int)$stats['miembros'] ?></h3></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><h6>Pagos por confirmar</h6><h3><?= (int)$stats['pagos_pendientes'] ?></h3></div></div></div>
</div>

<h5>Resumen por misión</h5>
<div class="table-responsive">
<table class="table table-striped">
 <thead><tr><th>Misión</th><th>Asignadas</th><th>Entregadas</th><th>Pagadas</th><th>Pendientes</th></tr></thead>
 <tbody>
 <?php foreach ($missionTotals as $t): ?>
 <tr>
   <td><?= h($t['name']) ?></td>
   <td><?= (int)$t['asignadas'] ?></td>
   <td><?= (int)$t['entregadas'] ?></td>
   <td><?= (int)$t['pagadas'] ?></td>
   <td><?= (int)$t['entregadas'] - (int)$t['pagadas'] ?></td>
 </tr>
 <?php endforeach; ?>
 </tbody>
</table>
</div>

<h5>Últimas entregas</h5>
<div class="table-responsive">
<table class="table table-sm">
 <thead><tr><th>Jornada</th><th>Misión</th><th>Miembro</th><th>Entregadas</th><th>Pagadas</th><th>Estado</th><th>Monto pagado</th></tr></thead>
 <tbody>
 <?php foreach ($recent as $e): ?>
 <tr>
   <td><?= h($e['jornada_name']) ?></td>
   <td><?= h($e['mission_name']) ?></td>
   <td><?= h($e['member_name']) ?></td>
   <td><?= (int)$e['hallacas_entregadas'] ?></td>
   <td><?= (int)$e['hallacas_pagadas'] ?></td>
   <td><?= status_badge($e['status']) ?></td>
   <td><?= money($e['monto_pagado']) ?></td>
 </tr>
 <?php endforeach; ?>
 </tbody>
</table>
</div>
<h5 class="mt-4">Pagos pendientes por confirmar</h5>
<?php if (empty($pagosPendientes)): ?>
<div class="alert alert-secondary">No hay pagos pendientes.</div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-sm table-striped">
 <thead><tr><th>Fecha</th><th>Jornada</th><th>Misión</th><th>Miembro</th><th>Hallacas</th><th>Monto</th></tr></thead>
 <tbody>
 <?php foreach ($pagosPendientes as $pp): ?>
 <tr>
   <td><?= h($pp['fecha']) ?></td>
   <td><?= h($pp['jornada_name']) ?></td>
   <td><?= h($pp['mission_name']) ?></td>
   <td><?= h($pp['member_name']) ?></td>
   <td><?= (int)$pp['hallacas'] ?></td>
   <td><?= money($pp['monto']) ?></td>
 </tr>
 <?php endforeach; ?>
 </tbody>
</table>
</div>
<a href="<?= url('payments') ?>" class="btn btn-sm btn-primary">Ir a confirmar pagos</a>
<?php endif; ?>

<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
