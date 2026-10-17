<?php include BASE_PATH . '/app/views/partials/header.php';
$member = $member ?? null;
$isEdit = (bool)$member;
$isAdmin = Auth::isAdmin();
page_back('members', 'Volver a miembros');
?>
<h2><?= $isEdit ? 'Editar miembro' : 'Nuevo miembro' ?></h2>
<form method="post" action="<?= url($isEdit ? 'member_update' : 'member_store') ?>" class="card p-3 shadow-sm">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$member['id'] ?>"><?php endif; ?>

  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Nombre</label>
      <input name="name" class="form-control" required value="<?= h($member['name'] ?? '') ?>">
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Correo</label>
      <input type="email" name="email" class="form-control" required value="<?= h($member['email'] ?? '') ?>">
    </div>
  </div>
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Contraseña <?= $isEdit ? '(dejar vacío para no cambiar)' : '' ?></label>
      <input type="password" name="password" class="form-control" autocomplete="new-password" <?= $isEdit ? '' : 'required' ?>>
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Teléfono</label>
      <input name="phone" class="form-control" value="<?= h($member['phone'] ?? '') ?>">
    </div>
  </div>
  <div class="row">
    <?php if ($isAdmin): ?>
    <div class="col-md-6 mb-3">
      <label class="form-label">Misión</label>
      <select name="mission_id" class="form-select" required>
        <option value="">-- Seleccionar --</option>
        <?php foreach ($missions as $m): if (($m['status'] ?? 'active') !== 'active') continue; ?>
        <option value="<?= (int)$m['id'] ?>" <?= ((int)($member['mission_id'] ?? 0) === (int)$m['id']) ? 'selected' : '' ?>><?= h($m['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="col-md-6 mb-3">
      <label class="form-label">Estado</label>
      <select name="status" class="form-select">
        <option value="active" <?= (($member['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Activo</option>
        <option value="inactive" <?= (($member['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactivo</option>
      </select>
    </div>
  </div>
  <?php if (!$isAdmin && !$isEdit): ?>
  <p class="text-muted small mb-0">El miembro quedará asignado a su misión.</p>
  <?php endif; ?>

  <div class="d-flex flex-column flex-sm-row gap-2 mt-2">
    <button class="btn btn-primary"><?= $isEdit ? 'Guardar cambios' : 'Crear miembro' ?></button>
    <a class="btn btn-outline-secondary" href="<?= url('members') ?>">Volver</a>
  </div>
</form>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
