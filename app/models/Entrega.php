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
    public static function registrarAbono(int $entregaId, int $hallacas, float $monto, string $metodo, ?string $notes, ?int $userId, string $moneda = 'VES', ?float $tasa = null, ?string $captura = null): void {
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
            'tasa'=>$tasa,
            'captura'=>$captura,
            'status'=>'pendiente',
            'notes'=>$notes,
            'created_by'=>$userId
        ]);
    }
    public static function delete(int $id): void { DB::query('DELETE FROM entregas WHERE id=?', [$id]); }
}
