<?php include BASE_PATH . '/app/views/partials/header.php';
$j = $jornada ?? null;
$isEdit = (bool)$j;
?>
<h2><?= $isEdit ? 'Editar jornada' : 'Nueva jornada' ?></h2>
<form method="post" action="<?= url($isEdit ? 'jornada_update' : 'jornada_store') ?>" class="card p-3 shadow-sm">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$j['id'] ?>"><?php endif; ?>
  <div class="mb-3">
    <label class="form-label">Nombre</label>
    <input name="name" class="form-control" required value="<?= h($j['name'] ?? '') ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Fecha</label>
    <input type="date" name="fecha" class="form-control" required value="<?= h($j['fecha'] ?? date('Y-m-d')) ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Precio por hallaca</label>
    <input type="number" step="0.01" name="precio_hallaca" class="form-control" value="<?= h($j['precio_hallaca'] ?? setting('precio_hallaca', DEFAULT_PRICE)) ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Notas</label>
    <textarea name="notes" class="form-control"><?= h($j['notes'] ?? '') ?></textarea>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-primary"><?= $isEdit ? 'Guardar' : 'Crear' ?></button>
    <a class="btn btn-secondary" href="<?= url('jornadas') ?>">Cancelar</a>
  </div>
</form>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
