<?php include BASE_PATH . '/app/views/partials/header.php';
page_back('dashboard', 'Volver al dashboard');
?>
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-2 mb-3">
  <div>
    <h2 class="mb-0">Miembros del equipo</h2>
    <?php if (Auth::role() === 'coordinator'): ?>
    <p class="text-muted small mb-0">Miembros de su misión</p>
    <?php endif; ?>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <a class="btn btn-outline-secondary" href="<?= url('member_import') ?>">Importar CSV</a>
    <a class="btn btn-primary" href="<?= url('member_create') ?>">Nuevo miembro</a>
  </div>
</div>
<div class="table-responsive">
<table class="table table-striped table-hover table-sm">
  <thead>
    <tr><th>Nombre</th><th>Correo</th><th>Teléfono</th><?php if (Auth::isAdmin()): ?><th>Misión</th><?php endif; ?><th>Estado</th><th>Acciones</th></tr>
  </thead>
  <tbody>
  <?php if (empty($members)): ?>
  <tr><td colspan="<?= Auth::isAdmin() ? 6 : 5 ?>" class="text-muted text-center py-4">No hay miembros registrados</td></tr>
  <?php else: foreach ($members as $m): ?>
  <tr>
    <td><?= h($m['name']) ?></td>
    <td><?= h($m['email']) ?></td>
    <td><?= h($m['phone'] ?? '-') ?></td>
    <?php if (Auth::isAdmin()): ?><td><?= h($m['mission_name'] ?? '-') ?></td><?php endif; ?>
    <td><?= status_badge($m['status']) ?></td>
    <td>
      <a class="btn btn-sm btn-outline-primary" href="<?= url('member_edit', ['id' => (int)$m['id']]) ?>">Editar</a>
    </td>
  </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table>
</div>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
