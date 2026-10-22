<?php
class InventoryPurchase {
    public static function create(array $header, array $lines, ?int $userId): int {
        $pdo = DB::connect();
        $pdo->beginTransaction();
        try {
            $esDonacion = !empty($header['es_donacion']) ? 1 : 0;
            $referencia = isset($header['referencia']) && $header['referencia'] !== ''
                ? trim((string)$header['referencia']) : null;
            $donante = $esDonacion && isset($header['donante']) && trim((string)$header['donante']) !== ''
                ? trim((string)$header['donante']) : null;
            DB::query(
                'INSERT INTO inventory_purchases (fecha, tasa, notes, es_donacion, donante, referencia, created_by) VALUES (?,?,?,?,?,?,?)',
                [$header['fecha'], $header['tasa'], $header['notes'] ?? null, $esDonacion, $donante, $referencia, $userId]
            );
            $purchaseId = (int)$pdo->lastInsertId();
            foreach ($lines as $line) {
                $precioBs = (float)$line['precio_bs'];
                $movNotes = self::movementNotes(
                    $line['notes'] ?? null,
                    $esDonacion,
                    $referencia,
                    $donante
                );
                InventoryMovement::register([
                    'product_id' => (int)$line['product_id'],
                    'tipo' => 'compra',
                    'cantidad' => (float)$line['cantidad'],
                    'precio_bs' => $precioBs > 0 ? $precioBs : null,
                    'tasa' => (float)$header['tasa'],
                    'fecha_ingreso' => $header['fecha'],
                    'purchase_id' => $purchaseId,
                    'notes' => $movNotes,
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

    private static function movementNotes(?string $lineNotes, int $esDonacion, ?string $referencia, ?string $donante = null): ?string {
        $parts = [];
        if ($referencia !== null && $referencia !== '') {
            $parts[] = 'Ref: ' . $referencia;
        }
        if ($esDonacion) {
            $parts[] = 'Donación' . ($donante !== null && $donante !== '' ? ' — Donante: ' . $donante : '');
        }
        if ($lineNotes !== null && trim($lineNotes) !== '') {
            $parts[] = trim($lineNotes);
        }
        if ($parts === []) {
            return null;
        }
        return implode(' — ', $parts);
    }
}
