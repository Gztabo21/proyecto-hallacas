<?php
class DashboardController {
    public static function index(): void {
        require_login();
        // Stats
        $stats = [
            'misiones' => DB::query('SELECT COUNT(*) c FROM missions WHERE status="active"')->fetch()['c'],
            'jornadas' => DB::query('SELECT COUNT(*) c FROM jornadas')->fetch()['c'],
            'miembros' => DB::query('SELECT COUNT(*) c FROM users WHERE role="member" AND status="active"')->fetch()['c'],
        ];
        $jornadas = Jornada::all();
        $missions = Mission::all();
        // totals per mission
        $missionTotals = [];
        foreach ($missions as $m) {
            $t = Mission::totals((int)$m['id']);
            $t['name'] = $m['name'];
            $missionTotals[] = $t;
        }
        // recent deliveries
        $recent = DB::query('SELECT e.*, u.name AS member_name, m.name AS mission_name, j.name AS jornada_name FROM entregas e JOIN users u ON u.id=e.member_id JOIN missions m ON m.id=e.mission_id JOIN jornadas j ON j.id=e.jornada_id ORDER BY e.created_at DESC LIMIT 10')->fetchAll();
        include BASE_PATH . '/app/views/dashboard.php';
    }
}
