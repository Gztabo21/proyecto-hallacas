<?php include BASE_PATH . '/app/views/partials/header.php'; ?>
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-2 mb-3">
 <h2>Jornadas</h2>
 <?php if (Auth::isAdmin()): ?>
 <a class="btn btn-primary" href="<?= url('jornada_create') ?>">Nueva jornada</a>
 <?php endif; ?>
</div>
<p class="text-muted small mb-2">Montos por hallacas <strong>entregadas a miembros</strong> (cantidad × precio unitario en USD). <strong>Total = cobrado + pendiente</strong> (cobrado = pagos confirmados).</p>
<div class="row g-3 mb-3">
  <div class="col-md-3"><div class="card"><div class="card-body"><h6 class="text-muted small mb-1">Hallacas realizadas</h6><h3 class="mb-0"><?= (int)($totalesHallacasRealizadas ?? 0) ?></h3><p class="small text-muted mb-0">Suma del total hecho por jornada<?= ($jornadasSinTotalHechas ?? 0) > 0 ? ' (' . (int)$jornadasSinTotalHechas . ' sin registrar)' : '' ?></p></div></div></div>
  <div class="col-md-3"><div class="card border-primary"><div class="card-body"><h6 class="text-muted small mb-1">Total entregado</h6><h3 class="mb-0"><?= money($totalesGeneral ?? 0) ?></h3><p class="small text-muted mb-0"><?= (int)($totalesHallacasEntregadas ?? 0) ?> hall. a miembros</p></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><h6 class="text-muted small mb-1">Cobrado (confirmado)</h6><h3 class="mb-0"><?= money($totalesPagado ?? 0) ?></h3></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><h6 class="text-muted small mb-1">Pendiente de cobro</h6><h3 class="mb-0"><?= money($totalesPendiente ?? 0) ?></h3><p class="small text-muted mb-0">= total − cobrado</p></div></div></div>
</div>
<div class="table-responsive">
<table class="table table-striped table-hover">
 <thead><tr><th>ID</th><th>Nombre</th><th>Fecha</th><th class="text-end">Hall. realizadas</th><th class="text-end">Hall. entreg.</th><th class="text-end">Precio (USD)</th><th class="text-end">Total</th><th class="text-end">Cobrado</th><th class="text-end">Pendiente</th><th>Estado</th><th>Acciones</th></tr></thead>
 <tbody>
 <?php
 $sumGeneral = $sumPagado = $sumPendiente = $sumHall = $sumRealizadas = 0;
 foreach ($jornadas as $j):
   $sumGeneral += (float)($j['general'] ?? 0);
   $sumPagado += (float)($j['pagado'] ?? 0);
   $sumPendiente += (float)($j['pendiente'] ?? 0);
   $sumHall += (int)($j['hallacas_entregadas'] ?? 0);
   if ($j['hallacas_realizadas'] !== null) {
       $sumRealizadas += (int)$j['hallacas_realizadas'];
   }
 ?>
 <tr>
   <td><?= (int)$j['id'] ?></td>
   <td><?= h($j['name']) ?></td>
   <td><?= h($j['fecha']) ?></td>
     <td class="text-end"><?= $j['hallacas_realizadas'] !== null ? (int)$j['hallacas_realizadas'] : '—' ?></td>
     <td class="text-end"><?= (int)($j['hallacas_entregadas'] ?? 0) ?></td>
     <td class="text-end"><?= money($j['precio_hallaca']) ?></td>
     <td class="text-end">
       <?= money($j['general'] ?? 0) ?>
       <?php
       $vp = $j['valor_producido_bs'] ?? null;
       $gen = (float)($j['general'] ?? 0);
       if ($vp !== null && abs($vp - $gen) > 0.01): ?>
       <div class="small text-muted fw-normal">Ref. producidas: <?= money($vp) ?></div>
       <?php endif; ?>
     </td>
     <td class="text-end"><?= money($j['pagado'] ?? 0) ?></td>
     <td class="text-end"><?= money($j['pendiente'] ?? 0) ?></td>
   <td><?= status_badge($j['status']) ?></td>
   <td>
     <a class="btn btn-sm btn-outline-primary" href="<?= url('jornada_show',['id'=>$j['id']]) ?>">Ver</a>
     <?php if (Auth::isAdmin()): ?>
       <a class="btn btn-sm btn-outline-secondary" href="<?= url('jornada_edit',['id'=>$j['id']]) ?>">Editar</a>
     <?php endif; ?>
     <?php if ($j['status']==='open'): ?>
       <form method="post" action="<?= url('jornada_close') ?>" class="d-inline">
         <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
         <input type="hidden" name="id" value="<?= (int)$j['id'] ?>">
         <button class="btn btn-sm btn-warning">Cerrar</button>
       </form>
     <?php else: ?>
       <?php if (Auth::isAdmin()): ?>
       <form method="post" action="<?= url('jornada_reopen') ?>" class="d-inline">
         <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
         <input type="hidden" name="id" value="<?= (int)$j['id'] ?>">
         <button class="btn btn-sm btn-outline-warning">Reabrir</button>
       </form>
       <?php endif; ?>
     <?php endif; ?>
     <?php if (Auth::isAdmin()): ?>
     <form method="post" action="<?= url('jornada_delete') ?>" class="d-inline" onsubmit="return confirm('Eliminar jornada?')">
       <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
       <input type="hidden" name="id" value="<?= (int)$j['id'] ?>">
       <button class="btn btn-sm btn-outline-danger">Eliminar</button>
     </form>
     <?php endif; ?>
   </td>
 </tr>
 <?php endforeach; ?>
 </tbody>
 <?php if ($jornadas !== []): ?>
 <tfoot class="table-light fw-semibold">
   <tr>
     <td colspan="3" class="text-end">Totales</td>
     <td class="text-end"><?= (int)$sumRealizadas ?></td>
     <td class="text-end"><?= (int)$sumHall ?></td>
     <td></td>
     <td class="text-end"><?= money($sumGeneral) ?></td>
     <td class="text-end"><?= money($sumPagado) ?></td>
     <td class="text-end"><?= money($sumPendiente) ?></td>
     <td colspan="2"></td>
   </tr>
 </tfoot>
 <?php endif; ?>
</table>
</div>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
