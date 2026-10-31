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
        $aggMap = Entrega::aggregateMoneyByJornada($where, $params);
        foreach ($jornadas as &$j) {
            $a = $aggMap[(int)$j['id']] ?? [
                'general' => 0.0,
                'pagado' => 0.0,
                'pendiente' => 0.0,
                'hallacas_entregadas' => 0,
                'hallacas_pagadas' => 0,
            ];
            $j['general'] = (float)$a['general'];
            $j['pagado'] = (float)$a['pagado'];
            $j['pendiente'] = (float)$a['pendiente'];
            $j['hallacas_entregadas'] = (int)$a['hallacas_entregadas'];
            $j['hallacas_pagadas'] = (int)$a['hallacas_pagadas'];
            $precio = (float)($j['precio_hallaca'] ?? 0);
            $hechas = Jornada::totalHechasFromRow($j);
            $j['hallacas_realizadas'] = $hechas;
            $j['valor_producido_bs'] = ($hechas !== null && $precio > 0) ? round($hechas * $precio, 2) : null;
        }
        unset($j);

        // Totales de las tarjetas = suma de las filas visibles (siempre cuadra con la tabla).
        $totalesGeneral = 0.0;
        $totalesPagado = 0.0;
        $totalesPendiente = 0.0;
        $totalesHallacasEntregadas = 0;
        $totalesHallacasRealizadas = 0;
        $jornadasSinTotalHechas = 0;
        foreach ($jornadas as $j) {
            $totalesGeneral += (float)($j['general'] ?? 0);
            $totalesPagado += (float)($j['pagado'] ?? 0);
            $totalesPendiente += (float)($j['pendiente'] ?? 0);
            $totalesHallacasEntregadas += (int)($j['hallacas_entregadas'] ?? 0);
            if ($j['hallacas_realizadas'] !== null) {
                $totalesHallacasRealizadas += (int)$j['hallacas_realizadas'];
            } else {
                $jornadasSinTotalHechas++;
            }
        }

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
                static fn(array $u): array => [
                    'id' => (int)$u['id'],
                    'name' => (string)$u['name'] . (($u['role'] ?? '') === 'coordinator' ? ' (Coordinador)' : ''),
                ],
                User::distributionRecipientsForMission($mid)
            );
        }
        $entregas = Entrega::allByJornada($id);
        // price
        $precio = price_for_jornada($jornada);
        $inventoryProducts = [];
        $jornadaInventoryOut = [];
        $jornadaInventoryBalance = [];
        $inventoryCostByProduct = [];
        $inventoryTablesReady = false;
        if (Auth::canAccessInventory()) {
            try {
                DB::query('SELECT 1 FROM inventory_products LIMIT 1');
                $inventoryTablesReady = true;
                InventoryMovement::ensureJornadaCostsFrozen();
                $inventoryProducts = InventoryProduct::all();
                $jornadaInventoryOut = InventoryMovement::byJornada($id);
                $jornadaInventoryBalance = InventoryMovement::balanceByJornada($id);
                $inventoryCostByProduct = InventoryMovement::latestCostByProduct();
            } catch (PDOException $e) {
                $inventoryTablesReady = false;
            }
        }
        $jornadaTabPagos = PaymentController::canAccessPaymentsModule();
        $paymentsPanel = null;
        if ($jornadaTabPagos) {
            $missionFilter = null;
            if (Auth::isCoordinator() && !Auth::isAdmin()) {
                $missionFilter = Auth::missionId();
            } elseif (Auth::isAdmin() && isset($_GET['mission_id']) && $_GET['mission_id'] !== '') {
                $missionFilter = (int)$_GET['mission_id'];
            }
            $paymentsPanel = PaymentController::indexData(
                $id,
                $missionFilter,
                'jornada_show',
                ['id' => $id, 'tab' => 'pagos']
            );
        }
        $jornadaTabProduccion = Auth::isAdmin()
            || (Auth::isJornadaAdmin() && in_array($id, Auth::jornadaIds(), true));
        $produccionTablesReady = false;
        $produccionMesas = [];
        $produccionTotalRetirado = 0;
        $produccionUsers = [];
        if ($jornadaTabProduccion) {
            $produccionTablesReady = JornadaMesa::ensureTables();
            if ($produccionTablesReady) {
                $produccionMesas = JornadaMesa::bundleForJornada($id);
                $produccionTotalRetirado = JornadaMesa::totalRetiradoJornada($id);
                $produccionUsers = User::all('', null);
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
        require_role('admin', 'coordinator', 'jornada_admin');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('jornadas'); }
        $jid = (int)($_POST['jornada_id'] ?? 0);
        $mid = (int)($_POST['mission_id'] ?? 0);
        $hall = (int)($_POST['hallacas_asignadas'] ?? 0);
        if (Auth::isJornadaAdmin() && !in_array($jid, Auth::jornadaIds(), true)) {
            flash('error', 'No autorizado');
            redirect('jornadas');
            return;
        }
        // Coordinador solo puede asignar su misión; admin y encargado de jornada, cualquier misión.
        if (Auth::isCoordinator() && !Auth::isAdmin() && Auth::missionId() !== $mid) {
            flash('error', 'No autorizado');
            redirect('jornada_show', ['id' => $jid]);
            return;
        }
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
    public static function corregirAsignacion(): void {
        require_role('admin', 'coordinator');
        if (!csrf_verify()) {
            flash('error', 'CSRF inválido');
            redirect('jornadas');
        }
        $jid = (int)($_POST['jornada_id'] ?? 0);
        $mid = (int)($_POST['mission_id'] ?? 0);
        if (!Auth::isAdmin() && Auth::missionId() !== $mid) {
            flash('error', 'No autorizado');
            redirect('jornada_show', ['id' => $jid]);
            return;
        }
        $jornada = Jornada::find($jid);
        if (!$jornada) {
            flash('error', 'Jornada no encontrada');
            redirect('jornadas');
            return;
        }
        if (($jornada['status'] ?? '') !== 'open' && !Auth::isAdmin()) {
            flash('error', 'La jornada está cerrada; solo administrador puede corregir');
            redirect('jornada_show', ['id' => $jid]);
            return;
        }
        $jmRow = null;
        foreach (Jornada::missionsFor($jid) as $jm) {
            if ((int)$jm['mission_id'] === $mid) {
                $jmRow = $jm;
                break;
            }
        }
        if (!$jmRow) {
            flash('error', 'La misión no está asignada a esta jornada');
            redirect('jornada_show', ['id' => $jid]);
            return;
        }
        $nueva = (int)($_POST['hallacas_asignadas'] ?? -1);
        if ($nueva < 0) {
            flash('error', 'Indique la cantidad asignada');
            redirect('jornada_show', ['id' => $jid]);
            return;
        }
        $entregadas = (int)DB::query(
            'SELECT COALESCE(SUM(hallacas_entregadas),0) AS s FROM entregas WHERE jornada_id=? AND mission_id=?',
            [$jid, $mid]
        )->fetch()['s'];
        if ($nueva < $entregadas) {
            flash('error', 'No puede asignar menos de lo ya entregado a miembros ('.$entregadas.')');
            redirect('jornada_show', ['id' => $jid]);
            return;
        }
        $actual = (int)$jmRow['hallacas_asignadas'];
        $totalHechas = isset($jornada['total_hallacas_hechas']) && $jornada['total_hallacas_hechas'] !== null && $jornada['total_hallacas_hechas'] !== ''
            ? (int)$jornada['total_hallacas_hechas'] : null;
        if ($totalHechas !== null) {
            $nuevoTotal = Jornada::totalAsignadas($jid) - $actual + $nueva;
            if ($nuevoTotal > $totalHechas) {
                $max = max($entregadas, $totalHechas - (Jornada::totalAsignadas($jid) - $actual));
                flash('error', 'Supera el total hecho de la jornada. Máximo para esta misión: '.$max);
                redirect('jornada_show', ['id' => $jid]);
                return;
            }
        }
        $notaCorreccion = trim($_POST['correccion_nota'] ?? '');
        $notes = trim((string)($jmRow['notes'] ?? ''));
        if ($notaCorreccion !== '') {
            $line = 'Corrección asignación: ' . $notaCorreccion;
            $notes = $notes === '' ? $line : $notes . "\n" . $line;
        }
        Jornada::setMissionAsignadas($jid, $mid, $nueva, $notaCorreccion !== '' ? $notes : null);
        flash('success', 'Asignación de la misión actualizada');
        redirect('jornada_show', ['id' => $jid]);
    }
}
