<?php
class PaymentController {
    public static function index(): void {
        require_role('admin','coordinator');
        $jornadaId = isset($_GET['jornada_id']) && $_GET['jornada_id'] !== '' ? (int)$_GET['jornada_id'] : null;
        $missionId = isset($_GET['mission_id']) && $_GET['mission_id'] !== '' ? (int)$_GET['mission_id'] : null;

        $where = 'WHERE (e.hallacas_entregadas - e.hallacas_pagadas) > 0';
        $params = [];
        if ($jornadaId) { $where .= ' AND e.jornada_id = ?'; $params[] = $jornadaId; }
        if ($missionId) { $where .= ' AND e.mission_id = ?'; $params[] = $missionId; }
        if (!Auth::isAdmin()) { $where .= ' AND e.mission_id = ?'; $params[] = Auth::missionId(); }

        $perCancelar = 10;
        $perConfirmar = 10;
        $perRecientes = 10;
        $pageCancelar = pagination_page('pc');
        $pageConfirmar = pagination_page('pf');
        $pageRecientes = pagination_page('pr');

        $totalPendientes = (int)DB::query(
            "SELECT COUNT(*) c FROM entregas e $where",
            $params
        )->fetch()['c'];
        $pendientes = DB::query(
            "SELECT e.*, u.name AS member_name, m.name AS mission_name, j.name AS jornada_name, j.fecha
             FROM entregas e
             JOIN users u ON u.id=e.member_id
             JOIN missions m ON m.id=e.mission_id
             JOIN jornadas j ON j.id=e.jornada_id
             $where
             ORDER BY j.fecha DESC, m.name, u.name
             LIMIT " . (int)$perCancelar . ' OFFSET ' . (int)(($pageCancelar - 1) * $perCancelar),
            $params
        )->fetchAll();

        // pagos pendientes por confirmar (admin confirma)
        $whereP = 'WHERE p.status="pendiente"';
        $paramsP = [];
        if ($jornadaId) { $whereP .= ' AND e.jornada_id = ?'; $paramsP[] = $jornadaId; }
        if ($missionId) { $whereP .= ' AND e.mission_id = ?'; $paramsP[] = $missionId; }
        if (!Auth::isAdmin()) { $whereP .= ' AND e.mission_id = ?'; $paramsP[] = Auth::missionId(); }
        $totalPagosPendientes = (int)DB::query(
            "SELECT COUNT(*) c FROM pagos p JOIN entregas e ON e.id=p.entrega_id $whereP",
            $paramsP
        )->fetch()['c'];
        $pagosPendientes = DB::query(
            "SELECT p.*, e.id AS entrega_id, u.name AS member_name, m.name AS mission_name, j.name AS jornada_name, j.fecha, uc.name AS creado_por
             FROM pagos p
             JOIN entregas e ON e.id=p.entrega_id
             JOIN users u ON u.id=e.member_id
             JOIN missions m ON m.id=e.mission_id
             JOIN jornadas j ON j.id=e.jornada_id
             LEFT JOIN users uc ON uc.id=p.created_by
             $whereP
             ORDER BY p.fecha ASC
             LIMIT " . (int)$perConfirmar . ' OFFSET ' . (int)(($pageConfirmar - 1) * $perConfirmar),
            $paramsP
        )->fetchAll();

        // recent payments
        $w2 = 'WHERE 1=1';
        $p2 = [];
        if ($jornadaId) { $w2 .= ' AND e.jornada_id = ?'; $p2[] = $jornadaId; }
        if ($missionId) { $w2 .= ' AND e.mission_id = ?'; $p2[] = $missionId; }
        if (!Auth::isAdmin()) { $w2 .= ' AND e.mission_id = ?'; $p2[] = Auth::missionId(); }
        $totalRecientes = (int)DB::query(
            "SELECT COUNT(*) c FROM pagos p JOIN entregas e ON e.id=p.entrega_id $w2",
            $p2
        )->fetch()['c'];
        $recientes = DB::query(
            "SELECT p.*, e.id AS entrega_id, u.name AS member_name, m.name AS mission_name, j.name AS jornada_name, uc.name AS creado_por, ucf.name AS confirmado_por_name
             FROM pagos p
             JOIN entregas e ON e.id=p.entrega_id
             JOIN users u ON u.id=e.member_id
             JOIN missions m ON m.id=e.mission_id
             JOIN jornadas j ON j.id=e.jornada_id
             LEFT JOIN users uc ON uc.id=p.created_by
             LEFT JOIN users ucf ON ucf.id=p.confirmado_por
             $w2
             ORDER BY p.fecha DESC
             LIMIT " . (int)$perRecientes . ' OFFSET ' . (int)(($pageRecientes - 1) * $perRecientes),
            $p2
        )->fetchAll();

        $filterPreserve = pagination_query_preserve([
            'jornada_id' => $jornadaId,
            'mission_id' => $missionId,
        ]);
        $pagination = [
            'confirmar' => ['total' => $totalPagosPendientes, 'page' => $pageConfirmar, 'per' => $perConfirmar, 'param' => 'pf'],
            'cancelar' => ['total' => $totalPendientes, 'page' => $pageCancelar, 'per' => $perCancelar, 'param' => 'pc'],
            'recientes' => ['total' => $totalRecientes, 'page' => $pageRecientes, 'per' => $perRecientes, 'param' => 'pr'],
            'params' => ['pf', 'pc', 'pr'],
            'filters' => $filterPreserve,
        ];

        $jornadas = Jornada::all();
        $missions = Mission::all(true);
        include BASE_PATH . '/app/views/payments/index.php';
    }

    public static function confirm(): void {
        require_role('admin');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('payments'); }
        $id = (int)($_POST['pago_id'] ?? 0);
        if (Pago::confirmar($id, (int)Auth::user()['id'])) {
            flash('success','Pago confirmado y aplicado a la entrega');
        } else {
            flash('error','No se pudo confirmar el pago');
        }
        redirect('payments');
    }


    public static function storeMethod(): void {
        require_role('admin');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('payments'); }
        $method = trim($_POST['new_payment_method'] ?? '');
        $currency = (($_POST['new_payment_method_currency'] ?? 'VES') === 'USD') ? 'USD' : 'VES';
        if ($method === '') { flash('error','Indique el nombre del método'); redirect('payments'); return; }
        $methods = payment_methods();
        $names = array_column($methods, 'name');
        if (!in_array($method, $names, true)) {
            $methods[] = ['name' => $method, 'currency' => $currency];
            set_setting('payment_methods', json_encode($methods, JSON_UNESCAPED_UNICODE));
        }
        flash('success','Método de pago agregado');
        redirect('payments');
    }
}
