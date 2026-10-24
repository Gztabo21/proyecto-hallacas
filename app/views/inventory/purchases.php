<?php include BASE_PATH . '/app/views/partials/header.php';
page_back('inventory', 'Volver al inventario');
?>
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-2 mb-3">
  <h2 class="mb-0">Compras y donaciones</h2>
  <a class="btn btn-success btn-sm" href="<?= url('inventory_purchase') ?>">Registrar compra / donación</a>
</div>
<p class="text-muted small">Totales por registro = suma de (cantidad × precio unitario Bs) de cada línea. En donaciones la tasa suele ser 0 y el USD no aplica.</p>
<?php
$tableTitle = 'Compras';
$isDonation = false;
$rows = $compras;
include BASE_PATH . '/app/views/inventory/_purchases_table.php';

$tableTitle = 'Donaciones';
$isDonation = true;
$rows = $donaciones;
include BASE_PATH . '/app/views/inventory/_purchases_table.php';
?>

<div class="modal fade" id="purchaseDetailModal" tabindex="-1" aria-labelledby="purchaseDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="purchaseDetailModalLabel">Detalle</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" id="purchaseDetailBody">
        <p class="text-muted mb-0">Cargando…</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-primary" id="purchaseDetailEditBtn" style="display:none">Editar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="purchaseEditModal" tabindex="-1" aria-labelledby="purchaseEditModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form method="post" action="<?= url('inventory_purchase_update') ?>" class="modal-content" id="purchaseEditForm">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="id" id="purchaseEditId" value="">
      <input type="hidden" name="es_donacion" id="purchaseEditEsDonacion" value="0">
      <div class="modal-header">
        <h5 class="modal-title" id="purchaseEditModalLabel">Editar</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="row g-2 mb-3">
          <div class="col-md-4">
            <label class="form-label">Fecha</label>
            <input type="date" name="fecha" id="purchaseEditFecha" class="form-control form-control-sm" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Tasa</label>
            <input type="number" step="0.0001" min="0" name="tasa" id="purchaseEditTasa" class="form-control form-control-sm">
          </div>
          <div class="col-md-4 purchase-field-donante d-none">
            <label class="form-label">Donante</label>
            <input type="text" name="donante" id="purchaseEditDonante" class="form-control form-control-sm">
          </div>
          <div class="col-md-4 purchase-field-referencia d-none">
            <label class="form-label">Referencia</label>
            <input type="text" name="referencia" id="purchaseEditReferencia" class="form-control form-control-sm">
          </div>
          <div class="col-12">
            <label class="form-label">Notas</label>
            <input type="text" name="notes" id="purchaseEditNotes" class="form-control form-control-sm">
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-sm">
            <thead>
              <tr>
                <th>Producto</th>
                <th>SKU</th>
                <th class="text-end">Cantidad</th>
                <th class="text-end">Precio unit. (Bs)</th>
              </tr>
            </thead>
            <tbody id="purchaseEditLines"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-outline-danger btn-sm" id="purchaseEditDeleteBtn">Eliminar registro</button>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </div>
    </form>
  </div>
</div>

<form method="post" action="<?= url('inventory_purchase_delete') ?>" id="purchaseDeleteForm" class="d-none">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <input type="hidden" name="id" id="purchaseDeleteId" value="">
</form>

