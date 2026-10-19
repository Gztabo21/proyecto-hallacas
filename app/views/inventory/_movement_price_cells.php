<?php
/** @var array<string,mixed> $m */
/** @var array<int,array{precio_bs:?float,precio_usd:?float,tasa:?float}> $refCostByProduct */
$price = InventoryMovement::displayUnitPrice($m, $refCostByProduct ?? []);
$refSuffix = !empty($price['is_ref']) ? ' ref.' : '';
$empty = 'n/d';
?>
<td class="num"><?= $price['precio_bs'] !== null && $price['precio_bs'] > 0 ? money($price['precio_bs']) . $refSuffix : $empty ?></td>
<td class="num"><?= $price['precio_usd'] !== null && $price['precio_usd'] > 0 ? money($price['precio_usd']) . $refSuffix : $empty ?></td>
<td class="num"><?= $price['tasa'] !== null && $price['tasa'] > 0 ? h(rtrim(rtrim(number_format($price['tasa'], 4, '.', ''), '0'), '.')) . $refSuffix : $empty ?></td>
