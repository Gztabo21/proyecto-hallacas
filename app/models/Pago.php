<?php
class Pago {
    public static function create(array $data): int {
        DB::query(
            'INSERT INTO pagos (entrega_id,hallacas,monto,moneda,metodo,status,notes,created_by) VALUES (?,?,?,?,?,?,?,?)',
            [
                $data['entrega_id'],
                $data['hallacas'] ?? 0,
                $data['monto'] ?? 0,
                $data['moneda'] ?? 'VES',
                $data['metodo'] ?? null,
                $data['status'] ?? 'pendiente',
                $data['notes'] ?? null,
                $data['created_by'] ?? null
            ]
        );
        return (int)DB::connect()->lastInsertId();
    }

    public static function byEntrega(int $entregaId): array {
        return DB::query(
            'SELECT p.*, u.name AS created_by_name, uc.name AS confirmado_por_name
             FROM pagos p
             LEFT JOIN users u ON u.id=p.created_by
             LEFT JOIN users uc ON uc.id=p.confirmado_por
             WHERE p.entrega_id=? ORDER BY p.fecha DESC',
            [$entregaId]
        )->fetchAll();
    }

    public static function find(int $id): ?array {
        $stmt = DB::query(
            'SELECT p.*, u.name AS created_by_name, uc.name AS confirmado_por_name
             FROM pagos p
             LEFT JOIN users u ON u.id=p.created_by
             LEFT JOIN users uc ON uc.id=p.confirmado_por
             WHERE p.id=?',
            [$id]
        );
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public static function pendientes(?int $missionId = null): array {
        $sql = 'SELECT p.*, e.id AS entrega_id, u.name AS member_name, m.name AS mission_name, j.name AS jornada_name, j.fecha, uc.name AS confirmado_por_name
                FROM pagos p
                JOIN entregas e ON e.id=p.entrega_id
                JOIN users u ON u.id=e.member_id
                JOIN missions m ON m.id=e.mission_id
                JOIN jornadas j ON j.id=e.jornada_id
                LEFT JOIN users uc ON uc.id=p.confirmado_por
                WHERE p.status="pendiente"';
        $params = [];
        if ($missionId) { $sql .= ' AND e.mission_id=?'; $params[] = $missionId; }
        $sql .= ' ORDER BY p.fecha ASC';
        return DB::query($sql, $params)->fetchAll();
    }

    public static function confirmar(int $id, int $adminId): bool {
        $p = self::find($id);
        if (!$p || $p['status'] !== 'pendiente') return false;
        DB::query(
            'UPDATE pagos SET status="confirmado", confirmado_por=?, confirmado_at=NOW() WHERE id=?',
            [$adminId, $id]
        );
        // update entrega totals
        $e = Entrega::find((int)$p['entrega_id']);
        if ($e) {
            $newHp = (int)$e['hallacas_pagadas'] + (int)$p['hallacas'];
            if ($newHp > (int)$e['hallacas_entregadas']) $newHp = (int)$e['hallacas_entregadas'];
            $newMonto = (float)$e['monto_pagado'] + (float)$p['monto'];
            $total = (int)$e['hallacas_entregadas'];
            $status = 'pendiente';
            if ($newHp >= $total && $total > 0) $status = 'pagado';
            elseif ($newHp > 0) $status = 'parcial';
            DB::query(
                'UPDATE entregas SET hallacas_pagadas=?, monto_pagado=?, status=? WHERE id=?',
                [$newHp, $newMonto, $status, $e['id']]
            );
        }
        return true;
    }

    public static function totalConfirmadoPorEntrega(int $entregaId): array {
        return DB::query(
            'SELECT COALESCE(SUM(hallacas),0) AS h, COALESCE(SUM(monto),0) AS m
             FROM pagos WHERE entrega_id=? AND status="confirmado"',
            [$entregaId]
        )->fetch();
    }
}
