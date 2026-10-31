<?php
class InventoryMovement {
    public static function tipoLabel(string $tipo): string {
        $map = [
            'stock_inicial' => 'Stock inicial',
            'compra' => 'Compra',
            'salida_jornada' => 'Salida a jornada',
            'devolucion_jornada' => 'Excedente / devolución',
            'ajuste' => 'Ajuste',
        ];
        return $map[$tipo] ?? $tipo;
    }

    /** @param array<string,mixed> $movement */
    public static function tipoLabelForMovement(array $movement): string {
        $tipo = (string)($movement['tipo'] ?? '');
        if ($tipo === 'compra' && !empty($movement['purchase_es_donacion'])) {
            return 'Donaciones';
        }
        return self::tipoLabel($tipo);
    }

    public static function isEntrada(string $tipo): bool {
        return in_array($tipo, ['stock_inicial', 'compra', 'devolucion_jornada'], true);
    }

    public static function deltaForTipo(string $tipo, float $cantidad): float {
        if ($tipo === 'ajuste') {
            return $cantidad;
        }
        if ($tipo === 'salida_jornada') {
            return -abs($cantidad);
        }
        if ($tipo === 'devolucion_jornada') {
            return abs($cantidad);
        }
        return abs($cantidad);
    }

    /** @return array<int,array{product_id:int,entregado:float,devuelto:float,pendiente_devolver:float}> */
    public static function balanceByJornada(int $jornadaId): array {
        $rows = DB::query(
            'SELECT m.product_id, m.tipo, SUM(m.cantidad) AS total,
                    p.name AS product_name, p.sku, p.unidad_metrica
             FROM inventory_movements m
             JOIN inventory_products p ON p.id = m.product_id
             WHERE m.jornada_id = ? AND m.tipo IN ("salida_jornada", "devolucion_jornada")
             GROUP BY m.product_id, m.tipo, p.name, p.sku, p.unidad_metrica',
            [$jornadaId]
        )->fetchAll();
        $map = [];
        foreach ($rows as $r) {
            $pid = (int)$r['product_id'];
            if (!isset($map[$pid])) {
                $map[$pid] = [
                    'product_id' => $pid,
                    'product_name' => $r['product_name'],
                    'sku' => $r['sku'],
                    'unidad_metrica' => $r['unidad_metrica'],
                    'entregado' => 0.0,
                    'devuelto' => 0.0,
                ];
            }
            if ($r['tipo'] === 'salida_jornada') {
                $map[$pid]['entregado'] += (float)$r['total'];
            } else {
                $map[$pid]['devuelto'] += (float)$r['total'];
            }
        }
        foreach ($map as &$item) {
            $rest = max(0, $item['entregado'] - $item['devuelto']);
            $item['pendiente_devolver'] = $rest;
            $item['consumido'] = $rest;
        }
        unset($item);
        return $map;
    }

    public static function maxDevolucion(int $jornadaId, int $productId): float {
        $bal = self::balanceByJornada($jornadaId);
        return (float)($bal[$productId]['pendiente_devolver'] ?? 0);
    }

    public static function usdFromBs(float $precioBs, float $tasa): ?float {
        if ($tasa <= 0) {
            return null;
        }
        return round($precioBs / $tasa, 4);
    }

    /** @param array<string,mixed> $data */
    public static function dataHasExplicitPrice(array $data): bool {
        $bs = isset($data['precio_bs']) && $data['precio_bs'] !== '' && $data['precio_bs'] !== null
            ? (float)$data['precio_bs'] : 0.0;
        $usd = isset($data['precio_usd']) && $data['precio_usd'] !== '' && $data['precio_usd'] !== null
            ? (float)$data['precio_usd'] : 0.0;
        return $bs > 0 || $usd > 0;
    }

