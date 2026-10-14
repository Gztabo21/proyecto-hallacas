<?php include BASE_PATH . '/app/views/partials/header.php'; ?>
<div class="row justify-content-center mt-5">
 <div class="col-md-4">
   <div class="card shadow">
     <div class="card-body">
       <h4 class="card-title text-center mb-3">Iniciar sesión</h4>
       <form method="post" action="<?= url('login') ?>">
         <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
         <div class="mb-3">
           <label class="form-label">Correo</label>
           <input type="email" name="email" class="form-control" required>
         </div>
         <div class="mb-3">
           <label class="form-label">Contraseña</label>
           <input type="password" name="password" class="form-control" required>
         </div>
         <button class="btn btn-primary w-100">Entrar</button>
       </form>
     </div>
   </div>
 </div>
</div>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
