<?php
class InventoryPurchase {
    public static function create(array $header, array $lines, ?int $userId): int {
        $pdo = DB::connect();
        $pdo->beginTransaction();
        try {
            DB::query(
                'INSERT INTO inventory_purchases (fecha, tasa, notes, created_by) VALUES (?,?,?,?)',
                [$header['fecha'], $header['tasa'], $header['notes'] ?? null, $userId]
            );
            $purchaseId = (int)$pdo->lastInsertId();
            foreach ($lines as $line) {
                InventoryMovement::register([
                    'product_id' => (int)$line['product_id'],
                    'tipo' => 'compra',
                    'cantidad' => (float)$line['cantidad'],
                    'precio_bs' => (float)$line['precio_bs'],
                    'tasa' => (float)$header['tasa'],
                    'fecha_ingreso' => $header['fecha'],
                    'purchase_id' => $purchaseId,
                    'notes' => $line['notes'] ?? null,
                    'created_by' => $userId,
                ]);
            }
            $pdo->commit();
            return $purchaseId;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
