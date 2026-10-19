<?php include BASE_PATH . '/app/views/partials/header.php';
page_back('inventory', 'Volver al inventario');
?>
<h2>Registrar compra</h2>
<p class="text-muted">Si el producto ya existe, se suma al stock y queda en el historial.</p>
<form method="post" action="<?= url('inventory_purchase_store') ?>" class="card p-3 shadow-sm" id="purchaseForm">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <div class="row mb-3">
    <div class="col-md-4">
      <label class="form-label">Fecha de compra</label>
      <input type="date" name="fecha" class="form-control" required value="<?= date('Y-m-d') ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Tasa del día (Bs/USD)</label>
      <input type="number" step="0.0001" min="0.0001" name="tasa" id="purchaseTasa" class="form-control" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">Notas generales</label>
      <input name="notes" class="form-control" placeholder="opcional">
    </div>
  </div>
  <div class="table-responsive">
  <table class="table table-sm" id="purchaseLines">
    <thead>
      <tr><th>Producto</th><th>Cantidad</th><th>Precio (Bs)</th><th>Precio (USD)</th><th>Notas</th></tr>
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
    <td><span class="usd-preview text-muted small">—</span></td>
    <td><input name="line_notes[]" class="form-control form-control-sm"></td>
  </tr>
</template>
<script>
(function(){
  var tasaEl = document.getElementById('purchaseTasa');
  function updateUsd(row){
    var tasa = parseFloat(tasaEl && tasaEl.value ? tasaEl.value : '0');
    var bs = parseFloat(row.querySelector('.precio-bs')?.value || '0');
    var el = row.querySelector('.usd-preview');
    if (!el) return;
    el.textContent = (tasa > 0 && bs >= 0) ? (bs / tasa).toFixed(4) : '—';
  }
  function bindRow(row){
    row.querySelectorAll('.precio-bs, .qty').forEach(function(inp){
      inp.addEventListener('input', function(){ updateUsd(row); });
    });
    updateUsd(row);
  }
  document.querySelectorAll('.purchase-line').forEach(bindRow);
  if (tasaEl) tasaEl.addEventListener('input', function(){
    document.querySelectorAll('.purchase-line').forEach(updateUsd);
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
