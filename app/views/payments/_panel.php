<?php
$paymentsEmbedded = $paymentsEmbedded ?? false;
$returnJornadaId = $returnJornadaId ?? null;
$paginationRoute = $pagination['route'] ?? 'payments';
$hideJornadaCol = $paymentsEmbedded;
?>
<div class="row g-3 mb-3">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header py-2"><h5 class="mb-0">Pagos pendientes por confirmar</h5></div>
      <div class="card-body p-0">
        <?php if (Auth::can('pagos', 'edit') || Auth::isAdmin()): ?>
          <?php if (empty($pagosPendientes)): ?>
          <div class="alert alert-secondary m-3 mb-0">No hay pagos pendientes de confirmación.</div>
          <?php else: ?>
          <div class="table-responsive">
          <table class="table table-striped table-sm align-middle mb-0">
           <thead><tr><th>Fecha</th><?php if (!$hideJornadaCol): ?><th>Jornada</th><?php endif; ?><th>Misión</th><th>Miembro</th><th>Hall.</th><th>Monto</th><th>Mon.</th><th>Método</th><th>Referencia</th><th></th></tr></thead>
           <tbody>
           <?php foreach ($pagosPendientes as $pp): ?>
           <tr>
             <td><?= h($pp['fecha']) ?></td>
             <?php if (!$hideJornadaCol): ?><td><?= h($pp['jornada_name']) ?></td><?php endif; ?>
             <td><?= h($pp['mission_name']) ?></td>
             <td><?= h($pp['member_name']) ?></td>
             <td><?= (int)$pp['hallacas'] ?></td>
             <td><?= money($pp['monto']) ?></td>
             <td><?= h($pp['moneda']) ?></td>
             <td><?= h($pp['metodo']) ?></td>
             <td><?= h($pp['referencia'] ?? '—') ?></td>
             <td class="text-nowrap">
               <?php if (Auth::can('pagos', 'edit')): ?>
               <form method="post" action="<?= url('payment_confirm') ?>" class="d-inline">
                 <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                 <input type="hidden" name="pago_id" value="<?= (int)$pp['id'] ?>">
                 <?php if ($returnJornadaId): ?><input type="hidden" name="return_jornada_id" value="<?= (int)$returnJornadaId ?>"><?php endif; ?>
                 <button class="btn btn-sm btn-primary">Confirmar</button>
               </form>
               <?php endif; ?>
               <?php if (Auth::can('pagos', 'delete')): ?>
               <form method="post" action="<?= url('payment_delete') ?>" class="d-inline" onsubmit="return confirm('¿Eliminar este pago pendiente?');">
                 <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                 <input type="hidden" name="pago_id" value="<?= (int)$pp['id'] ?>">
                 <?php if ($returnJornadaId): ?><input type="hidden" name="return_jornada_id" value="<?= (int)$returnJornadaId ?>"><?php endif; ?>
                 <button class="btn btn-sm btn-outline-danger">Eliminar</button>
               </form>
               <?php endif; ?>
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
              $paginationRoute,
              $pagination['confirmar']['param'],
              array_merge($pagination['filters'], pagination_preserve($pagination['params'], $pagination['confirmar']['param']))
          ) ?>
          <?php endif; ?>
        <?php else: ?>
        <div class="alert alert-info m-3 mb-0">Tus pagos registrados quedan <strong>pendientes por confirmar</strong> hasta que el administrador los valide.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header py-2"><h5 class="mb-0">Pendientes por cancelar</h5></div>
      <div class="card-body p-0">
        <?php if (empty($pendientes)): ?>
        <div class="alert alert-info m-3 mb-0">No hay hallacas pendientes por cancelar.</div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table table-striped table-sm align-middle mb-0">
         <thead>
          <tr><?php if (!$hideJornadaCol): ?><th>Jornada</th><?php endif; ?><th>Misión</th><th>Miembro</th><th>Pend.</th><th>Pend. $</th><th></th></tr>
         </thead>
         <tbody>
         <?php foreach ($pendientes as $e):
            $pend = (int)$e['hallacas_entregadas'] - (int)$e['hallacas_pagadas'];
            $pendMoney = $pend * (float)$e['precio_unitario'];
         ?>
         <tr>
           <?php if (!$hideJornadaCol): ?><td><?= h($e['jornada_name']) ?></td><?php endif; ?>
           <td><?= h($e['mission_name']) ?></td>
           <td><?= h($e['member_name']) ?></td>
           <td><strong><?= $pend ?></strong></td>
           <td><strong><?= money($pendMoney) ?></strong></td>
           <td class="text-end">
             <?php if (Auth::can('pagos', 'create')): ?>
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
             <?php endif; ?>
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
            $paginationRoute,
            $pagination['cancelar']['param'],
            array_merge($pagination['filters'], pagination_preserve($pagination['params'], $pagination['cancelar']['param']))
        ) ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<h5 class="mt-2">Historial de pagos</h5>
