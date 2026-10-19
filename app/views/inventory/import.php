<?php include BASE_PATH . '/app/views/partials/header.php';
page_back('inventory', 'Volver al inventario');
?>
<h2>Importar productos (CSV)</h2>
<div class="card p-3 shadow-sm mb-3">
  <p class="mb-2">Columnas requeridas en la primera fila:</p>
  <code>sku;producto;unidad_metrica;stock;precio_bs;tasa;fecha_ingreso</code>
  <p class="text-muted small mt-2 mb-0">
    Si el SKU ya existe, se actualiza nombre y unidad. <strong>stock</strong> ajusta existencias (opcional).
    <strong>precio_bs</strong>, <strong>tasa</strong> y <strong>fecha_ingreso</strong> registran el costo unitario (el USD se calcula con la tasa).
    Descargue la <strong>plantilla vacía</strong> o exporte el inventario actual como referencia.
  </p>
</div>
<form method="post" action="<?= url('inventory_import_store') ?>" enctype="multipart/form-data" class="card p-3 shadow-sm">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <div class="mb-3">
    <label class="form-label">Archivo CSV</label>
    <input type="file" name="csv" class="form-control" accept=".csv,text/csv" required>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <button class="btn btn-primary">Importar</button>
    <a class="btn btn-outline-secondary" href="<?= url('inventory_template') ?>">Descargar plantilla vacía</a>
    <a class="btn btn-outline-secondary" href="<?= url('inventory_export') ?>">Exportar inventario actual</a>
  </div>
</form>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
