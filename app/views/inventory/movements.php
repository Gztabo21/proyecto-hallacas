<?php include BASE_PATH . '/app/views/partials/header.php';
page_back('inventory', 'Volver al inventario');
$preserve = array_merge($pagination['filters'], pagination_preserve($pagination['params'], $pagination['list']['param']));
?>
<?php
$pdfParams = array_filter([
    'product_id' => isset($_GET['product_id']) && $_GET['product_id'] !== '' ? (int)$_GET['product_id'] : null,
    'jornada_id' => isset($_GET['jornada_id']) && $_GET['jornada_id'] !== '' ? (int)$_GET['jornada_id'] : null,
    'purchase_id' => isset($_GET['purchase_id']) && $_GET['purchase_id'] !== '' ? (int)$_GET['purchase_id'] : null,
], fn($v) => $v !== null);
$jornadas = $jornadas ?? [];
?>
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-2 mb-2">
  <h2 class="mb-0">Historial de movimientos</h2>
  <div class="d-flex flex-wrap gap-2">
    <?php if (!empty($_GET['purchase_id'])): ?>
    <a class="btn btn-outline-secondary btn-sm" href="<?= url('inventory_purchases') ?>">Compras y donaciones</a>
    <?php endif; ?>
    <a class="btn btn-outline-danger btn-sm" href="<?= url('inventory_movements_pdf', $pdfParams) ?>">Descargar PDF</a>
  </div>
