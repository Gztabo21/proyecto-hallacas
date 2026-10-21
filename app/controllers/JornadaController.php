<?php
class JornadaController {
    public static function index(): void {
        require_login();
        if (!Auth::canAccessJornadasList()) {
            http_response_code(403);
            die('Acceso denegado.');
        }
        if (Auth::isAdmin() || Auth::isPurchaseAdmin() || Auth::isPurchaseCoordinator()) {
            $jornadas = Jornada::all();
            $where = 'WHERE 1=1';
            $params = [];
        } elseif (Auth::isJornadaAdmin()) {
            $jornadas = [];
            foreach (Auth::jornadaIds() as $jid) {
                $j = Jornada::find($jid);
                if ($j) $jornadas[] = $j;
            }
            $ids = Auth::jornadaIds();
            $where = $ids ? 'WHERE e.jornada_id IN (' . implode(',', array_map('intval', $ids)) . ')' : 'WHERE 1=0';
            $params = [];
        } elseif (Auth::isCoordinator()) {
            $jornadas = Jornada::all();
            $where = 'WHERE e.mission_id = ?';
            $params = [Auth::missionId()];
        } else {
            $jornadas = Jornada::all();
            $where = 'WHERE 1=1';
            $params = [];
        }
        $row = DB::query(
            "SELECT COALESCE(SUM(e.monto_pagado),0) AS pagado,
                    COALESCE(SUM((e.hallacas_entregadas - e.hallacas_pagadas) * e.precio_unitario),0) AS pendiente
             FROM entregas e $where",
            $params
        )->fetch();
        $totalesPagado = (float)($row['pagado'] ?? 0);
        $totalesPendiente = (float)($row['pendiente'] ?? 0);
        $totalesGeneral = $totalesPagado + $totalesPendiente;

        // Totales por jornada para la lista
        $aggRows = DB::query('SELECT jornada_id, COALESCE(SUM(monto_pagado),0) AS pagado, COALESCE(SUM((hallacas_entregadas - hallacas_pagadas) * precio_unitario),0) AS pendiente FROM entregas GROUP BY jornada_id')->fetchAll();
        $aggMap = [];
        foreach ($aggRows as $ar) $aggMap[(int)$ar['jornada_id']] = $ar;
        foreach ($jornadas as &$j) {
            $a = $aggMap[(int)$j['id']] ?? ['pagado'=>0,'pendiente'=>0];
            $j['pagado'] = (float)$a['pagado'];
            $j['pendiente'] = (float)$a['pendiente'];
            $j['general'] = $j['pagado'] + $j['pendiente'];
        }
        unset($j);

        include BASE_PATH . '/app/views/jornadas/index.php';
    }
    public static function create(): void {
        require_role('admin');
        include BASE_PATH . '/app/views/jornadas/form.php';
    }
    public static function store(): void {
        require_role('admin');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('jornadas'); }
        $totalHechas = self::parseTotalHechasPost();
        Jornada::create([
            'name' => trim($_POST['name'] ?? ''),
            'fecha' => $_POST['fecha'] ?? date('Y-m-d'),
            'precio_hallaca' => (float)($_POST['precio_hallaca'] ?? 1.00),
            'total_hallacas_hechas' => $totalHechas,
            'notes' => trim($_POST['notes'] ?? ''),
            'created_by' => Auth::user()['id'],
        ]);
        flash('success','Jornada creada');
        redirect('jornadas');
    }
    public static function show(): void {
        require_login();
        $id = (int)($_GET['id'] ?? 0);
        $jornada = Jornada::find($id);
        if (!$jornada) { flash('error','Jornada no encontrada'); redirect('jornadas'); }
        if (Auth::isJornadaAdmin() && !in_array($id, Auth::jornadaIds(), true)) { flash('error','No autorizado'); redirect('jornadas'); return; }
        // coordinator can only see their mission's? Show assigned missions; if coordinator, filter missions assigned to them maybe
        $assigned = Jornada::missionsFor($id);
        $allMissions = Mission::all(true);
        // members grouped
        $membersByMission = [];
        foreach ($assigned as $jm) {
            $mid = (int)$jm['mission_id'];
            $membersByMission[(string)$mid] = array_map(
                static fn(array $u): array => ['id' => (int)$u['id'], 'name' => (string)$u['name']],
                User::membersOfMission($mid)
            );
        }
        $entregas = Entrega::allByJornada($id);
        // price
        $precio = price_for_jornada($jornada);
        $inventoryProducts = [];
        $jornadaInventoryOut = [];
        $jornadaInventoryBalance = [];
        $inventoryTablesReady = false;
        if (Auth::canAccessInventory()) {
            try {
                DB::query('SELECT 1 FROM inventory_products LIMIT 1');
                $inventoryTablesReady = true;
                $inventoryProducts = InventoryProduct::all();
                $jornadaInventoryOut = InventoryMovement::byJornada($id);
                $jornadaInventoryBalance = InventoryMovement::balanceByJornada($id);
            } catch (PDOException $e) {
                $inventoryTablesReady = false;
            }
        }
        include BASE_PATH . '/app/views/jornadas/show.php';
    }
    public static function edit(): void {
        require_role('admin');
        $id = (int)($_GET['id'] ?? 0);
        $jornada = Jornada::find($id);
        if (!$jornada) { flash('error','Jornada no encontrada'); redirect('jornadas'); }
        if (Auth::isJornadaAdmin() && !in_array($id, Auth::jornadaIds(), true)) { flash('error','No autorizado'); redirect('jornadas'); return; }
        include BASE_PATH . '/app/views/jornadas/form.php';
    }
    public static function update(): void {
        require_role('admin');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('jornadas'); }
        $id = (int)($_POST['id'] ?? 0);
        $totalHechas = self::parseTotalHechasPost();
        if ($totalHechas !== null) {
            $asignadas = Jornada::totalAsignadas($id);
            if ($asignadas > $totalHechas) {
                flash('error', 'El total hecho no puede ser menor que las hallacas ya asignadas a misiones ('.$asignadas.')');
                redirect('jornada_edit', ['id' => $id]);
                return;
            }
        }
        Jornada::update($id, [
            'name' => trim($_POST['name'] ?? ''),
            'fecha' => $_POST['fecha'] ?? date('Y-m-d'),
            'precio_hallaca' => (float)($_POST['precio_hallaca'] ?? 1.00),
            'total_hallacas_hechas' => $totalHechas,
            'notes' => trim($_POST['notes'] ?? ''),
        ]);
        flash('success','Jornada actualizada');
        redirect('jornada_show', ['id'=>$id]);
    }
    public static function close(): void {
        require_role('admin','coordinator');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('jornadas'); }
        $id = (int)($_POST['id'] ?? 0);
        Jornada::close($id);
        flash('success','Jornada cerrada');
        redirect('jornada_show', ['id'=>$id]);
    }
    public static function reopen(): void {
        require_role('admin');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('jornadas'); }
        $id = (int)($_POST['id'] ?? 0);
        Jornada::reopen($id);
        flash('success','Jornada reabierta');
        redirect('jornada_show', ['id'=>$id]);
    }
    public static function delete(): void {
        require_role('admin');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('jornadas'); }
        $id = (int)($_POST['id'] ?? 0);
        Jornada::delete($id);
        flash('success','Jornada eliminada');
        redirect('jornadas');
    }
    public static function asignar(): void {
        require_role('admin','coordinator');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('jornadas'); }
        $jid = (int)($_POST['jornada_id'] ?? 0);
        $mid = (int)($_POST['mission_id'] ?? 0);
        $hall = (int)($_POST['hallacas_asignadas'] ?? 0);
        $jm = Jornada::missionsFor($jid);
        // coordinator can only assign own mission
        if (!Auth::isAdmin() && Auth::missionId() !== $mid) { flash('error','No autorizado'); redirect('jornada_show',['id'=>$jid]); }
        $jornada = Jornada::find($jid);
        $totalHechas = isset($jornada['total_hallacas_hechas']) && $jornada['total_hallacas_hechas'] !== null && $jornada['total_hallacas_hechas'] !== ''
            ? (int)$jornada['total_hallacas_hechas'] : null;
        if ($totalHechas !== null) {
            $asignadas = Jornada::totalAsignadas($jid);
            if ($asignadas + $hall > $totalHechas) {
                $rest = max(0, $totalHechas - $asignadas);
                flash('error', 'Solo quedan '.$rest.' hallacas por asignar a misiones (total hecho: '.$totalHechas.')');
                redirect('jornada_show', ['id' => $jid]);
                return;
            }
        }
        Jornada::asignarMision($jid, $mid, $hall, trim($_POST['notes'] ?? ''));
        flash('success','Misión asignada a la jornada');
        redirect('jornada_show', ['id'=>$jid]);
    }
    public static function updateTotalHechas(): void {
        require_role('admin', 'jornada_admin');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('jornadas'); }
        $id = (int)($_POST['jornada_id'] ?? 0);
        if (Auth::isJornadaAdmin() && !in_array($id, Auth::jornadaIds(), true)) {
            flash('error', 'No autorizado');
            redirect('jornadas');
            return;
        }
        $totalHechas = self::parseTotalHechasPost();
        if ($totalHechas !== null && $totalHechas < 0) {
            flash('error', 'El total debe ser un número válido');
            redirect('jornada_show', ['id' => $id]);
            return;
        }
        if ($totalHechas !== null) {
            $asignadas = Jornada::totalAsignadas($id);
            if ($asignadas > $totalHechas) {
                flash('error', 'El total hecho no puede ser menor que las hallacas ya asignadas a misiones ('.$asignadas.')');
                redirect('jornada_show', ['id' => $id]);
                return;
            }
        }
        Jornada::setTotalHallacasHechas($id, $totalHechas);
        flash('success', $totalHechas === null ? 'Total de hallacas hechas eliminado' : 'Total de hallacas hechas actualizado');
        redirect('jornada_show', ['id' => $id]);
    }
    private static function parseTotalHechasPost(): ?int {
        if (!array_key_exists('total_hallacas_hechas', $_POST)) return null;
        $raw = trim((string)$_POST['total_hallacas_hechas']);
        if ($raw === '') return null;
        return max(0, (int)$raw);
    }
    public static function quitar(): void {
        require_role('admin','coordinator');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('jornadas'); }
        $jid = (int)($_POST['jornada_id'] ?? 0);
        $mid = (int)($_POST['mission_id'] ?? 0);
        if (!Auth::isAdmin() && Auth::missionId() !== $mid) { flash('error','No autorizado'); redirect('jornada_show',['id'=>$jid]); }
        Jornada::quitarMision($jid,$mid);
        flash('success','Misión removida de la jornada');
        redirect('jornada_show', ['id'=>$jid]);
    }
}
