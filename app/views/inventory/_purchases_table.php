<?php
/** @var string $tableTitle */
/** @var bool $isDonation */
/** @var array<int,array<string,mixed>> $rows */
$sumBs = 0.0;
$sumUsd = 0.0;
$sumCantidad = 0.0;
$sumLineas = 0;
$hasUsd = false;
foreach ($rows as $r) {
    $sumCantidad += (float)($r['total_cantidad'] ?? 0);
    $sumLineas += (int)($r['line_count'] ?? 0);
    $sumBs += (float)($r['total_bs'] ?? 0);
    $usd = InventoryPurchase::totalUsdForSummary($r);
    if ($usd !== null) {
        $sumUsd += $usd;
        $hasUsd = true;
    }
}
?>
<h3 class="h5 mt-4 mb-2"><?= h($tableTitle) ?></h3>
<div class="table-responsive">
<table class="table table-sm table-striped align-middle">
  <thead>
    <tr>
      <th>#</th>
      <th>Fecha</th>
      <?php if ($isDonation): ?>
      <th>Donante</th>
      <?php else: ?>
      <th>Referencia</th>
      <?php endif; ?>
      <th class="text-end">Tasa</th>
      <th class="text-end">Líneas</th>
      <th class="text-end">Cantidad</th>
      <th class="text-end">Total (Bs)</th>
      <th class="text-end">Total (USD)</th>
      <th>Notas</th>
      <th>Registrado por</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
  <?php if ($rows === []): ?>
    <tr><td colspan="11" class="text-center text-muted py-3">Sin registros</td></tr>
  <?php else: foreach ($rows as $r):
    $usd = InventoryPurchase::totalUsdForSummary($r);
  ?>
    <tr>
      <td><?= (int)$r['id'] ?></td>
      <td><?= h($r['fecha']) ?></td>
      <td><?= h($isDonation ? ($r['donante'] ?? '—') : ($r['referencia'] ?? '—')) ?></td>
      <td class="text-end"><?= (float)$r['tasa'] > 0 ? h(number_format((float)$r['tasa'], 4, '.', '')) : '—' ?></td>
      <td class="text-end"><?= (int)$r['line_count'] ?></td>
      <td class="text-end"><?= h(rtrim(rtrim(number_format((float)($r['total_cantidad'] ?? 0), 3, '.', ''), '0'), '.')) ?></td>
      <td class="text-end"><?= money($r['total_bs']) ?></td>
      <td class="text-end"><?= $usd !== null ? money($usd) : '—' ?></td>
      <td class="small text-muted"><?= h($r['notes'] ?? '') ?: '—' ?></td>
      <td class="small"><?= h($r['created_by_name'] ?? '—') ?></td>
      <td class="text-nowrap">
        <button type="button" class="btn btn-outline-secondary btn-sm py-0 btn-purchase-detail" data-id="<?= (int)$r['id'] ?>">Ver</button>
        <button type="button" class="btn btn-outline-primary btn-sm py-0 btn-purchase-edit" data-id="<?= (int)$r['id'] ?>">Editar</button>
        <button type="button" class="btn btn-outline-danger btn-sm py-0 btn-purchase-delete" data-id="<?= (int)$r['id'] ?>">Eliminar</button>
      </td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
  <?php if ($rows !== []): ?>
  <tfoot class="table-light fw-semibold">
    <tr>
      <td colspan="4" class="text-end">Totales (<?= count($rows) ?> registro<?= count($rows) === 1 ? '' : 's' ?>)</td>
      <td class="text-end"><?= (int)$sumLineas ?></td>
      <td class="text-end"><?= h(rtrim(rtrim(number_format($sumCantidad, 3, '.', ''), '0'), '.')) ?></td>
      <td class="text-end"><?= money($sumBs) ?></td>
      <td class="text-end"><?= $hasUsd ? money($sumUsd) : '—' ?></td>
      <td colspan="3"></td>
    </tr>
  </tfoot>
  <?php endif; ?>
</table>
</div>
