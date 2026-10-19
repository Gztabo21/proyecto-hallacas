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
        $precioBs = isset($data['precio_bs']) ? (float)$data['precio_bs'] : null;
        $tasa = isset($data['tasa']) ? (float)$data['tasa'] : null;
        $precioUsd = null;
        if ($precioBs !== null && $tasa !== null && $tasa > 0) {
            $precioUsd = self::usdFromBs($precioBs, $tasa);
        } elseif (isset($data['precio_usd'])) {
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
        $sql = "SELECT m.*, p.name AS product_name, p.sku, p.unidad_metrica,
                       j.name AS jornada_name, u.name AS created_by_name
                FROM inventory_movements m
                JOIN inventory_products p ON p.id = m.product_id
                LEFT JOIN jornadas j ON j.id = m.jornada_id
                LEFT JOIN users u ON u.id = m.created_by
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
        return (int)DB::query("SELECT COUNT(*) c FROM inventory_movements $where", $p)->fetch()['c'];
    }

    public static function byJornada(int $jornadaId): array {
        return self::all(['jornada_id' => $jornadaId], 500, 0);
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
}
