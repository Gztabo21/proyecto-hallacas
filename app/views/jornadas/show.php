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
<p>Precio por hallaca (USD): <strong><?= money($precio) ?></strong> <?= $jornada['notes'] ? ' - '.h($jornada['notes']) : '' ?></p>

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
$jornadaTabEntregas = in_array(Auth::role(), ['admin', 'coordinator', 'jornada_admin'], true);
$jornadaTabInventory = Auth::canAccessInventory();
$jornadaTabPagos = $jornadaTabPagos ?? false;
$jornadaTabProduccion = $jornadaTabProduccion ?? false;
$jornadaTabLabels = [];
if ($jornadaTabEntregas) {
    $jornadaTabLabels['entregas'] = 'Entregas';
}
if ($jornadaTabPagos) {
    $jornadaTabLabels['pagos'] = 'Pagos';
}
if ($jornadaTabProduccion) {
    $jornadaTabLabels['produccion'] = 'Producción';
}
if ($jornadaTabInventory) {
    $jornadaTabLabels['inventario'] = 'Inventario de jornada';
}
$jornadaReqTab = (string)($_GET['tab'] ?? '');
$jornadaActiveTab = isset($jornadaTabLabels[$jornadaReqTab]) ? $jornadaReqTab : array_key_first($jornadaTabLabels);
$jornadaUseTabs = count($jornadaTabLabels) > 1;
?>
<?php if ($jornadaUseTabs): ?>
<ul class="nav nav-tabs mb-3" id="jornadaDetailTabs" role="tablist">
  <?php foreach ($jornadaTabLabels as $tabKey => $tabLabel): ?>
  <li class="nav-item" role="presentation">
    <button class="nav-link <?= $jornadaActiveTab === $tabKey ? 'active' : '' ?>" id="jornada-tab-<?= h($tabKey) ?>" data-bs-toggle="tab" data-bs-target="#jornada-pane-<?= h($tabKey) ?>" type="button" role="tab" data-jornada-tab-key="<?= h($tabKey) ?>" aria-controls="jornada-pane-<?= h($tabKey) ?>" aria-selected="<?= $jornadaActiveTab === $tabKey ? 'true' : 'false' ?>"><?= h($tabLabel) ?></button>
  </li>
  <?php endforeach; ?>
</ul>
<div class="tab-content" id="jornadaDetailTabContent">
<?php endif; ?>
<?php if ($jornadaTabEntregas): ?>
<?php if ($jornadaUseTabs): ?><div class="tab-pane fade <?= $jornadaActiveTab === 'entregas' ? 'show active' : '' ?>" id="jornada-pane-entregas" role="tabpanel" aria-labelledby="jornada-tab-entregas" tabindex="0"><?php endif; ?>

<?php
$canManageJornadaMissions = Auth::isAdmin()
    || (Auth::isJornadaAdmin() && in_array((int)$jornada['id'], Auth::jornadaIds(), true));
