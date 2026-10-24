<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #222; }
  h1 { font-size: 14px; margin: 0 0 4px; }
  .meta { font-size: 8px; color: #555; margin-bottom: 10px; }
  table { width: 100%; border-collapse: collapse; }
  th, td { border: 1px solid #ccc; padding: 2px 3px; text-align: left; }
  th { background: #eee; font-weight: bold; }
  .num { text-align: right; }
</style>
</head>
<body>
<h1><?= h(APP_NAME) ?> — Historial de movimientos</h1>
<p class="meta">
  Generado: <?= h($generatedAt) ?> · Filtro: <?= h($filterLabel) ?>
  <?php if ($total > count($movements)): ?> · Mostrando <?= count($movements) ?> de <?= (int)$total ?> registros<?php endif; ?>
  <br>Precio con «ref.» = no se guardó en ese movimiento; se muestra el último costo unitario del producto (stock inicial o compra).
  Salidas a jornada y ajustes solo de cantidad no llevan precio propio.
</p>
<table>
  <thead>
    <tr>
      <th>Fecha</th><th>Tipo</th><th>Producto</th><th>SKU</th><th class="num">Cant.</th><th>Unidad</th>
      <th class="num">Precio Bs</th><th class="num">Total (Bs)</th><th class="num">USD</th><th class="num">Tasa</th><th>Jornada</th><th>Registrado por</th>
    </tr>
  </thead>
  <tbody>
  <?php if (empty($movements)): ?>
  <tr><td colspan="12">Sin movimientos</td></tr>
  <?php else: foreach ($movements as $m):
    $delta = InventoryMovement::deltaForTipo($m['tipo'], (float)$m['cantidad']);
    $sign = $delta >= 0 ? '+' : '−';
  ?>
  <tr>
    <td><?= h($m['fecha_ingreso']) ?></td>
    <td><?= h(InventoryMovement::tipoLabelForMovement($m)) ?></td>
    <td><?= h($m['product_name']) ?></td>
    <td><?= h($m['sku']) ?></td>
    <td class="num"><?= $sign ?><?= h(rtrim(rtrim(number_format((float)$m['cantidad'], 3, '.', ''), '0'), '.')) ?></td>
    <td><?= h($m['unidad_metrica']) ?></td>
    <?php include BASE_PATH . '/app/views/inventory/_movement_price_cells.php'; ?>
    <td><?= h($m['jornada_name'] ?? 'n/d') ?></td>
    <td><?= h($m['created_by_name'] ?? 'n/d') ?></td>
  </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table>
</body>
</html>