<?php if (empty($recientes)): ?>
<div class="alert alert-secondary">Sin pagos registrados.</div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-striped table-sm">
 <thead><tr><th>Fecha</th><?php if (!$hideJornadaCol): ?><th>Jornada</th><?php endif; ?><th>Misión</th><th>Miembro</th><th>Hallacas</th><th>Monto</th><th>Moneda</th><th>Monto (USD)</th><th>Método</th><th>Referencia</th><th>Estado</th><th>Registrado por</th><th>Confirmado por</th><th>Notas</th><th></th></tr></thead>
 <tbody>
 <?php foreach ($recientes as $p): ?>
 <tr>
   <td><?= h($p['fecha']) ?></td>
   <?php if (!$hideJornadaCol): ?><td><?= h($p['jornada_name']) ?></td><?php endif; ?>
   <td><?= h($p['mission_name']) ?></td>
   <td><?= h($p['member_name']) ?></td>
   <td><?= (int)$p['hallacas'] ?></td>
   <td><?= money($p['monto']) ?></td>
   <td><?= h($p['moneda']) ?></td>
   <td class="text-end"><?php
     $usd = Pago::montoUsd($p);
     if (($p['moneda'] ?? '') === 'USD') {
         echo '<strong>' . money($p['monto']) . '</strong>';
     } elseif ($usd !== null) {
         echo '<span class="text-muted" title="Estimado con tasa">' . money($usd) . '</span>';
     } else {
         echo '—';
     }
   ?></td>
   <td><?= h($p['metodo']) ?></td>
   <td><?= h($p['referencia'] ?? '—') ?></td>
   <td><?= status_badge($p['status']) ?></td>
   <td><?= h($p['creado_por'] ?? '-') ?></td>
   <td><?= (($p['status'] ?? '') === 'confirmado') ? h($p['confirmado_por_name'] ?? '-') : '-' ?></td>
   <td><?= h($p['notes']) ?></td>
   <td class="text-nowrap">
     <a class="btn btn-sm btn-outline-secondary" href="<?= url('payment_show', ['id' => (int)$p['id']]) ?>">Ver</a>
     <?php if (PaymentController::canEditPayment($p)): ?>
     <a class="btn btn-sm btn-outline-primary" href="<?= url('payment_edit', array_filter(['id' => (int)$p['id'], 'jornada_id' => $returnJornadaId ?: null])) ?>">Corregir</a>
     <?php endif; ?>
     <?php if (Auth::can('pagos', 'delete') && (($p['status'] ?? '') === 'pendiente' || Auth::isAdmin())): ?>
     <form method="post" action="<?= url('payment_delete') ?>" class="d-inline" onsubmit="return confirm('¿Eliminar este pago?');">
       <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
       <input type="hidden" name="pago_id" value="<?= (int)$p['id'] ?>">
       <?php if ($returnJornadaId): ?><input type="hidden" name="return_jornada_id" value="<?= (int)$returnJornadaId ?>"><?php endif; ?>
       <button class="btn btn-sm btn-outline-danger">Eliminar</button>
     </form>
     <?php endif; ?>
   </td>
 </tr>
 <?php endforeach; ?>
 </tbody>
</table>
</div>
<?= pagination_render(
    $pagination['recientes']['total'],
    $pagination['recientes']['page'],
    $pagination['recientes']['per'],
    $paginationRoute,
    $pagination['recientes']['param'],
    array_merge($pagination['filters'], pagination_preserve($pagination['params'], $pagination['recientes']['param']))
) ?>
<?php endif; ?>

<div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
    <form method="post" action="<?= url('entrega_abono') ?>" class="modal-content" enctype="multipart/form-data">
      <div class="modal-header">
        <h5 class="modal-title">Registrar pago</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="return_to" value="<?= $returnJornadaId ? 'jornada_pagos' : 'payments' ?>">
        <?php if ($returnJornadaId): ?><input type="hidden" name="return_jornada_id" value="<?= (int)$returnJornadaId ?>"><?php endif; ?>
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
        <div class="mb-3 d-none" id="payReferenciaGroup">
          <label class="form-label">Referencia <span class="text-danger">*</span></label>
          <input type="text" name="referencia" id="pay_referencia" class="form-control" maxlength="120" placeholder="Nº de referencia del pago móvil">
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
    if (!btn || !btn.getAttribute('data-entrega-id')) return;
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
    var rg = document.getElementById('payReferenciaGroup');
    var refInp = document.getElementById('pay_referencia');
    if (tg) tg.classList.toggle('d-none', cur === 'USD');
    if (cg) cg.classList.toggle('d-none', !isPago);
    if (rg) rg.classList.toggle('d-none', !isPago);
    if (refInp) refInp.required = isPago;
  }
  var payForm = modal.querySelector('form');
  if (payForm) {
    payForm.addEventListener('submit', function(ev){
      togglePayFields();
      var refInp = document.getElementById('pay_referencia');
      var m = methodSel ? methodSel.value : '';
      var lower = m.toLowerCase();
      var isPago = lower.indexOf('pago') !== -1 || lower.indexOf('movil') !== -1 || lower.indexOf('móvil') !== -1;
      if (isPago && refInp && !refInp.value.trim()) {
        ev.preventDefault();
        alert('Indique la referencia del pago móvil');
      }
    });
  }
  if (methodSel) methodSel.addEventListener('change', togglePayFields);
  var curSelPay = methodSel ? methodSel.parentElement.querySelector('select[name="metodo_otro_currency"]') : null;
  if (curSelPay) curSelPay.addEventListener('change', togglePayFields);
  togglePayFields();
  modal.querySelectorAll('select[name="metodo"]').forEach(function(sel){
    sel.addEventListener('change', function(){
      var inp = sel.parentElement.querySelector('input[name="metodo_otro"]');
      if (inp) { if (sel.value === 'otro') inp.classList.remove('d-none'); else inp.classList.add('d-none'); }
      var cur = sel.parentElement.querySelector('select[name="metodo_otro_currency"]');
      if (cur) { if (sel.value === 'otro') cur.classList.remove('d-none'); else cur.classList.add('d-none'); }
    });
  });
});
</script>
