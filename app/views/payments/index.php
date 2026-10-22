<?php include BASE_PATH . '/app/views/partials/header.php';
page_back('dashboard', 'Volver al dashboard');
?>
<h2>Registro de pagos</h2>
<p class="text-muted">Abono por hallacas pendientes de cancelar.</p>
<?php if (Auth::isAdmin()): ?>
<div class="mb-3">
  <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#methodModal">Gestionar métodos de pago</button>
  <div class="small text-muted mt-2">Métodos actuales: <?= h(implode(', ', array_column(payment_methods(), 'name'))) ?></div>
</div>
<div class="modal fade" id="methodModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="post" action="<?= url('payment_method_store') ?>" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Agregar método de pago</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="mb-3">
          <label class="form-label">Nombre del método</label>
          <input name="new_payment_method" class="form-control" placeholder="Ej. Zelle, Efectivo euros" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Moneda</label>
          <select name="new_payment_method_currency" class="form-select" required>
            <option value="VES">VES (bolívares)</option>
            <option value="USD">USD (dólares)</option>
          </select>
        </div>
        <div class="small text-muted">Métodos existentes: <?= h(implode(', ', array_column(payment_methods(), 'name'))) ?></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-primary">Agregar método</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<form method="get" action="<?= url('payments') ?>" class="card p-3 mb-3">
  <input type="hidden" name="r" value="payments">
  <div class="row g-2">
    <div class="col-md-4">
      <label class="form-label">Jornada</label>
      <select name="jornada_id" class="form-select">
        <option value="">Todas</option>
        <?php foreach ($jornadas as $j): ?>
        <option value="<?= (int)$j['id'] ?>" <?= (isset($_GET['jornada_id']) && $_GET['jornada_id']==$j['id'])?'selected':'' ?>><?= h($j['name'].' ('.$j['fecha'].')') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if (Auth::isAdmin()): ?>
    <div class="col-md-4">
      <label class="form-label">Misión</label>
      <select name="mission_id" class="form-select">
        <option value="">Todas</option>
        <?php foreach ($missions as $m): ?>
        <option value="<?= (int)$m['id'] ?>" <?= (isset($_GET['mission_id']) && $_GET['mission_id']==$m['id'])?'selected':'' ?>><?= h($m['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php else: ?>
    <input type="hidden" name="mission_id" value="<?= (int)Auth::missionId() ?>">
    <?php endif; ?>
    <div class="col-md-2 d-flex align-items-end">
      <button class="btn btn-primary w-100">Filtrar</button>
    </div>
  </div>
</form>

<?php include BASE_PATH . '/app/views/payments/_panel.php'; ?>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
