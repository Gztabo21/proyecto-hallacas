<?php include BASE_PATH . '/app/views/partials/header.php';
page_back('payments', 'Volver a pagos');
$p = $pago;
$isUsd = ($p['moneda'] ?? '') === 'USD';
?>
<h2>Detalle del pago #<?= (int)$p['id'] ?></h2>
<div class="row g-3">
  <div class="col-lg-7">
    <div class="card p-3 shadow-sm">
      <dl class="row mb-0 small">
        <dt class="col-sm-4">Fecha</dt><dd class="col-sm-8"><?= h($p['fecha']) ?></dd>
        <dt class="col-sm-4">Estado</dt><dd class="col-sm-8"><?= status_badge($p['status']) ?></dd>
        <dt class="col-sm-4">Jornada</dt><dd class="col-sm-8"><?= h($p['jornada_name']) ?> (<?= h($p['jornada_fecha'] ?? '') ?>)</dd>
        <dt class="col-sm-4">Misión</dt><dd class="col-sm-8"><?= h($p['mission_name']) ?></dd>
        <dt class="col-sm-4">Miembro</dt><dd class="col-sm-8"><?= h($p['member_name']) ?></dd>
        <dt class="col-sm-4">Hallacas pagadas</dt><dd class="col-sm-8"><?= (int)$p['hallacas'] ?></dd>
        <dt class="col-sm-4">Monto</dt><dd class="col-sm-8"><strong><?= money($p['monto']) ?></strong> <?= h($p['moneda']) ?></dd>
        <?php if ($isUsd): ?>
        <dt class="col-sm-4">Monto (USD)</dt><dd class="col-sm-8"><strong><?= money($p['monto']) ?></strong></dd>
        <?php elseif ($montoUsd !== null): ?>
        <dt class="col-sm-4">Equivalente (USD)</dt><dd class="col-sm-8"><?= money($montoUsd) ?> <span class="text-muted">(tasa <?= h((string)$p['tasa']) ?>)</span></dd>
        <?php endif; ?>
        <dt class="col-sm-4">Método</dt><dd class="col-sm-8"><?= h($p['metodo'] ?? '—') ?></dd>
        <dt class="col-sm-4">Referencia</dt><dd class="col-sm-8"><?= h($p['referencia'] ?? '—') ?></dd>
        <?php if (($p['moneda'] ?? '') === 'VES' && !empty($p['tasa'])): ?>
        <dt class="col-sm-4">Tasa (Bs/USD)</dt><dd class="col-sm-8"><?= h((string)$p['tasa']) ?></dd>
        <?php endif; ?>
        <dt class="col-sm-4">Registrado por</dt><dd class="col-sm-8"><?= h($p['creado_por'] ?? '—') ?></dd>
        <dt class="col-sm-4">Confirmado por</dt><dd class="col-sm-8"><?= ($p['status'] ?? '') === 'confirmado' ? h($p['confirmado_por_name'] ?? '—') : '—' ?></dd>
        <?php if (!empty($p['confirmado_at'])): ?>
        <dt class="col-sm-4">Confirmado el</dt><dd class="col-sm-8"><?= h($p['confirmado_at']) ?></dd>
        <?php endif; ?>
        <dt class="col-sm-4">Notas</dt><dd class="col-sm-8"><?= h($p['notes'] ?? '') !== '' ? h($p['notes']) : '—' ?></dd>
      </dl>
      <?php if (PaymentController::canEditPayment($p)): ?>
      <div class="mt-3 pt-2 border-top">
        <a class="btn btn-outline-primary btn-sm" href="<?= url('payment_edit', ['id' => (int)$p['id']]) ?>">Corregir pago</a>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card p-3 shadow-sm">
      <h6 class="mb-2">Capture de pago</h6>
      <?php if (!empty($p['captura'])): ?>
      <div class="mb-2 text-center">
        <img src="<?= url('payment_captura', ['id' => (int)$p['id']]) ?>" alt="Capture del pago" class="img-fluid rounded border" style="max-height:320px">
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-sm btn-outline-primary" href="<?= url('payment_captura', ['id' => (int)$p['id']]) ?>" target="_blank" rel="noopener">Ver en pantalla completa</a>
        <a class="btn btn-sm btn-primary" href="<?= url('payment_captura', ['id' => (int)$p['id'], 'download' => 1]) ?>">Descargar captura</a>
      </div>
      <?php else: ?>
      <p class="text-muted mb-0">No hay imagen de capture para este pago.</p>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