?>
<div class="d-flex flex-wrap gap-2 mb-3">
<?php if ($canManageJornadaMissions): ?>
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
            <?php if (!$canManageJornadaMissions && Auth::missionId() !== (int)$m['id']) continue; ?>
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
<button type="button" class="btn btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#importEntregasPanel" aria-expanded="false">Importar distribución (CSV)</button>
<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#distributeModal">Distribuir hallacas</button>
<div class="collapse w-100 mt-2" id="importEntregasPanel">
  <div class="card card-body shadow-sm">
    <p class="small text-muted mb-2">Columnas: <code>mision;miembro;hallacas;email</code> (correo opcional). Crea miembros si no existen y registra entregas. Ajusta el cupo de cada misión en la jornada si hace falta.</p>
    <form method="post" action="<?= url('entrega_import_store') ?>" enctype="multipart/form-data" class="row g-2 align-items-end">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="jornada_id" value="<?= (int)$jornada['id'] ?>">
      <div class="col-md-6">
        <label class="form-label">Archivo CSV</label>
        <input type="file" name="csv" class="form-control form-control-sm" accept=".csv,text/csv" required>
      </div>
      <div class="col-md-6 d-flex flex-wrap gap-2">
        <button class="btn btn-primary btn-sm">Importar</button>
        <a class="btn btn-outline-secondary btn-sm" href="<?= url('entrega_import_template', ['jornada_id' => (int)$jornada['id']]) ?>">Plantilla</a>
        <?php if (is_readable(BASE_PATH . '/data/jornada-' . (int)$jornada['id'] . '-distribucion.csv')): ?>
        <a class="btn btn-outline-secondary btn-sm" href="<?= url('entrega_import_example', ['jornada_id' => (int)$jornada['id']]) ?>">Descargar datos jornada <?= (int)$jornada['id'] ?></a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>
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
          <div class="input-group">
            <select name="member_id" id="distMember" class="form-select" required>
              <option value="">Seleccionar miembro</option>
            </select>
            <button type="button" class="btn btn-outline-primary px-3" id="distMemberAddBtn" title="Agregar miembro o coordinador" aria-label="Agregar miembro">+</button>
          </div>
          <div class="collapse mt-2 border rounded p-3 bg-light" id="distQuickMemberPanel">
            <p class="small text-muted mb-2 mb-md-1">Al guardar queda seleccionado arriba sin recargar la página.</p>
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label small mb-0">Nombre</label>
                <input type="text" class="form-control form-control-sm" id="distQuickName" autocomplete="name">
              </div>
              <div class="col-md-6">
                <label class="form-label small mb-0">Correo</label>
                <input type="email" class="form-control form-control-sm" id="distQuickEmail" autocomplete="email">
              </div>
              <div class="col-md-4">
                <label class="form-label small mb-0">Contraseña</label>
                <input type="text" class="form-control form-control-sm" id="distQuickPassword" placeholder="Opcional (se genera)">
              </div>
              <div class="col-md-4">
                <label class="form-label small mb-0">Teléfono</label>
                <input type="text" class="form-control form-control-sm" id="distQuickPhone">
              </div>
              <div class="col-md-4">
                <label class="form-label small mb-0">Rol</label>
                <select class="form-select form-select-sm" id="distQuickRole">
                  <option value="member">Miembro</option>
                  <?php if (Auth::isAdmin()): ?><option value="coordinator">Coordinador</option><?php endif; ?>
                </select>
              </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
              <button type="button" class="btn btn-sm btn-primary" id="distQuickMemberSave">Guardar y seleccionar</button>
              <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#distQuickMemberPanel">Cancelar</button>
              <span class="small text-danger d-none" id="distQuickMemberError"></span>
              <span class="small text-success d-none" id="distQuickMemberOk"></span>
            </div>
          </div>
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
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
(function(){
  window.membersByMission = <?= json_encode($membersByMission, JSON_UNESCAPED_UNICODE) ?>;
  var membersByMission = window.membersByMission;
  var missionSel = document.getElementById('distMission');
  var memberSel = document.getElementById('distMember');
  var distributeModal = document.getElementById('distributeModal');
  window.distUpdateMembers = function(mid, selectId){
    if (!memberSel) return;
    memberSel.innerHTML = '';
    var key = String(mid);
    var list = (membersByMission && key) ? (membersByMission[key] || []) : [];
    if (!list.length) {
      var empty = document.createElement('option');
      empty.value = '';
      empty.textContent = 'No hay miembros activos en esta misión';
      memberSel.appendChild(empty);
      memberSel.disabled = true;
      return;
    }
    memberSel.disabled = false;
    var placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = 'Seleccionar miembro';
    memberSel.appendChild(placeholder);
    list.forEach(function(m){
      var opt = document.createElement('option');
      opt.value = m.id;
      opt.textContent = m.name;
      memberSel.appendChild(opt);
    });
    if (selectId) {
      memberSel.value = String(selectId);
    }
  };
  if (missionSel){
    missionSel.addEventListener('change', function(){ window.distUpdateMembers(this.value); });
    window.distUpdateMembers(missionSel.value);
  }
  if (distributeModal) {
    distributeModal.addEventListener('show.bs.modal', function(ev){
      var btn = ev.relatedTarget;
      var mid = btn && btn.getAttribute('data-mission-id');
      var memberId = btn && btn.getAttribute('data-member-id');
      if (missionSel && mid) {
        missionSel.value = mid;
        window.distUpdateMembers(mid, memberId || null);
      } else if (missionSel) {
        window.distUpdateMembers(missionSel.value);
      }
    });
  }
})();
jQuery(function($){
  var quickUrl = <?= json_encode(url('distribution_quick_member')) ?>;
  var jornadaId = <?= (int)$jornada['id'] ?>;
  var csrf = <?= json_encode(csrf_token()) ?>;
  var panelEl = document.getElementById('distQuickMemberPanel');
  var panel = panelEl ? bootstrap.Collapse.getOrCreateInstance(panelEl, { toggle: false }) : null;

  $('#distMemberAddBtn').on('click', function(){
    if (!panel) return;
    panel.show();
    $('#distQuickMemberError').addClass('d-none').text('');
    $('#distQuickMemberOk').addClass('d-none').text('');
  });

  $('#distQuickMemberSave').on('click', function(){
    var $btn = $(this).prop('disabled', true);
    var missionId = $('#distMission').val();
    $('#distQuickMemberError').addClass('d-none').text('');
    $('#distQuickMemberOk').addClass('d-none').text('');
    $.ajax({
      url: quickUrl,
      method: 'POST',
      dataType: 'json',
      data: {
        csrf: csrf,
        jornada_id: jornadaId,
        mission_id: missionId,
        name: $('#distQuickName').val(),
        email: $('#distQuickEmail').val(),
        password: $('#distQuickPassword').val(),
        phone: $('#distQuickPhone').val(),
        role: $('#distQuickRole').val()
      }
    }).done(function(resp){
      if (!resp || !resp.ok) {
        $('#distQuickMemberError').removeClass('d-none').text((resp && resp.error) ? resp.error : 'No se pudo guardar');
        return;
      }
      var mid = String(resp.mission_id);
      if (!window.membersByMission[mid]) window.membersByMission[mid] = [];
      var exists = window.membersByMission[mid].some(function(m){ return String(m.id) === String(resp.member.id); });
      if (!exists) window.membersByMission[mid].push(resp.member);
      window.distUpdateMembers(mid, resp.member.id);
      var okMsg = 'Agregado y seleccionado.';
      if (resp.temp_password) okMsg += ' Contraseña temporal: ' + resp.temp_password;
      $('#distQuickMemberOk').removeClass('d-none').text(okMsg);
      $('#distQuickName, #distQuickEmail, #distQuickPassword, #distQuickPhone').val('');
      if (panel) panel.hide();
    }).fail(function(xhr){
      var msg = 'Error de conexión';
      try {
        var j = xhr.responseJSON;
        if (j && j.error) msg = j.error;
      } catch (e) {}
      $('#distQuickMemberError').removeClass('d-none').text(msg);
    }).always(function(){
      $btn.prop('disabled', false);
    });
  });
});
</script>
<div class="modal fade" id="correctMissionAssignModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="post" action="<?= url('jornada_corregir_asignacion') ?>" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Corregir asignación a la misión</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="jornada_id" value="<?= (int)$jornada['id'] ?>">
        <input type="hidden" name="mission_id" id="correct_mission_id" value="">
        <div class="mb-3">
          <label class="form-label">Misión</label>
          <input type="text" class="form-control" id="correct_mission_assign_name" readonly>
        </div>
        <div class="alert alert-info py-2 small mb-3">Actual: <strong id="correct_mission_assign_actual">0</strong> asignadas · <strong id="correct_mission_entregadas">0</strong> entregadas a miembros</div>
        <div class="mb-3">
          <label class="form-label">Nueva cantidad asignada</label>
          <input type="number" name="hallacas_asignadas" id="correct_mission_assign_cantidad" class="form-control" min="0" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Motivo (opcional)</label>
          <input type="text" name="correccion_nota" class="form-control" placeholder="Ej. error al asignar">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary">Guardar corrección</button>
      </div>
    </form>
  </div>
