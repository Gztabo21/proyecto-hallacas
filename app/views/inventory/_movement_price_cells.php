<?php
/** @var array<string,mixed> $m */
/** @var array<int,array{precio_bs:?float,precio_usd:?float,tasa:?float}> $refCostByProduct */
$price = InventoryMovement::displayUnitPrice($m, $refCostByProduct ?? []);
$refSuffix = !empty($price['is_ref']) ? ' ref.' : '';
$empty = 'n/d';
$qty = (float)($m['cantidad'] ?? 0);
$lineBs = ($price['precio_bs'] !== null && $price['precio_bs'] > 0) ? $qty * $price['precio_bs'] : null;
?>
<td class="text-end num"><?= $price['precio_bs'] !== null && $price['precio_bs'] > 0 ? money($price['precio_bs']) . $refSuffix : $empty ?></td>
<td class="text-end num"><?= $lineBs !== null && $lineBs > 0 ? money($lineBs) . $refSuffix : $empty ?></td>
<td class="text-end num"><?= $price['precio_usd'] !== null && $price['precio_usd'] > 0 ? money($price['precio_usd']) . $refSuffix : $empty ?></td>
<td class="text-end num"><?= $price['tasa'] !== null && $price['tasa'] > 0 ? h(rtrim(rtrim(number_format($price['tasa'], 4, '.', ''), '0'), '.')) . $refSuffix : $empty ?></td>
