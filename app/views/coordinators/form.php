<?php include BASE_PATH . '/app/views/partials/header.php';
$missions = $missions ?? Mission::all();
page_back('coordinators', 'Volver a coordinadores');
?>
<h2>Agregar coordinador</h2>
<form method="post" action="<?= url('coordinator_store') ?>" class="card p-3 shadow-sm">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Nombre</label>
      <input name="name" class="form-control" required>
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Correo</label>
      <input type="email" name="email" class="form-control" required>
    </div>
  </div>
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Contraseña</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Misión</label>
      <select name="mission_id" class="form-select">
        <option value="">-- Sin asignar --</option>
        <?php foreach ($missions as $m): ?>
        <option value="<?= (int)$m['id'] ?>"><?= h($m['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Teléfono</label>
      <input name="phone" class="form-control">
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Estado</label>
      <select name="status" class="form-select">
        <option value="active">Activo</option>
        <option value="inactive">Inactivo</option>
      </select>
    </div>
  </div>
  <div class="d-flex flex-column flex-sm-row gap-2">
    <button class="btn btn-primary">Crear coordinador</button>
    <a class="btn btn-outline-secondary" href="<?= url('coordinators') ?>">Volver</a>
  </div>
</form>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
