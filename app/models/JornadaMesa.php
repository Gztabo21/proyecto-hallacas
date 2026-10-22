<?php
class JornadaMesa {
    public static function tablesReady(): bool {
        try {
            DB::query('SELECT 1 FROM jornada_mesas LIMIT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function forJornada(int $jornadaId): array {
        return DB::query(
            'SELECT * FROM jornada_mesas WHERE jornada_id=? ORDER BY name, id',
            [$jornadaId]
        )->fetchAll();
    }

    public static function find(int $id): ?array {
        $r = DB::query('SELECT * FROM jornada_mesas WHERE id=?', [$id])->fetch();
        return $r ?: null;
    }

    public static function create(int $jornadaId, string $name, ?string $notes = null): int {
        DB::query(
            'INSERT INTO jornada_mesas (jornada_id, name, notes) VALUES (?,?,?)',
            [$jornadaId, $name, $notes]
        );
        return (int)DB::connect()->lastInsertId();
    }

    public static function delete(int $id): void {
        DB::query('DELETE FROM jornada_mesas WHERE id=?', [$id]);
    }

    public static function personasForMesa(int $mesaId): array {
        return DB::query(
            'SELECT p.*, u.name AS user_name FROM jornada_mesa_personas p
             LEFT JOIN users u ON u.id=p.user_id
             WHERE p.mesa_id=? ORDER BY p.persona_nombre, p.id',
            [$mesaId]
        )->fetchAll();
    }

    public static function addPersona(int $mesaId, string $nombre, ?int $userId = null): void {
        DB::query(
            'INSERT INTO jornada_mesa_personas (mesa_id, persona_nombre, user_id) VALUES (?,?,?)',
            [$mesaId, $nombre, $userId]
        );
    }

    public static function deletePersona(int $personaId): void {
        DB::query('DELETE FROM jornada_mesa_personas WHERE id=?', [$personaId]);
    }

    public static function retirosForMesa(int $mesaId): array {
        return DB::query(
            'SELECT r.*, u.name AS created_by_name FROM jornada_mesa_retiros r
             LEFT JOIN users u ON u.id=r.created_by
             WHERE r.mesa_id=? ORDER BY r.retirado_at DESC, r.id DESC',
            [$mesaId]
        )->fetchAll();
    }

    public static function addRetiro(int $mesaId, int $cantidad, string $retiradoAt, ?string $notes, ?int $createdBy): void {
        DB::query(
            'INSERT INTO jornada_mesa_retiros (mesa_id, cantidad, retirado_at, notes, created_by) VALUES (?,?,?,?,?)',
            [$mesaId, $cantidad, $retiradoAt, $notes, $createdBy]
        );
    }

    public static function deleteRetiro(int $retiroId): void {
        DB::query('DELETE FROM jornada_mesa_retiros WHERE id=?', [$retiroId]);
    }

    public static function totalRetiradoJornada(int $jornadaId): int {
        $r = DB::query(
            'SELECT COALESCE(SUM(r.cantidad),0) AS s FROM jornada_mesa_retiros r
             JOIN jornada_mesas m ON m.id=r.mesa_id WHERE m.jornada_id=?',
            [$jornadaId]
        )->fetch();
        return (int)($r['s'] ?? 0);
    }

    /** @return array<int, array{personas: array, retiros: array, total_retirado: int}> */
    public static function bundleForJornada(int $jornadaId): array {
        $mesas = self::forJornada($jornadaId);
        $out = [];
        foreach ($mesas as $m) {
            $mid = (int)$m['id'];
            $retiros = self::retirosForMesa($mid);
            $total = 0;
            foreach ($retiros as $r) {
                $total += (int)$r['cantidad'];
            }
            $out[$mid] = [
                'mesa' => $m,
                'personas' => self::personasForMesa($mid),
                'retiros' => $retiros,
                'total_retirado' => $total,
            ];
        }
        return $out;
    }
}
