<?php
class Pago {
    public static function create(array $data): int {
        DB::query('INSERT INTO pagos (entrega_id,hallacas,monto,metodo,notes,created_by) VALUES (?,?,?,?,?,?)',
            [$data['entrega_id'],$data['hallacas'] ?? 0,$data['monto'] ?? 0,$data['metodo'] ?? null,$data['notes'] ?? null,$data['created_by'] ?? null]);
        return (int)DB::connect()->lastInsertId();
    }
    public static function byEntrega(int $entregaId): array {
        return DB::query('SELECT p.*, u.name AS created_by_name FROM pagos p LEFT JOIN users u ON u.id=p.created_by WHERE p.entrega_id=? ORDER BY p.fecha DESC', [$entregaId])->fetchAll();
    }
}
