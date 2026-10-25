<?php include BASE_PATH . '/app/views/partials/header.php';
page_back('inventory_purchases', 'Compras y donaciones');
?>
<h2>Registrar compra</h2>
<p class="text-muted">Precio total (Bs) = cantidad × precio unitario (Bs). Precio total (USD) = precio total (Bs) ÷ tasa del día. Cantidad y precio unitario admiten decimales (punto o coma).</p>
<div class="card p-3 shadow-sm mb-3">
  <h6 class="mb-2">Importar donaciones (CSV)</h6>
  <p class="text-muted small mb-2">Columnas: <code>fecha;donante;sku;cantidad;precio_bs;notas</code> (Excel a veces trunca el encabezado a <code>fech</code>; también vale). Fecha en <code>AAAA-MM-DD</code> o <code>DD/MM/AAAA</code>. <strong>sku</strong>: código del producto en inventario o <strong>id numérico</strong> (rubro 1, 2, 3…). Decimales con punto o coma. <strong>precio_bs</strong> vacío = 0. Se agrupa por fecha + donante.</p>
  <form method="post" action="<?= url('inventory_purchase_donations_import') ?>" enctype="multipart/form-data" class="d-flex flex-wrap align-items-end gap-2">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <div class="flex-grow-1" style="min-width:14rem">
      <label class="form-label small mb-0">Archivo CSV</label>
      <input type="file" name="csv" class="form-control form-control-sm" accept=".csv,text/csv" required>
    </div>
    <button class="btn btn-sm btn-primary">Importar donaciones</button>
    <a class="btn btn-sm btn-outline-secondary" href="<?= url('inventory_purchase_donations_template') ?>">Plantilla</a>
    <a class="btn btn-sm btn-outline-secondary" href="<?= h(asset('data/donaciones_2026-10-15.csv')) ?>" download>Descargar ejemplo 15/10/2026</a>
  </form>
</div>
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
    <div class="col-md-4">
      <label class="form-label">Etiquetas</label>
      <input name="etiquetas" class="form-control" placeholder="Ej: jornada 2, insumos" list="purchaseEtiquetasDatalist">
      <datalist id="purchaseEtiquetasDatalist">
        <?php foreach (InventoryPurchase::allDistinctEtiquetas() as $tag): ?>
        <option value="<?= h($tag) ?>">
        <?php endforeach; ?>
      </datalist>
      <div class="form-text">Separadas por coma; categorías para filtrar después.</div>
    </div>
    <div class="col-md-4">
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
      <td><input type="text" inputmode="decimal" name="cantidad[]" class="form-control form-control-sm qty" placeholder="ej. 1,59" autocomplete="off"></td>
      <td><input type="text" inputmode="decimal" name="precio_bs[]" class="form-control form-control-sm precio-bs" placeholder="ej. 4382,95" autocomplete="off"></td>
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
    <td><input type="text" inputmode="decimal" name="cantidad[]" class="form-control form-control-sm qty" placeholder="ej. 1,59" autocomplete="off"></td>
    <td><input type="text" inputmode="decimal" name="precio_bs[]" class="form-control form-control-sm precio-bs" placeholder="ej. 4382,95" autocomplete="off"></td>
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
  function parseDecimal(raw){
    raw = String(raw || '').trim().replace(/\s/g, '');
    if (!raw) return NaN;
    var hasComma = raw.indexOf(',') >= 0;
    var hasDot = raw.indexOf('.') >= 0;
    if (hasComma && hasDot) {
      if (raw.lastIndexOf(',') > raw.lastIndexOf('.')) {
        raw = raw.replace(/\./g, '').replace(',', '.');
      } else {
        raw = raw.replace(/,/g, '');
      }
    } else if (hasComma) {
      raw = raw.replace(',', '.');
    }
    var n = parseFloat(raw);
    return isFinite(n) ? n : NaN;
  }
  function updateRow(row){
    var tasa = parseDecimal(tasaEl && tasaEl.value ? tasaEl.value : '0');
    var qty = parseDecimal(row.querySelector('.qty')?.value || '');
    var unitBs = parseDecimal(row.querySelector('.precio-bs')?.value || '');
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
