<?php include BASE_PATH . '/app/views/partials/header.php'; ?>
<h2>Mi dashboard</h2>
<?php
$entregadas = 0; $pagadas=0; $pend=0; $monto=0;
foreach ($entregas as $e){ $entregadas += (int)$e['hallacas_entregadas']; $pagadas += (int)$e['hallacas_pagadas']; $monto += (float)$e['monto_pagado']; }
$pend = $entregadas - $pagadas;
?>
<div class="row mb-3">
  <div class="col-md-3"><div class="card"><div class="card-body"><h6>Entregadas</h6><h3><?= $entregadas ?></h3></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><h6>Pagadas</h6><h3><?= $pagadas ?></h3></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><h6>Pendientes</h6><h3><?= $pend ?></h3></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><h6>Monto pagado</h6><h3><?= money($monto) ?></h3></div></div></div>
</div>
<div class="table-responsive">
<table class="table table-sm table-striped">
 <thead><tr><th>Jornada</th><th>Fecha</th><th>Misión</th><th>Entreg.</th><th>Pag.</th><th>Pend.</th><th>Estado</th><th>Monto</th></tr></thead>
 <tbody>
 <?php foreach ($entregas as $e): ?>
 <tr>
   <td><?= h($e['jornada_name']) ?></td>
   <td><?= h($e['fecha']) ?></td>
   <td><?= h($e['mission_name']) ?></td>
   <td><?= (int)$e['hallacas_entregadas'] ?></td>
   <td><?= (int)$e['hallacas_pagadas'] ?></td>
   <td><?= (int)$e['hallacas_entregadas']-(int)$e['hallacas_pagadas'] ?></td>
   <td><?= status_badge($e['status']) ?></td>
   <td><?= money($e['monto_pagado']) ?></td>
 </tr>
 <?php endforeach; ?>
 </tbody>
</table>
</div>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
