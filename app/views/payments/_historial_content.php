<?php
/** @var array<int,array<string,mixed>> $recientes */
/** @var array<string,mixed> $historialPagination */
$paginationRoute = $historialPagination['route'] ?? ($pagination['route'] ?? 'payments');
$histPreserve = $historialPagination['filters'] ?? [];
?>
<?php if (empty($recientes)): ?>
<div class="alert alert-secondary mb-0">Sin pagos que coincidan con el filtro.</div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-striped table-sm">
 <thead><tr><th>Fecha</th><?php if (!$hideJornadaCol): ?><th>Jornada</th><?php endif; ?><th>Misión</th><th>Miembro</th><th>Hallacas</th><th>Monto</th><th>Moneda</th><th>Monto (USD)</th><th>Método</th><th>Referencia</th><th>Estado</th><th>Registrado por</th><th>Confirmado por</th><th>Notas</th><th></th></tr></thead>
 <tbody>
 <?php foreach ($recientes as $p): ?>
 <tr>
   <td><?= h($p['fecha']) ?></td>
   <?php if (!$hideJornadaCol): ?><td><?= h($p['jornada_name']) ?></td><?php endif; ?>
   <td><?= h($p['mission_name']) ?></td>
   <td><?= h($p['member_name']) ?></td>
   <td><?= (int)$p['hallacas'] ?></td>
   <td><?= money($p['monto']) ?></td>
   <td><?= h($p['moneda']) ?></td>
   <td class="text-end"><?php
     $usd = Pago::montoUsd($p);
     if (($p['moneda'] ?? '') === 'USD') {
         echo '<strong>' . money($p['monto']) . '</strong>';
     } elseif ($usd !== null) {
         echo '<span class="text-muted" title="Estimado con tasa">' . money($usd) . '</span>';
     } else {
         echo '—';
     }
   ?></td>
   <td><?= h($p['metodo']) ?></td>
   <td><?= h($p['referencia'] ?? '—') ?></td>
   <td><?= status_badge($p['status']) ?></td>
   <td><?= h($p['creado_por'] ?? '-') ?></td>
   <td><?= (($p['status'] ?? '') === 'confirmado') ? h($p['confirmado_por_name'] ?? '-') : '-' ?></td>
   <td><?= h($p['notes']) ?></td>
   <td class="text-nowrap">
     <a class="btn btn-sm btn-outline-secondary" href="<?= url('payment_show', ['id' => (int)$p['id']]) ?>">Ver</a>
     <?php if (PaymentController::canEditPayment($p)): ?>
     <a class="btn btn-sm btn-outline-primary" href="<?= url('payment_edit', array_filter(['id' => (int)$p['id'], 'jornada_id' => $returnJornadaId ?: null])) ?>">Corregir</a>
     <?php endif; ?>
     <?php if (Auth::can('pagos', 'delete') && (($p['status'] ?? '') === 'pendiente' || Auth::isAdmin())): ?>
     <form method="post" action="<?= url('payment_delete') ?>" class="d-inline" onsubmit="return confirm('¿Eliminar este pago?');">
       <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
       <input type="hidden" name="pago_id" value="<?= (int)$p['id'] ?>">
       <?php if ($returnJornadaId): ?><input type="hidden" name="return_jornada_id" value="<?= (int)$returnJornadaId ?>"><?php endif; ?>
       <button class="btn btn-sm btn-outline-danger">Eliminar</button>
     </form>
     <?php endif; ?>
   </td>
 </tr>
 <?php endforeach; ?>
 </tbody>
</table>
</div>
<?php
$histPagPreserve = pagination_preserve($historialPagination['preserveParams'] ?? ['pr'], 'pr');
?>
<?= pagination_render(
    (int)$historialPagination['total'],
    (int)$historialPagination['page'],
    (int)$historialPagination['per'],
    $paginationRoute,
    $historialPagination['param'] ?? 'pr',
    array_merge($histPreserve, $histPagPreserve)
) ?>
<?php endif; ?>
