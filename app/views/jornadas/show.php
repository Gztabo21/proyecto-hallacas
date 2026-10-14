<?php include BASE_PATH . '/app/views/partials/header.php';
$precio = price_for_jornada($jornada);
$entregasByMember = [];
foreach ($entregas as $e) $entregasByMember[$e['member_id']] = $e;

$missionById = [];
foreach (Mission::all() as $m) $missionById[$m['id']] = $m;
?>
<h2><?= h($jornada['name']) ?> <small class="text-muted">(<?= h($jornada['fecha']) ?>)</small> <?= status_badge($jornada['status']) ?></h2>
<p>Precio por hallaca: <strong><?= money($precio) ?></strong> <?= $jornada['notes'] ? ' - '.h($jornada['notes']) : '' ?></p>

<?php if (Auth::isAdmin() || Auth::isCoordinator()): ?>
<div class="card mb-3">
 <div class="card-header">Asignar misión a la jornada</div>
 <div class="card-body">
  <form method="post" action="<?= url('jornada_asignar') ?>" class="row g-2 align-items-end">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="jornada_id" value="<?= (int)$jornada['id'] ?>">
    <div class="col-md-4">
      <label class="form-label">Misión</label>
      <select name="mission_id" class="form-select" required>
        <?php foreach ($allMissions as $m): if($m['status']!=='active') continue; ?>
        <?php if (!Auth::isAdmin() && Auth::missionId() !== (int)$m['id']) continue; ?>
        <option value="<?= (int)$m['id'] ?>"><?= h($m['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Hallacas asignadas</label>
      <input type="number" name="hallacas_asignadas" class="form-control" min="0" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Notas</label>
      <input name="notes" class="form-control">
    </div>
    <div class="col-md-2">
      <button class="btn btn-primary w-100">Asignar</button>
    </div>
  </form>
 </div>
</div>
<?php endif; ?>

<?php if (empty($assigned)): ?>
<div class="alert alert-info">No hay misiones asignadas a esta jornada.</div>
<?php endif; ?>

<?php foreach ($assigned as $jm):
  $mid = (int)$jm['mission_id'];
  if (!Auth::isAdmin() && Auth::missionId() !== $mid) continue;
  $members = $membersByMission[$mid] ?? User::membersOfMission($mid);
  // delivered totals
  $deliv = DB::query('SELECT COALESCE(SUM(hallacas_entregadas),0) s, COALESCE(SUM(hallacas_pagadas),0) p, COALESCE(SUM(monto_pagado),0) mp FROM entregas WHERE jornada_id=? AND mission_id=?', [(int)$jornada['id'],$mid])->fetch();
  $remaining = (int)$jm['hallacas_asignadas'] - (int)$deliv['s'];
?>
<div class="card mb-3">
 <div class="card-header d-flex justify-content-between">
   <strong><?= h($jm['mission_name']) ?></strong>
   <span>Asignadas: <?= (int)$jm['hallacas_asignadas'] ?> | Entregadas: <?= (int)$deliv['s'] ?> | Pagadas: <?= (int)$deliv['p'] ?> | Pendientes: <?= (int)$deliv['s']-(int)$deliv['p'] ?> | Restantes por distribuir: <strong><?= $remaining ?></strong></span>
 </div>
 <div class="card-body">
   <?php if (Auth::isAdmin() || Auth::isCoordinator()): ?>
   <form method="post" action="<?= url('jornada_quitar') ?>" class="mb-2" onsubmit="return confirm('Quitar misión de la jornada y sus entregas?')">
     <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
     <input type="hidden" name="jornada_id" value="<?= (int)$jornada['id'] ?>">
     <input type="hidden" name="mission_id" value="<?= $mid ?>">
     <button class="btn btn-sm btn-outline-danger">Quitar misión</button>
   </form>
   <?php endif; ?>

   <div class="table-responsive">
   <table class="table table-sm table-bordered">
     <thead><tr><th>Miembro</th><th>Entregadas</th><th>Pagadas</th><th>Pendientes</th><th>Estado</th><th>Monto pagado</th><th>Acciones</th></tr></thead>
     <tbody>
     <?php foreach ($members as $mem):
        $e = $entregasByMember[$mem['id']] ?? null;
     ?>
      <tr>
        <td><?= h($mem['name']) ?></td>
        <?php if ($e): ?>
          <td><?= (int)$e['hallacas_entregadas'] ?></td>
          <td><?= (int)$e['hallacas_pagadas'] ?></td>
          <td><?= (int)$e['hallacas_entregadas']-(int)$e['hallacas_pagadas'] ?></td>
          <td><?= status_badge($e['status']) ?></td>
          <td><?= money($e['monto_pagado']) ?></td>
          <td>
            <?php if (Auth::isAdmin() || Auth::isCoordinator()): ?>
            <!-- Abono -->
            <button class="btn btn-sm btn-success" data-bs-toggle="collapse" data-bs-target="#abono<?= (int)$e['id'] ?>">Abonar</button>
            <form method="post" action="<?= url('entrega_delete') ?>" class="d-inline" onsubmit="return confirm('Eliminar entrega?')">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="entrega_id" value="<?= (int)$e['id'] ?>">
              <button class="btn btn-sm btn-outline-danger">Eliminar</button>
            </form>
            <div class="collapse mt-2" id="abono<?= (int)$e['id'] ?>">
              <form method="post" action="<?= url('entrega_abono') ?>" class="border p-2">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="entrega_id" value="<?= (int)$e['id'] ?>">
                <div class="row g-2">
                  <div class="col"><input type="number" name="hallacas" class="form-control form-control-sm" placeholder="+ hallacas pagadas" min="0" value="0"></div>
                  <div class="col"><input type="number" step="0.01" name="monto" class="form-control form-control-sm" placeholder="monto" value="0"></div>
                  <div class="col"><input name="metodo" class="form-control form-control-sm" placeholder="método"></div>
                  <div class="col"><input name="notes" class="form-control form-control-sm" placeholder="notas"></div>
                  <div class="col-auto"><button class="btn btn-sm btn-primary">Guardar abono</button></div>
                </div>
              </form>
            </div>
            <?php if (!empty($e['notes'])): ?><div class="small text-muted mt-1"><?= h($e['notes']) ?></div><?php endif; ?>
            <?php endif; ?>
          </td>
        <?php else: ?>
          <td>0</td><td>0</td><td>0</td><td><?= status_badge('pendiente') ?></td><td><?= money(0) ?></td>
          <td>
            <?php if (($jornada['status']==='open') && (Auth::isAdmin() || Auth::isCoordinator())): ?>
            <form method="post" action="<?= url('entrega_store') ?>" class="row g-1">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="jornada_id" value="<?= (int)$jornada['id'] ?>">
              <input type="hidden" name="mission_id" value="<?= $mid ?>">
              <input type="hidden" name="member_id" value="<?= (int)$mem['id'] ?>">
              <div class="col"><input type="number" name="hallacas_entregadas" class="form-control form-control-sm" placeholder="cant." min="0" required></div>
              <div class="col"><input type="number" name="hallacas_pagadas" class="form-control form-control-sm" placeholder="pagadas" min="0" value="0"></div>
              <div class="col"><input type="number" step="0.01" name="monto_pagado" class="form-control form-control-sm" placeholder="monto" value="0"></div>
              <div class="col"><input name="notes" class="form-control form-control-sm" placeholder="notas"></div>
              <div class="col-auto"><button class="btn btn-sm btn-primary">Entregar</button></div>
            </form>
            <?php else: ?>
            <span class="text-muted small">Sin entrega</span>
            <?php endif; ?>
          </td>
        <?php endif; ?>
      </tr>
     <?php endforeach; ?>
     </tbody>
   </table>
   </div>
 </div>
</div>
<?php endforeach; ?>

<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
