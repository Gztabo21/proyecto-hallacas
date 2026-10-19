<?php include BASE_PATH . '/app/views/partials/header.php';
$precio = price_for_jornada($jornada);
$entregasByMember = [];
foreach ($entregas as $e) $entregasByMember[$e['member_id']] = $e;

$missionById = [];
foreach (Mission::all() as $m) $missionById[$m['id']] = $m;
$totalHechas = isset($jornada['total_hallacas_hechas']) && $jornada['total_hallacas_hechas'] !== null && $jornada['total_hallacas_hechas'] !== ''
    ? (int)$jornada['total_hallacas_hechas'] : null;
$totalAsignadasJornada = Jornada::totalAsignadas((int)$jornada['id']);
$totalEntregadasJornada = Jornada::totalEntregadas((int)$jornada['id']);
$restPorAsignar = $totalHechas !== null ? $totalHechas - $totalAsignadasJornada : null;
$cuadraProduccion = $totalHechas !== null && $totalAsignadasJornada === $totalHechas && $totalEntregadasJornada <= $totalAsignadasJornada;
$sobreAsignado = $totalHechas !== null && $totalAsignadasJornada > $totalHechas;
page_back('jornadas', 'Volver a jornadas');
?>
<h2><?= h($jornada['name']) ?> <small class="text-muted">(<?= h($jornada['fecha']) ?>)</small> <?= status_badge($jornada['status']) ?></h2>
<p>Precio por hallaca: <strong><?= money($precio) ?></strong> <?= $jornada['notes'] ? ' - '.h($jornada['notes']) : '' ?></p>

<div class="card mb-3 border-secondary">
  <div class="card-body py-3">
    <h6 class="card-title mb-2">Producción y distribución</h6>
    <div class="d-flex flex-wrap gap-3 align-items-center mb-2">
      <span>Total hechas: <strong><?= $totalHechas !== null ? (int)$totalHechas : '—' ?></strong></span>
      <span>Asignadas a misiones: <strong><?= (int)$totalAsignadasJornada ?></strong></span>
      <span>Entregadas a miembros: <strong><?= (int)$totalEntregadasJornada ?></strong></span>
      <?php if ($restPorAsignar !== null): ?>
      <span>Por asignar a misiones: <strong class="<?= $restPorAsignar < 0 ? 'text-danger' : ($restPorAsignar > 0 ? 'text-warning' : 'text-success') ?>"><?= (int)$restPorAsignar ?></strong></span>
      <?php endif; ?>
    </div>
    <?php if ($totalHechas === null): ?>
    <div class="alert alert-warning py-2 mb-2 small mb-0">Indique el total de hallacas hechas en esta jornada para controlar el cuadre con las misiones.</div>
    <?php elseif ($sobreAsignado): ?>
    <div class="alert alert-danger py-2 mb-2 small mb-0">Descuadre: las misiones tienen más hallacas asignadas (<?= (int)$totalAsignadasJornada ?>) que el total hecho (<?= (int)$totalHechas ?>).</div>
    <?php elseif ($cuadraProduccion): ?>
    <div class="alert alert-success py-2 mb-2 small mb-0">Cuadre correcto: todo lo producido está asignado a misiones.</div>
    <?php elseif ($restPorAsignar > 0): ?>
    <div class="alert alert-info py-2 mb-2 small mb-0">Faltan <?= (int)$restPorAsignar ?> hallacas por asignar a misiones.</div>
    <?php endif; ?>
    <?php if (Auth::isAdmin() || (Auth::isJornadaAdmin() && in_array((int)$jornada['id'], Auth::jornadaIds(), true))): ?>
    <form method="post" action="<?= url('jornada_total_hechas') ?>" class="row g-2 align-items-end mt-2">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="jornada_id" value="<?= (int)$jornada['id'] ?>">
      <div class="col-auto">
        <label class="form-label small mb-0">Total hallacas hechas</label>
        <input type="number" name="total_hallacas_hechas" class="form-control form-control-sm" min="0" style="width:8rem"
               value="<?= $totalHechas !== null ? (int)$totalHechas : '' ?>" placeholder="Cantidad">
      </div>
      <div class="col-auto">
        <button class="btn btn-sm btn-outline-primary">Guardar total</button>
      </div>
    </form>
    <?php endif; ?>
  </div>
