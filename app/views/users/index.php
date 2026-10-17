<?php include BASE_PATH . '/app/views/partials/header.php'; ?>
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-2 mb-3">
 <h2>Usuarios</h2>
 <a class="btn btn-primary" href="<?= url('user_create') ?>">Nuevo usuario</a>
</div>
<div class="table-responsive">
<table class="table table-striped table-hover">
 <thead><tr><th>ID</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Misión</th><th>Estado</th><th>Acciones</th></tr></thead>
 <tbody>
 <?php foreach ($users as $u): ?>
 <tr>
   <td><?= (int)$u['id'] ?></td>
   <td><?= h($u['name']) ?></td>
   <td><?= h($u['email']) ?></td>
   <td><?= h($u['role']) ?></td>
   <td><?= h($u['mission_name'] ?? '-') ?></td>
   <td><?= status_badge($u['status']) ?></td>
   <td>
     <a class="btn btn-sm btn-outline-primary" href="<?= url('user_edit',['id'=>$u['id']]) ?>">Editar</a>
     <form method="post" action="<?= url('user_delete') ?>" class="d-inline" onsubmit="return confirm('Eliminar usuario?')">
       <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
       <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
       <button class="btn btn-sm btn-outline-danger">Eliminar</button>
     </form>
   </td>
 </tr>
 <?php endforeach; ?>
 </tbody>
</table>
</div>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
