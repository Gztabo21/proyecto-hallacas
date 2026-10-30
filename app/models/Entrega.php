<?php
class Entrega {
    public static function allByJornada(int $jornadaId, ?int $missionId = null): array {
        $sql = 'SELECT e.*, u.name AS member_name, m.name AS mission_name FROM entregas e JOIN users u ON u.id=e.member_id JOIN missions m ON m.id=e.mission_id WHERE e.jornada_id=?';
        $p = [$jornadaId];
        if ($missionId) { $sql .= ' AND e.mission_id=?'; $p[] = $missionId; }
        $sql .= ' ORDER BY m.name, u.name';
        return DB::query($sql, $p)->fetchAll();
    }
    public static function find(int $id): ?array {
        $stmt = DB::query('SELECT e.*, u.name AS member_name, m.name AS mission_name, j.precio_hallaca AS jprecio, j.fecha FROM entregas e JOIN users u ON u.id=e.member_id JOIN missions m ON m.id=e.mission_id JOIN jornadas j ON j.id=e.jornada_id WHERE e.id=?', [$id]);
        $r = $stmt->fetch();
        return $r ?: null;
    }
    public static function byMemberJornada(int $jornadaId, int $memberId): ?array {
        $stmt = DB::query('SELECT * FROM entregas WHERE jornada_id=? AND member_id=?', [$jornadaId,$memberId]);
        $r = $stmt->fetch();
        return $r ?: null;
    }
    public static function create(array $data): int {
        DB::query('INSERT INTO entregas (jornada_id,mission_id,member_id,hallacas_entregadas,hallacas_pagadas,precio_unitario,monto_pagado,status,notes) VALUES (?,?,?,?,?,?,?,?,?)',
            [$data['jornada_id'], $data['mission_id'], $data['member_id'], $data['hallacas_entregadas'], 0, $data['precio_unitario'], 0, 'pendiente', $data['notes'] ?? null]);
        $id = (int)DB::connect()->lastInsertId();
        $hp = (int)($data['hallacas_pagadas'] ?? 0);
        $montoPagado = (float)($data['monto_pagado'] ?? 0);
        if ($hp > 0 || $montoPagado > 0) {
            Pago::create([
                'entrega_id'=>$id,
                'hallacas'=>$hp,
                'monto'=>$montoPagado,
                'moneda'=>$data['moneda'] ?? 'VES',
                'metodo'=>$data['metodo'] ?? 'inicial',
                'status'=>'pendiente',
                'notes'=>'Entrega inicial',
                'created_by'=>$data['created_by'] ?? null
            ]);
        }
        return $id;
    }
    public static function updatePago(int $id, int $hallacasPagadas, float $montoPagado, string $metodo = '', ?string $notes = null, ?int $userId = null): void {
        $e = self::find($id);
        if (!$e) return;
        $hp = max(0, min((int)$e['hallacas_entregadas'], $hallacasPagadas));
        $total = (int)$e['hallacas_entregadas'];
        $status = 'pendiente';
        if ($hp >= $total && $total > 0) $status = 'pagado';
        elseif ($hp > 0) $status = 'parcial';
        DB::query('UPDATE entregas SET hallacas_pagadas=?, monto_pagado=?, status=? WHERE id=?', [$hp, $montoPagado, $status, $id]);
        $deltaH = $hp - (int)$e['hallacas_pagadas'];
        $deltaM = $montoPagado - (float)$e['monto_pagado'];
        if ($deltaH != 0 || $deltaM != 0) {
            Pago::create(['entrega_id'=>$id,'hallacas'=>$deltaH,'monto'=>$deltaM,'metodo'=>$metodo ?: 'abono','notes'=>$notes,'created_by'=>$userId]);
        }
    }
    public static function registrarAbono(int $entregaId, int $hallacas, float $monto, string $metodo, ?string $notes, ?int $userId, string $moneda = 'VES', ?float $tasa = null, ?string $captura = null, ?string $referencia = null): void {
        $e = self::find($entregaId);
        if (!$e) return;
        $hp = $hallacas;
        if ($hp < 0) $hp = 0;
        if ($hp > ((int)$e['hallacas_entregadas'] - (int)$e['hallacas_pagadas'])) {
            $hp = (int)$e['hallacas_entregadas'] - (int)$e['hallacas_pagadas'];
        }
        if ($hp <= 0 && $monto <= 0) return;
        Pago::create([
            'entrega_id'=>$entregaId,
            'hallacas'=>$hp,
            'monto'=>$monto,
            'moneda'=>$moneda,
            'metodo'=>$metodo,
            'referencia'=>$referencia,
            'tasa'=>$tasa,
            'captura'=>$captura,
            'status'=>'pendiente',
            'notes'=>$notes,
            'created_by'=>$userId
        ]);
    }
    public static function delete(int $id): void { DB::query('DELETE FROM entregas WHERE id=?', [$id]); }