    /**
     * Último costo unitario conocido para un producto en o antes de una fecha;
     * con $beforeMovementId excluye movimientos posteriores el mismo día (p. ej. compras después de la salida).
     *
     * @return array{precio_bs:?float,precio_usd:?float,tasa:?float}|null
     */
    public static function costAsOf(int $productId, string $fechaIngreso, ?int $beforeMovementId = null): ?array {
        if ($productId <= 0 || $fechaIngreso === '') {
            return null;
        }
        $beforeId = ($beforeMovementId === null || $beforeMovementId <= 0) ? PHP_INT_MAX : $beforeMovementId;
        $row = DB::query(
            'SELECT precio_bs, precio_usd, tasa FROM inventory_movements
             WHERE product_id = ?
               AND (precio_bs IS NOT NULL OR precio_usd IS NOT NULL OR (tasa IS NOT NULL AND tasa > 0))
               AND (fecha_ingreso < ? OR (fecha_ingreso = ? AND id < ?))
             ORDER BY fecha_ingreso DESC, id DESC
             LIMIT 1',
            [$productId, $fechaIngreso, $fechaIngreso, $beforeId]
        )->fetch();
        if (!$row) {
            return null;
        }
        $bs = $row['precio_bs'] !== null && $row['precio_bs'] !== '' ? (float)$row['precio_bs'] : null;
        $usd = $row['precio_usd'] !== null && $row['precio_usd'] !== '' ? (float)$row['precio_usd'] : null;
        $tasa = $row['tasa'] !== null && $row['tasa'] !== '' ? (float)$row['tasa'] : null;
        if (($bs === null || $bs <= 0) && ($usd === null || $usd <= 0)) {
            return null;
        }
        if (($usd === null || $usd <= 0) && $bs !== null && $bs > 0 && $tasa !== null && $tasa > 0) {
            $usd = self::usdFromBs($bs, $tasa);
        }
        return ['precio_bs' => $bs, 'precio_usd' => $usd, 'tasa' => $tasa];
    }

    /** Congela precio_bs/tasa en salidas y devoluciones de jornada que aún no tienen costo. */
    public static function backfillJornadaMovementCosts(): int {
        $rows = DB::query(
            'SELECT id, product_id, fecha_ingreso FROM inventory_movements
             WHERE tipo IN ("salida_jornada", "devolucion_jornada")
               AND (precio_bs IS NULL OR precio_bs <= 0)
               AND (precio_usd IS NULL OR precio_usd <= 0)'
        )->fetchAll();
        $updated = 0;
        foreach ($rows as $r) {
            $snap = self::costAsOf((int)$r['product_id'], (string)$r['fecha_ingreso'], (int)$r['id']);
            if (!$snap || (($snap['precio_bs'] ?? 0) <= 0 && ($snap['precio_usd'] ?? 0) <= 0)) {
                continue;
            }
            $bs = $snap['precio_bs'];
            $tasa = $snap['tasa'];
            $usd = $snap['precio_usd'];
            if ($usd === null && $bs !== null && $tasa !== null && $tasa > 0) {
                $usd = self::usdFromBs($bs, $tasa);
            }
            DB::query(
                'UPDATE inventory_movements SET precio_bs = ?, precio_usd = ?, tasa = ? WHERE id = ?',
                [$bs, $usd, $tasa, (int)$r['id']]
            );
            $updated++;
        }
        return $updated;
    }

    public static function ensureJornadaCostsFrozen(): void {
        static $ran = false;
        if ($ran) {
            return;
        }
        $ran = true;
        if (setting('inventory_jornada_cost_frozen', '') === '1') {
            return;
        }
        try {
            self::backfillJornadaMovementCosts();
            set_setting('inventory_jornada_cost_frozen', '1');
        } catch (Throwable $e) {
            if (APP_DEBUG) {
                error_log('ensureJornadaCostsFrozen: ' . $e->getMessage());
            }
        }
    }

