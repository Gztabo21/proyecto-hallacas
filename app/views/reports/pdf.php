<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
  h1 { font-size: 16px; margin: 0 0 4px; }
  .meta { font-size: 9px; color: #555; margin-bottom: 12px; }
  h2 { font-size: 12px; margin: 14px 0 6px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
  th, td { border: 1px solid #ccc; padding: 4px 5px; text-align: left; }
  th { background: #eee; font-weight: bold; }
  tfoot td { font-weight: bold; background: #f5f5f5; }
  .num { text-align: right; }
</style>
</head>
<body>
<h1><?= h(APP_NAME) ?> — Reporte</h1>
<p class="meta">
  Generado: <?= h($generatedAt) ?> · Jornada: <?= h($filterLabels['jornada']) ?> · Misión: <?= h($filterLabels['mision']) ?>
</p>

<h2>Por misión / jornada</h2>
<table>
  <thead>
    <tr>
      <th>Jornada</th><th>Fecha</th><th>Misión</th>
      <th class="num">Asign.</th><th class="num">Entreg.</th><th class="num">Pag.</th><th class="num">Pend.</th><th class="num">Monto</th>
    </tr>
  </thead>
  <tbody>
  <?php
  $t_asig = $t_ent = $t_pag = $t_pend = 0; $t_monto = 0;
  foreach ($porMision as $r):
    $pend = (int)$r['entregadas'] - (int)$r['pagadas'];
    $t_asig += (int)$r['hallacas_asignadas']; $t_ent += (int)$r['entregadas']; $t_pag += (int)$r['pagadas'];
    $t_pend += $pend; $t_monto += (float)$r['monto_pagado'];
  ?>
  <tr>
    <td><?= h($r['jornada_name']) ?></td>
    <td><?= h($r['fecha']) ?></td>
    <td><?= h($r['mission_name']) ?></td>
    <td class="num"><?= (int)$r['hallacas_asignadas'] ?></td>
    <td class="num"><?= (int)$r['entregadas'] ?></td>
    <td class="num"><?= (int)$r['pagadas'] ?></td>
    <td class="num"><?= $pend ?></td>
    <td class="num"><?= money($r['monto_pagado']) ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
  <tfoot>
  <tr>
    <td colspan="3">Totales</td>
    <td class="num"><?= $t_asig ?></td>
    <td class="num"><?= $t_ent ?></td>
    <td class="num"><?= $t_pag ?></td>
    <td class="num"><?= $t_pend ?></td>
    <td class="num"><?= money($t_monto) ?></td>
  </tr>
  </tfoot>
</table>

<h2>Por miembro</h2>
<table>
  <thead>
    <tr>
      <th>Miembro</th><th>Misión</th><th class="num">Jornadas</th>
      <th class="num">Entreg.</th><th class="num">Pag.</th><th class="num">Pend.</th><th class="num">Monto</th>
    </tr>
  </thead>
  <tbody>
  <?php
  $t_jor = $t_ent2 = $t_pag2 = $t_pend2 = 0; $t_monto2 = 0;
  foreach ($porMiembro as $r):
    $t_jor += (int)$r['jornadas']; $t_ent2 += (int)$r['entregadas']; $t_pag2 += (int)$r['pagadas'];
    $t_pend2 += (int)$r['entregadas'] - (int)$r['pagadas']; $t_monto2 += (float)$r['monto_pagado'];
  ?>
  <tr>
    <td><?= h($r['member_name']) ?></td>
    <td><?= h($r['mission_name']) ?></td>
    <td class="num"><?= (int)$r['jornadas'] ?></td>
    <td class="num"><?= (int)$r['entregadas'] ?></td>
    <td class="num"><?= (int)$r['pagadas'] ?></td>
    <td class="num"><?= (int)$r['entregadas'] - (int)$r['pagadas'] ?></td>
    <td class="num"><?= money($r['monto_pagado']) ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
  <tfoot>
  <tr>
    <td colspan="2">Totales</td>
    <td class="num"><?= $t_jor ?></td>
    <td class="num"><?= $t_ent2 ?></td>
    <td class="num"><?= $t_pag2 ?></td>
    <td class="num"><?= $t_pend2 ?></td>
    <td class="num"><?= money($t_monto2) ?></td>
  </tr>
  </tfoot>
</table>
</body>
</html>
