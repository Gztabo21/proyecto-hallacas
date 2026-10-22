<?php include BASE_PATH . '/app/views/partials/header.php';
page_back('dashboard', 'Volver al dashboard');

$inventoryUsd = static function (array $p): ?float {
    if (isset($p['ultimo_precio_usd']) && $p['ultimo_precio_usd'] !== null && $p['ultimo_precio_usd'] !== '') {
        return (float)$p['ultimo_precio_usd'];
    }
    $bs = isset($p['ultimo_precio_bs']) ? (float)$p['ultimo_precio_bs'] : 0.0;
    $tasa = isset($p['ultima_tasa']) ? (float)$p['ultima_tasa'] : 0.0;
    if ($bs > 0 && $tasa > 0) {
        return InventoryMovement::usdFromBs($bs, $tasa);
    }
    return null;
};
$inventorySort = $inventorySort ?? 'name';
$inventorySortDir = $inventorySortDir ?? 'asc';
$invSortLink = static function (string $col, string $label) use ($inventorySort, $inventorySortDir): string {
    $nextDir = ($inventorySort === $col && $inventorySortDir === 'asc') ? 'desc' : 'asc';
    $href = url('inventory', ['sort' => $col, 'dir' => $nextDir]);
    $arrow = $inventorySort === $col ? ($inventorySortDir === 'asc' ? ' ↑' : ' ↓') : '';
    return '<a href="' . h($href) . '" class="text-decoration-none text-reset">' . h($label) . $arrow . '</a>';
};
?>
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-2 mb-3">
  <h2 class="mb-0">Inventario</h2>
  <div class="d-flex flex-wrap gap-2">
    <a class="btn btn-outline-secondary btn-sm" href="<?= url('inventory_movements') ?>">Historial de movimientos</a>
    <a class="btn btn-outline-danger btn-sm" href="<?= url('inventory_pdf') ?>">Descargar PDF</a>
    <a class="btn btn-outline-dark btn-sm" href="<?= url('inventory_export') ?>">Exportar CSV</a>
    <a class="btn btn-outline-dark btn-sm" href="<?= url('inventory_import') ?>">Importar CSV</a>
    <a class="btn btn-success btn-sm" href="<?= url('inventory_purchase') ?>">Registrar compra</a>
    <a class="btn btn-warning btn-sm" href="<?= url('inventory_initial') ?>">Stock inicial</a>
    <?php if (Auth::canManageInventoryCatalog()): ?>
    <a class="btn btn-primary btn-sm" href="<?= url('inventory_product_create') ?>">Nuevo producto</a>
    <?php endif; ?>
  </div>
</div>
<p class="text-muted small">Precio unitario y fecha según el último movimiento con costo (stock inicial, compra o importación).</p>
<div class="table-responsive">
<table class="table table-striped table-sm align-middle">
  <thead>
    <tr>
      <th><?= $invSortLink('name', 'Producto') ?></th>
      <th><?= $invSortLink('sku', 'SKU') ?></th>
      <th>Stock</th>
      <th>Unidad</th>
      <th class="text-end">Precio Bs</th>
      <th class="text-end">Tasa</th>
      <th class="text-end">Precio USD</th>
      <th>Fecha ingreso</th>
      <?php if (Auth::canManageInventoryCatalog()): ?><th></th><?php endif; ?>
    </tr>
  </thead>
  <tbody>
  <?php
  $invTotalStock = 0.0;
  $invTotalValorBs = 0.0;
  $invTotalValorUsd = 0.0;
  $invTotalConPrecio = 0;
  ?>
  <?php if (empty($products)): ?>
  <tr><td colspan="<?= Auth::canManageInventoryCatalog() ? 9 : 8 ?>" class="text-muted text-center py-4">No hay productos. Cree el catálogo y registre stock inicial o compras.</td></tr>
  <?php else: foreach ($products as $p):
    $usd = $inventoryUsd($p);
    $stock = (float)$p['stock'];
    $invTotalStock += $stock;
    if ($stock > 0 && $usd !== null) {
        $invTotalValorUsd += $stock * $usd;
        $invTotalConPrecio++;
    }
    if ($stock > 0 && $p['ultimo_precio_bs'] !== null && $p['ultimo_precio_bs'] !== '') {
        $invTotalValorBs += $stock * (float)$p['ultimo_precio_bs'];
    }
  ?>
  <tr>
    <td><?= h($p['name']) ?></td>
    <td><code><?= h($p['sku']) ?></code></td>
    <td><strong><?= h(rtrim(rtrim(number_format((float)$p['stock'], 3, '.', ''), '0'), '.')) ?></strong></td>
    <td><?= h($p['unidad_metrica']) ?></td>
    <td class="text-end"><?= ($p['ultimo_precio_bs'] !== null && $p['ultimo_precio_bs'] !== '') ? money($p['ultimo_precio_bs']) : '—' ?></td>
    <td class="text-end"><?= ($p['ultima_tasa'] !== null && $p['ultima_tasa'] !== '') ? h(rtrim(rtrim(number_format((float)$p['ultima_tasa'], 4, '.', ''), '0'), '.')) : '—' ?></td>
    <td class="text-end"><?= $usd !== null ? money($usd) : '—' ?></td>
    <td><?= !empty($p['ultima_fecha_ingreso']) ? h($p['ultima_fecha_ingreso']) : '—' ?></td>
    <?php if (Auth::canManageInventoryCatalog()): ?>
    <td><a class="btn btn-sm btn-outline-primary" href="<?= url('inventory_product_edit', ['id' => (int)$p['id']]) ?>">Editar</a></td>
    <?php endif; ?>
  </tr>
  <?php endforeach; ?>
  </tbody>
  <tfoot class="table-light">
  <tr class="fw-semibold">
    <td colspan="2">Totales · <?= count($products) ?> productos</td>
    <td><?= h(rtrim(rtrim(number_format($invTotalStock, 3, '.', ''), '0'), '.')) ?></td>
    <td class="small text-muted">Σ stock</td>
    <td class="text-end"><?= $invTotalValorBs > 0 ? money($invTotalValorBs) : '—' ?></td>
    <td></td>
    <td class="text-end"><?= $invTotalValorUsd > 0 ? money($invTotalValorUsd) : '—' ?></td>
    <td colspan="<?= Auth::canManageInventoryCatalog() ? 2 : 1 ?>" class="small text-muted">Valor = stock × último precio</td>
  </tr>
  </tfoot>
  <?php endif; ?>
</table>
</div>
<?php if (!empty($products)): ?>
<p class="text-muted small mb-0">La suma de stock mezcla unidades distintas (kg, l, unidades). Los montos en Bs/USD solo incluyen productos con stock y último precio registrado<?= $invTotalConPrecio > 0 && $invTotalConPrecio < count($products) ? ' (' . $invTotalConPrecio . ' de ' . count($products) . ')' : '' ?>.</p>
<?php endif; ?>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
