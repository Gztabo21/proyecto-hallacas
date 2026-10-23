<?php include BASE_PATH . '/app/views/partials/header.php';
page_back('inventory', 'Volver al inventario');
?>
<h2>Registrar compra</h2>
<p class="text-muted">Precio total (Bs) = cantidad × precio unitario (Bs). Precio total (USD) = precio total (Bs) ÷ tasa del día.</p>
<form method="post" action="<?= url('inventory_purchase_store') ?>" class="card p-3 shadow-sm" id="purchaseForm">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <div class="row mb-3">
    <div class="col-md-4">
      <label class="form-label">Fecha de compra</label>
      <input type="date" name="fecha" class="form-control" required value="<?= date('Y-m-d') ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Tasa del día (Bs/USD) <span class="text-muted small fw-normal" id="tasaOptionalHint">(opcional en donación)</span></label>
      <input type="number" step="0.0001" min="0.0001" name="tasa" id="purchaseTasa" class="form-control" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">Referencia</label>
      <input name="referencia" class="form-control" placeholder="Factura, orden, etc. (opcional)" maxlength="120">
    </div>
  </div>
  <div class="row mb-3">
    <div class="col-md-8">
      <label class="form-label">Notas generales</label>
      <input name="notes" class="form-control" placeholder="opcional">
    </div>
    <div class="col-md-4 d-flex align-items-end">
      <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" name="es_donacion" value="1" id="esDonacion">
        <label class="form-check-label" for="esDonacion">¿Es donación?</label>
        <div class="form-text">Si marca donación, indique el donante.</div>
      </div>
    </div>
  </div>
  <div class="row mb-3 d-none" id="donanteRow">
    <div class="col-md-6">
      <label class="form-label">Donante</label>
      <input type="text" name="donante" id="donanteInput" class="form-control" maxlength="160" placeholder="Persona u organización que dona">
    </div>
  </div>
  <div class="table-responsive">
  <table class="table table-sm" id="purchaseLines">
    <thead>
      <tr>
        <th>Producto</th>
        <th>Cantidad</th>
        <th>Precio unitario (Bs)</th>
        <th>Precio total (Bs)</th>
        <th>Precio total (USD)</th>
        <th>Notas</th>
      </tr>
    </thead>
    <tbody>
    <?php for ($i = 0; $i < 3; $i++): ?>
    <tr class="purchase-line">
      <td>
        <select name="product_id[]" class="form-select form-select-sm">
          <option value="">—</option>
          <?php foreach ($products as $p): ?>
          <option value="<?= (int)$p['id'] ?>"><?= h($p['name']) ?> (<?= h($p['sku']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </td>
      <td><input type="number" step="0.001" min="0" name="cantidad[]" class="form-control form-control-sm qty"></td>
      <td><input type="number" step="0.0001" min="0" name="precio_bs[]" class="form-control form-control-sm precio-bs"></td>
      <td><span class="total-bs-preview text-muted small">—</span></td>
      <td><span class="usd-preview text-muted small">—</span></td>
      <td><input name="line_notes[]" class="form-control form-control-sm"></td>
    </tr>
    <?php endfor; ?>
    </tbody>
  </table>
  </div>
  <button type="button" class="btn btn-sm btn-outline-secondary mb-3" id="addPurchaseLine">+ Agregar línea</button>
  <div class="d-flex gap-2">
    <button class="btn btn-success">Guardar compra</button>
    <a class="btn btn-outline-secondary" href="<?= url('inventory') ?>">Volver</a>
  </div>
</form>
<template id="purchaseLineTpl">
  <tr class="purchase-line">
    <td>
      <select name="product_id[]" class="form-select form-select-sm">
        <option value="">—</option>
        <?php foreach ($products as $p): ?>
        <option value="<?= (int)$p['id'] ?>"><?= h($p['name']) ?> (<?= h($p['sku']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </td>
    <td><input type="number" step="0.001" min="0" name="cantidad[]" class="form-control form-control-sm qty"></td>
    <td><input type="number" step="0.0001" min="0" name="precio_bs[]" class="form-control form-control-sm precio-bs"></td>
    <td><span class="total-bs-preview text-muted small">—</span></td>
    <td><span class="usd-preview text-muted small">—</span></td>
    <td><input name="line_notes[]" class="form-control form-control-sm"></td>
  </tr>
</template>
<script>
(function(){
  var tasaEl = document.getElementById('purchaseTasa');
  var donEl = document.getElementById('esDonacion');
  var donanteRow = document.getElementById('donanteRow');
  var donanteInput = document.getElementById('donanteInput');
  function toggleDonante(){
    var show = donEl && donEl.checked;
    if (donanteRow) donanteRow.classList.toggle('d-none', !show);
    if (donanteInput) {
      donanteInput.required = show;
      if (!show) donanteInput.value = '';
    }
    if (tasaEl) {
      tasaEl.required = !show;
      if (show) {
        tasaEl.removeAttribute('min');
      } else {
        tasaEl.min = '0.0001';
      }
    }
    var hint = document.getElementById('tasaOptionalHint');
    if (hint) hint.classList.toggle('d-none', !show);
  }
  if (donEl) {
    donEl.addEventListener('change', toggleDonante);
    toggleDonante();
  }
  function fmt(n, dec){
    if (!isFinite(n)) return '—';
    return n.toFixed(dec);
  }
  function updateRow(row){
    var tasa = parseFloat(tasaEl && tasaEl.value ? tasaEl.value : '0');
    var qty = parseFloat(row.querySelector('.qty')?.value || '0');
    var unitBs = parseFloat(row.querySelector('.precio-bs')?.value || '0');
    var totalBs = (qty > 0 && unitBs >= 0) ? qty * unitBs : NaN;
    var totalBsEl = row.querySelector('.total-bs-preview');
    var usdEl = row.querySelector('.usd-preview');
    if (totalBsEl) {
      totalBsEl.textContent = isFinite(totalBs) ? fmt(totalBs, 2) : '—';
    }
    if (usdEl) {
      usdEl.textContent = (isFinite(totalBs) && tasa > 0) ? fmt(totalBs / tasa, 2) : '—';
    }
  }
  function bindRow(row){
    row.querySelectorAll('.precio-bs, .qty').forEach(function(inp){
      inp.addEventListener('input', function(){ updateRow(row); });
    });
    updateRow(row);
  }
  document.querySelectorAll('.purchase-line').forEach(bindRow);
  if (tasaEl) tasaEl.addEventListener('input', function(){
    document.querySelectorAll('.purchase-line').forEach(updateRow);
  });
  var addBtn = document.getElementById('addPurchaseLine');
  var tbody = document.querySelector('#purchaseLines tbody');
  var tpl = document.getElementById('purchaseLineTpl');
  if (addBtn && tbody && tpl) {
    addBtn.addEventListener('click', function(){
      var row = tpl.content.firstElementChild.cloneNode(true);
      tbody.appendChild(row);
      bindRow(row);
    });
  }
})();
</script>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
