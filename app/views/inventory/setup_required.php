<?php include BASE_PATH . '/app/views/partials/header.php';
page_back('dashboard', 'Volver al dashboard');
?>
<div class="alert alert-warning">
  <h4 class="alert-heading">Inventario no instalado en la base de datos</h4>
  <p>Faltan las tablas de inventario. Ejecute la migración en el servidor:</p>
  <pre class="bg-dark text-light p-3 rounded small mb-0">cd ~/containers/personales/proyecto-hallacas
docker compose exec -T db mysql -uhallacas -phallacas_secret hallacas_jireh &lt; sql/003_inventory.sql</pre>
  <p class="mt-3 mb-0 small text-muted">Luego recargue esta página. En instalaciones nuevas, las tablas ya vienen en <code>sql/init.sql</code>.</p>
</div>
<?php include BASE_PATH . '/app/views/partials/footer.php'; ?>
