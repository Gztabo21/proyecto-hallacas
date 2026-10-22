<?php
class Pago {
    public static function create(array $data): int {
        DB::query(
            'INSERT INTO pagos (entrega_id,hallacas,monto,moneda,metodo,referencia,tasa,captura,status,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
            [
                $data['entrega_id'],
                $data['hallacas'] ?? 0,
                $data['monto'] ?? 0,
                $data['moneda'] ?? 'VES',
                $data['metodo'] ?? null,
                isset($data['referencia']) && $data['referencia'] !== '' ? $data['referencia'] : null,
                isset($data['tasa']) && $data['tasa'] !== '' ? (float)$data['tasa'] : null,
                $data['captura'] ?? null,
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

    public static function findDetailed(int $id): ?array {
        $stmt = DB::query(
            'SELECT p.*, e.jornada_id, e.mission_id, u.name AS member_name, m.name AS mission_name,
                    j.name AS jornada_name, j.fecha AS jornada_fecha,
                    uc.name AS creado_por, ucf.name AS confirmado_por_name
             FROM pagos p
             JOIN entregas e ON e.id = p.entrega_id
             JOIN users u ON u.id = e.member_id
             JOIN missions m ON m.id = e.mission_id
             JOIN jornadas j ON j.id = e.jornada_id
             LEFT JOIN users uc ON uc.id = p.created_by
             LEFT JOIN users ucf ON ucf.id = p.confirmado_por
             WHERE p.id = ?',
            [$id]
        );
        $r = $stmt->fetch();
        return $r ?: null;
    }

    /** Monto en USD si el pago es en dólares; si es VES con tasa, conversión estimada. */
    public static function montoUsd(array $p): ?float {
        $monto = (float)($p['monto'] ?? 0);
        $moneda = (string)($p['moneda'] ?? 'VES');
        if ($moneda === 'USD') {
            return $monto;
        }
        $tasa = isset($p['tasa']) && $p['tasa'] !== '' && $p['tasa'] !== null ? (float)$p['tasa'] : 0.0;
        if ($tasa > 0) {
            return round($monto / $tasa, 2);
        }
        return null;
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

    public static function syncEntregaConfirmados(int $entregaId): void {
        $e = Entrega::find($entregaId);
        if (!$e) {
            return;
        }
        $tot = self::totalConfirmadoPorEntrega($entregaId);
        $totalEnt = (int)$e['hallacas_entregadas'];
        $newHp = min($totalEnt, (int)$tot['h']);
        $newMonto = (float)$tot['m'];
        $status = 'pendiente';
        if ($newHp >= $totalEnt && $totalEnt > 0) {
            $status = 'pagado';
        } elseif ($newHp > 0) {
            $status = 'parcial';
        }
        DB::query(
            'UPDATE entregas SET hallacas_pagadas=?, monto_pagado=?, status=? WHERE id=?',
            [$newHp, $newMonto, $status, $entregaId]
        );
    }

    /** @return string|null Mensaje de error o null si ok */
    public static function update(int $id, array $data): ?string {
        $p = self::find($id);
        if (!$p) {
            return 'Pago no encontrado';
        }
        $hall = array_key_exists('hallacas', $data) ? (int)$data['hallacas'] : (int)$p['hallacas'];
        $monto = array_key_exists('monto', $data) ? (float)$data['monto'] : (float)$p['monto'];
        if ($hall < 0 || $monto < 0) {
            return 'Indique cantidades válidas';
        }
        $e = Entrega::find((int)$p['entrega_id']);
        if (!$e) {
            return 'Entrega no encontrada';
        }
        if (($p['status'] ?? '') === 'confirmado') {
            $tot = self::totalConfirmadoPorEntrega((int)$p['entrega_id']);
            $otherHall = (int)$tot['h'] - (int)$p['hallacas'];
            if ($otherHall + $hall > (int)$e['hallacas_entregadas']) {
                return 'Las hallacas del pago superan lo entregado al miembro (máx. ' . (int)$e['hallacas_entregadas'] . ')';
            }
        }
        $moneda = $data['moneda'] ?? ($p['moneda'] ?? 'VES');
        $moneda = $moneda === 'USD' ? 'USD' : 'VES';
        $metodo = array_key_exists('metodo', $data) ? ($data['metodo'] ?: null) : $p['metodo'];
        $referencia = array_key_exists('referencia', $data)
            ? ($data['referencia'] !== '' ? $data['referencia'] : null)
            : $p['referencia'];
        $tasa = array_key_exists('tasa', $data)
            ? ($data['tasa'] !== null && $data['tasa'] !== '' ? (float)$data['tasa'] : null)
            : ($p['tasa'] !== null && $p['tasa'] !== '' ? (float)$p['tasa'] : null);
        $notes = array_key_exists('notes', $data) ? $data['notes'] : $p['notes'];
        if (!empty($data['correccion_nota'])) {
            $line = 'Corrección: ' . trim((string)$data['correccion_nota']);
            $prev = trim((string)($notes ?? ''));
            $notes = $prev === '' ? $line : $prev . "\n" . $line;
        }
        $captura = array_key_exists('captura', $data) ? $data['captura'] : null;
        if ($captura !== null) {
            DB::query(
                'UPDATE pagos SET hallacas=?, monto=?, moneda=?, metodo=?, referencia=?, tasa=?, notes=?, captura=? WHERE id=?',
                [$hall, $monto, $moneda, $metodo, $referencia, $tasa, $notes, $captura, $id]
            );
        } else {
            DB::query(
                'UPDATE pagos SET hallacas=?, monto=?, moneda=?, metodo=?, referencia=?, tasa=?, notes=? WHERE id=?',
                [$hall, $monto, $moneda, $metodo, $referencia, $tasa, $notes, $id]
            );
        }
        if (($p['status'] ?? '') === 'confirmado') {
            self::syncEntregaConfirmados((int)$p['entrega_id']);
        }
        return null;
    }

    public static function delete(int $id): bool {
        $p = self::find($id);
        if (!$p) {
            return false;
        }
        $pdo = DB::connect();
        $pdo->beginTransaction();
        try {
            if ($p['status'] === 'confirmado') {
                $e = Entrega::find((int)$p['entrega_id']);
                if ($e) {
                    $newHp = max(0, (int)$e['hallacas_pagadas'] - (int)$p['hallacas']);
                    $newMonto = max(0, (float)$e['monto_pagado'] - (float)$p['monto']);
                    $total = (int)$e['hallacas_entregadas'];
                    $status = 'pendiente';
                    if ($newHp >= $total && $total > 0) {
                        $status = 'pagado';
                    } elseif ($newHp > 0) {
                        $status = 'parcial';
                    }
                    DB::query(
                        'UPDATE entregas SET hallacas_pagadas=?, monto_pagado=?, status=? WHERE id=?',
                        [$newHp, $newMonto, $status, $e['id']]
                    );
                }
            }
            DB::query('DELETE FROM pagos WHERE id=?', [$id]);
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
