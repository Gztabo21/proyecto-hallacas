<?php include BASE_PATH . '/app/views/partials/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
 <h2>Misiones</h2>
 <a class="btn btn-primary" href="<?= url('mission_create') ?>">Nueva misión</a>
</div>
<div class="table-responsive">
<table class="table table-striped table-hover">
 <thead><tr><th>ID</th><th>Nombre</th><th>Código</th><th>Coordinador</th><th>Estado</th><th>Acciones</th></tr></thead>
 <tbody>
 <?php foreach ($missions as $m): ?>
 <tr>
   <td><?= (int)$m['id'] ?></td>
   <td><?= h($m['name']) ?></td>
   <td><?= h($m['code']) ?></td>
   <td><?= h($m['coordinator_name'] ?? '-') ?></td>
   <td><?= status_badge($m['status']) ?></td>
   <td>
     <a class="btn btn-sm btn-outline-primary" href="<?= url('mission_edit',['id'=>$m['id']]) ?>">Editar</a>
     <form method="post" action="<?= url('mission_delete') ?>" class="d-inline" onsubmit="return confirm('Eliminar misión?')">
       <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
       <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
       <button class="btn btn-sm btn-outline-danger">Eliminar</button>
     </form>
   </td>
 </tr>
 <?php endforeach; ?>
 </tbody>
</table>
</div>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
