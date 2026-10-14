<?php include BASE_PATH . '/app/views/partials/header.php';
$u = $user ?? null;
$isEdit = (bool)$u;
?>
<h2><?= $isEdit ? 'Editar usuario' : 'Nuevo usuario' ?></h2>
<form method="post" action="<?= url($isEdit ? 'user_update' : 'user_store') ?>" class="card p-3 shadow-sm">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><?php endif; ?>
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Nombre</label>
      <input name="name" class="form-control" required value="<?= h($u['name'] ?? '') ?>">
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Correo</label>
      <input type="email" name="email" class="form-control" required value="<?= h($u['email'] ?? '') ?>">
    </div>
  </div>
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Contraseña <?= $isEdit ? '(dejar vacío para no cambiar)' : '' ?></label>
      <input type="password" name="password" class="form-control" <?= $isEdit ? '' : 'required' ?>>
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Rol</label>
      <select name="role" class="form-select">
        <?php foreach (['admin','coordinator','member'] as $r): ?>
        <option value="<?= $r ?>" <?= (($u['role'] ?? 'member')===$r)?'selected':'' ?>><?= $r ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Misión</label>
      <select name="mission_id" class="form-select">
        <option value="">-- Ninguna --</option>
        <?php foreach ($missions as $m): ?>
        <option value="<?= (int)$m['id'] ?>" <?= (($u['mission_id'] ?? '')==$m['id'])?'selected':'' ?>><?= h($m['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3 mb-3">
      <label class="form-label">Teléfono</label>
      <input name="phone" class="form-control" value="<?= h($u['phone'] ?? '') ?>">
    </div>
    <div class="col-md-3 mb-3">
      <label class="form-label">Estado</label>
      <select name="status" class="form-select">
        <option value="active" <?= (($u['status'] ?? 'active')==='active')?'selected':'' ?>>Activo</option>
        <option value="inactive" <?= (($u['status'] ?? '')==='inactive')?'selected':'' ?>>Inactivo</option>
      </select>
    </div>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-primary"><?= $isEdit ? 'Guardar' : 'Crear' ?></button>
    <a class="btn btn-secondary" href="<?= url('users') ?>">Cancelar</a>
  </div>
</form>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
