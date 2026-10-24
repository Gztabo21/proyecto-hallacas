<?php include BASE_PATH . '/app/views/partials/header.php';
page_back('members', 'Volver a miembros');
?>
<h2>Importar miembros (CSV / Excel)</h2>
<div class="card p-3 shadow-sm mb-3">
  <p class="mb-2">Primera fila con encabezados (separador <code>;</code> o <code>,</code>). Guarde desde Excel como <strong>CSV UTF-8</strong>.</p>
  <code>nombre;email;telefono;mision;password;estado</code>
  <ul class="text-muted small mt-2 mb-0">
    <li><strong>nombre</strong>: obligatorio. <strong>email</strong>: opcional (sin correo no ingresa a la plataforma).</li>
    <li><strong>mision</strong>: obligatorio para admin — código (<code>M01</code>), nombre (<code>Misión 1</code>) o número (<code>1</code>). Coordinador: opcional (usa su misión).</li>
    <li><strong>password</strong>: opcional por fila; si está vacío se usa la contraseña por defecto del formulario.</li>
    <li><strong>estado</strong>: <code>active</code> / <code>inactive</code> (opcional).</li>
    <li>Si el correo ya existe como miembro, se actualiza nombre, misión, teléfono y contraseña.</li>
  </ul>
</div>
<form method="post" action="<?= url('member_import_store') ?>" enctype="multipart/form-data" class="card p-3 shadow-sm">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <div class="mb-3">
    <label class="form-label">Archivo CSV</label>
    <input type="file" name="csv" class="form-control" accept=".csv,text/csv" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Contraseña por defecto (filas sin columna password)</label>
    <input type="password" name="default_password" class="form-control" minlength="6" autocomplete="new-password" required>
    <div class="form-text">Mínimo 6 caracteres.</div>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <button class="btn btn-primary">Importar</button>
    <a class="btn btn-outline-secondary" href="<?= url('member_import_template') ?>">Descargar plantilla</a>
  </div>
</form>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
