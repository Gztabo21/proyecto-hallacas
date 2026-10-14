<?php include BASE_PATH . '/app/views/partials/header.php'; ?>
<h2>Registro de pagos</h2>
<p class="text-muted">Abono por hallacas pendientes de cancelar.</p>

<form method="get" action="<?= url('payments') ?>" class="card p-3 mb-3">
  <input type="hidden" name="r" value="payments">
  <div class="row g-2">
    <div class="col-md-4">
      <label class="form-label">Jornada</label>
      <select name="jornada_id" class="form-select">
        <option value="">Todas</option>
        <?php foreach ($jornadas as $j): ?>
        <option value="<?= (int)$j['id'] ?>" <?= (isset($_GET['jornada_id']) && $_GET['jornada_id']==$j['id'])?'selected':'' ?>><?= h($j['name'].' ('.$j['fecha'].')') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Misión</label>
      <select name="mission_id" class="form-select">
        <option value="">Todas</option>
        <?php foreach ($missions as $m): ?>
        <option value="<?= (int)$m['id'] ?>" <?= (isset($_GET['mission_id']) && $_GET['mission_id']==$m['id'])?'selected':'' ?>><?= h($m['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2 d-flex align-items-end">
      <button class="btn btn-primary w-100">Filtrar</button>
    </div>
  </div>
</form>

<h5>Pendientes por cancelar</h5>
<?php if (empty($pendientes)): ?>
<div class="alert alert-info">No hay hallacas pendientes por cancelar con los filtros actuales.</div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-striped table-sm align-middle">
 <thead>
  <tr><th>Jornada</th><th>Fecha</th><th>Misión</th><th>Miembro</th><th>Entreg.</th><th>Pag.</th><th>Pend.</th><th>Precio</th><th>Pend. dinero</th><th>Registrar pago</th></tr>
 </thead>
 <tbody>
 <?php foreach ($pendientes as $e):
    $pend = (int)$e['hallacas_entregadas'] - (int)$e['hallacas_pagadas'];
    $pendMoney = $pend * (float)$e['precio_unitario'];
 ?>
 <tr>
   <td><?= h($e['jornada_name']) ?></td>
   <td><?= h($e['fecha']) ?></td>
   <td><?= h($e['mission_name']) ?></td>
   <td><?= h($e['member_name']) ?></td>
   <td><?= (int)$e['hallacas_entregadas'] ?></td>
   <td><?= (int)$e['hallacas_pagadas'] ?></td>
   <td><strong><?= $pend ?></strong></td>
   <td><?= money($e['precio_unitario']) ?></td>
   <td><strong><?= money($pendMoney) ?></strong></td>
   <td>
     <form method="post" action="<?= url('entrega_abono') ?>" class="row g-1">
       <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
       <input type="hidden" name="entrega_id" value="<?= (int)$e['id'] ?>">
       <div class="col-12 col-md-3"><input type="number" name="hallacas" class="form-control form-control-sm" min="0" max="<?= $pend ?>" value="0" placeholder="+ hall"></div>
       <div class="col-12 col-md-3"><input type="number" step="0.01" name="monto" class="form-control form-control-sm" value="0" placeholder="monto"></div>
       <div class="col-12 col-md-2"><input name="metodo" class="form-control form-control-sm" placeholder="método"></div>
       <div class="col-12 col-md-2"><input name="notes" class="form-control form-control-sm" placeholder="notas"></div>
       <div class="col-12 col-md-2"><button class="btn btn-sm btn-success w-100">Pagar</button></div>
     </form>
   </td>
 </tr>
 <?php endforeach; ?>
 </tbody>
</table>
</div>
<?php endif; ?>

<h5 class="mt-4">Historial reciente de pagos</h5>
<?php if (empty($recientes)): ?>
<div class="alert alert-secondary">Sin pagos registrados.</div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-striped table-sm">
 <thead><tr><th>Fecha</th><th>Jornada</th><th>Misión</th><th>Miembro</th><th>Hallacas</th><th>Monto</th><th>Método</th><th>Registrado por</th><th>Notas</th></tr></thead>
 <tbody>
 <?php foreach ($recientes as $p): ?>
 <tr>
   <td><?= h($p['fecha']) ?></td>
   <td><?= h($p['jornada_name']) ?></td>
   <td><?= h($p['mission_name']) ?></td>
   <td><?= h($p['member_name']) ?></td>
   <td><?= (int)$p['hallacas'] ?></td>
   <td><?= money($p['monto']) ?></td>
   <td><?= h($p['metodo']) ?></td>
   <td><?= h($p['creado_por'] ?? '-') ?></td>
   <td><?= h($p['notes']) ?></td>
 </tr>
 <?php endforeach; ?>
 </tbody>
</table>
</div>
<?php endif; ?>

<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
