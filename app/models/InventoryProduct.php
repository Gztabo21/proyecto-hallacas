<?php
class InventoryProduct {
    /** Fragmento ORDER BY: SKU numérico en orden natural, luego alfanuméricos. */
    public static function sqlOrderBySku(string $prefix = '', string $dir = 'ASC'): string {
        $dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        $s = $prefix;
        return "CASE WHEN {$s}sku REGEXP '^[0-9]+\$' THEN 0 ELSE 1 END ASC,
                CASE WHEN {$s}sku REGEXP '^[0-9]+\$' THEN CAST({$s}sku AS UNSIGNED) END {$dir},
                {$s}sku {$dir},
                {$s}name ASC";
    }

    public static function all(): array {
        return DB::query(
            'SELECT * FROM inventory_products ORDER BY ' . self::sqlOrderBySku('', 'ASC')
        )->fetchAll();
    }

    /** Catálogo con último precio/tasa/fecha registrados en movimientos de entrada o ajuste con costo. */
    public static function allForIndex(string $sort = 'sku', string $dir = 'asc'): array {
        $sort = $sort === 'sku' ? 'sku' : 'name';
        $dirSql = strtolower($dir) === 'desc' ? 'DESC' : 'ASC';
        if ($sort === 'sku') {
            $orderBy = self::sqlOrderBySku('p.', $dirSql);
        } else {
            $orderBy = "p.name {$dirSql}, " . self::sqlOrderBySku('p.', 'ASC');
        }
        return DB::query(
            "SELECT p.*,
                    lm.precio_bs AS ultimo_precio_bs,
                    lm.precio_usd AS ultimo_precio_usd,
                    lm.tasa AS ultima_tasa,
                    lm.fecha_ingreso AS ultima_fecha_ingreso
             FROM inventory_products p
             LEFT JOIN inventory_movements lm ON lm.id = (
               SELECT m.id FROM inventory_movements m
               WHERE m.product_id = p.id
                 AND (m.precio_bs IS NOT NULL OR m.precio_usd IS NOT NULL OR (m.tasa IS NOT NULL AND m.tasa > 0))
               ORDER BY m.fecha_ingreso DESC, m.id DESC
               LIMIT 1
             )
             ORDER BY {$orderBy}"
        )->fetchAll();
    }

    public static function find(int $id): ?array {
        $r = DB::query('SELECT * FROM inventory_products WHERE id = ?', [$id])->fetch();
        return $r ?: null;
    }

    public static function findBySku(string $sku): ?array {
        $r = DB::query('SELECT * FROM inventory_products WHERE sku = ?', [trim($sku)])->fetch();
        return $r ?: null;
    }

    /** Código en CSV: sku textual o id numérico del producto (p. ej. columna «rubro» 1, 2, 3). */
    public static function resolveImportCode(string $code): ?array {
        $code = trim($code);
        if ($code === '') {
            return null;
        }
        $bySku = self::findBySku($code);
        if ($bySku) {
            return $bySku;
        }
        if (preg_match('/^\d+$/', $code) === 1) {
            return self::find((int)$code);
        }
        return null;
    }

    public static function create(array $data): int {
        DB::query(
            'INSERT INTO inventory_products (name, sku, unidad_metrica, stock) VALUES (?,?,?,0)',
            [$data['name'], $data['sku'], $data['unidad_metrica']]
        );
        return (int)DB::connect()->lastInsertId();
    }

    public static function update(int $id, array $data): void {
        DB::query(
            'UPDATE inventory_products SET name = ?, unidad_metrica = ? WHERE id = ?',
            [$data['name'], $data['unidad_metrica'], $id]
        );
    }

    public static function adjustStock(int $id, float $delta): void {
        DB::query('UPDATE inventory_products SET stock = stock + ? WHERE id = ?', [$delta, $id]);
    }

    public static function stockOf(int $id): float {
        $r = DB::query('SELECT stock FROM inventory_products WHERE id = ?', [$id])->fetch();
        return (float)($r['stock'] ?? 0);
    }
}
