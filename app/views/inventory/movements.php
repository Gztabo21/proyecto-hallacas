<?php include BASE_PATH . '/app/views/partials/header.php';
page_back('inventory', 'Volver al inventario');
$preserve = array_merge($pagination['filters'], pagination_preserve($pagination['params'], $pagination['list']['param']));
?>
<?php
$pdfParams = array_filter([
    'product_id' => isset($_GET['product_id']) && $_GET['product_id'] !== '' ? (int)$_GET['product_id'] : null,
    'jornada_id' => isset($_GET['jornada_id']) && $_GET['jornada_id'] !== '' ? (int)$_GET['jornada_id'] : null,
], fn($v) => $v !== null);
?>
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-2 mb-2">
  <h2 class="mb-0">Historial de movimientos</h2>
  <a class="btn btn-outline-danger btn-sm" href="<?= url('inventory_movements_pdf', $pdfParams) ?>">Descargar PDF</a>
</div>
<p class="text-muted small">En salidas y ajustes de cantidad a veces no hay precio en el movimiento; se muestra el último costo del producto marcado como <em>ref.</em></p>
<form method="get" action="<?= url('inventory_movements') ?>" class="card p-3 mb-3">
  <input type="hidden" name="r" value="inventory_movements">
  <div class="row g-2 align-items-end">
    <div class="col-md-4">
      <label class="form-label">Producto</label>
      <select name="product_id" class="form-select">
        <option value="">Todos</option>
        <?php foreach ($products as $p): ?>
        <option value="<?= (int)$p['id'] ?>" <?= (isset($_GET['product_id']) && (int)$_GET['product_id'] === (int)$p['id']) ? 'selected' : '' ?>><?= h($p['name']) ?></option>
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
      <th>Precio Bs</th><th>Precio USD</th><th>Tasa</th><th>Jornada</th><th>Registrado por</th>
    </tr>
  </thead>
  <tbody>
  <?php if (empty($movements)): ?>
  <tr><td colspan="11" class="text-center text-muted py-3">Sin movimientos</td></tr>
  <?php else: foreach ($movements as $m):
    $delta = InventoryMovement::deltaForTipo($m['tipo'], (float)$m['cantidad']);
    $sign = $delta >= 0 ? '+' : '−';
  ?>
  <tr>
    <td><?= h($m['fecha_ingreso']) ?></td>
    <td><?= h(InventoryMovement::tipoLabel($m['tipo'])) ?></td>
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
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
