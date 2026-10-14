<?php
class PaymentController {
    public static function index(): void {
        require_login();
        $jornadaId = isset($_GET['jornada_id']) && $_GET['jornada_id'] !== '' ? (int)$_GET['jornada_id'] : null;
        $missionId = isset($_GET['mission_id']) && $_GET['mission_id'] !== '' ? (int)$_GET['mission_id'] : null;

        $where = 'WHERE (e.hallacas_entregadas - e.hallacas_pagadas) > 0';
        $params = [];
        if ($jornadaId) { $where .= ' AND e.jornada_id = ?'; $params[] = $jornadaId; }
        if ($missionId) { $where .= ' AND e.mission_id = ?'; $params[] = $missionId; }
        if (!Auth::isAdmin()) { $where .= ' AND e.mission_id = ?'; $params[] = Auth::missionId(); }

        $pendientes = DB::query(
            "SELECT e.*, u.name AS member_name, m.name AS mission_name, j.name AS jornada_name, j.fecha
             FROM entregas e
             JOIN users u ON u.id=e.member_id
             JOIN missions m ON m.id=e.mission_id
             JOIN jornadas j ON j.id=e.jornada_id
             $where
             ORDER BY j.fecha DESC, m.name, u.name",
            $params
        )->fetchAll();

        // recent payments
        $w2 = 'WHERE 1=1';
        $p2 = [];
        if ($jornadaId) { $w2 .= ' AND e.jornada_id = ?'; $p2[] = $jornadaId; }
        if ($missionId) { $w2 .= ' AND e.mission_id = ?'; $p2[] = $missionId; }
        if (!Auth::isAdmin()) { $w2 .= ' AND e.mission_id = ?'; $p2[] = Auth::missionId(); }
        $recientes = DB::query(
            "SELECT p.*, e.id AS entrega_id, u.name AS member_name, m.name AS mission_name, j.name AS jornada_name, uc.name AS creado_por
             FROM pagos p
             JOIN entregas e ON e.id=p.entrega_id
             JOIN users u ON u.id=e.member_id
             JOIN missions m ON m.id=e.mission_id
             JOIN jornadas j ON j.id=e.jornada_id
             LEFT JOIN users uc ON uc.id=p.created_by
             $w2
             ORDER BY p.fecha DESC
             LIMIT 30",
            $p2
        )->fetchAll();

        $jornadas = Jornada::all();
        $missions = Mission::all(true);
        include BASE_PATH . '/app/views/payments/index.php';
    }
}
