<?php
/** @var array<string,mixed> $m */
/** @var array<int,array{precio_bs:?float,precio_usd:?float,tasa:?float}> $refCostByProduct */
$price = InventoryMovement::displayUnitPrice($m, $refCostByProduct ?? []);
$refSuffix = !empty($price['is_ref']) ? ' ref.' : '';
$empty = 'n/d';
if (!isset($lineTotals)) {
    $lineTotals = InventoryMovement::signedLineAmounts($m, $refCostByProduct ?? []);
}
$lineBs = $lineTotals['bs'] ?? 0.0;
$lineBsDisplay = money_signed((float)$lineBs);
?>
<td class="text-end num"><?= $price['precio_bs'] !== null && $price['precio_bs'] > 0 ? money($price['precio_bs']) . $refSuffix : $empty ?></td>
<td class="text-end num"><?= $lineBsDisplay !== 'n/d' ? $lineBsDisplay . $refSuffix : $empty ?></td>
<td class="text-end num"><?= $price['precio_usd'] !== null && $price['precio_usd'] > 0 ? money($price['precio_usd']) . $refSuffix : $empty ?></td>
<td class="text-end num"><?= $price['tasa'] !== null && $price['tasa'] > 0 ? h(rtrim(rtrim(number_format($price['tasa'], 4, '.', ''), '0'), '.')) . $refSuffix : $empty ?></td>