</div>

<?php
$jornadaTabInventory = in_array(Auth::role(), ['admin', 'jornada_admin'], true);
$jornadaActiveTab = (($_GET['tab'] ?? '') === 'inventario' && $jornadaTabInventory) ? 'inventario' : 'entregas';
?>
<?php if ($jornadaTabInventory): ?>
<ul class="nav nav-tabs mb-3" id="jornadaDetailTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link <?= $jornadaActiveTab === 'entregas' ? 'active' : '' ?>" id="jornada-tab-entregas" data-bs-toggle="tab" data-bs-target="#jornada-pane-entregas" type="button" role="tab" aria-controls="jornada-pane-entregas" aria-selected="<?= $jornadaActiveTab === 'entregas' ? 'true' : 'false' ?>">Entregas</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link <?= $jornadaActiveTab === 'inventario' ? 'active' : '' ?>" id="jornada-tab-inventario" data-bs-toggle="tab" data-bs-target="#jornada-pane-inventario" type="button" role="tab" aria-controls="jornada-pane-inventario" aria-selected="<?= $jornadaActiveTab === 'inventario' ? 'true' : 'false' ?>">Inventario de jornada</button>
  </li>
</ul>
<div class="tab-content" id="jornadaDetailTabContent">
<div class="tab-pane fade <?= $jornadaActiveTab === 'entregas' ? 'show active' : '' ?>" id="jornada-pane-entregas" role="tabpanel" aria-labelledby="jornada-tab-entregas" tabindex="0">
<?php endif; ?>

