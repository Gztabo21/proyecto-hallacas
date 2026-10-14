<?php include BASE_PATH . '/app/views/partials/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
 <h2>Jornadas</h2>
 <?php if (Auth::isCoordinator()): ?>
 <a class="btn btn-primary" href="<?= url('jornada_create') ?>">Nueva jornada</a>
 <?php endif; ?>
</div>
<div class="table-responsive">
<table class="table table-striped table-hover">
 <thead><tr><th>ID</th><th>Nombre</th><th>Fecha</th><th>Precio</th><th>Estado</th><th>Acciones</th></tr></thead>
 <tbody>
 <?php foreach ($jornadas as $j): ?>
 <tr>
   <td><?= (int)$j['id'] ?></td>
   <td><?= h($j['name']) ?></td>
   <td><?= h($j['fecha']) ?></td>
   <td><?= money($j['precio_hallaca']) ?></td>
   <td><?= status_badge($j['status']) ?></td>
   <td>
     <a class="btn btn-sm btn-outline-primary" href="<?= url('jornada_show',['id'=>$j['id']]) ?>">Ver</a>
     <?php if (Auth::isAdmin()): ?>
       <a class="btn btn-sm btn-outline-secondary" href="<?= url('jornada_edit',['id'=>$j['id']]) ?>">Editar</a>
     <?php endif; ?>
     <?php if ($j['status']==='open'): ?>
       <form method="post" action="<?= url('jornada_close') ?>" class="d-inline">
         <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
         <input type="hidden" name="id" value="<?= (int)$j['id'] ?>">
         <button class="btn btn-sm btn-warning">Cerrar</button>
       </form>
     <?php else: ?>
       <?php if (Auth::isAdmin()): ?>
       <form method="post" action="<?= url('jornada_reopen') ?>" class="d-inline">
         <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
         <input type="hidden" name="id" value="<?= (int)$j['id'] ?>">
         <button class="btn btn-sm btn-outline-warning">Reabrir</button>
       </form>
       <?php endif; ?>
     <?php endif; ?>
     <?php if (Auth::isAdmin()): ?>
     <form method="post" action="<?= url('jornada_delete') ?>" class="d-inline" onsubmit="return confirm('Eliminar jornada?')">
       <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
       <input type="hidden" name="id" value="<?= (int)$j['id'] ?>">
       <button class="btn btn-sm btn-outline-danger">Eliminar</button>
     </form>
     <?php endif; ?>
   </td>
 </tr>
 <?php endforeach; ?>
 </tbody>
</table>
</div>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