</div>
<script>
(function(){
  var modal = document.getElementById('correctMissionAssignModal');
  if (!modal) return;
  modal.addEventListener('show.bs.modal', function(ev){
    var btn = ev.relatedTarget;
    if (!btn) return;
    document.getElementById('correct_mission_id').value = btn.getAttribute('data-mission-id') || '';
    document.getElementById('correct_mission_assign_name').value = btn.getAttribute('data-mission-name') || '';
    var actual = parseInt(btn.getAttribute('data-asignadas') || '0', 10);
    var entregadas = parseInt(btn.getAttribute('data-entregadas') || '0', 10);
    var maxAttr = btn.getAttribute('data-max-asign');
    document.getElementById('correct_mission_assign_actual').textContent = actual;
    document.getElementById('correct_mission_entregadas').textContent = entregadas;
    var inp = document.getElementById('correct_mission_assign_cantidad');
    if (inp) {
      inp.min = entregadas;
      inp.value = actual;
      if (maxAttr !== null && maxAttr !== '') {
        inp.max = parseInt(maxAttr, 10);
      } else {
        inp.removeAttribute('max');
      }
    }
  });
})();
</script>
<div class="modal fade" id="correctEntregaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="post" action="<?= url('entrega_correct') ?>" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Corregir hallacas entregadas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="entrega_id" id="correct_entrega_id" value="">
        <p class="mb-2"><strong id="correct_member"></strong><br><small class="text-muted" id="correct_mission"></small></p>
        <div class="alert alert-info py-2 small">Actual: <strong id="correct_actual">0</strong> entregadas · <strong id="correct_pagadas">0</strong> pagadas · Máximo permitido: <strong id="correct_max">0</strong></div>
        <div class="mb-3">
          <label class="form-label">Nueva cantidad entregada</label>
          <input type="number" name="hallacas_entregadas" id="correct_cantidad" class="form-control" min="0" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Motivo (opcional)</label>
          <input type="text" name="correccion_nota" class="form-control" placeholder="Ej. error al registrar">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary">Guardar corrección</button>
      </div>
    </form>
  </div>
