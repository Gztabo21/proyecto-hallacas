<?php include BASE_PATH . '/app/views/partials/header.php';
$m = $mission ?? null;
$isEdit = (bool)$m;
?>
<h2><?= $isEdit ? 'Editar misión' : 'Nueva misión' ?></h2>
<form method="post" action="<?= url($isEdit ? 'mission_update' : 'mission_store') ?>" class="card p-3 shadow-sm">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><?php endif; ?>
  <div class="mb-3">
    <label class="form-label">Nombre</label>
    <input name="name" class="form-control" required value="<?= h($m['name'] ?? '') ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Código</label>
    <input name="code" class="form-control" required value="<?= h($m['code'] ?? '') ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Descripción</label>
    <textarea name="description" class="form-control"><?= h($m['description'] ?? '') ?></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">Coordinador</label>
    <select name="coordinator_id" class="form-select">
      <option value="">-- Sin asignar --</option>
      <?php foreach ($coordinators as $c): ?>
      <option value="<?= (int)$c['id'] ?>" <?= ($m['coordinator_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?> (<?= h($c['email']) ?>)</option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Estado</label>
    <select name="status" class="form-select">
      <option value="active" <?= (($m['status'] ?? 'active')==='active')?'selected':'' ?>>Activa</option>
      <option value="inactive" <?= (($m['status'] ?? '')==='inactive')?'selected':'' ?>>Inactiva</option>
    </select>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-primary"><?= $isEdit ? 'Guardar' : 'Crear' ?></button>
    <a class="btn btn-secondary" href="<?= url('missions') ?>">Cancelar</a>
  </div>
</form>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