</div>
<?php if (!empty($_GET['purchase_id'])): ?>
<p class="alert alert-light border py-2 small mb-2">Mostrando líneas de la compra/donación #<?= (int)$_GET['purchase_id'] ?>.</p>
<?php endif; ?>
<p class="text-muted small">Salidas y devoluciones de jornada guardan el costo de su fecha; <em>ref.</em> solo si aún no hay costo congelado. El total en Bs/USD en el pie es <strong>neto</strong> (salidas suman, excedentes restan).</p>
<?php
$pl = $pagination['list'];
$movPerPage = (int)$pl['per'];
$movTotal = (int)$pl['total'];
$movPage = (int)$pl['page'];
$movFrom = $movTotal > 0 ? ($movPage - 1) * $movPerPage + 1 : 0;
$movTo = min($movTotal, $movPage * $movPerPage);
?>
<p class="text-muted small mb-2"><?php if ($movTotal > 0): ?><strong><?= (int)$movFrom ?>–<?= (int)$movTo ?></strong> de <strong><?= $movTotal ?></strong> movimientos · <strong><?= $movPerPage ?></strong> líneas por página · página <?= $movPage ?><?php else: ?>Sin movimientos que mostrar<?php endif; ?></p>
<form method="get" action="<?= url('inventory_movements') ?>" class="card p-3 mb-3">
  <input type="hidden" name="r" value="inventory_movements">
  <?php if (!empty($_GET['purchase_id'])): ?>
  <input type="hidden" name="purchase_id" value="<?= (int)$_GET['purchase_id'] ?>">
  <?php endif; ?>
  <?php
  $selectedJornadaId = isset($_GET['jornada_id']) && $_GET['jornada_id'] !== '' ? (int)$_GET['jornada_id'] : 0;
  ?>
  <div class="row g-2 align-items-end">
    <div class="col-md-4">
      <label class="form-label" for="productFilterSelect">Producto</label>
      <select name="product_id" id="productFilterSelect" class="form-select">
        <option value=""></option>
        <?php foreach ($products as $p): ?>
        <option value="<?= (int)$p['id'] ?>" <?= (isset($_GET['product_id']) && (int)$_GET['product_id'] === (int)$p['id']) ? 'selected' : '' ?>><?= h($p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label" for="jornadaFilterSelect">Jornada</label>
      <select name="jornada_id" id="jornadaFilterSelect" class="form-select">
        <option value=""></option>
        <?php foreach ($jornadas as $j): ?>
        <option value="<?= (int)$j['id'] ?>" <?= $selectedJornadaId === (int)$j['id'] ? 'selected' : '' ?>><?= h($j['name'] . ' (' . $j['fecha'] . ')') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <button class="btn btn-primary w-100">Filtrar</button>
    </div>
  </div>
</form>
<div class="table-responsive">
<table class="table table-sm table-striped">
  <thead>
    <tr>
      <th>Fecha</th><th>Tipo</th><th>Producto</th><th>SKU</th><th>Cant.</th><th>Unidad</th>
      <th class="text-end">Precio Bs</th><th class="text-end">Total (Bs)</th><th class="text-end">Precio USD</th><th class="text-end">Tasa</th><th>Jornada</th><th>Registrado por</th>
    </tr>
  </thead>
  <tbody>
  <?php
  $totDeltaCant = 0.0;
  $totLineBs = 0.0;
  $totLineUsd = 0.0;
  $totHasUsd = false;
  ?>
  <?php if (empty($movements)): ?>
  <tr><td colspan="12" class="text-center text-muted py-3">Sin movimientos</td></tr>
  <?php else: foreach ($movements as $m):
    $delta = InventoryMovement::deltaForTipo($m['tipo'], (float)$m['cantidad']);
    $sign = $delta >= 0 ? '+' : '−';
    $totDeltaCant += $delta;
    $lineTotals = InventoryMovement::signedLineAmounts($m, $refCostByProduct);
    $totLineBs += $lineTotals['bs'];
    if (abs($lineTotals['usd']) > 0) {
      $totLineUsd += $lineTotals['usd'];
      $totHasUsd = true;
    }
  ?>
  <tr>
    <td><?= h($m['fecha_ingreso']) ?></td>
    <td><?= h(InventoryMovement::tipoLabelForMovement($m)) ?></td>
    <td><?= h($m['product_name']) ?></td>
    <td><code><?= h($m['sku']) ?></code></td>
    <td><?= $sign ?><?= h(rtrim(rtrim(number_format((float)$m['cantidad'], 3, '.', ''), '0'), '.')) ?></td>
    <td><?= h($m['unidad_metrica']) ?></td>
    <?php include BASE_PATH . '/app/views/inventory/_movement_price_cells.php'; ?>
    <td><?= h($m['jornada_name'] ?? '—') ?></td>
    <td><?= h($m['created_by_name'] ?? '—') ?></td>
  </tr>
  <?php endforeach; endif; ?>
  </tbody>
  <?php if (!empty($movements)): ?>
  <tfoot class="table-light fw-semibold">
    <tr>
      <td colspan="4" class="text-end">Totales (página)</td>
      <td class="text-end"><?= ($totDeltaCant >= 0 ? '+' : '−') . h(rtrim(rtrim(number_format(abs($totDeltaCant), 3, '.', ''), '0'), '.')) ?></td>
      <td></td>
      <td></td>
      <td class="text-end"><?= money_signed($totLineBs) ?></td>
      <td class="text-end"><?= $totHasUsd ? money_signed($totLineUsd) : 'n/d' ?></td>
      <td colspan="3"></td>
    </tr>
  </tfoot>
  <?php endif; ?>
</table>
</div>
<?= pagination_render(
    $pagination['list']['total'],
    $pagination['list']['page'],
    $pagination['list']['per'],
    'inventory_movements',
    $pagination['list']['param'],
    $preserve
) ?>
<?php ob_start(); ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script>
jQuery(function($) {
  if (!$.fn.select2) {
    return;
  }
  var select2Lang = {
    noResults: function() { return 'Sin coincidencias'; },
    searching: function() { return 'Buscando…'; }
  };
  function initMovementFilterSelect(selector, placeholder) {
    var $el = $(selector);
    if (!$el.length) {
      return;
    }
    $el.select2({
      theme: 'bootstrap-5',
      width: '100%',
      placeholder: placeholder,
      allowClear: true,
      language: select2Lang
    });
  }
  initMovementFilterSelect('#productFilterSelect', 'Todos los productos');
  initMovementFilterSelect('#jornadaFilterSelect', 'Todas las jornadas');
});
</script>
<?php
$extraFooterScripts = ob_get_clean();
include BASE_PATH . '/app/views/partials/footer.php';
