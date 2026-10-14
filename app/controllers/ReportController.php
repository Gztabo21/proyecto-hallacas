<?php
class ReportController {
    public static function index(): void {
        require_login();
        $jornadaId = isset($_GET['jornada_id']) && $_GET['jornada_id'] !== '' ? (int)$_GET['jornada_id'] : null;
        $missionId = isset($_GET['mission_id']) && $_GET['mission_id'] !== '' ? (int)$_GET['mission_id'] : null;

        // By mission in jornada
        $sql = 'SELECT jm.jornada_id, j.name AS jornada_name, j.fecha, m.id AS mission_id, m.name AS mission_name,
                jm.hallacas_asignadas,
                COALESCE(SUM(e.hallacas_entregadas),0) AS entregadas,
                COALESCE(SUM(e.hallacas_pagadas),0) AS pagadas,
                COALESCE(SUM(e.monto_pagado),0) AS monto_pagado
               FROM jornada_mision jm
               JOIN jornadas j ON j.id=jm.jornada_id
               JOIN missions m ON m.id=jm.mission_id
               LEFT JOIN entregas e ON e.jornada_id=jm.jornada_id AND e.mission_id=jm.mission_id
               WHERE 1=1';
        $p = [];
        if ($jornadaId) { $sql .= ' AND jm.jornada_id=?'; $p[] = $jornadaId; }
        if ($missionId) { $sql .= ' AND jm.mission_id=?'; $p[] = $missionId; }
        if (!Auth::isAdmin()) {
            $sql .= ' AND jm.mission_id=?';
            $p[] = Auth::missionId();
        }
        $sql .= ' GROUP BY jm.jornada_id, jm.mission_id ORDER BY j.fecha DESC, m.name';
        $porMision = DB::query($sql, $p)->fetchAll();

        // By member
        $sql2 = 'SELECT e.member_id, u.name AS member_name, m.name AS mission_name,
                 SUM(e.hallacas_entregadas) AS entregadas,
                 SUM(e.hallacas_pagadas) AS pagadas,
                 SUM(e.monto_pagado) AS monto_pagado,
                 COUNT(DISTINCT e.jornada_id) AS jornadas
                FROM entregas e
                JOIN users u ON u.id=e.member_id
                JOIN missions m ON m.id=e.mission_id
                WHERE 1=1';
        $p2 = [];
        if ($jornadaId) { $sql2 .= ' AND e.jornada_id=?'; $p2[] = $jornadaId; }
        if ($missionId) { $sql2 .= ' AND e.mission_id=?'; $p2[] = $missionId; }
        if (!Auth::isAdmin()) { $sql2 .= ' AND e.mission_id=?'; $p2[] = Auth::missionId(); }
        $sql2 .= ' GROUP BY e.member_id ORDER BY u.name';
        $porMiembro = DB::query($sql2, $p2)->fetchAll();

        $jornadas = Jornada::all();
        $missions = Mission::all(true);
        include BASE_PATH . '/app/views/reports/index.php';
    }
}
