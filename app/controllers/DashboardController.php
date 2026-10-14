<?php
class DashboardController {
    public static function index(): void {
        require_login();
        if (Auth::role() === 'coordinator') {
            self::coordinator();
            return;
        }
        if (Auth::role() === 'member') {
            self::member();
            return;
        }
        self::admin();
    }

    private static function admin(): void {
        $stats = [
            'misiones' => DB::query('SELECT COUNT(*) c FROM missions WHERE status="active"')->fetch()['c'],
            'jornadas' => DB::query('SELECT COUNT(*) c FROM jornadas')->fetch()['c'],
            'miembros' => DB::query('SELECT COUNT(*) c FROM users WHERE role="member" AND status="active"')->fetch()['c'],
            'pagos_pendientes' => DB::query('SELECT COUNT(*) c FROM pagos WHERE status="pendiente"')->fetch()['c'],
        ];
        $jornadas = Jornada::all();
        $missions = Mission::all();
        $missionTotals = [];
        foreach ($missions as $m) {
            $t = Mission::totals((int)$m['id']);
            $t['name'] = $m['name'];
            $missionTotals[] = $t;
        }
        $recent = DB::query('SELECT e.*, u.name AS member_name, m.name AS mission_name, j.name AS jornada_name FROM entregas e JOIN users u ON u.id=e.member_id JOIN missions m ON m.id=e.mission_id JOIN jornadas j ON j.id=e.jornada_id ORDER BY e.created_at DESC LIMIT 10')->fetchAll();
        $pagosPendientes = DB::query('SELECT p.*, e.id entrega_id, u.name member_name, m.name mission_name, j.name jornada_name FROM pagos p JOIN entregas e ON e.id=p.entrega_id JOIN users u ON u.id=e.member_id JOIN missions m ON m.id=e.mission_id JOIN jornadas j ON j.id=e.jornada_id WHERE p.status="pendiente" ORDER BY p.fecha ASC LIMIT 8')->fetchAll();
        include BASE_PATH . '/app/views/dashboard.php';
    }

    private static function coordinator(): void {
        $mid = (int)Auth::missionId();
        $mission = Mission::find($mid);
        // assigned total across jornadas for this mission
        $assigned = (int)DB::query('SELECT COALESCE(SUM(hallacas_asignadas),0) s FROM jornada_mision WHERE mission_id=?', [$mid])->fetch()['s'];
        $deliv = DB::query('SELECT COALESCE(SUM(hallacas_entregadas),0) e, COALESCE(SUM(hallacas_pagadas),0) p, COALESCE(SUM(monto_pagado),0) m FROM entregas WHERE mission_id=?', [$mid])->fetch();
        $entregadas = (int)$deliv['e'];
        $canceladas = (int)$deliv['p'];
        $debe = $entregadas - $canceladas;
        // money pending
        $pendMoney = (float)DB::query('SELECT COALESCE(SUM((hallacas_entregadas-hallacas_pagadas)*precio_unitario),0) s FROM entregas WHERE mission_id=?', [$mid])->fetch()['s'];
        $totalMoney = (float)DB::query('SELECT COALESCE(SUM(hallacas_entregadas*precio_unitario),0) s FROM entregas WHERE mission_id=?', [$mid])->fetch()['s'];
        $cobrado = (float)$deliv['m'];
        $jornadas = DB::query('SELECT j.* FROM jornadas j JOIN jornada_mision jm ON jm.jornada_id=j.id WHERE jm.mission_id=? ORDER BY j.fecha DESC', [$mid])->fetchAll();
        $entregas = DB::query('SELECT e.*, u.name AS member_name, j.name AS jornada_name, j.fecha FROM entregas e JOIN users u ON u.id=e.member_id JOIN jornadas j ON j.id=e.jornada_id WHERE e.mission_id=? ORDER BY j.fecha DESC, u.name', [$mid])->fetchAll();
        $pagosPendientes = DB::query('SELECT p.*, u.name AS member_name, j.name AS jornada_name FROM pagos p JOIN entregas e ON e.id=p.entrega_id JOIN users u ON u.id=e.member_id JOIN jornadas j ON j.id=e.jornada_id WHERE e.mission_id=? AND p.status="pendiente" ORDER BY p.fecha ASC', [$mid])->fetchAll();
        include BASE_PATH . '/app/views/dashboard_coordinator.php';
    }

    private static function member(): void {
        $uid = (int)Auth::user()['id'];
        $entregas = DB::query(
            'SELECT e.*, j.name AS jornada_name, j.fecha, m.name AS mission_name, m.code
             FROM entregas e JOIN jornadas j ON j.id=e.jornada_id JOIN missions m ON m.id=e.mission_id
             WHERE e.member_id=? ORDER BY j.fecha DESC',
            [$uid]
        )->fetchAll();
        include BASE_PATH . '/app/views/dashboard_member.php';
    }
}
