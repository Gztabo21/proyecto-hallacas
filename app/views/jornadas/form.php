<?php include BASE_PATH . '/app/views/partials/header.php';
$j = $jornada ?? null;
$isEdit = (bool)$j;
if ($isEdit) {
    page_back('jornada_show', 'Volver a la jornada', ['id' => (int)$j['id']]);
} else {
    page_back('jornadas', 'Volver a jornadas');
}
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
    <label class="form-label">Precio por hallaca (USD)</label>
    <input type="number" step="0.01" name="precio_hallaca" class="form-control" value="<?= h($j['precio_hallaca'] ?? setting('precio_hallaca', DEFAULT_PRICE)) ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Total hallacas hechas (producción del día)</label>
    <input type="number" name="total_hallacas_hechas" class="form-control" min="0" placeholder="Opcional"
           value="<?= isset($j['total_hallacas_hechas']) && $j['total_hallacas_hechas'] !== null && $j['total_hallacas_hechas'] !== '' ? (int)$j['total_hallacas_hechas'] : '' ?>">
    <div class="form-text">Cantidad total producida en la jornada; se compara con lo asignado a misiones.</div>
  </div>
  <div class="mb-3">
    <label class="form-label">Notas</label>
    <textarea name="notes" class="form-control"><?= h($j['notes'] ?? '') ?></textarea>
  </div>
  <div class="d-flex flex-column flex-sm-row gap-2">
    <button class="btn btn-primary"><?= $isEdit ? 'Guardar' : 'Crear' ?></button>
    <a class="btn btn-outline-secondary" href="<?= url($isEdit ? 'jornada_show' : 'jornadas', $isEdit ? ['id' => (int)$j['id']] : []) ?>">Volver</a>
  </div>
</form>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