    /** @return string|null mensaje de error o null si ok */
    public static function setHallacasEntregadas(int $id, int $nueva): ?string {
        $e = self::find($id);
        if (!$e) {
            return 'Entrega no encontrada';
        }
        $nueva = max(0, $nueva);
        $pagadas = (int)$e['hallacas_pagadas'];
        if ($nueva < $pagadas) {
            return 'No puede ser menor que las hallacas ya pagadas (' . $pagadas . ')';
        }
        $jid = (int)$e['jornada_id'];
        $mid = (int)$e['mission_id'];
        $assigned = 0;
        foreach (Jornada::missionsFor($jid) as $jm) {
            if ((int)$jm['mission_id'] === $mid) {
                $assigned = (int)$jm['hallacas_asignadas'];
            }
        }
        $sumOthers = (int)DB::query(
            'SELECT COALESCE(SUM(hallacas_entregadas),0) s FROM entregas WHERE jornada_id=? AND mission_id=? AND id<>?',
            [$jid, $mid, $id]
        )->fetch()['s'];
        if ($sumOthers + $nueva > $assigned) {
            $max = max(0, $assigned - $sumOthers);
            return 'Supera el cupo de la misión. Máximo para esta persona: ' . $max;
        }
        $status = 'pendiente';
        if ($nueva > 0 && $pagadas >= $nueva) {
            $status = 'pagado';
        } elseif ($pagadas > 0) {
            $status = 'parcial';
        }
        DB::query('UPDATE entregas SET hallacas_entregadas=?, status=? WHERE id=?', [$nueva, $status, $id]);
        return null;
    }

    private static function moneyTotalsFromSql(string $where, array $params, ?string $groupBy = null): array {
        $sql = 'SELECT';
        if ($groupBy !== null) {
            $sql .= ' e.' . $groupBy . ',';
        }
        $sql .= ' COALESCE(SUM(e.hallacas_entregadas), 0) AS hallacas_entregadas,
                  COALESCE(SUM(e.hallacas_pagadas), 0) AS hallacas_pagadas,
                  COALESCE(SUM(e.hallacas_entregadas * e.precio_unitario), 0) AS general,
                  COALESCE(SUM(COALESCE(pc.pagado_confirmado, 0)), 0) AS pagado
                  FROM entregas e
                  LEFT JOIN (
                      SELECT entrega_id, SUM(monto) AS pagado_confirmado
                      FROM pagos WHERE status = "confirmado"
                      GROUP BY entrega_id
                  ) pc ON pc.entrega_id = e.id
                  ' . $where;
        if ($groupBy !== null) {
            $sql .= ' GROUP BY e.' . $groupBy;
        }
        $rows = DB::query($sql, $params)->fetchAll();
        if ($groupBy === null) {
            $row = $rows[0] ?? [];
            return self::normalizeMoneyAggregateRow($row);
        }
        $map = [];
        foreach ($rows as $row) {
            $key = (int)$row[$groupBy];
            $map[$key] = self::normalizeMoneyAggregateRow($row);
        }
        return $map;
    }

    /** @param array<string,mixed> $row */
    private static function normalizeMoneyAggregateRow(array $row): array {
        $general = (float)($row['general'] ?? 0);
        $pagado = (float)($row['pagado'] ?? 0);
        $pendiente = max(0.0, $general - $pagado);
        return [
            'hallacas_entregadas' => (int)($row['hallacas_entregadas'] ?? 0),
            'hallacas_pagadas' => (int)($row['hallacas_pagadas'] ?? 0),
            'general' => $general,
            'pagado' => $pagado,
            'pendiente' => $pendiente,
        ];
    }

    /** @return array{general: float, pagado: float, pendiente: float} */
    public static function aggregateMoneyTotals(string $where, array $params): array {
        return self::moneyTotalsFromSql($where, $params, null);
    }

    /** @return array<int, array{general: float, pagado: float, pendiente: float}> */
    public static function aggregateMoneyByJornada(string $where, array $params): array {
        return self::moneyTotalsFromSql($where, $params, 'jornada_id');
    }
}