<?php ob_start(); ?>
<script>
(function(){
  if (typeof bootstrap === 'undefined') {
    console.error('Bootstrap no cargado; modales de compras no disponibles.');
    return;
  }
  var jsonBase = <?= json_encode(url('inventory_purchase_json')) ?>;
  var detailModalEl = document.getElementById('purchaseDetailModal');
  var editModalEl = document.getElementById('purchaseEditModal');
  var detailModal = detailModalEl ? bootstrap.Modal.getOrCreateInstance(detailModalEl) : null;
  var editModal = editModalEl ? bootstrap.Modal.getOrCreateInstance(editModalEl) : null;
  var detailBody = document.getElementById('purchaseDetailBody');
  var detailEditBtn = document.getElementById('purchaseDetailEditBtn');
  var lastPayload = null;

  function fmtMoney(n) {
    if (n === null || n === undefined || isNaN(n)) return '—';
    return Number(n).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function fetchPurchase(id) {
    var sep = jsonBase.indexOf('?') >= 0 ? '&' : '?';
    return fetch(jsonBase + sep + 'id=' + encodeURIComponent(id), {
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    }).then(function(r) {
      if (!r.ok) {
        throw new Error('HTTP ' + r.status);
      }
      return r.json();
    });
  }

  function renderDetail(data) {
    var p = data.purchase;
    var html = '<dl class="row small mb-3">';
    html += '<dt class="col-sm-3">#</dt><dd class="col-sm-9">' + p.id + '</dd>';
    html += '<dt class="col-sm-3">Fecha</dt><dd class="col-sm-9">' + escapeHtml(p.fecha) + '</dd>';
    if (p.es_donacion) {
      html += '<dt class="col-sm-3">Donante</dt><dd class="col-sm-9">' + escapeHtml(p.donante || '—') + '</dd>';
    } else {
      html += '<dt class="col-sm-3">Referencia</dt><dd class="col-sm-9">' + escapeHtml(p.referencia || '—') + '</dd>';
    }
    html += '<dt class="col-sm-3">Tasa</dt><dd class="col-sm-9">' + (p.tasa > 0 ? p.tasa : '—') + '</dd>';
    html += '<dt class="col-sm-3">Notas</dt><dd class="col-sm-9">' + escapeHtml(p.notes || '—') + '</dd>';
    html += '<dt class="col-sm-3">Registrado</dt><dd class="col-sm-9">' + escapeHtml(p.created_by_name || '—') + '</dd>';
    html += '</dl>';
    html += '<div class="table-responsive"><table class="table table-sm table-striped"><thead><tr>';
    html += '<th>Producto</th><th>SKU</th><th class="text-end">Cant.</th><th class="text-end">P. unit. Bs</th><th class="text-end">Total Bs</th>';
    html += '</tr></thead><tbody>';
    data.lines.forEach(function(ln) {
      html += '<tr><td>' + escapeHtml(ln.product_name) + '</td><td><code>' + escapeHtml(ln.sku) + '</code></td>';
      html += '<td class="text-end">' + ln.cantidad + '</td><td class="text-end">' + fmtMoney(ln.precio_bs) + '</td>';
      html += '<td class="text-end">' + fmtMoney(ln.total_bs) + '</td></tr>';
    });
    html += '</tbody><tfoot class="table-light"><tr><th colspan="4" class="text-end">Total</th>';
    html += '<th class="text-end">' + fmtMoney(p.total_bs) + ' Bs';
    if (p.total_usd != null) html += ' · ' + fmtMoney(p.total_usd) + ' USD';
    html += '</th></tr></tfoot></table></div>';
    return html;
  }

  function escapeHtml(s) {
    if (s == null) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function fillEditForm(data) {
    var p = data.purchase;
    document.getElementById('purchaseEditId').value = p.id;
    document.getElementById('purchaseEditEsDonacion').value = p.es_donacion ? '1' : '0';
    document.getElementById('purchaseEditFecha').value = p.fecha;
    document.getElementById('purchaseEditTasa').value = p.tasa > 0 ? p.tasa : '';
    document.getElementById('purchaseEditNotes').value = p.notes || '';
    document.getElementById('purchaseEditDonante').value = p.donante || '';
    document.getElementById('purchaseEditReferencia').value = p.referencia || '';
    document.querySelectorAll('.purchase-field-donante').forEach(function(el) {
      el.classList.toggle('d-none', !p.es_donacion);
    });
    document.querySelectorAll('.purchase-field-referencia').forEach(function(el) {
      el.classList.toggle('d-none', !!p.es_donacion);
    });
    var tbody = document.getElementById('purchaseEditLines');
    tbody.innerHTML = '';
    data.lines.forEach(function(ln) {
      var tr = document.createElement('tr');
      tr.innerHTML =
        '<td>' + escapeHtml(ln.product_name) + '<input type="hidden" name="product_id[]" value="' + ln.product_id + '"></td>' +
        '<td><code>' + escapeHtml(ln.sku) + '</code></td>' +
        '<td><input type="text" inputmode="decimal" name="cantidad[]" class="form-control form-control-sm text-end" value="' + ln.cantidad + '" required></td>' +
        '<td><input type="text" inputmode="decimal" name="precio_bs[]" class="form-control form-control-sm text-end" value="' + ln.precio_bs + '" required></td>';
      tbody.appendChild(tr);
    });
    document.getElementById('purchaseDeleteId').value = p.id;
    document.getElementById('purchaseEditModalLabel').textContent = p.es_donacion ? 'Editar donación #' + p.id : 'Editar compra #' + p.id;
  }

  function openDetail(id) {
    if (!detailBody || !detailModal) return;
    detailBody.innerHTML = '<p class="text-muted mb-0">Cargando…</p>';
    if (detailEditBtn) detailEditBtn.style.display = 'none';
    detailModal.show();
    fetchPurchase(id).then(function(data) {
      if (data.error) {
        detailBody.innerHTML = '<p class="text-danger">' + escapeHtml(data.error) + '</p>';
        return;
      }
      lastPayload = data;
      detailBody.innerHTML = renderDetail(data);
      if (detailEditBtn) {
        detailEditBtn.style.display = '';
        detailEditBtn.onclick = function() {
          detailModal.hide();
          openEdit(data);
        };
      }
    }).catch(function() {
      detailBody.innerHTML = '<p class="text-danger">No se pudo cargar el detalle.</p>';
    });
  }

  function openEdit(dataOrId) {
    if (!editModal) return;
    var p = typeof dataOrId === 'object' ? Promise.resolve(dataOrId) : fetchPurchase(dataOrId);
    p.then(function(data) {
      if (data.error) return;
      lastPayload = data;
      fillEditForm(data);
      editModal.show();
    }).catch(function() {
      alert('No se pudo cargar el registro para editar.');
    });
  }

  document.querySelectorAll('.btn-purchase-detail').forEach(function(btn) {
    btn.addEventListener('click', function() {
      openDetail(btn.getAttribute('data-id'));
    });
  });
  document.querySelectorAll('.btn-purchase-edit').forEach(function(btn) {
    btn.addEventListener('click', function() {
      openEdit(parseInt(btn.getAttribute('data-id'), 10));
    });
  });
  document.querySelectorAll('.btn-purchase-delete').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var id = btn.getAttribute('data-id');
      if (!confirm('¿Eliminar este registro y revertir el stock de sus líneas?')) return;
      document.getElementById('purchaseDeleteId').value = id;
      document.getElementById('purchaseDeleteForm').submit();
    });
  });

  var delBtn = document.getElementById('purchaseEditDeleteBtn');
  if (delBtn) {
    delBtn.addEventListener('click', function() {
      if (!confirm('¿Eliminar este registro y revertir el stock?')) return;
      document.getElementById('purchaseDeleteForm').submit();
    });
  }
})();
</script>
<?php
$extraFooterScripts = ob_get_clean();
include BASE_PATH . '/app/views/partials/footer.php';