    /** @return int movement id */
    public static function register(array $data): int {
        $tipo = $data['tipo'];
        $cantidad = (float)$data['cantidad'];
        $delta = self::deltaForTipo($tipo, $cantidad);
        $productId = (int)$data['product_id'];
        $stock = InventoryProduct::stockOf($productId);
        if ($delta < 0 && $stock + $delta < -0.0001) {
            throw new InvalidArgumentException('Stock insuficiente para el producto.');
        }
        if (in_array($tipo, ['salida_jornada', 'devolucion_jornada'], true) && !self::dataHasExplicitPrice($data)) {
            $snap = self::costAsOf($productId, (string)($data['fecha_ingreso'] ?? ''), null);
            if ($snap) {
                if (($snap['precio_bs'] ?? 0) > 0) {
                    $data['precio_bs'] = $snap['precio_bs'];
                }
                if (($snap['tasa'] ?? 0) > 0) {
                    $data['tasa'] = $snap['tasa'];
                }
                if (($snap['precio_usd'] ?? 0) > 0) {
                    $data['precio_usd'] = $snap['precio_usd'];
                }
            }
        }
        $precioBs = isset($data['precio_bs']) && $data['precio_bs'] !== '' ? (float)$data['precio_bs'] : null;
        $tasa = isset($data['tasa']) && $data['tasa'] !== '' ? (float)$data['tasa'] : null;
        $precioUsd = null;
        if ($precioBs !== null && $tasa !== null && $tasa > 0) {
            $precioUsd = self::usdFromBs($precioBs, $tasa);
        } elseif (isset($data['precio_usd']) && $data['precio_usd'] !== '') {
            $precioUsd = (float)$data['precio_usd'];
        }

        $pdo = DB::connect();
        $ownTx = !$pdo->inTransaction();
        if ($ownTx) {
            $pdo->beginTransaction();
        }
        try {
            DB::query(
                'INSERT INTO inventory_movements (product_id, tipo, cantidad, precio_bs, precio_usd, tasa, fecha_ingreso, jornada_id, purchase_id, notes, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $productId,
                    $tipo,
                    abs($cantidad),
                    $precioBs,
                    $precioUsd,
                    $tasa,
                    $data['fecha_ingreso'],
                    $data['jornada_id'] ?? null,
                    $data['purchase_id'] ?? null,
                    $data['notes'] ?? null,
                    $data['created_by'] ?? null,
                ]
            );
            $movId = (int)$pdo->lastInsertId();
            InventoryProduct::adjustStock($productId, $delta);
            if ($ownTx) {
                $pdo->commit();
            }
            return $movId;
        } catch (Throwable $e) {
            if ($ownTx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function all(array $filters = [], int $limit = 100, int $offset = 0): array {
        $where = 'WHERE 1=1';
        $p = [];
        if (!empty($filters['product_id'])) {
            $where .= ' AND m.product_id = ?';
            $p[] = (int)$filters['product_id'];
        }
        if (!empty($filters['jornada_id'])) {
            $where .= ' AND m.jornada_id = ?';
            $p[] = (int)$filters['jornada_id'];
        }
        if (!empty($filters['tipo'])) {
            $where .= ' AND m.tipo = ?';
            $p[] = $filters['tipo'];
        }
        if (!empty($filters['purchase_id'])) {
            $where .= ' AND m.purchase_id = ?';
            $p[] = (int)$filters['purchase_id'];
        }
        $sql = "SELECT m.*, p.name AS product_name, p.sku, p.unidad_metrica,
                       j.name AS jornada_name, u.name AS created_by_name,
                       pur.es_donacion AS purchase_es_donacion
                FROM inventory_movements m
                JOIN inventory_products p ON p.id = m.product_id
                LEFT JOIN jornadas j ON j.id = m.jornada_id
                LEFT JOIN users u ON u.id = m.created_by
                LEFT JOIN inventory_purchases pur ON pur.id = m.purchase_id
                $where
                ORDER BY m.fecha_ingreso DESC, m.id DESC
                LIMIT " . (int)$limit . ' OFFSET ' . (int)$offset;
        return DB::query($sql, $p)->fetchAll();
    }

    public static function count(array $filters = []): int {
        $where = 'WHERE 1=1';
        $p = [];
        if (!empty($filters['product_id'])) {
            $where .= ' AND product_id = ?';
            $p[] = (int)$filters['product_id'];
        }
        if (!empty($filters['jornada_id'])) {
            $where .= ' AND jornada_id = ?';
            $p[] = (int)$filters['jornada_id'];
        }
        if (!empty($filters['purchase_id'])) {
            $where .= ' AND purchase_id = ?';
            $p[] = (int)$filters['purchase_id'];
        }
        return (int)DB::query("SELECT COUNT(*) c FROM inventory_movements $where", $p)->fetch()['c'];
    }

    public static function byJornada(int $jornadaId): array {
        return self::all(['jornada_id' => $jornadaId], 500, 0);
    }

    public static function find(int $id): ?array {
        $stmt = DB::query(
            'SELECT m.*, p.name AS product_name, p.sku, p.unidad_metrica
             FROM inventory_movements m
             JOIN inventory_products p ON p.id = m.product_id
             WHERE m.id = ?',
            [$id]
        );
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public static function sumSalidaJornada(int $jornadaId, int $productId, ?int $excludeMovementId = null): float {
        $sql = 'SELECT COALESCE(SUM(cantidad),0) s FROM inventory_movements
                WHERE jornada_id=? AND product_id=? AND tipo="salida_jornada"';
        $p = [$jornadaId, $productId];
        if ($excludeMovementId !== null) {
            $sql .= ' AND id <> ?';
            $p[] = $excludeMovementId;
        }
        return (float)DB::query($sql, $p)->fetch()['s'];
    }

    public static function sumDevolucionJornada(int $jornadaId, int $productId): float {
        return (float)DB::query(
            'SELECT COALESCE(SUM(cantidad),0) s FROM inventory_movements
             WHERE jornada_id=? AND product_id=? AND tipo="devolucion_jornada"',
            [$jornadaId, $productId]
        )->fetch()['s'];
    }

    /** @param array<string,mixed> $movement */
    private static function assertSalidaCantidadValid(array $movement, float $newCantidad, ?int $excludeId = null): void {
        $newCantidad = abs($newCantidad);
        if ($newCantidad <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor que cero.');
        }
        $jid = (int)$movement['jornada_id'];
        $pid = (int)$movement['product_id'];
        $movId = (int)$movement['id'];
        $exclude = $excludeId ?? $movId;
        $otrasSalidas = self::sumSalidaJornada($jid, $pid, $exclude);
        $devuelto = self::sumDevolucionJornada($jid, $pid);
        if ($otrasSalidas + $newCantidad + 0.0001 < $devuelto) {
            throw new InvalidArgumentException(
                'No puede quedar por debajo de lo ya devuelto (' . rtrim(rtrim(number_format($devuelto, 3, '.', ''), '0'), '.') . ').'
            );
        }
    }

    public static function updateSalidaJornada(int $id, float $cantidad, string $fechaIngreso, ?string $notes): void {
        $mov = self::find($id);
        if (!$mov || ($mov['tipo'] ?? '') !== 'salida_jornada') {
            throw new InvalidArgumentException('Salida no encontrada.');
        }
        $oldQty = (float)$mov['cantidad'];
        $newQty = abs($cantidad);
        self::assertSalidaCantidadValid($mov, $newQty);
        $pid = (int)$mov['product_id'];
        $stockDelta = $oldQty - $newQty;
        if ($stockDelta < -0.0001) {
            $need = -$stockDelta;
            $stock = InventoryProduct::stockOf($pid);
            if ($stock + 1e-9 < $need) {
                throw new InvalidArgumentException('Stock insuficiente en almacén para aumentar esta salida.');
            }
        }
        $pdo = DB::connect();
        $ownTx = !$pdo->inTransaction();
        if ($ownTx) {
            $pdo->beginTransaction();
        }
        try {
            if (abs($stockDelta) > 0.0001) {
                InventoryProduct::adjustStock($pid, $stockDelta);
            }
            DB::query(
                'UPDATE inventory_movements SET cantidad=?, fecha_ingreso=?, notes=? WHERE id=?',
                [$newQty, $fechaIngreso, $notes !== '' ? $notes : null, $id]
            );
            if ($ownTx) {
                $pdo->commit();
            }
        } catch (Throwable $e) {
            if ($ownTx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function deleteSalidaJornada(int $id): void {
        $mov = self::find($id);
        if (!$mov || ($mov['tipo'] ?? '') !== 'salida_jornada') {
            throw new InvalidArgumentException('Salida no encontrada.');
        }
        $qty = (float)$mov['cantidad'];
        $jid = (int)$mov['jornada_id'];
        $pid = (int)$mov['product_id'];
        $otrasSalidas = self::sumSalidaJornada($jid, $pid, $id);
        $devuelto = self::sumDevolucionJornada($jid, $pid);
        if ($otrasSalidas + 0.0001 < $devuelto) {
            throw new InvalidArgumentException(
                'No se puede eliminar: hay devoluciones registradas que superan el resto entregado de este producto.'
            );
        }
        $pdo = DB::connect();
        $ownTx = !$pdo->inTransaction();
        if ($ownTx) {
            $pdo->beginTransaction();
        }
        try {
            InventoryProduct::adjustStock($pid, $qty);
            DB::query('DELETE FROM inventory_movements WHERE id=?', [$id]);
            if ($ownTx) {
                $pdo->commit();
            }
        } catch (Throwable $e) {
            if ($ownTx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** @return array<int,array{precio_bs:?float,precio_usd:?float,tasa:?float}> */
    public static function latestCostByProduct(): array {
        $rows = DB::query(
            'SELECT m.product_id, m.precio_bs, m.precio_usd, m.tasa
             FROM inventory_movements m
             INNER JOIN (
               SELECT product_id, MAX(id) AS mid
               FROM inventory_movements
               WHERE precio_bs IS NOT NULL OR precio_usd IS NOT NULL OR (tasa IS NOT NULL AND tasa > 0)
               GROUP BY product_id
             ) last ON last.mid = m.id'
        )->fetchAll();
        $map = [];
        foreach ($rows as $r) {
            $pid = (int)$r['product_id'];
            $map[$pid] = [
                'precio_bs' => $r['precio_bs'] !== null ? (float)$r['precio_bs'] : null,
                'precio_usd' => $r['precio_usd'] !== null ? (float)$r['precio_usd'] : null,
                'tasa' => $r['tasa'] !== null ? (float)$r['tasa'] : null,
            ];
        }
        return $map;
    }

    /** @param array<string,mixed> $movement */
    public static function movementHasStoredUnitPrice(array $movement): bool {
        $bs = isset($movement['precio_bs']) && $movement['precio_bs'] !== '' ? (float)$movement['precio_bs'] : 0.0;
        $usd = isset($movement['precio_usd']) && $movement['precio_usd'] !== '' ? (float)$movement['precio_usd'] : 0.0;
        return $bs > 0 || $usd > 0;
    }

    /**
     * @param array<string,mixed> $movement
     * @param array<int,array{precio_bs:?float,precio_usd:?float,tasa:?float}> $refByProduct
     * @return array{precio_bs:?float,precio_usd:?float,tasa:?float,is_ref:bool}
     */
    public static function displayUnitPrice(array $movement, array $refByProduct): array {
        if (self::movementHasStoredUnitPrice($movement)) {
            $bs = $movement['precio_bs'] !== null && $movement['precio_bs'] !== '' ? (float)$movement['precio_bs'] : null;
            $tasa = $movement['tasa'] !== null && $movement['tasa'] !== '' ? (float)$movement['tasa'] : null;
            $usd = $movement['precio_usd'] !== null && $movement['precio_usd'] !== '' ? (float)$movement['precio_usd'] : null;
            if ($usd === null && $bs !== null && $tasa !== null && $tasa > 0) {
                $usd = self::usdFromBs($bs, $tasa);
            }
            return ['precio_bs' => $bs, 'precio_usd' => $usd, 'tasa' => $tasa, 'is_ref' => false];
        }
        $pid = (int)($movement['product_id'] ?? 0);
        $fecha = (string)($movement['fecha_ingreso'] ?? '');
        $movId = isset($movement['id']) ? (int)$movement['id'] : 0;
        $asOf = self::costAsOf($pid, $fecha, $movId > 0 ? $movId : null);
        if ($asOf) {
            return [
                'precio_bs' => $asOf['precio_bs'],
                'precio_usd' => $asOf['precio_usd'],
                'tasa' => $asOf['tasa'],
                'is_ref' => true,
            ];
        }
        $ref = $refByProduct[$pid] ?? null;
        if (!$ref) {
            return ['precio_bs' => null, 'precio_usd' => null, 'tasa' => null, 'is_ref' => false];
        }
        $bs = $ref['precio_bs'];
        $tasa = $ref['tasa'];
        $usd = $ref['precio_usd'];
        if (($bs === null || $bs <= 0) && ($usd === null || $usd <= 0)) {
            return ['precio_bs' => null, 'precio_usd' => null, 'tasa' => null, 'is_ref' => false];
        }
        if (($usd === null || $usd <= 0) && $bs !== null && $bs > 0 && $tasa !== null && $tasa > 0) {
            $usd = self::usdFromBs($bs, $tasa);
        }
        return ['precio_bs' => $bs, 'precio_usd' => $usd, 'tasa' => $tasa, 'is_ref' => true];
    }

    /**
     * @param array<string,mixed> $movement
     * @param array<int,array{precio_bs:?float,precio_usd:?float,tasa:?float}> $refByProduct
     * @return array{bs:float,usd:float}
     */
    public static function lineTotalAmounts(array $movement, array $refByProduct): array {
        $qty = (float)($movement['cantidad'] ?? 0);
        $price = self::displayUnitPrice($movement, $refByProduct);
        $bs = 0.0;
        $usd = 0.0;
        if ($price['precio_bs'] !== null && $price['precio_bs'] > 0) {
            $bs = $qty * $price['precio_bs'];
        }
        if ($price['precio_usd'] !== null && $price['precio_usd'] > 0) {
            $usd = $qty * $price['precio_usd'];
        } elseif ($bs > 0 && $price['tasa'] !== null && $price['tasa'] > 0 && $price['precio_bs'] !== null) {
            $unitUsd = self::usdFromBs($price['precio_bs'], $price['tasa']);
            if ($unitUsd !== null) {
                $usd = $qty * $unitUsd;
            }
        }
        return ['bs' => $bs, 'usd' => $usd];
    }

    /**
     * Valor en Bs/USD con signo para totales: salida a jornada (+), excedente/devolución (−),
     * entradas (+), ajuste según delta de cantidad.
     *
     * @param array<string,mixed> $movement
     * @param array<int,array{precio_bs:?float,precio_usd:?float,tasa:?float}> $refByProduct
     * @return array{bs:float,usd:float}
     */
    public static function signedLineAmounts(array $movement, array $refByProduct): array {
        $line = self::lineTotalAmounts($movement, $refByProduct);
        $tipo = (string)($movement['tipo'] ?? '');
        if ($line['bs'] == 0.0 && $line['usd'] == 0.0) {
            return $line;
        }
        if ($tipo === 'devolucion_jornada') {
            return ['bs' => -abs($line['bs']), 'usd' => -abs($line['usd'])];
        }
        if ($tipo === 'salida_jornada') {
            return ['bs' => abs($line['bs']), 'usd' => abs($line['usd'])];
        }
        if ($tipo === 'ajuste') {
            $delta = self::deltaForTipo($tipo, (float)($movement['cantidad'] ?? 0));
            $sign = $delta >= 0 ? 1.0 : -1.0;
            return ['bs' => $sign * abs($line['bs']), 'usd' => $sign * abs($line['usd'])];
        }
        return ['bs' => abs($line['bs']), 'usd' => abs($line['usd'])];
    }
}
