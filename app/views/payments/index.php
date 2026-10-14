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

<?php if (Auth::isAdmin()): ?>
<h5>Pagos pendientes por confirmar</h5>
<?php if (empty($pagosPendientes)): ?>
<div class="alert alert-secondary">No hay pagos pendientes de confirmación.</div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-striped table-sm align-middle">
 <thead><tr><th>Fecha</th><th>Jornada</th><th>Misión</th><th>Miembro</th><th>Hallacas</th><th>Monto</th><th>Moneda</th><th>Método</th><th>Registrado por</th><th>Conf.</th></tr></thead>
 <tbody>
 <?php foreach ($pagosPendientes as $pp): ?>
 <tr>
   <td><?= h($pp['fecha']) ?></td>
   <td><?= h($pp['jornada_name']) ?></td>
   <td><?= h($pp['mission_name']) ?></td>
   <td><?= h($pp['member_name']) ?></td>
   <td><?= (int)$pp['hallacas'] ?></td>
   <td><?= money($pp['monto']) ?></td>
   <td><?= h($pp['moneda']) ?></td>
   <td><?= h($pp['metodo']) ?></td>
   <td><?= h($pp['creado_por'] ?? '-') ?></td>
   <td>
     <form method="post" action="<?= url('payment_confirm') ?>" class="d-inline">
       <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
       <input type="hidden" name="pago_id" value="<?= (int)$pp['id'] ?>">
       <button class="btn btn-sm btn-primary">Confirmar</button>
     </form>
   </td>
 </tr>
 <?php endforeach; ?>
 </tbody>
</table>
</div>
<?php endif; ?>
<?php else: ?>
<div class="alert alert-info">Tus pagos registrados quedan <strong>pendientes por confirmar</strong> hasta que el administrador los valide.</div>
<?php endif; ?>

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
   <td class="text-end">
     <button class="btn btn-sm btn-success"
       data-bs-toggle="modal"
       data-bs-target="#payModal"
       data-entrega-id="<?= (int)$e['id'] ?>"
       data-pend="<?= (int)$pend ?>"
       data-member="<?= h($e['member_name']) ?>"
       data-mission="<?= h($e['mission_name']) ?>"
       data-jornada="<?= h($e['jornada_name']) ?>">
       Pagar
     </button>
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
 <thead><tr><th>Fecha</th><th>Jornada</th><th>Misión</th><th>Miembro</th><th>Hallacas</th><th>Monto</th><th>Moneda</th><th>Método</th><th>Estado</th><th>Registrado por</th><th>Notas</th></tr></thead>
 <tbody>
 <?php foreach ($recientes as $p): ?>
 <tr>
   <td><?= h($p['fecha']) ?></td>
   <td><?= h($p['jornada_name']) ?></td>
   <td><?= h($p['mission_name']) ?></td>
   <td><?= h($p['member_name']) ?></td>
   <td><?= (int)$p['hallacas'] ?></td>
   <td><?= money($p['monto']) ?></td>
   <td><?= h($p['moneda']) ?></td>
   <td><?= h($p['metodo']) ?></td>
   <td><?= status_badge($p['status']) ?></td>
   <td><?= h($p['creado_por'] ?? '-') ?></td>
   <td><?= h($p['notes']) ?></td>
 </tr>
 <?php endforeach; ?>
 </tbody>
</table>
</div>
<?php endif; ?>

<!-- Modal de registro de pago -->
<div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
    <form method="post" action="<?= url('entrega_abono') ?>" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Registrar pago</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="entrega_id" id="pay_entrega_id" value="">
        <p class="mb-2"><strong id="pay_member"></strong><br><small class="text-muted" id="pay_meta"></small></p>
        <div class="alert alert-info py-2 small">Disponibles por cancelar: <strong id="pay_pend">0</strong> hallacas</div>
        <div class="mb-3">
          <label class="form-label">Hallacas pagadas (adicional)</label>
          <input type="number" name="hallacas" id="pay_hallacas" class="form-control" min="0" value="0" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Monto</label>
          <input type="number" step="0.01" name="monto" class="form-control" value="0" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Método de pago</label>
          <select name="metodo" class="form-select" required>
            <option value="pago_movil">Pago móvil</option>
            <option value="efectivo_ves">Efectivo bolívares</option>
            <option value="efectivo_usd">Efectivo dólares</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Notas</label>
          <input type="text" name="notes" class="form-control" placeholder="opcional">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-success">Registrar pago</button>
      </div>
    </form>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
  var modal = document.getElementById('payModal');
  if (!modal) return;
  modal.addEventListener('show.bs.modal', function(event){
    var btn = event.relatedTarget;
    var pend = parseInt(btn.getAttribute('data-pend') || '0', 10);
    document.getElementById('pay_entrega_id').value = btn.getAttribute('data-entrega-id') || '';
    document.getElementById('pay_member').textContent = btn.getAttribute('data-member') || '';
    document.getElementById('pay_meta').textContent = (btn.getAttribute('data-mission')||'') + ' · ' + (btn.getAttribute('data-jornada')||'');
    document.getElementById('pay_pend').textContent = pend;
    var h = document.getElementById('pay_hallacas');
    h.max = pend;
    h.value = 0;
  });
});
</script>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