<div class="d-flex flex-wrap gap-2 mb-3">
<?php if (Auth::isAdmin()): ?>
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignMissionModal">Asignar misión</button>
<div class="modal fade" id="assignMissionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="post" action="<?= url('jornada_asignar') ?>" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Asignar misión a la jornada</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="jornada_id" value="<?= (int)$jornada['id'] ?>">
        <div class="mb-3">
          <label class="form-label">Misión</label>
          <select name="mission_id" class="form-select" required>
            <?php foreach ($allMissions as $m): if($m['status']!=='active') continue; ?>
            <?php if (!Auth::isAdmin() && Auth::missionId() !== (int)$m['id']) continue; ?>
            <option value="<?= (int)$m['id'] ?>"><?= h($m['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Hallacas asignadas</label>
          <input type="number" name="hallacas_asignadas" class="form-control" min="0" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Notas</label>
          <input name="notes" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary">Asignar</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
<?php if (Auth::isAdmin() || Auth::isCoordinator() || Auth::isJornadaAdmin()): ?>
<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#distributeModal">Distribuir hallacas</button>
<div class="modal fade" id="distributeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="post" action="<?= url('entrega_store') ?>" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Distribuir hallacas a un miembro</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="jornada_id" value="<?= (int)$jornada['id'] ?>">
        <div class="mb-3">
          <label class="form-label">Misión</label>
          <select name="mission_id" id="distMission" class="form-select" required>
            <?php foreach ($assigned as $jm):
              if (Auth::isCoordinator() && !Auth::isAdmin() && Auth::missionId() !== (int)$jm['mission_id']) continue; ?>
            <option value="<?= (int)$jm['mission_id'] ?>"><?= h($jm['mission_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Miembro del equipo</label>
          <select name="member_id" id="distMember" class="form-select" required>
            <option value="">Seleccionar miembro</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Hallacas entregadas</label>
          <input type="number" name="hallacas_entregadas" class="form-control" min="0" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Notas</label>
          <input name="notes" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-success">Distribuir</button>
      </div>
    </form>
  </div>
</div>
<script>
(function(){
  var membersByMission = <?= json_encode($membersByMission, JSON_UNESCAPED_UNICODE) ?>;
  var missionSel = document.getElementById('distMission');
  var memberSel = document.getElementById('distMember');
  function updateMembers(mid){
    if (!memberSel) return;
    memberSel.innerHTML = '<option value="">Seleccionar miembro</option>';
    var list = (membersByMission && membersByMission[mid]) ? membersByMission[mid] : [];
    list.forEach(function(m){
      var opt = document.createElement('option');
      opt.value = m.id;
      opt.textContent = m.name;
      memberSel.appendChild(opt);
    });
  }
  if (missionSel){
    missionSel.addEventListener('change', function(){ updateMembers(this.value); });
    updateMembers(missionSel.value);
  }
})();
</script>
<button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#paymentModal">Registrar pago</button>
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="post" action="<?= url('entrega_abono') ?>" class="modal-content" enctype="multipart/form-data">
      <div class="modal-header">
        <h5 class="modal-title">Registrar pago</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="mb-3">
          <label class="form-label">Entrega</label>
          <select name="entrega_id" id="payEntrega" class="form-select" required>
            <?php foreach ($entregas as $e):
              if (Auth::isCoordinator() && !Auth::isAdmin() && (int)$e['mission_id'] !== (int)Auth::missionId()) continue;
              $rest = (int)$e['hallacas_entregadas'] - (int)$e['hallacas_pagadas'];
              if ($rest <= 0) continue; ?>
            <option value="<?= (int)$e['id'] ?>" data-rest="<?= $rest ?>">
              <?= h($e['member_name']) ?> · <?= h($e['mission_name']) ?> (pend. <?= $rest ?>)
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="alert alert-info py-2 small">Disponibles por cancelar: <strong id="payRest">0</strong> hallacas</div>
        <div class="mb-3">
          <label class="form-label">Hallacas pagadas (adicional)</label>
          <input type="number" name="hallacas" id="payHall" class="form-control" min="0" value="0" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Monto</label>
          <input type="number" step="0.01" name="monto" class="form-control" value="0" required>
        </div>
        <div class="mb-3" id="payTasaGroup">
          <label class="form-label">Tasa (Bs por USD)</label>
          <input type="number" step="0.0001" name="tasa" class="form-control" placeholder="Opcional para pagos en bolívares">
        </div>
        <div class="mb-3 d-none" id="payCaptureGroup">
          <label class="form-label">Capture de pago (imagen)</label>
          <input type="file" name="captura" class="form-control" accept="image/*">
        </div>
        <div class="mb-3">
          <label class="form-label">Método de pago</label>
          <select name="metodo" class="form-select" required>
            <?php foreach (payment_methods() as $pm): ?>
            <option value="<?= h($pm['name']) ?>" data-currency="<?= h($pm['currency']) ?>"><?= h($pm['name']) ?> (<?= h($pm['currency']) ?>)</option>
            <?php endforeach; ?>
            <option value="otro">Otro</option>
          </select>
          <input type="text" name="metodo_otro" class="form-control mt-2 d-none" placeholder="Especifique el método">
          <select name="metodo_otro_currency" class="form-select mt-2 d-none">
            <option value="VES">VES</option>
            <option value="USD">USD</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Notas</label>
          <input type="text" name="notes" class="form-control" placeholder="opcional">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-success">Registrar pago</button>
      </div>
    </form>
  </div>
</div>
<script>
(function(){
  var modal = document.getElementById('paymentModal');
  if (!modal) return;
  function updateRest(){
    var sel = document.getElementById('payEntrega');
    var opt = sel.options[sel.selectedIndex];
    var rest = opt ? parseInt(opt.getAttribute('data-rest') || '0', 10) : 0;
    var el = document.getElementById('payRest');
    if (el) el.textContent = rest;
    var h = document.getElementById('payHall');
    if (h) { h.max = rest; }
  }
  var sel = document.getElementById('payEntrega');
  if (sel) { sel.addEventListener('change', updateRest); }
  var methodSel = modal ? modal.querySelector('select[name="metodo"]') : null;
  function togglePayFields(){
    var m = methodSel ? methodSel.value : '';
    var cur = 'VES';
    if (m === 'otro') {
      var curSel = methodSel ? methodSel.parentElement.querySelector('select[name="metodo_otro_currency"]') : null;
      cur = curSel ? curSel.value : 'VES';
    } else if (methodSel) {
      var opt = methodSel.options[methodSel.selectedIndex];
      if (opt) cur = opt.getAttribute('data-currency') || 'VES';
    }
    var lower = m.toLowerCase();
    var isPago = lower.indexOf('pago') !== -1 || lower.indexOf('movil') !== -1 || lower.indexOf('móvil') !== -1;
    var tg = document.getElementById('payTasaGroup');
    var cg = document.getElementById('payCaptureGroup');
    if (tg) tg.classList.toggle('d-none', cur === 'USD');
    if (cg) cg.classList.toggle('d-none', !isPago);
  }
  if (methodSel) { methodSel.addEventListener('change', togglePayFields); }
  var curSel = methodSel ? methodSel.parentElement.querySelector('select[name="metodo_otro_currency"]') : null;
  if (curSel) { curSel.addEventListener('change', togglePayFields); }
  if (modal) {
    modal.addEventListener('show.bs.modal', function(event){
      var btn = event.relatedTarget;
      if (btn && btn.getAttribute && btn.getAttribute('data-entrega-id')) {
        if (sel) sel.value = btn.getAttribute('data-entrega-id');
      }
      updateRest();
      togglePayFields();
    });
  }
  updateRest();
  togglePayFields();
})();
</script>
<?php endif; ?>
</div>

<?php if (empty($assigned)): ?>
<div class="alert alert-info">No hay misiones asignadas a esta jornada.</div>
<?php endif; ?>

<div id="distribucion-equipo">
<?php foreach ($assigned as $jm):
  $mid = (int)$jm['mission_id'];
  if (Auth::isCoordinator() && !Auth::isAdmin() && Auth::missionId() !== $mid) continue;
  $members = $membersByMission[$mid] ?? User::membersOfMission($mid);
  // delivered totals
  $deliv = DB::query('SELECT COALESCE(SUM(hallacas_entregadas),0) s, COALESCE(SUM(hallacas_pagadas),0) p, COALESCE(SUM(monto_pagado),0) mp FROM entregas WHERE jornada_id=? AND mission_id=?', [(int)$jornada['id'],$mid])->fetch();
  $remaining = (int)$jm['hallacas_asignadas'] - (int)$deliv['s'];
?>
<div class="card mb-3">
 <div class="card-header d-flex flex-column flex-md-row flex-wrap justify-content-between align-items-stretch align-items-md-center gap-2">
   <strong><?= h($jm['mission_name']) ?></strong>
   <span>Asignadas: <?= (int)$jm['hallacas_asignadas'] ?> | Entregadas: <?= (int)$deliv['s'] ?> | Pagadas: <?= (int)$deliv['p'] ?> | Pendientes: <?= (int)$deliv['s']-(int)$deliv['p'] ?> | Restantes por distribuir: <strong><?= $remaining ?></strong></span>
 </div>
 <div class="card-body">
   <?php if (Auth::isAdmin() || Auth::isCoordinator()): ?>
   <form method="post" action="<?= url('jornada_quitar') ?>" class="mb-2" onsubmit="return confirm('Quitar misión de la jornada y sus entregas?')">
     <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
     <input type="hidden" name="jornada_id" value="<?= (int)$jornada['id'] ?>">
     <input type="hidden" name="mission_id" value="<?= $mid ?>">
     <button class="btn btn-sm btn-outline-danger">Quitar misión</button>
   </form>
   <?php endif; ?>

   <div class="table-responsive">
   <table class="table table-sm table-bordered">
     <thead><tr><th>Miembro</th><th>Entregadas</th><th>Pagadas</th><th>Pendientes</th><th>Estado</th><th>Monto pagado</th><th>Acciones</th></tr></thead>
     <tbody>
     <?php foreach ($members as $mem):
        $e = $entregasByMember[$mem['id']] ?? null;
     ?>
      <tr>
        <td><?= h($mem['name']) ?></td>
        <?php if ($e): ?>
          <td><?= (int)$e['hallacas_entregadas'] ?></td>
          <td><?= (int)$e['hallacas_pagadas'] ?></td>
          <td><?= (int)$e['hallacas_entregadas']-(int)$e['hallacas_pagadas'] ?></td>
          <td><?= status_badge($e['status']) ?></td>
          <td><?= money($e['monto_pagado']) ?></td>
          <td>
            <?php if (Auth::isAdmin() || Auth::isCoordinator() || Auth::isJornadaAdmin()): ?>
            <!-- Abono -->
            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal" data-entrega-id="<?= (int)$e['id'] ?>">Abonar</button>
            <form method="post" action="<?= url('entrega_delete') ?>" class="d-inline" onsubmit="return confirm('Eliminar entrega?')">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="entrega_id" value="<?= (int)$e['id'] ?>">
              <button class="btn btn-sm btn-outline-danger">Eliminar</button>
            </form>
            <?php if (!empty($e['notes'])): ?><div class="small text-muted mt-1"><?= h($e['notes']) ?></div><?php endif; ?>
            <?php endif; ?>
          </td>
        <?php else: ?>
          <td>0</td><td>0</td><td>0</td><td><?= status_badge('pendiente') ?></td><td><?= money(0) ?></td>
          <td>
            <?php if (($jornada['status']==='open') && (Auth::isAdmin() || Auth::isCoordinator() || Auth::isJornadaAdmin())): ?>
            <form method="post" action="<?= url('entrega_store') ?>" class="row g-1">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="jornada_id" value="<?= (int)$jornada['id'] ?>">
              <input type="hidden" name="mission_id" value="<?= $mid ?>">
              <input type="hidden" name="member_id" value="<?= (int)$mem['id'] ?>">
              <div class="col"><input type="number" name="hallacas_entregadas" class="form-control form-control-sm" placeholder="cant." min="0" required></div>
              <div class="col"><input type="number" name="hallacas_pagadas" class="form-control form-control-sm" placeholder="pagadas" min="0" value="0"></div>
              <div class="col"><input type="number" step="0.01" name="monto_pagado" class="form-control form-control-sm" placeholder="monto" value="0"></div>
              <div class="col"><input name="notes" class="form-control form-control-sm" placeholder="notas"></div>
              <div class="col-auto"><button class="btn btn-sm btn-primary">Entregar</button></div>
            </form>
            <?php else: ?>
            <span class="text-muted small">Sin entrega</span>
            <?php endif; ?>
          </td>
        <?php endif; ?>
      </tr>
     <?php endforeach; ?>
     </tbody>
   </table>
   </div>
 </div>
</div>
<?php endforeach; ?>
</div>

<?php if ($jornadaTabInventory): ?>
</div>
<div class="tab-pane fade <?= $jornadaActiveTab === 'inventario' ? 'show active' : '' ?>" id="jornada-pane-inventario" role="tabpanel" aria-labelledby="jornada-tab-inventario" tabindex="0">
<div class="card mb-3">
  <div class="card-header py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <h5 class="mb-0">Entrega de ingredientes (inventario)</h5>
    <a class="btn btn-sm btn-outline-secondary" href="<?= url('inventory') ?>">Ver inventario global</a>
  </div>
  <div class="card-body">
    <?php if (empty($inventoryTablesReady)): ?>
    <div class="alert alert-warning mb-0">Falta instalar las tablas de inventario. Ejecute <code>sql/003_inventory.sql</code> en MySQL o abra <a href="<?= url('inventory') ?>">Inventario</a> para ver las instrucciones.</div>
    <?php else: ?>
    <p class="text-muted small">Al entregar ingredientes a esta jornada se registra una <strong>salida</strong> de stock y queda en el historial.</p>
    <form method="post" action="<?= url('inventory_jornada_deliver') ?>">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="jornada_id" value="<?= (int)$jornada['id'] ?>">
      <div class="row g-2 mb-2">
        <div class="col-md-3">
          <label class="form-label small mb-0">Fecha</label>
          <input type="date" name="fecha_ingreso" class="form-control form-control-sm" value="<?= h($jornada['fecha']) ?>">
        </div>
        <div class="col-md-9">
          <label class="form-label small mb-0">Notas</label>
          <input name="notes" class="form-control form-control-sm" placeholder="Entrega a elaboradores">
        </div>
      </div>
      <div class="table-responsive">
      <table class="table table-sm mb-2">
        <thead><tr><th>Producto</th><th>Stock disp.</th><th>Cantidad a entregar</th></tr></thead>
        <tbody>
        <?php foreach ($inventoryProducts as $ip): ?>
        <tr>
          <td><?= h($ip['name']) ?> <small class="text-muted">(<?= h($ip['sku']) ?>)</small></td>
          <td><?= h(rtrim(rtrim(number_format((float)$ip['stock'], 3, '.', ''), '0'), '.')) ?> <?= h($ip['unidad_metrica']) ?></td>
          <td style="max-width:8rem">
            <input type="hidden" name="product_id[]" value="<?= (int)$ip['id'] ?>">
            <input type="number" step="0.001" min="0" name="cantidad[]" class="form-control form-control-sm" placeholder="0">
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($inventoryProducts)): ?>
        <tr><td colspan="3" class="text-muted">No hay productos en inventario. <a href="<?= url('inventory') ?>">Registrar productos</a></td></tr>
        <?php endif; ?>
        </tbody>
      </table>
      </div>
      <?php if (!empty($inventoryProducts)): ?>
      <button class="btn btn-sm btn-warning">Registrar salida a jornada</button>
      <?php endif; ?>
    </form>
    <?php if (!empty($jornadaInventoryOut)): ?>
    <h6 class="mt-3 mb-2">Salidas registradas en esta jornada</h6>
    <div class="table-responsive">
    <table class="table table-sm table-striped mb-0">
      <thead><tr><th>Fecha</th><th>Producto</th><th>Cantidad</th><th>Registrado por</th></tr></thead>
      <tbody>
      <?php foreach ($jornadaInventoryOut as $jo): if ($jo['tipo'] !== 'salida_jornada') continue; ?>
      <tr>
        <td><?= h($jo['fecha_ingreso']) ?></td>
        <td><?= h($jo['product_name']) ?></td>
        <td><?= h(rtrim(rtrim(number_format((float)$jo['cantidad'], 3, '.', ''), '0'), '.')) ?> <?= h($jo['unidad_metrica']) ?></td>
        <td><?= h($jo['created_by_name'] ?? '—') ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>

    <?php
    $balanceRows = array_values($jornadaInventoryBalance ?? []);
    $returnable = array_filter($balanceRows, static fn($b) => ($b['pendiente_devolver'] ?? 0) > 0.0001);
    ?>
    <?php if (!empty($balanceRows)): ?>
    <hr class="my-3">
    <h6 class="mb-2">Excedentes / devolución a almacén</h6>
    <p class="text-muted small mb-2">Lo no devuelto se considera <strong>consumido en jornada</strong> (entregado − devuelto). Al registrar excedentes, el stock vuelve al almacén.</p>
    <div class="table-responsive mb-3">
    <table class="table table-sm table-bordered mb-0">
      <thead><tr><th>Producto</th><th>Entregado</th><th>Devuelto</th><th>Consumido (est.)</th><th>Pend. devolver</th></tr></thead>
      <tbody>
      <?php foreach ($balanceRows as $b): ?>
      <tr>
        <td><?= h($b['product_name']) ?> <small class="text-muted">(<?= h($b['sku']) ?>)</small></td>
        <td><?= h(rtrim(rtrim(number_format((float)$b['entregado'], 3, '.', ''), '0'), '.')) ?> <?= h($b['unidad_metrica']) ?></td>
        <td><?= h(rtrim(rtrim(number_format((float)$b['devuelto'], 3, '.', ''), '0'), '.')) ?> <?= h($b['unidad_metrica']) ?></td>
        <td><?= h(rtrim(rtrim(number_format((float)$b['consumido'], 3, '.', ''), '0'), '.')) ?> <?= h($b['unidad_metrica']) ?></td>
        <td><?= h(rtrim(rtrim(number_format((float)$b['pendiente_devolver'], 3, '.', ''), '0'), '.')) ?> <?= h($b['unidad_metrica']) ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php if (!empty($returnable)): ?>
    <form method="post" action="<?= url('inventory_jornada_return') ?>">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="jornada_id" value="<?= (int)$jornada['id'] ?>">
      <div class="row g-2 mb-2">
        <div class="col-md-3">
          <label class="form-label small mb-0">Fecha devolución</label>
          <input type="date" name="fecha_ingreso" class="form-control form-control-sm" value="<?= h($jornada['fecha']) ?>">
        </div>
        <div class="col-md-9">
          <label class="form-label small mb-0">Notas</label>
          <input name="return_notes" class="form-control form-control-sm" placeholder="Excedente no usado">
        </div>
      </div>
      <div class="table-responsive">
      <table class="table table-sm mb-2">
        <thead><tr><th>Producto</th><th>Máx. a devolver</th><th>Cantidad a devolver</th></tr></thead>
        <tbody>
        <?php foreach ($returnable as $b): ?>
        <tr>
          <td><?= h($b['product_name']) ?></td>
          <td><?= h(rtrim(rtrim(number_format((float)$b['pendiente_devolver'], 3, '.', ''), '0'), '.')) ?> <?= h($b['unidad_metrica']) ?></td>
          <td style="max-width:8rem">
            <input type="hidden" name="return_product_id[]" value="<?= (int)$b['product_id'] ?>">
            <input type="number" step="0.001" min="0" max="<?= h((string)$b['pendiente_devolver']) ?>" name="return_cantidad[]" class="form-control form-control-sm" placeholder="0">
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
      <button class="btn btn-sm btn-success">Registrar excedentes en almacén</button>
    </form>
    <?php endif; ?>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
</div>
</div>
<script>
(function(){
  var tabs = document.getElementById('jornadaDetailTabs');
  if (!tabs) return;
  var jid = <?= (int)$jornada['id'] ?>;
  tabs.querySelectorAll('button[data-bs-toggle="tab"]').forEach(function(btn){
    btn.addEventListener('shown.bs.tab', function(){
      var tab = btn.id === 'jornada-tab-inventario' ? 'inventario' : 'entregas';
      var u = new URL(window.location.href);
      u.searchParams.set('r', 'jornada_show');
      u.searchParams.set('id', String(jid));
      u.searchParams.set('tab', tab);
      history.replaceState(null, '', u.pathname + '?' + u.searchParams.toString());
    });
  });
})();
</script>
<?php endif; ?>

<script>
document.querySelectorAll('select[name="metodo"]').forEach(function(sel){
  sel.addEventListener('change', function(){
    var inp = sel.parentElement.querySelector('input[name="metodo_otro"]');
    if (inp) { if (sel.value === 'otro') inp.classList.remove('d-none'); else inp.classList.add('d-none'); }
    var cur = sel.parentElement.querySelector('select[name="metodo_otro_currency"]');
    if (cur) { if (sel.value === 'otro') cur.classList.remove('d-none'); else cur.classList.add('d-none'); }
  });
});
</script>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
