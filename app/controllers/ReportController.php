<?php
class ReportController {
    /** @return array{jornadaId:?int,missionId:?int} */
    private static function filtersFromRequest(): array {
        $jornadaId = isset($_GET['jornada_id']) && $_GET['jornada_id'] !== '' ? (int)$_GET['jornada_id'] : null;
        $missionId = isset($_GET['mission_id']) && $_GET['mission_id'] !== '' ? (int)$_GET['mission_id'] : null;
        return ['jornadaId' => $jornadaId, 'missionId' => $missionId];
    }

    /** @return array{porMision:array,porMiembro:array} */
    private static function fetchData(?int $jornadaId, ?int $missionId): array {
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
        $sql .= ' GROUP BY jm.jornada_id, jm.mission_id, j.name, j.fecha, m.name, jm.hallacas_asignadas ORDER BY j.fecha DESC, m.name';
        $porMision = DB::query($sql, $p)->fetchAll();

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
        $sql2 .= ' GROUP BY e.member_id, e.mission_id, u.name, m.name ORDER BY u.name';
        $porMiembro = DB::query($sql2, $p2)->fetchAll();

        return ['porMision' => $porMision, 'porMiembro' => $porMiembro];
    }

    private static function filterLabels(?int $jornadaId, ?int $missionId, array $jornadas, array $missions): array {
        $jLabel = 'Todas';
        $mLabel = 'Todas';
        if ($jornadaId) {
            foreach ($jornadas as $j) {
                if ((int)$j['id'] === $jornadaId) {
                    $jLabel = $j['name'] . ' (' . $j['fecha'] . ')';
                    break;
                }
            }
        }
        if ($missionId) {
            foreach ($missions as $m) {
                if ((int)$m['id'] === $missionId) {
                    $mLabel = $m['name'];
                    break;
                }
            }
        }
        return ['jornada' => $jLabel, 'mision' => $mLabel];
    }

    public static function index(): void {
        require_login();
        if (!Auth::isAdmin() && Auth::role() !== 'coordinator') {
            http_response_code(403);
            die('Acceso denegado.');
        }
        $f = self::filtersFromRequest();
        $data = self::fetchData($f['jornadaId'], $f['missionId']);
        $porMision = $data['porMision'];
        $porMiembro = $data['porMiembro'];
        $jornadas = Jornada::all();
        $missions = Mission::all(true);
        include BASE_PATH . '/app/views/reports/index.php';
    }

    public static function pdf(): void {
        require_login();
        if (!Auth::isAdmin() && Auth::role() !== 'coordinator') {
            http_response_code(403);
            die('Acceso denegado.');
        }
        if (!class_exists(\Dompdf\Dompdf::class)) {
            flash('error', 'PDF no disponible: falta vendor/autoload.php. Con Docker: docker compose up -d --build (o docker compose exec app composer install). Sin Docker: composer install en la raíz del proyecto.');
            $f = self::filtersFromRequest();
            $params = [];
            if ($f['jornadaId']) {
                $params['jornada_id'] = $f['jornadaId'];
            }
            if ($f['missionId']) {
                $params['mission_id'] = $f['missionId'];
            }
            redirect('reports', $params);
            return;
        }
        $f = self::filtersFromRequest();
        $data = self::fetchData($f['jornadaId'], $f['missionId']);
        $porMision = $data['porMision'];
        $porMiembro = $data['porMiembro'];
        $jornadas = Jornada::all();
        $missions = Mission::all(true);
        $filterLabels = self::filterLabels($f['jornadaId'], $f['missionId'], $jornadas, $missions);
        $generatedAt = date('Y-m-d H:i');

        ob_start();
        include BASE_PATH . '/app/views/reports/pdf.php';
        $html = ob_get_clean();

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $fname = 'reporte-hallacas-' . date('Y-m-d') . '.pdf';
        $dompdf->stream($fname, ['Attachment' => true]);
        exit;
    }
}
