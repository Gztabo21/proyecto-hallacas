<?php
class InventoryPurchase {
    /** @return array<int,array<string,mixed>> */
    public static function allSummaries(?bool $esDonacion = null): array {
        $where = '';
        $params = [];
        if ($esDonacion !== null) {
            $where = 'WHERE p.es_donacion = ?';
            $params[] = $esDonacion ? 1 : 0;
        }
        return DB::query(
            "SELECT p.*,
                    u.name AS created_by_name,
                    COUNT(m.id) AS line_count,
                    COALESCE(SUM(m.cantidad), 0) AS total_cantidad,
                    COALESCE(SUM(m.cantidad * COALESCE(m.precio_bs, 0)), 0) AS total_bs,
                    COALESCE(SUM(
                        CASE WHEN m.precio_usd IS NOT NULL AND m.precio_usd > 0
                        THEN m.cantidad * m.precio_usd ELSE 0 END
                    ), 0) AS total_usd_lines
             FROM inventory_purchases p
             LEFT JOIN users u ON u.id = p.created_by
             LEFT JOIN inventory_movements m ON m.purchase_id = p.id AND m.tipo = 'compra'
             $where
             GROUP BY p.id
             ORDER BY p.fecha DESC, p.id DESC",
            $params
        )->fetchAll();
    }

    /** @param array<string,mixed> $row */
    public static function totalUsdForSummary(array $row): ?float {
        $bs = (float)($row['total_bs'] ?? 0);
        $tasa = (float)($row['tasa'] ?? 0);
        if ($tasa > 0 && $bs > 0) {
            return round($bs / $tasa, 2);
        }
        $usd = (float)($row['total_usd_lines'] ?? 0);
        return $usd > 0 ? round($usd, 2) : null;
    }

    public static function find(int $id): ?array {
        $r = DB::query(
            'SELECT p.*, u.name AS created_by_name
             FROM inventory_purchases p
             LEFT JOIN users u ON u.id = p.created_by
             WHERE p.id = ?',
            [$id]
        )->fetch();
        return $r ?: null;
    }

    /** @return array<int,array<string,mixed>> */
    public static function lines(int $purchaseId): array {
        return DB::query(
            'SELECT m.*, p.name AS product_name, p.sku, p.unidad_metrica
             FROM inventory_movements m
             JOIN inventory_products p ON p.id = m.product_id
             WHERE m.purchase_id = ? AND m.tipo = "compra"
             ORDER BY m.id',
            [$purchaseId]
        )->fetchAll();
    }

    public static function delete(int $id): void {
        $purchase = self::find($id);
        if (!$purchase) {
            throw new InvalidArgumentException('Compra no encontrada.');
        }
        $pdo = DB::connect();
        $pdo->beginTransaction();
        try {
            self::removeMovementsAndRevertStock($id);
            DB::query('DELETE FROM inventory_purchases WHERE id = ?', [$id]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** @param list<array{product_id:int,cantidad:float,precio_bs:float,notes:?string}> $lines */
    public static function update(int $id, array $header, array $lines, ?int $userId): void {
        if (!self::find($id)) {
            throw new InvalidArgumentException('Compra no encontrada.');
        }
        if ($lines === []) {
            throw new InvalidArgumentException('Debe incluir al menos una línea.');
        }
        $pdo = DB::connect();
        $pdo->beginTransaction();
        try {
            self::removeMovementsAndRevertStock($id);
            $esDonacion = !empty($header['es_donacion']) ? 1 : 0;
            $referencia = isset($header['referencia']) && $header['referencia'] !== ''
                ? trim((string)$header['referencia']) : null;
            $donante = $esDonacion && isset($header['donante']) && trim((string)$header['donante']) !== ''
                ? trim((string)$header['donante']) : null;
            DB::query(
                'UPDATE inventory_purchases SET fecha = ?, tasa = ?, notes = ?, es_donacion = ?, donante = ?, referencia = ? WHERE id = ?',
                [
                    $header['fecha'],
                    $header['tasa'],
                    $header['notes'] ?? null,
                    $esDonacion,
                    $donante,
                    $referencia,
                    $id,
                ]
            );
            self::insertLines($id, $header, $lines, $userId);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

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
            self::insertLines($purchaseId, $header, $lines, $userId);
            $pdo->commit();
            return $purchaseId;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private static function removeMovementsAndRevertStock(int $purchaseId): void {
        $movs = self::lines($purchaseId);
        foreach ($movs as $m) {
            $pid = (int)$m['product_id'];
            $qty = (float)$m['cantidad'];
            $stock = InventoryProduct::stockOf($pid);
            if ($stock + 1e-9 < $qty) {
                $name = (string)($m['product_name'] ?? $m['sku'] ?? $pid);
                throw new InvalidArgumentException(
                    'Stock insuficiente para modificar o eliminar (faltan ' . round($qty - $stock, 3) . ' de «' . $name . '»).'
                );
            }
            InventoryProduct::adjustStock($pid, -$qty);
        }
        DB::query('DELETE FROM inventory_movements WHERE purchase_id = ?', [$purchaseId]);
    }

    /** @param list<array{product_id:int,cantidad:float,precio_bs:float,notes:?string}> $lines */
    private static function insertLines(int $purchaseId, array $header, array $lines, ?int $userId): void {
        $esDonacion = !empty($header['es_donacion']) ? 1 : 0;
        $referencia = isset($header['referencia']) && $header['referencia'] !== ''
            ? trim((string)$header['referencia']) : null;
        $donante = $esDonacion && isset($header['donante']) && trim((string)$header['donante']) !== ''
            ? trim((string)$header['donante']) : null;
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
                'tasa' => isset($header['tasa']) && (float)$header['tasa'] > 0 ? (float)$header['tasa'] : null,
                'fecha_ingreso' => $header['fecha'],
                'purchase_id' => $purchaseId,
                'notes' => $movNotes,
                'created_by' => $userId,
            ]);
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
