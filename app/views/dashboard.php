<?php include BASE_PATH . '/app/views/partials/header.php'; ?>
<h2>Dashboard</h2>
<div class="row mb-3 g-3">
  <div class="col-md-3"><div class="card h-100"><div class="card-body"><h6>Misiones activas</h6><h3><?= (int)$stats['misiones'] ?></h3></div></div></div>
  <div class="col-md-3"><div class="card h-100"><div class="card-body"><h6>Jornadas</h6><h3><?= (int)$stats['jornadas'] ?></h3></div></div></div>
  <div class="col-md-3"><div class="card h-100"><div class="card-body"><h6>Miembros activos</h6><h3><?= (int)$stats['miembros'] ?></h3></div></div></div>
  <div class="col-md-3"><div class="card h-100"><div class="card-body"><h6>Pagos por confirmar</h6><h3><?= (int)$stats['pagos_pendientes'] ?></h3></div></div></div>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header py-2"><h5 class="mb-0">Últimas entregas</h5></div>
      <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-sm table-striped mb-0">
         <thead><tr><th>Jornada</th><th>Misión</th><th>Miembro</th><th>Entregadas</th><th>Pagadas</th><th>Estado</th><th>Monto pagado</th></tr></thead>
         <tbody>
         <?php if (empty($recent)): ?>
         <tr><td colspan="7" class="text-muted text-center py-3">Sin entregas recientes</td></tr>
         <?php else: foreach ($recent as $e): ?>
         <tr>
           <td><?= h($e['jornada_name']) ?></td>
           <td><?= h($e['mission_name']) ?></td>
           <td><?= h($e['member_name']) ?></td>
           <td><?= (int)$e['hallacas_entregadas'] ?></td>
           <td><?= (int)$e['hallacas_pagadas'] ?></td>
           <td><?= status_badge($e['status']) ?></td>
           <td><?= money($e['monto_pagado']) ?></td>
         </tr>
         <?php endforeach; endif; ?>
         </tbody>
        </table>
        </div>
        <?= pagination_render(
            $pagination['entregas']['total'],
            $pagination['entregas']['page'],
            $pagination['entregas']['per'],
            'dashboard',
            $pagination['entregas']['param'],
            pagination_preserve($pagination['params'], $pagination['entregas']['param'])
        ) ?>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header py-2"><h5 class="mb-0">Resumen por misión</h5></div>
      <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-striped mb-0">
         <thead><tr><th>Misión</th><th>Asignadas</th><th>Entregadas</th><th>Pagadas</th><th>Pendientes</th></tr></thead>
         <tbody>
         <?php if (empty($missionTotals)): ?>
         <tr><td colspan="5" class="text-muted text-center py-3">Sin datos de misiones</td></tr>
         <?php else: foreach ($missionTotals as $t): ?>
         <tr>
           <td><?= h($t['name']) ?></td>
           <td><?= (int)$t['asignadas'] ?></td>
           <td><?= (int)$t['entregadas'] ?></td>
           <td><?= (int)$t['pagadas'] ?></td>
           <td><?= (int)$t['entregadas'] - (int)$t['pagadas'] ?></td>
         </tr>
         <?php endforeach; endif; ?>
         </tbody>
        </table>
        </div>
        <?= pagination_render(
            $pagination['misiones']['total'],
            $pagination['misiones']['page'],
            $pagination['misiones']['per'],
            'dashboard',
            $pagination['misiones']['param'],
            pagination_preserve($pagination['params'], $pagination['misiones']['param'])
        ) ?>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <h5 class="mb-0">Pagos pendientes por confirmar</h5>
    <a href="<?= url('payments') ?>" class="btn btn-sm btn-primary flex-shrink-0">Ir a confirmar pagos</a>
  </div>
  <div class="card-body p-0">
    <?php if (empty($pagosPendientes)): ?>
    <div class="alert alert-secondary m-3 mb-0">No hay pagos pendientes.</div>
    <?php else: ?>
    <div class="table-responsive">
    <table class="table table-sm table-striped mb-0">
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
    <?= pagination_render(
        $pagination['pagos']['total'],
        $pagination['pagos']['page'],
        $pagination['pagos']['per'],
        'dashboard',
        $pagination['pagos']['param'],
        pagination_preserve($pagination['params'], $pagination['pagos']['param'])
    ) ?>
    <?php endif; ?>
  </div>
</div>

<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
