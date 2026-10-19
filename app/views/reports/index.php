<?php include BASE_PATH . '/app/views/partials/header.php';
page_back('dashboard', 'Volver al dashboard');
?>
<h2>Reportes</h2>
<form method="get" action="<?= url('reports') ?>" class="card p-3 mb-3">
  <input type="hidden" name="r" value="reports">
  <div class="row g-2">
    <div class="col-md-4">
      <label class="form-label">Jornada</label>
      <select name="jornada_id" class="form-select">
        <option value="">Todas</option>
        <?php foreach ($jornadas as $j): ?>
        <option value="<?= (int)$j['id'] ?>" <?= (isset($_GET['jornada_id']) && $_GET['jornada_id']==$j['id'])?'selected':'' ?>><?= h($j['name'].' ('.$j['fecha'].')') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Misión</label>
      <select name="mission_id" class="form-select">
        <option value="">Todas</option>
        <?php foreach ($missions as $m): ?>
        <option value="<?= (int)$m['id'] ?>" <?= (isset($_GET['mission_id']) && $_GET['mission_id']==$m['id'])?'selected':'' ?>><?= h($m['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2 d-flex align-items-end gap-2">
      <button class="btn btn-primary flex-grow-1">Filtrar</button>
    </div>
    <div class="col-md-2 d-flex align-items-end">
      <?php
        $pdfParams = [];
        if (!empty($_GET['jornada_id'])) $pdfParams['jornada_id'] = (int)$_GET['jornada_id'];
        if (!empty($_GET['mission_id'])) $pdfParams['mission_id'] = (int)$_GET['mission_id'];
      ?>
      <a class="btn btn-outline-danger w-100" href="<?= url('reports_pdf', $pdfParams) ?>">Exportar PDF</a>
    </div>
  </div>
</form>

<h5>Por misión / jornada</h5>
<div class="table-responsive">
<table class="table table-striped table-sm">
 <thead><tr><th>Jornada</th><th>Fecha</th><th>Misión</th><th>Asignadas</th><th>Entregadas</th><th>Pagadas</th><th>Pendientes</th><th>Monto pagado</th></tr></thead>
 <tbody>
 <?php foreach ($porMision as $r):
   $pend = (int)$r['entregadas'] - (int)$r['pagadas'];
   $price = 1.0; // not precise per row
 ?>
 <tr>
   <td><?= h($r['jornada_name']) ?></td>
   <td><?= h($r['fecha']) ?></td>
   <td><?= h($r['mission_name']) ?></td>
   <td><?= (int)$r['hallacas_asignadas'] ?></td>
   <td><?= (int)$r['entregadas'] ?></td>
   <td><?= (int)$r['pagadas'] ?></td>
   <td><?= $pend ?></td>
   <td><?= money($r['monto_pagado']) ?></td>
 </tr>
 <?php endforeach; ?>
 </tbody>
 <tfoot class="table-light fw-bold">
 <?php
   $t_asig = $t_ent = $t_pag = $t_pend = 0; $t_monto = 0;
   foreach ($porMision as $r) { $t_asig += (int)$r['hallacas_asignadas']; $t_ent += (int)$r['entregadas']; $t_pag += (int)$r['pagadas']; $t_pend += (int)$r['entregadas']-(int)$r['pagadas']; $t_monto += (float)$r['monto_pagado']; }
 ?>
 <tr>
   <td colspan="3">Totales</td>
   <td><?= $t_asig ?></td>
   <td><?= $t_ent ?></td>
   <td><?= $t_pag ?></td>
   <td><?= $t_pend ?></td>
   <td><?= money($t_monto) ?></td>
 </tr>
 </tfoot>
</table>
</div>

<h5 class="mt-4">Por miembro</h5>
<div class="table-responsive">
<table class="table table-striped table-sm">
 <thead><tr><th>Miembro</th><th>Misión</th><th>Jornadas</th><th>Entregadas</th><th>Pagadas</th><th>Pendientes</th><th>Monto pagado</th></tr></thead>
 <tbody>
 <?php foreach ($porMiembro as $r): ?>
 <tr>
   <td><?= h($r['member_name']) ?></td>
   <td><?= h($r['mission_name']) ?></td>
   <td><?= (int)$r['jornadas'] ?></td>
   <td><?= (int)$r['entregadas'] ?></td>
   <td><?= (int)$r['pagadas'] ?></td>
   <td><?= (int)$r['entregadas']-(int)$r['pagadas'] ?></td>
   <td><?= money($r['monto_pagado']) ?></td>
 </tr>
 <?php endforeach; ?>
 </tbody>
 <tfoot class="table-light fw-bold">
 <?php
   $t_jor = $t_ent2 = $t_pag2 = $t_pend2 = 0; $t_monto2 = 0;
   foreach ($porMiembro as $r) { $t_jor += (int)$r['jornadas']; $t_ent2 += (int)$r['entregadas']; $t_pag2 += (int)$r['pagadas']; $t_pend2 += (int)$r['entregadas']-(int)$r['pagadas']; $t_monto2 += (float)$r['monto_pagado']; }
 ?>
 <tr>
   <td colspan="2">Totales</td>
   <td><?= $t_jor ?></td>
   <td><?= $t_ent2 ?></td>
   <td><?= $t_pag2 ?></td>
   <td><?= $t_pend2 ?></td>
   <td><?= money($t_monto2) ?></td>
 </tr>
 </tfoot>
</table>
</div>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
