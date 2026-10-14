<?php
class Mission {
    public static function all(bool $onlyActive = false): array {
        $sql = 'SELECT m.*, u.name AS coordinator_name FROM missions m LEFT JOIN users u ON u.id=m.coordinator_id';
        if ($onlyActive) $sql .= ' WHERE m.status="active"';
        $sql .= ' ORDER BY m.name';
        return DB::query($sql)->fetchAll();
    }
    public static function find(int $id): ?array {
        $stmt = DB::query('SELECT m.*, u.name AS coordinator_name FROM missions m LEFT JOIN users u ON u.id=m.coordinator_id WHERE m.id = ?', [$id]);
        $r = $stmt->fetch();
        return $r ?: null;
    }
    public static function create(array $data): int {
        DB::query('INSERT INTO missions (name,code,description,coordinator_id,status) VALUES (?,?,?,?,?)',
            [$data['name'],$data['code'],$data['description'] ?? null, $data['coordinator_id'] ?? null, $data['status'] ?? 'active']);
        return (int)DB::connect()->lastInsertId();
    }
    public static function update(int $id, array $data): void {
        $fields = ['name','code','description','coordinator_id','status'];
        $sets=[]; $p=[];
        foreach ($fields as $f) {
            if (array_key_exists($f,$data)) { $sets[]="$f = ?"; $p[]=$data[$f]; }
        }
        if (!$sets) return;
        $p[]=$id;
        DB::query('UPDATE missions SET '.implode(',',$sets).' WHERE id = ?', $p);
    }
    public static function delete(int $id): void {
        DB::query('DELETE FROM missions WHERE id = ?', [$id]);
    }
    public static function totals(int $missionId): array {
        $row = DB::query(
            'SELECT COALESCE(SUM(jm.hallacas_asignadas),0) AS asignadas,
                    COALESCE((SELECT SUM(e.hallacas_entregadas) FROM entregas e JOIN jornada_mision jm2 ON jm2.jornada_id=e.jornada_id AND jm2.mission_id=e.mission_id WHERE e.mission_id=?),0) AS entregadas,
                    COALESCE((SELECT SUM(e.hallacas_pagadas) FROM entregas e WHERE e.mission_id=?),0) AS pagadas
             FROM missions m LEFT JOIN jornada_mision jm ON jm.mission_id=m.id WHERE m.id=?',
            [$missionId,$missionId,$missionId]
        )->fetch();
        return $row ?: ['asignadas'=>0,'entregadas'=>0,'pagadas'=>0];
    }
}
