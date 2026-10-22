<?php
class Jornada {
    public static function all(): array {
        return DB::query('SELECT j.*, u.name AS created_by_name FROM jornadas j LEFT JOIN users u ON u.id=j.created_by ORDER BY j.fecha DESC, j.id DESC')->fetchAll();
    }
    public static function find(int $id): ?array {
        $stmt = DB::query('SELECT j.*, u.name AS created_by_name FROM jornadas j LEFT JOIN users u ON u.id=j.created_by WHERE j.id = ?', [$id]);
        $r = $stmt->fetch();
        return $r ?: null;
    }
    public static function create(array $data): int {
        $totalHechas = array_key_exists('total_hallacas_hechas', $data) ? $data['total_hallacas_hechas'] : null;
        DB::query('INSERT INTO jornadas (name,fecha,precio_hallaca,total_hallacas_hechas,status,notes,created_by) VALUES (?,?,?,?,?,?,?)',
            [$data['name'],$data['fecha'],$data['precio_hallaca'] ?? 1.00,$totalHechas,'open',$data['notes'] ?? null, $data['created_by'] ?? null]);
        return (int)DB::connect()->lastInsertId();
    }
    public static function close(int $id): void { DB::query('UPDATE jornadas SET status="closed", closed_at=NOW() WHERE id=?', [$id]); }
    public static function reopen(int $id): void { DB::query('UPDATE jornadas SET status="open", closed_at=NULL WHERE id=?', [$id]); }
    public static function totalAsignadas(int $jornadaId): int {
        $r = DB::query('SELECT COALESCE(SUM(hallacas_asignadas),0) AS s FROM jornada_mision WHERE jornada_id=?', [$jornadaId])->fetch();
        return (int)$r['s'];
    }
    public static function totalEntregadas(int $jornadaId): int {
        $r = DB::query('SELECT COALESCE(SUM(hallacas_entregadas),0) AS s FROM entregas WHERE jornada_id=?', [$jornadaId])->fetch();
        return (int)$r['s'];
    }
    public static function setTotalHallacasHechas(int $id, ?int $total): void {
        DB::query('UPDATE jornadas SET total_hallacas_hechas=? WHERE id=?', [$total, $id]);
    }
    public static function update(int $id, array $data): void {
        $fields = ['name','fecha','precio_hallaca','total_hallacas_hechas','notes'];
        $sets=[]; $p=[];
        foreach ($fields as $f) { if (array_key_exists($f,$data)) { $sets[]="$f = ?"; $p[]=$data[$f]; } }
        if (!$sets) return;
        $p[]=$id;
        DB::query('UPDATE jornadas SET '.implode(',',$sets).' WHERE id=?', $p);
    }
    public static function delete(int $id): void { DB::query('DELETE FROM jornadas WHERE id=?', [$id]); }
    public static function missionsFor(int $jornadaId): array {
        return DB::query('SELECT jm.*, m.name AS mission_name, m.code FROM jornada_mision jm JOIN missions m ON m.id=jm.mission_id WHERE jm.jornada_id=? ORDER BY m.name', [$jornadaId])->fetchAll();
    }
    public static function asignarMision(int $jornadaId, int $missionId, int $hallacas, ?string $notes = null): void {
        DB::query('INSERT INTO jornada_mision (jornada_id,mission_id,hallacas_asignadas,notes) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE hallacas_asignadas=hallacas_asignadas+VALUES(hallacas_asignadas), notes=VALUES(notes)',
            [$jornadaId,$missionId,$hallacas,$notes]);
    }
    public static function setMissionAsignadas(int $jornadaId, int $missionId, int $hallacas, ?string $notes = null): void {
        if ($notes !== null) {
            DB::query('UPDATE jornada_mision SET hallacas_asignadas=?, notes=? WHERE jornada_id=? AND mission_id=?',
                [$hallacas, $notes, $jornadaId, $missionId]);
        } else {
            DB::query('UPDATE jornada_mision SET hallacas_asignadas=? WHERE jornada_id=? AND mission_id=?',
                [$hallacas, $jornadaId, $missionId]);
        }
    }
    public static function quitarMision(int $jornadaId, int $missionId): void {
        DB::query('DELETE FROM jornada_mision WHERE jornada_id=? AND mission_id=?', [$jornadaId,$missionId]);
        DB::query('DELETE FROM entregas WHERE jornada_id=? AND mission_id=?', [$jornadaId,$missionId]);
    }
}
