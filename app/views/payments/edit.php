<?php include BASE_PATH . '/app/views/partials/header.php';
$p = $pago;
$backParams = [];
if (!empty($returnJornadaId)) {
    page_back('jornada_show', 'Volver a la jornada', ['id' => (int)$returnJornadaId, 'tab' => 'pagos']);
} else {
    page_back('payments', 'Volver a pagos');
}
$methods = payment_methods();
$currentMetodo = (string)($p['metodo'] ?? '');
$metodoInList = false;
foreach ($methods as $pm) {
    if ($pm['name'] === $currentMetodo) {
        $metodoInList = true;
        break;
    }
}
?>
<h2>Corregir pago #<?= (int)$p['id'] ?></h2>
<p class="text-muted small mb-3">
  <?= h($p['member_name']) ?> · <?= h($p['mission_name']) ?> · <?= h($p['jornada_name']) ?>
  · <?= status_badge($p['status']) ?>
</p>
<?php if (($p['status'] ?? '') === 'confirmado'): ?>
<div class="alert alert-warning py-2 small">Este pago ya está confirmado: al guardar se recalculan las hallacas y montos aplicados a la entrega.</div>
<?php endif; ?>
<form method="post" action="<?= url('payment_update') ?>" class="card p-3 shadow-sm" enctype="multipart/form-data" id="paymentEditForm">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <input type="hidden" name="pago_id" value="<?= (int)$p['id'] ?>">
  <?php if (!empty($returnJornadaId)): ?>
  <input type="hidden" name="return_jornada_id" value="<?= (int)$returnJornadaId ?>">
  <?php endif; ?>
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label">Hallacas pagadas</label>
      <input type="number" name="hallacas" class="form-control" min="0" value="<?= (int)$p['hallacas'] ?>" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">Monto</label>
      <input type="number" step="0.01" name="monto" class="form-control" min="0" value="<?= h((string)$p['monto']) ?>" required>
    </div>
    <div class="col-md-4" id="editTasaGroup">
      <label class="form-label">Tasa (Bs por USD)</label>
      <input type="number" step="0.0001" name="tasa" class="form-control" value="<?= $p['tasa'] !== null && $p['tasa'] !== '' ? h((string)$p['tasa']) : '' ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Método de pago</label>
      <select name="metodo" class="form-select" id="editMetodo" required>
        <?php foreach ($methods as $pm): ?>
        <option value="<?= h($pm['name']) ?>" data-currency="<?= h($pm['currency']) ?>" <?= $currentMetodo === $pm['name'] ? 'selected' : '' ?>><?= h($pm['name']) ?> (<?= h($pm['currency']) ?>)</option>
        <?php endforeach; ?>
        <option value="otro" <?= !$metodoInList && $currentMetodo !== '' ? 'selected' : '' ?>>Otro</option>
      </select>
      <input type="text" name="metodo_otro" class="form-control mt-2 <?= ($metodoInList || $currentMetodo === '') ? 'd-none' : '' ?>" placeholder="Especifique el método" value="<?= !$metodoInList ? h($currentMetodo) : '' ?>">
      <select name="metodo_otro_currency" class="form-select mt-2 <?= ($metodoInList || $currentMetodo === '') ? 'd-none' : '' ?>">
        <option value="VES" <?= ($p['moneda'] ?? '') !== 'USD' ? 'selected' : '' ?>>VES</option>
        <option value="USD" <?= ($p['moneda'] ?? '') === 'USD' ? 'selected' : '' ?>>USD</option>
      </select>
    </div>
    <div class="col-md-6 d-none" id="editReferenciaGroup">
      <label class="form-label">Referencia</label>
      <input type="text" name="referencia" id="edit_referencia" class="form-control" maxlength="120" value="<?= h($p['referencia'] ?? '') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Notas</label>
      <input type="text" name="notes" class="form-control" value="<?= h($p['notes'] ?? '') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Motivo de la corrección</label>
      <input type="text" name="correccion_nota" class="form-control" placeholder="Ej. error en referencia o monto">
    </div>
    <div class="col-12">
      <label class="form-label">Nueva captura (opcional)</label>
      <input type="file" name="captura" class="form-control" accept="image/*">
      <?php if (!empty($p['captura'])): ?><div class="form-text">Si no sube archivo, se mantiene la captura actual.</div><?php endif; ?>
    </div>
  </div>
  <div class="mt-3 d-flex gap-2">
    <button type="submit" class="btn btn-primary">Guardar corrección</button>
    <a class="btn btn-outline-secondary" href="<?= url('payment_show', ['id' => (int)$p['id']]) ?>">Ver detalle</a>
  </div>
</form>
<script>
(function(){
  var sel = document.getElementById('editMetodo');
  if (!sel) return;
  function toggle(){
    var m = sel.value;
    var lower = m.toLowerCase();
    var isPago = lower.indexOf('pago') !== -1 || lower.indexOf('movil') !== -1 || lower.indexOf('móvil') !== -1;
    var cur = 'VES';
    if (m === 'otro') {
      var curSel = sel.parentElement.querySelector('select[name="metodo_otro_currency"]');
      cur = curSel ? curSel.value : 'VES';
    } else {
      var opt = sel.options[sel.selectedIndex];
      if (opt) cur = opt.getAttribute('data-currency') || 'VES';
    }
    var tg = document.getElementById('editTasaGroup');
    var rg = document.getElementById('editReferenciaGroup');
    var refInp = document.getElementById('edit_referencia');
    if (tg) tg.classList.toggle('d-none', cur === 'USD');
    if (rg) rg.classList.toggle('d-none', !isPago);
    if (refInp) refInp.required = isPago;
    var inp = sel.parentElement.querySelector('input[name="metodo_otro"]');
    var curSel = sel.parentElement.querySelector('select[name="metodo_otro_currency"]');
    if (inp) inp.classList.toggle('d-none', m !== 'otro');
    if (curSel) curSel.classList.toggle('d-none', m !== 'otro');
  }
  sel.addEventListener('change', toggle);
  var curSel = sel.parentElement.querySelector('select[name="metodo_otro_currency"]');
  if (curSel) curSel.addEventListener('change', toggle);
  toggle();
})();
</script>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
