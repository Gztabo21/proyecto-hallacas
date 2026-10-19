<?php include BASE_PATH . '/app/views/partials/header.php';
page_back('inventory', 'Volver al inventario');
?>
<h2>Stock inicial</h2>
<p class="text-muted">Carga manual del inventario existente antes de registrar compras.</p>
<form method="post" action="<?= url('inventory_initial_store') ?>" class="card p-3 shadow-sm">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Producto</label>
      <select name="product_id" class="form-select" required>
        <option value="">Seleccionar</option>
        <?php foreach ($products as $p): ?>
        <option value="<?= (int)$p['id'] ?>"><?= h($p['name']) ?> (<?= h($p['sku']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3 mb-3">
      <label class="form-label">Cantidad</label>
      <input type="number" step="0.001" min="0.001" name="cantidad" class="form-control" required>
    </div>
    <div class="col-md-3 mb-3">
      <label class="form-label">Fecha de ingreso</label>
      <input type="date" name="fecha_ingreso" class="form-control" required value="<?= date('Y-m-d') ?>">
    </div>
  </div>
  <div class="row">
    <div class="col-md-4 mb-3">
      <label class="form-label">Precio (Bs)</label>
      <input type="number" step="0.0001" min="0" name="precio_bs" class="form-control" required>
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label">Tasa del día</label>
      <input type="number" step="0.0001" min="0.0001" name="tasa" class="form-control" required placeholder="Bs por USD">
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label">Notas</label>
      <input name="notes" class="form-control" placeholder="opcional">
    </div>
  </div>
  <p class="small text-muted mb-3">El precio en USD se calcula automáticamente: precio Bs ÷ tasa.</p>
  <div class="d-flex gap-2">
    <button class="btn btn-primary">Registrar stock inicial</button>
    <a class="btn btn-outline-secondary" href="<?= url('inventory') ?>">Volver</a>
  </div>
</form>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
