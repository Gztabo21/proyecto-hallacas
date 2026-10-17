<?php include BASE_PATH . '/app/views/partials/header.php'; ?>
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-2 mb-3">
 <h2>Coordinadores</h2>
 <a class="btn btn-primary" href="<?= url('coordinator_create') ?>">Agregar coordinador</a>
</div>
<div class="table-responsive">
<table class="table table-striped table-hover">
 <thead><tr><th>ID</th><th>Nombre</th><th>Correo</th><th>Misión</th><th>Estado</th><th>Acciones</th></tr></thead>
 <tbody>
 <?php foreach ($coordinators as $c): ?>
 <tr>
   <td><?= (int)$c['id'] ?></td>
   <td><?= h($c['name']) ?></td>
   <td><?= h($c['email']) ?></td>
   <td><?= h($c['mission_name'] ?? '-') ?></td>
   <td><?= status_badge($c['status']) ?></td>
   <td>
     <a class="btn btn-sm btn-outline-primary" href="<?= url('user_edit',['id'=>$c['id']]) ?>">Editar</a>
   </td>
 </tr>
 <?php endforeach; ?>
 </tbody>
</table>
</div>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
