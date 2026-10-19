<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #222; }
  h1 { font-size: 15px; margin: 0 0 4px; }
  .meta { font-size: 8px; color: #555; margin-bottom: 10px; }
  table { width: 100%; border-collapse: collapse; }
  th, td { border: 1px solid #ccc; padding: 3px 4px; text-align: left; }
  th { background: #eee; font-weight: bold; }
  tfoot td { font-weight: bold; background: #f5f5f5; }
  .num { text-align: right; }
</style>
</head>
<body>
<h1><?= h(APP_NAME) ?> — Inventario</h1>
<p class="meta">Generado: <?= h($generatedAt) ?> · Precios según último movimiento con costo</p>
<table>
  <thead>
    <tr>
      <th>Producto</th><th>SKU</th><th class="num">Stock</th><th>Unidad</th>
      <th class="num">Precio Bs</th><th class="num">Tasa</th><th class="num">Precio USD</th><th>Fecha ingreso</th>
    </tr>
  </thead>
  <tbody>
  <?php
  $tStock = 0.0;
  foreach ($products as $p):
    $usd = null;
    if (isset($p['ultimo_precio_usd']) && $p['ultimo_precio_usd'] !== null && $p['ultimo_precio_usd'] !== '') {
        $usd = (float)$p['ultimo_precio_usd'];
    } elseif (!empty($p['ultimo_precio_bs']) && !empty($p['ultima_tasa'])) {
        $usd = InventoryMovement::usdFromBs((float)$p['ultimo_precio_bs'], (float)$p['ultima_tasa']);
    }
    $tStock += (float)$p['stock'];
  ?>
  <tr>
    <td><?= h($p['name']) ?></td>
    <td><?= h($p['sku']) ?></td>
    <td class="num"><?= h(rtrim(rtrim(number_format((float)$p['stock'], 3, '.', ''), '0'), '.')) ?></td>
    <td><?= h($p['unidad_metrica']) ?></td>
    <td class="num"><?= ($p['ultimo_precio_bs'] !== null && $p['ultimo_precio_bs'] !== '') ? money($p['ultimo_precio_bs']) : '—' ?></td>
    <td class="num"><?= ($p['ultima_tasa'] !== null && $p['ultima_tasa'] !== '') ? h($p['ultima_tasa']) : '—' ?></td>
    <td class="num"><?= $usd !== null ? money($usd) : '—' ?></td>
    <td><?= !empty($p['ultima_fecha_ingreso']) ? h($p['ultima_fecha_ingreso']) : '—' ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
  <tfoot>
  <tr>
    <td colspan="2">Productos: <?= count($products) ?></td>
    <td class="num"><?= h(rtrim(rtrim(number_format($tStock, 3, '.', ''), '0'), '.')) ?></td>
    <td colspan="5">Total unidades (suma de stock)</td>
  </tr>
  </tfoot>
</table>
</body>
</html>
