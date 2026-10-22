<?php include BASE_PATH . '/app/views/partials/header.php';
page_back('dashboard', 'Volver al dashboard');
?>
<h2>Permisos por rol</h2>
<p class="text-muted">Defina quién puede <strong>crear</strong>, <strong>editar</strong> y <strong>eliminar</strong> en cada módulo. El rol <strong>Administrador</strong> siempre tiene acceso total.</p>
<?php if (!RolePermission::tableExists()): ?>
<div class="alert alert-warning">Ejecute en MySQL: <code>sql/010_role_permissions.sql</code></div>
<?php endif; ?>
<form method="post" action="<?= url('role_permissions_update') ?>" class="card p-3 shadow-sm">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <div class="table-responsive">
  <table class="table table-sm table-bordered align-middle">
    <thead class="table-light">
      <tr>
        <th>Rol</th>
        <?php foreach ($resources as $resLabel): ?>
        <th class="text-center" colspan="3"><?= h($resLabel) ?></th>
        <?php endforeach; ?>
      </tr>
      <tr>
        <th></th>
        <?php foreach ($resources as $_): ?>
        <th class="text-center small">Crear</th>
        <th class="text-center small">Editar</th>
        <th class="text-center small">Eliminar</th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($roles as $roleSlug => $roleLabel):
      if ($roleSlug === 'admin') continue;
      if ($roleSlug === 'member') continue;
    ?>
    <tr>
      <td><strong><?= h($roleLabel) ?></strong><br><code class="small"><?= h($roleSlug) ?></code></td>
      <?php foreach ($resources as $resSlug => $_resLabel):
        $p = $matrix[$roleSlug][$resSlug] ?? ['can_create' => false, 'can_edit' => false, 'can_delete' => false];
      ?>
      <td class="text-center">
        <input type="checkbox" name="perm[<?= h($roleSlug) ?>][<?= h($resSlug) ?>][create]" value="1" <?= !empty($p['can_create']) ? 'checked' : '' ?>>
      </td>
      <td class="text-center">
        <input type="checkbox" name="perm[<?= h($roleSlug) ?>][<?= h($resSlug) ?>][edit]" value="1" <?= !empty($p['can_edit']) ? 'checked' : '' ?>>
      </td>
      <td class="text-center">
        <input type="checkbox" name="perm[<?= h($roleSlug) ?>][<?= h($resSlug) ?>][delete]" value="1" <?= !empty($p['can_delete']) ? 'checked' : '' ?>>
      </td>
      <?php endforeach; ?>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <button class="btn btn-primary" <?= RolePermission::tableExists() ? '' : 'disabled' ?>>Guardar permisos</button>
</form>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
