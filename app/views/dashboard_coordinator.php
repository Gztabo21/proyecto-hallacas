<?php include BASE_PATH . '/app/views/partials/header.php'; ?>
<h2>Dashboard del coordinador — <?= h($mission['name'] ?? '') ?></h2>

<div class="d-flex flex-column flex-sm-row gap-2 mb-3">
  <a class="btn btn-primary" href="<?= url('jornadas') ?>">Jornadas y distribución</a>
  <a class="btn btn-success" href="<?= url('payments') ?>">Registrar pagos</a>
</div>

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
<?= pagination_render(
    $pagination['pagos']['total'],
    $pagination['pagos']['page'],
    $pagination['pagos']['per'],
    'dashboard',
    $pagination['pagos']['param'],
    pagination_preserve($pagination['params'], $pagination['pagos']['param'])
) ?>
<?php endif; ?>

<div class="row g-3 mt-2">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header py-2"><h5 class="mb-0">Mis entregas por miembro</h5></div>
      <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-sm table-striped mb-0">
         <thead><tr><th>Jornada</th><th>Fecha</th><th>Miembro</th><th>Entreg.</th><th>Pagadas</th><th>Pend.</th><th>Estado</th><th>Monto</th></tr></thead>
         <tbody>
         <?php if (empty($entregas)): ?>
         <tr><td colspan="8" class="text-muted text-center py-3">Sin entregas registradas</td></tr>
         <?php else: foreach ($entregas as $e): ?>
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
      <div class="card-header py-2"><h5 class="mb-0">Mis jornadas</h5></div>
      <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-sm table-striped mb-0">
         <thead><tr><th>Nombre</th><th>Fecha</th><th>Estado</th><th>Acciones</th></tr></thead>
         <tbody>
         <?php if (empty($jornadas)): ?>
         <tr><td colspan="4" class="text-muted text-center py-3">Sin jornadas asignadas</td></tr>
         <?php else: foreach ($jornadas as $j): ?>
         <tr>
           <td><?= h($j['name']) ?></td>
           <td><?= h($j['fecha']) ?></td>
           <td><?= status_badge($j['status']) ?></td>
           <td><a class="btn btn-sm btn-outline-primary" href="<?= url('jornada_show',['id'=>$j['id']]) ?>">Abrir</a></td>
         </tr>
         <?php endforeach; endif; ?>
         </tbody>
        </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
