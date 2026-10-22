<?php
$nowLocal = date('Y-m-d\TH:i');
?>
<?php if (empty($produccionTablesReady)): ?>
<div class="alert alert-warning">Falta instalar las tablas de producción. Ejecute <code>sql/012_jornada_produccion.sql</code> en MySQL.</div>
<?php else: ?>
<div class="d-flex flex-wrap align-items-center gap-3 mb-3">
  <span>Total retirado de mesas: <strong><?= (int)$produccionTotalRetirado ?></strong> hallacas</span>
  <?php if ($totalHechas !== null): ?>
  <span class="text-muted small">Total hechas registrado en jornada: <?= (int)$totalHechas ?></span>
  <?php endif; ?>
</div>

<div class="card mb-3">
  <div class="card-header py-2"><h5 class="mb-0">Agregar mesa</h5></div>
  <div class="card-body">
    <form method="post" action="<?= url('jornada_mesa_store') ?>" class="row g-2 align-items-end">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="jornada_id" value="<?= (int)$jornada['id'] ?>">
      <div class="col-md-4">
        <label class="form-label small mb-0">Nombre / número de mesa</label>
        <input name="name" class="form-control form-control-sm" placeholder="Ej. Mesa 1" required>
      </div>
      <div class="col-md-6">
        <label class="form-label small mb-0">Notas (opcional)</label>
        <input name="notes" class="form-control form-control-sm" placeholder="Ubicación, responsable…">
      </div>
      <div class="col-md-2">
        <button class="btn btn-sm btn-primary w-100">Agregar mesa</button>
      </div>
    </form>
  </div>
</div>

<?php if (empty($produccionMesas)): ?>
<div class="alert alert-info">No hay mesas registradas. Agregue al menos una mesa de elaboración.</div>
<?php endif; ?>

<?php foreach ($produccionMesas as $bundle):
  $mesa = $bundle['mesa'];
  $mesaId = (int)$mesa['id'];
  $personas = $bundle['personas'];
  $retiros = $bundle['retiros'];
  $totalMesa = (int)$bundle['total_retirado'];
?>
<div class="card mb-3">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
    <strong><?= h($mesa['name']) ?></strong>
    <span class="small">Retirado de esta mesa: <strong><?= $totalMesa ?></strong> hallacas</span>
  </div>
  <div class="card-body">
    <?php if (!empty($mesa['notes'])): ?><p class="text-muted small"><?= h($mesa['notes']) ?></p><?php endif; ?>

    <h6 class="mb-2">Personas en la mesa</h6>
    <?php if (empty($personas)): ?>
    <p class="text-muted small">Sin personas asignadas.</p>
    <?php else: ?>
    <ul class="list-group list-group-flush mb-3">
      <?php foreach ($personas as $per): ?>
      <li class="list-group-item d-flex justify-content-between align-items-center px-0">
        <span><?= h($per['persona_nombre']) ?><?php if (!empty($per['user_name']) && $per['user_name'] !== $per['persona_nombre']): ?> <small class="text-muted">(<?= h($per['user_name']) ?>)</small><?php endif; ?></span>
        <form method="post" action="<?= url('jornada_mesa_persona_delete') ?>" class="d-inline" onsubmit="return confirm('¿Quitar persona de la mesa?');">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="persona_id" value="<?= (int)$per['id'] ?>">
          <button class="btn btn-sm btn-outline-danger">Quitar</button>
        </form>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <form method="post" action="<?= url('jornada_mesa_persona_store') ?>" class="row g-2 align-items-end mb-4">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="mesa_id" value="<?= $mesaId ?>">
      <div class="col-md-5">
        <label class="form-label small mb-0">Nombre</label>
        <input name="persona_nombre" class="form-control form-control-sm" required placeholder="Nombre en la mesa">
      </div>
      <div class="col-md-5">
        <label class="form-label small mb-0">Vincular usuario (opcional)</label>
        <select name="user_id" class="form-select form-select-sm">
          <option value="">— Ninguno —</option>
          <?php foreach ($produccionUsers as $u): if (($u['status'] ?? '') !== 'active') continue; ?>
          <option value="<?= (int)$u['id'] ?>"><?= h($u['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <button class="btn btn-sm btn-outline-primary w-100">Agregar</button>
      </div>
    </form>

    <h6 class="mb-2">Retiros de hallacas (recogida en mesa)</h6>
    <form method="post" action="<?= url('jornada_mesa_retiro_store') ?>" class="row g-2 align-items-end mb-3 border rounded p-2 bg-light">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="mesa_id" value="<?= $mesaId ?>">
      <div class="col-md-3">
        <label class="form-label small mb-0">Cantidad</label>
        <input type="number" name="cantidad" class="form-control form-control-sm" min="1" required>
      </div>
      <div class="col-md-4">
        <label class="form-label small mb-0">Fecha y hora de retiro</label>
        <input type="datetime-local" name="retirado_at" class="form-control form-control-sm" value="<?= h($nowLocal) ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label small mb-0">Notas</label>
        <input name="notes" class="form-control form-control-sm" placeholder="opcional">
      </div>
      <div class="col-md-2">
        <button class="btn btn-sm btn-success w-100">Registrar retiro</button>
      </div>
    </form>

    <?php if (empty($retiros)): ?>
    <p class="text-muted small mb-0">Aún no hay retiros registrados para esta mesa.</p>
    <?php else: ?>
    <div class="table-responsive">
    <table class="table table-sm table-striped mb-0">
      <thead><tr><th>Fecha y hora</th><th>Cantidad</th><th>Notas</th><th>Registrado por</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($retiros as $r): ?>
      <tr>
        <td><?= h($r['retirado_at']) ?></td>
        <td><strong><?= (int)$r['cantidad'] ?></strong></td>
        <td><?= h($r['notes'] ?? '') ?></td>
        <td><?= h($r['created_by_name'] ?? '—') ?></td>
        <td>
          <form method="post" action="<?= url('jornada_mesa_retiro_delete') ?>" class="d-inline" onsubmit="return confirm('¿Eliminar este retiro?');">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="retiro_id" value="<?= (int)$r['id'] ?>">
            <button class="btn btn-sm btn-outline-danger">Eliminar</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>

    <form method="post" action="<?= url('jornada_mesa_delete') ?>" class="mt-3" onsubmit="return confirm('¿Eliminar mesa y todo su historial?');">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="mesa_id" value="<?= $mesaId ?>">
      <button class="btn btn-sm btn-outline-danger">Eliminar mesa</button>
    </form>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
