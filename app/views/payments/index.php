<?php include BASE_PATH . '/app/views/partials/header.php'; ?>
<h2>Registro de pagos</h2>
<p class="text-muted">Abono por hallacas pendientes de cancelar.</p>
<?php if (Auth::isAdmin()): ?>
<div class="mb-3">
  <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#methodModal">Gestionar métodos de pago</button>
  <div class="small text-muted mt-2">Métodos actuales: <?= h(implode(', ', array_column(payment_methods(), 'name'))) ?></div>
</div>
<div class="modal fade" id="methodModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="post" action="<?= url('payment_method_store') ?>" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Agregar método de pago</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="mb-3">
          <label class="form-label">Nombre del método</label>
          <input name="new_payment_method" class="form-control" placeholder="Ej. Zelle, Efectivo euros" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Moneda</label>
          <select name="new_payment_method_currency" class="form-select" required>
            <option value="VES">VES (bolívares)</option>
            <option value="USD">USD (dólares)</option>
          </select>
        </div>
        <div class="small text-muted">Métodos existentes: <?= h(implode(', ', array_column(payment_methods(), 'name'))) ?></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-primary">Agregar método</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

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
    <?php if (Auth::isAdmin()): ?>
    <div class="col-md-4">
      <label class="form-label">Misión</label>
      <select name="mission_id" class="form-select">
        <option value="">Todas</option>
        <?php foreach ($missions as $m): ?>
        <option value="<?= (int)$m['id'] ?>" <?= (isset($_GET['mission_id']) && $_GET['mission_id']==$m['id'])?'selected':'' ?>><?= h($m['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php else: ?>
    <input type="hidden" name="mission_id" value="<?= (int)Auth::missionId() ?>">
    <?php endif; ?>
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
<?= pagination_render(
    $pagination['confirmar']['total'],
    $pagination['confirmar']['page'],
    $pagination['confirmar']['per'],
    'payments',
    $pagination['confirmar']['param'],
    array_merge($pagination['filters'], pagination_preserve($pagination['params'], $pagination['confirmar']['param']))
) ?>
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
<?= pagination_render(
    $pagination['cancelar']['total'],
    $pagination['cancelar']['page'],
    $pagination['cancelar']['per'],
    'payments',
    $pagination['cancelar']['param'],
    array_merge($pagination['filters'], pagination_preserve($pagination['params'], $pagination['cancelar']['param']))
) ?>
<?php endif; ?>

<h5 class="mt-4">Historial reciente de pagos</h5>
<?php if (empty($recientes)): ?>
<div class="alert alert-secondary">Sin pagos registrados.</div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-striped table-sm">
 <thead><tr><th>Fecha</th><th>Jornada</th><th>Misión</th><th>Miembro</th><th>Hallacas</th><th>Monto</th><th>Moneda</th><th>Método</th><th>Estado</th><th>Registrado por</th><th>Confirmado por</th><th>Notas</th></tr></thead>
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
   <td><?= (($p['status'] ?? '') === 'confirmado') ? h($p['confirmado_por_name'] ?? '-') : '-' ?></td>
   <td><?= h($p['notes']) ?></td>
 </tr>
 <?php endforeach; ?>
 </tbody>
</table>
</div>
<?= pagination_render(
    $pagination['recientes']['total'],
    $pagination['recientes']['page'],
    $pagination['recientes']['per'],
    'payments',
    $pagination['recientes']['param'],
    array_merge($pagination['filters'], pagination_preserve($pagination['params'], $pagination['recientes']['param']))
) ?>
<?php endif; ?>

<!-- Modal de registro de pago -->
<div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
    <form method="post" action="<?= url('entrega_abono') ?>" class="modal-content" enctype="multipart/form-data">
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
        <div class="mb-3" id="payTasaGroup">
          <label class="form-label">Tasa (Bs por USD)</label>
          <input type="number" step="0.0001" name="tasa" class="form-control" placeholder="Opcional para pagos en bolívares">
        </div>
        <div class="mb-3 d-none" id="payCaptureGroup">
          <label class="form-label">Capture de pago (imagen)</label>
          <input type="file" name="captura" class="form-control" accept="image/*">
        </div>
        <div class="mb-3">
          <label class="form-label">Método de pago</label>
          <select name="metodo" class="form-select" required>
            <?php foreach (payment_methods() as $pm): ?>
            <option value="<?= h($pm['name']) ?>" data-currency="<?= h($pm['currency']) ?>"><?= h($pm['name']) ?> (<?= h($pm['currency']) ?>)</option>
            <?php endforeach; ?>
            <option value="otro">Otro</option>
          </select>
          <input type="text" name="metodo_otro" class="form-control mt-2 d-none" placeholder="Especifique el método">
          <select name="metodo_otro_currency" class="form-select mt-2 d-none">
            <option value="VES">VES</option>
            <option value="USD">USD</option>
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
    togglePayFields();
  });
  var methodSel = modal.querySelector('select[name="metodo"]');
  function togglePayFields(){
    var m = methodSel ? methodSel.value : '';
    var cur = 'VES';
    if (m === 'otro') {
      var curSel = methodSel ? methodSel.parentElement.querySelector('select[name="metodo_otro_currency"]') : null;
      cur = curSel ? curSel.value : 'VES';
    } else if (methodSel) {
      var opt = methodSel.options[methodSel.selectedIndex];
      if (opt) cur = opt.getAttribute('data-currency') || 'VES';
    }
    var lower = m.toLowerCase();
    var isPago = lower.indexOf('pago') !== -1 || lower.indexOf('movil') !== -1 || lower.indexOf('móvil') !== -1;
    var tg = document.getElementById('payTasaGroup');
    var cg = document.getElementById('payCaptureGroup');
    if (tg) tg.classList.toggle('d-none', cur === 'USD');
    if (cg) cg.classList.toggle('d-none', !isPago);
  }
  if (methodSel) methodSel.addEventListener('change', togglePayFields);
  var curSelPay = methodSel ? methodSel.parentElement.querySelector('select[name="metodo_otro_currency"]') : null;
  if (curSelPay) curSelPay.addEventListener('change', togglePayFields);
  togglePayFields();
  document.querySelectorAll('select[name="metodo"]').forEach(function(sel){
  sel.addEventListener('change', function(){
    var inp = sel.parentElement.querySelector('input[name="metodo_otro"]');
    if (inp) { if (sel.value === 'otro') inp.classList.remove('d-none'); else inp.classList.add('d-none'); }
    var cur = sel.parentElement.querySelector('select[name="metodo_otro_currency"]');
    if (cur) { if (sel.value === 'otro') cur.classList.remove('d-none'); else cur.classList.add('d-none'); }
  });
  });
});
</script>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
