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

    /** Resuelve misión desde CSV: id, código (M01), nombre o «Misión 3». */
    public static function resolveImportReference(string $cell, bool $onlyActive = true): ?int {
        $cell = trim($cell);
        if ($cell === '') {
            return null;
        }
        $missions = self::all($onlyActive);
        if (ctype_digit($cell)) {
            $id = (int)$cell;
            foreach ($missions as $m) {
                if ((int)$m['id'] === $id) {
                    return $id;
                }
            }
            return null;
        }
        $compact = strtoupper(str_replace([' ', '-', '_'], '', $cell));
        foreach ($missions as $m) {
            $code = strtoupper(str_replace([' ', '-', '_'], '', (string)$m['code']));
            if ($code !== '' && $code === $compact) {
                return (int)$m['id'];
            }
        }
        $lower = mb_strtolower($cell, 'UTF-8');
        foreach ($missions as $m) {
            if (mb_strtolower((string)$m['name'], 'UTF-8') === $lower) {
                return (int)$m['id'];
            }
        }
        if (preg_match('/(\d+)/u', $cell, $mm)) {
            $n = (int)$mm[1];
            $code = 'M' . str_pad((string)$n, 2, '0', STR_PAD_LEFT);
            foreach ($missions as $m) {
                if (strcasecmp((string)$m['code'], $code) === 0) {
                    return (int)$m['id'];
                }
            }
        }
        $byName = self::findIdByName($cell, $onlyActive);
        if ($byName !== null) {
            return $byName;
        }
        return null;
    }

    public static function findIdByName(string $name, bool $onlyActive = true): ?int {
        $name = trim($name);
        if ($name === '') {
            return null;
        }
        $lower = mb_strtolower($name, 'UTF-8');
        foreach (self::all($onlyActive) as $m) {
            if (mb_strtolower((string)$m['name'], 'UTF-8') === $lower) {
                return (int)$m['id'];
            }
        }
        return null;
    }

    /** Crea misión auxiliar (ej. Iglesia) si no existe. */
    public static function ensureByNameAndCode(string $name, string $code): int {
        $id = self::findIdByName($name, false);
        if ($id !== null) {
            return $id;
        }
        $code = strtoupper(trim($code));
        foreach (self::all(false) as $m) {
            if (strcasecmp((string)$m['code'], $code) === 0) {
                return (int)$m['id'];
            }
        }
        return self::create([
            'name' => $name,
            'code' => $code,
            'description' => null,
            'status' => 'active',
        ]);
    }

    public static function resolveForDistributionImport(string $cell): ?int {
        $cell = trim($cell);
        if ($cell === '') {
            return null;
        }
        $id = self::resolveImportReference($cell);
        if ($id !== null) {
            return $id;
        }
        if (mb_strtolower($cell, 'UTF-8') === 'iglesia') {
            return self::ensureByNameAndCode('Iglesia', 'IGLE');
        }
        return self::findIdByName($cell) ?? self::ensureByNameAndCode($cell, self::codeFromName($cell));
    }

    private static function codeFromName(string $name): string {
        $slug = strtoupper(preg_replace('/[^A-Z0-9]+/i', '', mb_substr($name, 0, 8, 'UTF-8')) ?? '');
        if ($slug === '') {
            $slug = 'MISC';
        }
        return substr($slug, 0, 12);
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
