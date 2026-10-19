<?php include BASE_PATH . '/app/views/partials/header.php';
$product = $product ?? null;
$isEdit = (bool)$product;
page_back('inventory', 'Volver al inventario');
?>
<h2><?= $isEdit ? 'Editar producto' : 'Nuevo producto' ?></h2>
<form method="post" action="<?= url($isEdit ? 'inventory_product_update' : 'inventory_product_store') ?>" class="card p-3 shadow-sm">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$product['id'] ?>"><?php endif; ?>
  <div class="mb-3">
    <label class="form-label">Producto</label>
    <input name="name" class="form-control" required value="<?= h($product['name'] ?? '') ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">SKU / Código</label>
    <input name="sku" class="form-control" required <?= $isEdit ? 'readonly' : '' ?> value="<?= h($product['sku'] ?? '') ?>">
    <?php if ($isEdit): ?><div class="form-text">El SKU no se puede cambiar.</div><?php endif; ?>
  </div>
  <div class="mb-3">
    <label class="form-label">Unidad métrica</label>
    <input name="unidad_metrica" class="form-control" required placeholder="kg, und, litro..." value="<?= h($product['unidad_metrica'] ?? '') ?>">
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-primary"><?= $isEdit ? 'Guardar' : 'Crear' ?></button>
    <a class="btn btn-outline-secondary" href="<?= url('inventory') ?>">Volver</a>
  </div>
</form>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