</div>
<script>
(function(){
  var modal = document.getElementById('correctEntregaModal');
  if (!modal) return;
  modal.addEventListener('show.bs.modal', function(ev){
    var btn = ev.relatedTarget;
    if (!btn) return;
    document.getElementById('correct_entrega_id').value = btn.getAttribute('data-entrega-id') || '';
    document.getElementById('correct_member').textContent = btn.getAttribute('data-member-name') || '';
    document.getElementById('correct_mission').textContent = btn.getAttribute('data-mission-name') || '';
    var actual = parseInt(btn.getAttribute('data-entregadas') || '0', 10);
    var pagadas = parseInt(btn.getAttribute('data-pagadas') || '0', 10);
    var max = parseInt(btn.getAttribute('data-max') || '0', 10);
    document.getElementById('correct_actual').textContent = actual;
    document.getElementById('correct_pagadas').textContent = pagadas;
    document.getElementById('correct_max').textContent = max;
    var inp = document.getElementById('correct_cantidad');
    if (inp) {
      inp.min = pagadas;
      inp.max = max;
      inp.value = actual;
    }
  });
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
        <div class="mb-3 d-none" id="payReferenciaGroup">
          <label class="form-label">Referencia <span class="text-danger">*</span></label>
          <input type="text" name="referencia" id="pay_referencia" class="form-control" maxlength="120" placeholder="Nº de referencia del pago móvil">
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
    var rg = document.getElementById('payReferenciaGroup');
    var refInp = document.getElementById('pay_referencia');
    if (tg) tg.classList.toggle('d-none', cur === 'USD');
    if (cg) cg.classList.toggle('d-none', !isPago);
    if (rg) rg.classList.toggle('d-none', !isPago);
    if (refInp) refInp.required = isPago;
  }
  if (methodSel) { methodSel.addEventListener('change', togglePayFields); }
  var curSel = methodSel ? methodSel.parentElement.querySelector('select[name="metodo_otro_currency"]') : null;
  if (curSel) { curSel.addEventListener('change', togglePayFields); }
  var payForm = modal ? modal.querySelector('form') : null;
  if (payForm) {
    payForm.addEventListener('submit', function(ev){
      togglePayFields();
      var refInp = document.getElementById('pay_referencia');
      var m = methodSel ? methodSel.value : '';
      var lower = m.toLowerCase();
      var isPago = lower.indexOf('pago') !== -1 || lower.indexOf('movil') !== -1 || lower.indexOf('móvil') !== -1;
      if (isPago && refInp && !refInp.value.trim()) {
        ev.preventDefault();
        alert('Indique la referencia del pago móvil');
      }
    });
  }
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
  $members = $membersByMission[$mid] ?? User::distributionRecipientsForMission($mid);
  // delivered totals
  $deliv = DB::query('SELECT COALESCE(SUM(hallacas_entregadas),0) s, COALESCE(SUM(hallacas_pagadas),0) p, COALESCE(SUM(monto_pagado),0) mp FROM entregas WHERE jornada_id=? AND mission_id=?', [(int)$jornada['id'],$mid])->fetch();
  $remaining = (int)$jm['hallacas_asignadas'] - (int)$deliv['s'];
  $asignadasMission = (int)$jm['hallacas_asignadas'];
  $entregadasMission = (int)$deliv['s'];
  $maxAsignMission = $totalHechas !== null
      ? max($entregadasMission, $totalHechas - ($totalAsignadasJornada - $asignadasMission))
      : null;
?>
<div class="card mb-3">
 <div class="card-header d-flex flex-column flex-md-row flex-wrap justify-content-between align-items-stretch align-items-md-center gap-2">
   <strong><?= h($jm['mission_name']) ?></strong>
   <div class="d-flex flex-wrap align-items-center gap-2">
   <span>Asignadas: <?= $asignadasMission ?> | Entregadas: <?= $entregadasMission ?> | Pagadas: <?= (int)$deliv['p'] ?> | Pendientes: <?= $entregadasMission-(int)$deliv['p'] ?> | Restantes por distribuir: <strong><?= $remaining ?></strong></span>
   <?php if (Auth::isAdmin() || Auth::isCoordinator()): ?>
   <button type="button" class="btn btn-sm btn-outline-primary btn-correct-mission-assign"
     data-bs-toggle="modal" data-bs-target="#correctMissionAssignModal"
     data-mission-id="<?= $mid ?>"
     data-mission-name="<?= h($jm['mission_name']) ?>"
     data-asignadas="<?= $asignadasMission ?>"
     data-entregadas="<?= $entregadasMission ?>"
     <?php if ($maxAsignMission !== null): ?>data-max-asign="<?= (int)$maxAsignMission ?>"<?php endif; ?>>Corregir asignación</button>
   <?php endif; ?>
   </div>
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
            <button type="button" class="btn btn-sm btn-outline-primary btn-correct-entrega"
              data-bs-toggle="modal" data-bs-target="#correctEntregaModal"
              data-entrega-id="<?= (int)$e['id'] ?>"
              data-member-name="<?= h($mem['name']) ?>"
              data-mission-name="<?= h($jm['mission_name']) ?>"
              data-entregadas="<?= (int)$e['hallacas_entregadas'] ?>"
              data-pagadas="<?= (int)$e['hallacas_pagadas'] ?>"
              data-max="<?= (int)$remaining + (int)$e['hallacas_entregadas'] ?>">Corregir</button>
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
            <button type="button" class="btn btn-sm btn-primary"
              data-bs-toggle="modal" data-bs-target="#distributeModal"
              data-mission-id="<?= (int)$mid ?>"
              data-member-id="<?= (int)$mem['id'] ?>">Entregar</button>
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
<?php if ($jornadaUseTabs && $jornadaTabEntregas): ?></div><?php endif; ?>
<?php endif; ?>

<?php if ($jornadaTabPagos && $paymentsPanel): ?>
<?php if ($jornadaUseTabs): ?><div class="tab-pane fade <?= $jornadaActiveTab === 'pagos' ? 'show active' : '' ?>" id="jornada-pane-pagos" role="tabpanel" aria-labelledby="jornada-tab-pagos" tabindex="0"><?php endif; ?>
<?php
extract($paymentsPanel);
$paymentsEmbedded = true;
$returnJornadaId = (int)$jornada['id'];
include BASE_PATH . '/app/views/payments/_panel.php';
?>
<?php if ($jornadaUseTabs): ?></div><?php endif; ?>
<?php endif; ?>

<?php if ($jornadaTabProduccion): ?>
<?php if ($jornadaUseTabs): ?><div class="tab-pane fade <?= $jornadaActiveTab === 'produccion' ? 'show active' : '' ?>" id="jornada-pane-produccion" role="tabpanel" aria-labelledby="jornada-tab-produccion" tabindex="0"><?php endif; ?>
<?php include BASE_PATH . '/app/views/jornadas/_tab_produccion.php'; ?>
<?php if ($jornadaUseTabs): ?></div><?php endif; ?>
<?php endif; ?>

<?php if ($jornadaTabInventory): ?>
<?php if ($jornadaUseTabs): ?><div class="tab-pane fade <?= $jornadaActiveTab === 'inventario' ? 'show active' : '' ?>" id="jornada-pane-inventario" role="tabpanel" aria-labelledby="jornada-tab-inventario" tabindex="0"><?php endif; ?>
<div class="card mb-3">
  <div class="card-header py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <h5 class="mb-0">Entrega de ingredientes (inventario)</h5>
    <a class="btn btn-sm btn-outline-secondary" href="<?= url('inventory') ?>">Ver inventario global</a>
  </div>
  <div class="card-body">
    <?php if (empty($inventoryTablesReady)): ?>
    <div class="alert alert-warning mb-0">Falta instalar las tablas de inventario. Ejecute <code>sql/003_inventory.sql</code> en MySQL o abra <a href="<?= url('inventory') ?>">Inventario</a> para ver las instrucciones.</div>
    <?php else:
    $inventoryCostByProduct = $inventoryCostByProduct ?? [];
    $jornadaInvSubLabels = [
        'entrega' => 'Entregar ingredientes',
        'salidas' => 'Salidas registradas',
        'balance' => 'Balance y devoluciones',
    ];
    $jornadaInvSubReq = (string)($_GET['inv_sub'] ?? '');
    $jornadaInvSub = isset($jornadaInvSubLabels[$jornadaInvSubReq]) ? $jornadaInvSubReq : 'entrega';
    $jornadaInvSubUrl = static function (string $sub) use ($jornada): string {
        return url('jornada_show', ['id' => (int)$jornada['id'], 'tab' => 'inventario', 'inv_sub' => $sub]);
    };
    $salidasCount = 0;
    foreach ($jornadaInventoryOut as $joCount) {
        if (($joCount['tipo'] ?? '') === 'salida_jornada') {
            $salidasCount++;
        }
    }
    ?>
    <ul class="nav nav-tabs mb-3 flex-nowrap overflow-auto">
      <?php foreach ($jornadaInvSubLabels as $invKey => $invLabel): ?>
      <li class="nav-item">
        <a class="nav-link text-nowrap <?= $jornadaInvSub === $invKey ? 'active' : '' ?>" href="<?= h($jornadaInvSubUrl($invKey)) ?>"><?= h($invLabel) ?><?php if ($invKey === 'salidas' && $salidasCount > 0): ?> <span class="badge text-bg-secondary"><?= (int)$salidasCount ?></span><?php endif; ?></a>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php if ($jornadaInvSub === 'entrega'): ?>
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
    <?php elseif ($jornadaInvSub === 'salidas'): ?>
    <?php $canEditJornadaSalidas = Auth::canManageJornadaInventory((int)$jornada['id']); ?>
    <p class="text-muted small mb-2">Precio unitario en Bs. según el movimiento o, si no hay costo en la salida, el <strong>último precio</strong> del catálogo (<span class="text-muted">ref.</span>).</p>
    <?php if ($salidasCount === 0): ?>
    <p class="text-muted mb-0">No hay salidas registradas. Use la pestaña «Entregar ingredientes».</p>
    <?php else: ?>
    <div class="table-responsive">
    <table class="table table-sm table-striped mb-0">
      <thead><tr><th>Fecha</th><th>Producto</th><th>Cantidad</th><th class="text-end">Precio unit. (Bs)</th><th class="text-end">Total (Bs)</th><th>Registrado por</th><?php if ($canEditJornadaSalidas): ?><th class="text-end" style="width:8rem">Acciones</th><?php endif; ?></tr></thead>
      <tbody>
      <?php
      $totSalidasBs = 0.0;
      $totSalidasConPrecio = 0;
      foreach ($jornadaInventoryOut as $jo):
        if (($jo['tipo'] ?? '') !== 'salida_jornada') {
            continue;
        }
        $lineTotals = InventoryMovement::lineTotalAmounts($jo, $inventoryCostByProduct);
        $price = InventoryMovement::displayUnitPrice($jo, $inventoryCostByProduct);
        $refSuffix = !empty($price['is_ref']) ? ' <span class="text-muted small">ref.</span>' : '';
        if ($lineTotals['bs'] > 0) {
            $totSalidasBs += $lineTotals['bs'];
            $totSalidasConPrecio++;
        }
      ?>
      <tr>
        <td><?= h($jo['fecha_ingreso']) ?></td>
        <td><?= h($jo['product_name']) ?></td>
        <td><?= h(rtrim(rtrim(number_format((float)$jo['cantidad'], 3, '.', ''), '0'), '.')) ?> <?= h($jo['unidad_metrica']) ?></td>
        <td class="text-end"><?= ($price['precio_bs'] !== null && $price['precio_bs'] > 0) ? money($price['precio_bs']) . $refSuffix : 'n/d' ?></td>
        <td class="text-end"><?= $lineTotals['bs'] > 0 ? money($lineTotals['bs']) . $refSuffix : 'n/d' ?></td>
        <td><?= h($jo['created_by_name'] ?? '—') ?></td>
        <?php if ($canEditJornadaSalidas): ?>
        <td class="text-end text-nowrap">
          <button type="button" class="btn btn-sm btn-outline-primary py-0 btn-edit-salida"
                  data-bs-toggle="modal" data-bs-target="#editSalidaModal"
                  data-id="<?= (int)$jo['id'] ?>"
                  data-product="<?= h($jo['product_name']) ?>"
                  data-cantidad="<?= h((float)$jo['cantidad']) ?>"
                  data-fecha="<?= h($jo['fecha_ingreso']) ?>"
                  data-notes="<?= h($jo['notes'] ?? '') ?>">Editar</button>
          <form method="post" action="<?= url('inventory_jornada_salida_delete') ?>" class="d-inline" onsubmit="return confirm('¿Eliminar esta salida? El stock volverá al almacén.');">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="jornada_id" value="<?= (int)$jornada['id'] ?>">
            <input type="hidden" name="movement_id" value="<?= (int)$jo['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger py-0">Eliminar</button>
          </form>
        </td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
      <tr class="table-light">
        <td colspan="4" class="text-end fw-semibold">Total salidas (Bs)</td>
        <td class="text-end fw-semibold"><?= money($totSalidasBs) ?></td>
        <td colspan="<?= $canEditJornadaSalidas ? 2 : 1 ?>" class="text-muted small"><?php if ($totSalidasConPrecio < $salidasCount): ?>Incluye solo líneas con precio; <?= (int)($salidasCount - $totSalidasConPrecio) ?> sin costo en catálogo.<?php endif; ?></td>
      </tr>
      </tfoot>
    </table>
    </div>
    <?php if ($canEditJornadaSalidas): ?>
    <div class="modal fade" id="editSalidaModal" tabindex="-1" aria-labelledby="editSalidaModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= url('inventory_jornada_salida_update') ?>" class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editSalidaModalLabel">Editar salida</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="jornada_id" value="<?= (int)$jornada['id'] ?>">
            <input type="hidden" name="movement_id" id="editSalidaMovementId" value="">
            <p class="small text-muted mb-2">Producto: <strong id="editSalidaProductLabel">—</strong></p>
            <div class="mb-3">
              <label class="form-label">Cantidad</label>
              <input type="number" step="0.001" min="0.001" name="cantidad" id="editSalidaCantidad" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Fecha</label>
              <input type="date" name="fecha_ingreso" id="editSalidaFecha" class="form-control" required>
            </div>
            <div class="mb-0">
              <label class="form-label">Notas</label>
              <input type="text" name="notes" id="editSalidaNotes" class="form-control">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-primary">Guardar</button>
          </div>
        </form>
      </div>
    </div>
    <script>
    document.querySelectorAll('.btn-edit-salida').forEach(function (btn) {
      btn.addEventListener('click', function () {
        document.getElementById('editSalidaMovementId').value = btn.dataset.id || '';
        document.getElementById('editSalidaProductLabel').textContent = btn.dataset.product || '—';
        document.getElementById('editSalidaCantidad').value = btn.dataset.cantidad || '';
        document.getElementById('editSalidaFecha').value = btn.dataset.fecha || '';
        document.getElementById('editSalidaNotes').value = btn.dataset.notes || '';
      });
    });
    </script>
    <?php endif; ?>
    <?php endif; ?>

    <?php elseif ($jornadaInvSub === 'balance'): ?>
    <?php
    $balanceRows = array_values($jornadaInventoryBalance ?? []);
    $returnable = array_filter($balanceRows, static fn($b) => ($b['pendiente_devolver'] ?? 0) > 0.0001);
    ?>
    <?php if (empty($balanceRows)): ?>
    <p class="text-muted mb-0">Aún no hay entregas ni devoluciones registradas en esta jornada.</p>
    <?php else: ?>
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
    <?php endif; ?>
  </div>
</div>
<?php if ($jornadaUseTabs): ?></div><?php endif; ?>
<?php endif; ?>

<?php if ($jornadaUseTabs): ?>
</div>
<script>
(function(){
  var tabs = document.getElementById('jornadaDetailTabs');
  if (!tabs) return;
  var jid = <?= (int)$jornada['id'] ?>;
  tabs.querySelectorAll('button[data-bs-toggle="tab"]').forEach(function(btn){
    btn.addEventListener('shown.bs.tab', function(){
      var tab = btn.getAttribute('data-jornada-tab-key') || 'entregas';
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