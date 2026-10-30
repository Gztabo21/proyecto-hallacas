<?php
class InventoryController {
    private static function requireInventoryRole(): void {
        require_login();
        if (!Auth::canAccessInventory()) {
            http_response_code(403);
            die('Acceso denegado.');
        }
    }

    private static function requireInventoryCatalogRole(): void {
        require_login();
        if (!Auth::canManageInventoryCatalog()) {
            http_response_code(403);
            die('Acceso denegado.');
        }
    }

    private static function assertJornadaInventory(int $jornadaId): void {
        if (!Auth::canManageJornadaInventory($jornadaId)) {
            flash('error', 'No autorizado');
            redirect('jornadas');
            exit;
        }
    }

    private static function ensureTables(): bool {
        static $ready = null;
        if ($ready === true) {
            return true;
        }
        if ($ready === false) {
            include BASE_PATH . '/app/views/inventory/setup_required.php';
            return false;
        }
        try {
            DB::query('SELECT 1 FROM inventory_products LIMIT 1');
            self::ensurePurchaseColumns();
            $ready = true;
            return true;
        } catch (PDOException $e) {
            $ready = false;
            include BASE_PATH . '/app/views/inventory/setup_required.php';
            return false;
        }
    }

    private static function ensurePurchaseColumns(): void {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;
        try {
            $rows = DB::query('SHOW COLUMNS FROM inventory_purchases')->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException) {
            return;
        }
        $cols = [];
        foreach ($rows as $row) {
            $cols[strtolower((string)$row['Field'])] = true;
        }
        if (!isset($cols['es_donacion'])) {
            DB::query(
                'ALTER TABLE inventory_purchases ADD COLUMN es_donacion TINYINT(1) NOT NULL DEFAULT 0'
            );
        }
        if (!isset($cols['donante'])) {
            DB::query(
                'ALTER TABLE inventory_purchases ADD COLUMN donante VARCHAR(160) NULL'
            );
        }
        if (!isset($cols['referencia'])) {
            DB::query(
                'ALTER TABLE inventory_purchases ADD COLUMN referencia VARCHAR(120) NULL'
            );
        }
        if (!isset($cols['etiquetas'])) {
            DB::query(
                'ALTER TABLE inventory_purchases ADD COLUMN etiquetas JSON NULL'
            );
        }
    }

    /** @return array{fecha_desde:string,fecha_hasta:string,etiquetas:list<string>} */
    private static function purchaseListFilters(): array {
        $tags = [];
        if (isset($_GET['etiqueta'])) {
            $raw = $_GET['etiqueta'];
            $tags = is_array($raw) ? $raw : [$raw];
        }
        $tags = array_values(array_unique(array_filter(array_map(
            static fn($t) => trim((string)$t),
            $tags
        ), static fn($t) => $t !== '')));
        return [
            'fecha_desde' => trim((string)($_GET['fecha_desde'] ?? '')),
            'fecha_hasta' => trim((string)($_GET['fecha_hasta'] ?? '')),
            'etiquetas' => $tags,
        ];
    }

    private static function etiquetasFromPost(): ?string {
        return InventoryPurchase::parseEtiquetasInput(trim((string)($_POST['etiquetas'] ?? '')));
    }

    /** @return array{0: string, 1: string} */
    private static function parseIndexSort(): array {
        $sort = (string)($_GET['sort'] ?? 'sku');
        if (!in_array($sort, ['name', 'sku'], true)) {
            $sort = 'name';
        }
        $dir = strtolower((string)($_GET['dir'] ?? 'asc'));
        if (!in_array($dir, ['asc', 'desc'], true)) {
            $dir = 'asc';
        }
        return [$sort, $dir];
    }

    public static function index(): void {
        self::requireInventoryRole();
        if (!self::ensureTables()) {
            return;
        }
        [$inventorySort, $inventorySortDir] = self::parseIndexSort();
        $products = InventoryProduct::allForIndex($inventorySort, $inventorySortDir);
        include BASE_PATH . '/app/views/inventory/index.php';
    }

    public static function movements(): void {
        self::requireInventoryRole();
        if (!self::ensureTables()) {
            return;
        }
        $filters = self::movementFiltersFromRequest();
        $filters = array_filter([
            'product_id' => $filters['product_id'] ?? null,
            'jornada_id' => $filters['jornada_id'] ?? null,
            'purchase_id' => $filters['purchase_id'] ?? null,
        ], static fn($v) => $v !== null && $v !== '');
        $page = pagination_page('p');
        $per = 25;
        $total = InventoryMovement::count($filters);
        $movements = InventoryMovement::all($filters, $per, ($page - 1) * $per);
        $products = InventoryProduct::all();
        $jornadas = Jornada::all();
        $refCostByProduct = InventoryMovement::latestCostByProduct();
        $pagination = [
            'list' => ['total' => $total, 'page' => $page, 'per' => $per, 'param' => 'p'],
            'params' => ['p'],
            'filters' => pagination_query_preserve([
                'product_id' => $filters['product_id'] ?? null,
                'jornada_id' => $filters['jornada_id'] ?? null,
                'purchase_id' => $filters['purchase_id'] ?? null,
            ]),
        ];
        include BASE_PATH . '/app/views/inventory/movements.php';
    }

    /** @return array{product_id:?int,jornada_id:?int,purchase_id:?int} */
    private static function movementFiltersFromRequest(): array {
        $filters = [];
        if (!empty($_GET['product_id'])) {
            $filters['product_id'] = (int)$_GET['product_id'];
        }
        if (!empty($_GET['jornada_id'])) {
            $filters['jornada_id'] = (int)$_GET['jornada_id'];
        }
        if (!empty($_GET['purchase_id'])) {
            $filters['purchase_id'] = (int)$_GET['purchase_id'];
        }
        return [
            'product_id' => $filters['product_id'] ?? null,
            'jornada_id' => $filters['jornada_id'] ?? null,
            'purchase_id' => $filters['purchase_id'] ?? null,
        ];
    }

    /** @param array<int,array<string,mixed>> $jornadas */
    private static function movementFilterLabel(array $filters, array $products, array $jornadas = []): string {
        $parts = [];
        if (!empty($filters['product_id'])) {
            foreach ($products as $p) {
                if ((int)$p['id'] === (int)$filters['product_id']) {
                    $parts[] = 'Producto: ' . $p['name'] . ' (' . $p['sku'] . ')';
                    break;
                }
            }
        }
        if (!empty($filters['jornada_id'])) {
            foreach ($jornadas as $j) {
                if ((int)$j['id'] === (int)$filters['jornada_id']) {
                    $parts[] = 'Jornada: ' . $j['name'] . ' (' . $j['fecha'] . ')';
                    break;
                }
            }
        }
        if (!empty($filters['purchase_id'])) {
            $parts[] = 'Compra/donación #' . (int)$filters['purchase_id'];
        }
        return $parts !== [] ? implode(' · ', $parts) : 'Sin filtros';
    }

    private static function requireDompdfOrRedirect(string $backRoute, array $params = []): bool {
        if (class_exists(\Dompdf\Dompdf::class)) {
            return true;
        }
        flash('error', 'PDF no disponible: falta vendor/autoload.php. Con Docker: docker compose up -d --build (o docker compose exec app composer install).');
        redirect($backRoute, $params);
        return false;
    }

    private static function streamPdf(string $html, string $filename, string $orientation = 'landscape'): void {
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }

    public static function catalogPdf(): void {
        self::requireInventoryRole();
        if (!self::ensureTables()) {
            return;
        }
        if (!self::requireDompdfOrRedirect('inventory')) {
            return;
        }
        $products = InventoryProduct::allForIndex();
        $generatedAt = date('Y-m-d H:i');
        ob_start();
        include BASE_PATH . '/app/views/inventory/pdf_catalog.php';
        $html = ob_get_clean();
        self::streamPdf($html, 'inventario-' . date('Y-m-d') . '.pdf');
    }

    public static function movementsPdf(): void {
        self::requireInventoryRole();
        if (!self::ensureTables()) {
            return;
        }
        $filters = self::movementFiltersFromRequest();
        $query = array_filter([
            'product_id' => $filters['product_id'],
            'jornada_id' => $filters['jornada_id'],
            'purchase_id' => $filters['purchase_id'],
        ], fn($v) => $v !== null && $v !== '');
        if (!self::requireDompdfOrRedirect('inventory_movements', $query)) {
            return;
        }
        $filterArgs = [];
        if ($filters['product_id']) {
            $filterArgs['product_id'] = $filters['product_id'];
        }
        if ($filters['jornada_id']) {
            $filterArgs['jornada_id'] = $filters['jornada_id'];
        }
        $total = InventoryMovement::count($filterArgs);
        $movements = InventoryMovement::all($filterArgs, min(5000, max(1, $total)), 0);
        $products = InventoryProduct::all();
        $jornadas = Jornada::all();
        $refCostByProduct = InventoryMovement::latestCostByProduct();
        if ($filters['purchase_id']) {
            $filterArgs['purchase_id'] = $filters['purchase_id'];
        }
        $filterLabel = self::movementFilterLabel($filterArgs, $products, $jornadas);
        $generatedAt = date('Y-m-d H:i');
        ob_start();
        include BASE_PATH . '/app/views/inventory/pdf_movements.php';
        $html = ob_get_clean();
        self::streamPdf($html, 'movimientos-inventario-' . date('Y-m-d') . '.pdf');
    }

    public static function productCreate(): void {
        self::requireInventoryCatalogRole();
        if (!self::ensureTables()) {
            return;
        }
        include BASE_PATH . '/app/views/inventory/product_form.php';
    }

    public static function productStore(): void {
        self::requireInventoryCatalogRole();
        if (!csrf_verify()) {
            flash('error', 'CSRF inválido');
            redirect('inventory');
        }
        $sku = trim($_POST['sku'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $unit = trim($_POST['unidad_metrica'] ?? '');
        if ($sku === '' || $name === '' || $unit === '') {
            flash('error', 'Complete producto, SKU y unidad');
            redirect('inventory_product_create');
            return;
        }
        if (InventoryProduct::findBySku($sku)) {
            flash('error', 'El SKU ya existe');
            redirect('inventory_product_create');
            return;
        }
        InventoryProduct::create(['name' => $name, 'sku' => $sku, 'unidad_metrica' => $unit]);
        flash('success', 'Producto registrado');
        redirect('inventory');
    }

    public static function productEdit(): void {
        self::requireInventoryCatalogRole();
        $id = (int)($_GET['id'] ?? 0);
        $product = InventoryProduct::find($id);
        if (!$product) {
            flash('error', 'Producto no encontrado');
            redirect('inventory');
            return;
        }
        include BASE_PATH . '/app/views/inventory/product_form.php';
    }

    public static function productUpdate(): void {
        self::requireInventoryCatalogRole();
        if (!csrf_verify()) {
            flash('error', 'CSRF inválido');
            redirect('inventory');
        }
        $id = (int)($_POST['id'] ?? 0);
        $product = InventoryProduct::find($id);
        if (!$product) {
            flash('error', 'Producto no encontrado');
            redirect('inventory');
            return;
        }
        $sku = trim($_POST['sku'] ?? '');
        if ($sku !== $product['sku']) {
            flash('error', 'El SKU no se puede modificar');
            redirect('inventory_product_edit', ['id' => $id]);
            return;
        }
        InventoryProduct::update($id, [
            'name' => trim($_POST['name'] ?? ''),
            'unidad_metrica' => trim($_POST['unidad_metrica'] ?? ''),
        ]);
        flash('success', 'Producto actualizado');
        redirect('inventory');
    }

    public static function initialStockForm(): void {
        self::requireInventoryRole();
        if (!self::ensureTables()) {
            return;
        }
        $products = InventoryProduct::all();
        include BASE_PATH . '/app/views/inventory/initial_stock.php';
    }

    public static function initialStockStore(): void {
        self::requireInventoryRole();
        if (!csrf_verify()) {
            flash('error', 'CSRF inválido');
            redirect('inventory');
        }
        self::storeEntryMovement('stock_inicial', 'inventory_initial');
    }

    public static function purchaseForm(): void {
        self::requireInventoryRole();
        if (!self::ensureTables()) {
            return;
        }
        self::ensurePurchaseColumns();
        $products = InventoryProduct::all();
        include BASE_PATH . '/app/views/inventory/purchase.php';
    }

    public static function purchasesIndex(): void {
        self::requireInventoryRole();
        if (!self::ensureTables()) {
            return;
        }
        self::ensurePurchaseColumns();
        $purchaseFilters = self::purchaseListFilters();
        $compras = InventoryPurchase::allSummaries(false, $purchaseFilters);
        $donaciones = InventoryPurchase::allSummaries(true, $purchaseFilters);
        $etiquetaOptions = InventoryPurchase::allDistinctEtiquetas();
        include BASE_PATH . '/app/views/inventory/purchases.php';
    }

    public static function purchaseJson(): void {
        self::requireInventoryRole();
        if (!self::ensureTables()) {
            return;
        }
        header('Content-Type: application/json; charset=UTF-8');
        $id = (int)($_GET['id'] ?? 0);
        $purchase = InventoryPurchase::find($id);
        if (!$purchase) {
            http_response_code(404);
            echo json_encode(['error' => 'No encontrado'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $linesOut = [];
        $totalBs = 0.0;
        foreach (InventoryPurchase::lines($id) as $m) {
            $qty = (float)$m['cantidad'];
            $unit = (float)($m['precio_bs'] ?? 0);
            $lineBs = $qty * $unit;
            $totalBs += $lineBs;
            $linesOut[] = [
                'id' => (int)$m['id'],
                'product_id' => (int)$m['product_id'],
                'product_name' => $m['product_name'],
                'sku' => $m['sku'],
                'unidad_metrica' => $m['unidad_metrica'],
                'cantidad' => $qty,
                'precio_bs' => $m['precio_bs'] !== null ? (float)$m['precio_bs'] : 0,
                'total_bs' => round($lineBs, 2),
            ];
        }
        $summary = array_merge($purchase, ['total_bs' => $totalBs]);
        $totalUsd = InventoryPurchase::totalUsdForSummary($summary);
        echo json_encode([
            'purchase' => [
                'id' => (int)$purchase['id'],
                'fecha' => $purchase['fecha'],
                'tasa' => (float)$purchase['tasa'],
                'notes' => $purchase['notes'],
                'es_donacion' => (int)$purchase['es_donacion'] === 1,
                'donante' => $purchase['donante'],
                'referencia' => $purchase['referencia'],
                'etiquetas' => InventoryPurchase::etiquetasList($purchase['etiquetas'] ?? null),
                'etiquetas_text' => InventoryPurchase::etiquetasToInput($purchase['etiquetas'] ?? null),
                'created_by_name' => $purchase['created_by_name'] ?? null,
                'total_bs' => round($totalBs, 2),
                'total_usd' => $totalUsd,
            ],
            'lines' => $linesOut,
        ], JSON_UNESCAPED_UNICODE);
    }

    public static function purchaseUpdateStore(): void {
        self::requireInventoryRole();
        if (!self::ensureTables()) {
            return;
        }
        if (!csrf_verify()) {
            flash('error', 'CSRF inválido');
            redirect('inventory_purchases');
        }
        $id = (int)($_POST['id'] ?? 0);
        $purchase = InventoryPurchase::find($id);
        if (!$purchase) {
            flash('error', 'Compra no encontrada');
            redirect('inventory_purchases');
            return;
        }
        $esDonacion = !empty($_POST['es_donacion']) || (int)$purchase['es_donacion'] === 1;
        $fecha = trim($_POST['fecha'] ?? '');
        $tasa = (float)($_POST['tasa'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        $referencia = trim($_POST['referencia'] ?? '');
        $donante = trim($_POST['donante'] ?? '');
        if ($fecha === '') {
            flash('error', 'La fecha es obligatoria');
            redirect('inventory_purchases');
            return;
        }
        if ($esDonacion && $donante === '') {
            flash('error', 'Indique el donante');
            redirect('inventory_purchases');
            return;
        }
        if (!$esDonacion && $tasa <= 0) {
            flash('error', 'Indique la tasa del día');
            redirect('inventory_purchases');
            return;
        }
        if ($esDonacion && $tasa <= 0) {
            $tasa = 0;
        }
        $lines = self::parsePurchaseLines($_POST);
        if ($lines === []) {
            flash('error', 'Indique al menos una línea válida');
            redirect('inventory_purchases');
            return;
        }
        try {
            InventoryPurchase::update(
                $id,
                [
                    'fecha' => $fecha,
                    'tasa' => $tasa,
                    'notes' => $notes !== '' ? $notes : null,
                    'es_donacion' => $esDonacion,
                    'donante' => $esDonacion && $donante !== '' ? $donante : null,
                    'referencia' => !$esDonacion && $referencia !== '' ? $referencia : null,
                    'etiquetas' => self::etiquetasFromPost(),
                ],
                $lines,
                Auth::user()['id'] ?? null
            );
            flash('success', 'Registro actualizado');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('inventory_purchases');
    }

    public static function purchaseDeleteStore(): void {
        self::requireInventoryRole();
        if (!self::ensureTables()) {
            return;
        }
        if (!csrf_verify()) {
            flash('error', 'CSRF inválido');
            redirect('inventory_purchases');
        }
        $id = (int)($_POST['id'] ?? 0);
        try {
            InventoryPurchase::delete($id);
            flash('success', 'Registro eliminado e inventario ajustado');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('inventory_purchases');
    }

    public static function purchaseStore(): void {
        self::requireInventoryRole();
        if (!csrf_verify()) {
            flash('error', 'CSRF inválido');
            redirect('inventory');
        }
        $fecha = $_POST['fecha'] ?? date('Y-m-d');
        $esDonacion = !empty($_POST['es_donacion']);
        $tasa = (float)($_POST['tasa'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        $referencia = trim($_POST['referencia'] ?? '');
        $donante = trim($_POST['donante'] ?? '');
        if ($esDonacion && $donante === '') {
            flash('error', 'Indique el donante cuando la compra es una donación');
            redirect('inventory_purchase');
            return;
        }
        if (!$esDonacion && $tasa <= 0) {
            flash('error', 'Indique la tasa del día de compra');
            redirect('inventory_purchase');
            return;
        }
        if ($esDonacion && $tasa <= 0) {
            $tasa = 0;
        }
        $lines = self::parsePurchaseLines($_POST);
        if (!$lines) {
            flash('error', 'Agregue al menos un producto a la compra');
            redirect('inventory_purchase');
            return;
        }
        try {
            InventoryPurchase::create(
                [
                    'fecha' => $fecha,
                    'tasa' => $tasa,
                    'notes' => $notes !== '' ? $notes : null,
                    'es_donacion' => $esDonacion,
                    'donante' => $esDonacion && $donante !== '' ? $donante : null,
                    'referencia' => $referencia !== '' ? $referencia : null,
                    'etiquetas' => self::etiquetasFromPost(),
                ],
                $lines,
                Auth::user()['id'] ?? null
            );
            flash('success', 'Compra registrada e inventario actualizado');
            redirect('inventory_purchases');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect('inventory_purchase');
        }
    }

    public static function jornadaDeliverStore(): void {
        self::requireInventoryRole();
        if (!csrf_verify()) {
            flash('error', 'CSRF inválido');
            redirect('jornadas');
        }
        $jid = (int)($_POST['jornada_id'] ?? 0);
        $jornada = Jornada::find($jid);
        if (!$jornada) {
            flash('error', 'Jornada no encontrada');
            redirect('jornadas');
            return;
        }
        self::assertJornadaInventory($jid);
        $fecha = $_POST['fecha_ingreso'] ?? $jornada['fecha'];
        $productIds = $_POST['product_id'] ?? [];
        $cantidades = $_POST['cantidad'] ?? [];
        $notes = trim($_POST['notes'] ?? '');
        $userId = Auth::user()['id'] ?? null;
        $done = 0;
        try {
            foreach ($productIds as $i => $pid) {
                $pid = (int)$pid;
                $qty = (float)($cantidades[$i] ?? 0);
                if ($pid <= 0 || $qty <= 0) {
                    continue;
                }
                InventoryMovement::register([
                    'product_id' => $pid,
                    'tipo' => 'salida_jornada',
                    'cantidad' => $qty,
                    'fecha_ingreso' => $fecha,
                    'jornada_id' => $jid,
                    'notes' => $notes !== '' ? $notes : 'Entrega de ingredientes a jornada',
                    'created_by' => $userId,
                ]);
                $done++;
            }
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect('jornada_show', ['id' => $jid, 'tab' => 'inventario', 'inv_sub' => 'entrega']);
            return;
        }
        if ($done === 0) {
            flash('error', 'Indique al menos un producto y cantidad');
            redirect('jornada_show', ['id' => $jid, 'tab' => 'inventario', 'inv_sub' => 'entrega']);
            return;
        }
        flash('success', 'Salida de inventario registrada para la jornada');
        redirect('jornada_show', ['id' => $jid, 'tab' => 'inventario', 'inv_sub' => 'salidas']);
    }

    public static function jornadaSalidaUpdate(): void {
        self::requireInventoryRole();
        if (!csrf_verify()) {
            flash('error', 'CSRF inválido');
            redirect('jornadas');
        }
        $jid = (int)($_POST['jornada_id'] ?? 0);
        $movId = (int)($_POST['movement_id'] ?? 0);
        self::assertJornadaInventory($jid);
        $mov = InventoryMovement::find($movId);
        if (!$mov || (int)($mov['jornada_id'] ?? 0) !== $jid) {
            flash('error', 'Salida no encontrada');
            redirect('jornada_show', ['id' => $jid, 'tab' => 'inventario', 'inv_sub' => 'salidas']);
            return;
        }
        $cantidad = (float)($_POST['cantidad'] ?? 0);
        $fecha = trim((string)($_POST['fecha_ingreso'] ?? ''));
        $notes = trim((string)($_POST['notes'] ?? ''));
        if ($fecha === '') {
            flash('error', 'Indique la fecha');
            redirect('jornada_show', ['id' => $jid, 'tab' => 'inventario', 'inv_sub' => 'salidas']);
            return;
        }
        try {
            InventoryMovement::updateSalidaJornada($movId, $cantidad, $fecha, $notes);
            flash('success', 'Salida actualizada');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('jornada_show', ['id' => $jid, 'tab' => 'inventario', 'inv_sub' => 'salidas']);
    }

    public static function jornadaSalidaDelete(): void {
        self::requireInventoryRole();
        if (!csrf_verify()) {
            flash('error', 'CSRF inválido');
            redirect('jornadas');
        }
        $jid = (int)($_POST['jornada_id'] ?? 0);
        $movId = (int)($_POST['movement_id'] ?? 0);
        self::assertJornadaInventory($jid);
        $mov = InventoryMovement::find($movId);
        if (!$mov || (int)($mov['jornada_id'] ?? 0) !== $jid) {
            flash('error', 'Salida no encontrada');
            redirect('jornada_show', ['id' => $jid, 'tab' => 'inventario', 'inv_sub' => 'salidas']);
            return;
        }
        try {
            InventoryMovement::deleteSalidaJornada($movId);
            flash('success', 'Salida eliminada y stock restaurado');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('jornada_show', ['id' => $jid, 'tab' => 'inventario', 'inv_sub' => 'salidas']);
    }

    public static function jornadaReturnStore(): void {
        self::requireInventoryRole();
        if (!csrf_verify()) {
            flash('error', 'CSRF inválido');
            redirect('jornadas');
        }
        $jid = (int)($_POST['jornada_id'] ?? 0);
        $jornada = Jornada::find($jid);
        if (!$jornada) {
            flash('error', 'Jornada no encontrada');
            redirect('jornadas');
            return;
        }
        self::assertJornadaInventory($jid);
        $fecha = $_POST['fecha_ingreso'] ?? $jornada['fecha'];
        $productIds = $_POST['return_product_id'] ?? [];
        $cantidades = $_POST['return_cantidad'] ?? [];
        $notes = trim($_POST['return_notes'] ?? '');
        $userId = Auth::user()['id'] ?? null;
        $done = 0;
        try {
            foreach ($productIds as $i => $pid) {
                $pid = (int)$pid;
                $qty = (float)($cantidades[$i] ?? 0);
                if ($pid <= 0 || $qty <= 0) {
                    continue;
                }
                $max = InventoryMovement::maxDevolucion($jid, $pid);
                if ($qty > $max + 0.0001) {
                    throw new InvalidArgumentException('La devolución supera lo entregado pendiente por producto (máx. ' . $max . ').');
                }
                InventoryMovement::register([
                    'product_id' => $pid,
                    'tipo' => 'devolucion_jornada',
                    'cantidad' => $qty,
                    'fecha_ingreso' => $fecha,
                    'jornada_id' => $jid,
                    'notes' => $notes !== '' ? $notes : 'Excedente devuelto al almacén',
                    'created_by' => $userId,
                ]);
                $done++;
            }
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect('jornada_show', ['id' => $jid, 'tab' => 'inventario', 'inv_sub' => 'balance']);
            return;
        }
        if ($done === 0) {
            flash('error', 'Indique al menos un producto y cantidad a devolver');
            redirect('jornada_show', ['id' => $jid, 'tab' => 'inventario', 'inv_sub' => 'balance']);
            return;
        }
        flash('success', 'Excedentes registrados; stock actualizado en almacén');
        redirect('jornada_show', ['id' => $jid, 'tab' => 'inventario', 'inv_sub' => 'balance']);
    }

    private static function storeEntryMovement(string $tipo, string $redirectRoute): void {
        $productId = (int)($_POST['product_id'] ?? 0);
        $cantidad = (float)($_POST['cantidad'] ?? 0);
        $precioBs = (float)($_POST['precio_bs'] ?? 0);
        $tasa = (float)($_POST['tasa'] ?? 0);
        $fecha = $_POST['fecha_ingreso'] ?? date('Y-m-d');
        if ($productId <= 0 || $cantidad <= 0) {
            flash('error', 'Producto y cantidad son obligatorios');
            redirect($redirectRoute);
            return;
        }
        if ($precioBs < 0 || $tasa <= 0) {
            flash('error', 'Indique precio en Bs y tasa válida');
            redirect($redirectRoute);
            return;
        }
        try {
            InventoryMovement::register([
                'product_id' => $productId,
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'precio_bs' => $precioBs,
                'tasa' => $tasa,
                'fecha_ingreso' => $fecha,
                'notes' => trim($_POST['notes'] ?? ''),
                'created_by' => Auth::user()['id'] ?? null,
            ]);
            flash('success', 'Movimiento registrado');
            redirect('inventory');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect($redirectRoute);
        }
    }

    /** @return list<array{product_id:int,cantidad:float,precio_bs:float,notes:?string}> */
    private static function parsePurchaseLines(array $post): array {
        $lines = [];
        $productIds = $post['product_id'] ?? [];
        $cantidades = $post['cantidad'] ?? [];
        $precios = $post['precio_bs'] ?? [];
        $lineNotes = $post['line_notes'] ?? [];
        foreach ($productIds as $i => $pid) {
            $pid = (int)$pid;
            $qty = self::parseDecimalCell((string)($cantidades[$i] ?? ''));
            $pb = self::parseDecimalCell((string)($precios[$i] ?? ''));
            if ($pid <= 0 || $qty === null || $qty <= 0 || $pb === null || $pb < 0) {
                continue;
            }
            $lines[] = [
                'product_id' => $pid,
                'cantidad' => $qty,
                'precio_bs' => $pb,
                'notes' => trim($lineNotes[$i] ?? '') ?: null,
            ];
        }
        return $lines;
    }

    public static function exportCsv(): void {
        self::requireInventoryRole();
        if (!self::ensureTables()) {
            return;
        }
        $products = InventoryProduct::allForIndex();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="inventario-productos-' . date('Y-m-d') . '.csv"');
        echo "\xEF\xBB\xBF";
        echo self::csvLine(['sku', 'producto', 'unidad_metrica', 'stock', 'precio_bs', 'tasa', 'fecha_ingreso'], ';') . "\r\n";
        foreach ($products as $p) {
            echo self::csvLine([
                $p['sku'],
                $p['name'],
                $p['unidad_metrica'],
                rtrim(rtrim(number_format((float)$p['stock'], 3, '.', ''), '0'), '.'),
                $p['ultimo_precio_bs'] !== null && $p['ultimo_precio_bs'] !== '' ? rtrim(rtrim(number_format((float)$p['ultimo_precio_bs'], 4, '.', ''), '0'), '.') : '',
                $p['ultima_tasa'] !== null && $p['ultima_tasa'] !== '' ? rtrim(rtrim(number_format((float)$p['ultima_tasa'], 4, '.', ''), '0'), '.') : '',
                $p['ultima_fecha_ingreso'] ?? '',
            ], ';') . "\r\n";
        }
        exit;
    }

    public static function importForm(): void {
        self::requireInventoryRole();
        if (!self::ensureTables()) {
            return;
        }
        include BASE_PATH . '/app/views/inventory/import.php';
    }

    public static function purchaseDonationsTemplate(): void {
        self::requireInventoryRole();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="plantilla-donaciones-compras.csv"');
        echo "\xEF\xBB\xBF";
        echo self::csvLine(['fecha', 'donante', 'sku', 'cantidad', 'precio_bs', 'notas'], ';') . "\r\n";
        echo self::csvLine(['2026-10-15', 'Nombre del donante', 'SKU-DEL-PRODUCTO', '1', '8800', 'opcional'], ';') . "\r\n";
        exit;
    }

    public static function purchaseDonationsImportStore(): void {
        self::requireInventoryRole();
        if (!self::ensureTables()) {
            return;
        }
        if (!csrf_verify()) {
            flash('error', 'CSRF inválido');
            redirect('inventory_purchase');
        }
        if (empty($_FILES['csv']) || !is_uploaded_file($_FILES['csv']['tmp_name'])) {
            flash('error', 'Seleccione un archivo CSV');
            redirect('inventory_purchase');
            return;
        }
        try {
            $allRows = self::readCsvRows($_FILES['csv']['tmp_name']);
        } catch (InvalidArgumentException $e) {
            flash('error', $e->getMessage());
            redirect('inventory_purchase');
            return;
        }
        $header = array_map([self::class, 'normalizeCsvHeaderName'], $allRows[0]);
        $idx = [
            'fecha' => self::csvColumnIndex($header, 'fecha', ['fech', 'fecha_compra', 'fecha_de_compra', 'date']),
            'donante' => self::csvColumnIndex($header, 'donante', ['apellidos_y_nombres', 'nombre', 'donor']),
            'sku' => self::csvColumnIndex($header, 'sku', ['rubro', 'codigo', 'code']),
            'cantidad' => self::csvColumnIndex($header, 'cantidad', ['unidades', 'qty', 'cant']),
            'precio_bs' => self::csvColumnIndex($header, 'precio_bs', ['precio_unitario', 'precio_unitario_bs', 'precio']),
            'notas' => self::csvColumnIndex($header, 'notas', ['notes', 'observaciones']),
        ];
        if ($idx['fecha'] === false || $idx['donante'] === false || $idx['sku'] === false || $idx['cantidad'] === false || $idx['precio_bs'] === false) {
            flash('error', 'El CSV debe incluir: fecha, donante, sku (rubro), cantidad, precio_bs');
            redirect('inventory_purchase');
            return;
        }
        /** @var array<string, array{fecha:string,donante:string,lines:array<int,array{product_id:int,cantidad:float,precio_bs:float,notes:?string}>}> $groups */
        $groups = [];
        $errors = [];
        $lineNum = 1;
        for ($r = 1, $n = count($allRows); $r < $n; $r++) {
            $lineNum++;
            $row = self::alignDonationCsvRow($allRows[$r], count($allRows[0]), $idx);
            if (count(array_filter($row, static fn($c) => trim((string)$c) !== '')) === 0) {
                continue;
            }
            $fechaRaw = trim((string)($row[$idx['fecha']] ?? ''));
            $fecha = self::parseDonationCsvDate($fechaRaw);
            $donante = trim((string)($row[$idx['donante']] ?? ''));
            $donante = preg_replace('/\s*;\s*/', ' ', $donante) ?? $donante;
            $donante = trim($donante, " \t\"'");
            $sku = trim((string)($row[$idx['sku']] ?? ''));
            $qty = self::parseDecimalCell((string)($row[$idx['cantidad']] ?? ''));
            $precioRaw = trim((string)($row[$idx['precio_bs']] ?? ''));
            $precio = $precioRaw === '' ? 0.0 : self::parseDecimalCell($precioRaw);
            $notas = $idx['notas'] !== false ? trim((string)($row[$idx['notas']] ?? '')) : '';
            if ($fecha === null || $donante === '' || $sku === '') {
                $errors[] = "Línea $lineNum: fecha, donante y sku obligatorios"
                    . ($fechaRaw !== '' && $fecha === null ? " (fecha «$fechaRaw» no reconocida)" : '');
                continue;
            }
            if ($qty === null || $qty <= 0 || $precio === null || $precio < 0) {
                $errors[] = "Línea $lineNum: cantidad y precio_bs inválidos (cantidad="
                    . trim((string)($row[$idx['cantidad']] ?? '')) . ', precio_bs='
                    . $precioRaw . ')';
                continue;
            }
            $product = InventoryProduct::resolveImportCode($sku);
            if (!$product) {
                $errors[] = "Línea $lineNum: producto «$sku» no existe (sku o id en inventario)";
                continue;
            }
            $key = $fecha . '|' . mb_strtolower($donante, 'UTF-8');
            if (!isset($groups[$key])) {
                $groups[$key] = ['fecha' => $fecha, 'donante' => $donante, 'lines' => []];
            }
            $groups[$key]['lines'][] = [
                'product_id' => (int)$product['id'],
                'cantidad' => $qty,
                'precio_bs' => $precio,
                'notes' => $notas !== '' ? $notas : null,
            ];
        }
        if ($groups === []) {
            flash('error', $errors ? implode(' | ', array_slice($errors, 0, 5)) : 'No hay filas válidas para importar');
            redirect('inventory_purchase');
            return;
        }
        $userId = Auth::user()['id'] ?? null;
        $created = 0;
        foreach ($groups as $group) {
            try {
                InventoryPurchase::create(
                    [
                        'fecha' => $group['fecha'],
                        'tasa' => 0,
                        'notes' => 'Importación CSV donaciones',
                        'es_donacion' => true,
                        'donante' => $group['donante'],
                        'referencia' => null,
                    ],
                    $group['lines'],
                    $userId
                );
                $created++;
            } catch (Throwable $e) {
                $errors[] = 'Donante ' . $group['donante'] . ': ' . $e->getMessage();
            }
        }
        $msg = "Donaciones importadas: $created compra(s) registrada(s).";
        if ($errors) {
            flash('error', $msg . ' Errores: ' . implode(' | ', array_slice($errors, 0, 5)));
        } else {
            flash('success', $msg);
        }
        redirect('inventory_purchases');
    }

    public static function templateCsv(): void {
        self::requireInventoryRole();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="plantilla-inventario.csv"');
        echo "\xEF\xBB\xBF";
        // Punto y coma: Excel en español abre columnas correctamente (sin envolver toda la fila en comillas).
        echo self::csvLine(['sku', 'producto', 'unidad_metrica', 'stock', 'precio_bs', 'tasa', 'fecha_ingreso'], ';') . "\r\n";
        echo self::csvLine(['EJ-001', 'Ejemplo producto', 'kg', '100', '25.50', '36.50', date('Y-m-d')], ';') . "\r\n";
        exit;
    }

    /** @param array<string,mixed> $row */
    private static function displayUsdForProductRow(array $row): ?float {
        if (isset($row['ultimo_precio_usd']) && $row['ultimo_precio_usd'] !== null && $row['ultimo_precio_usd'] !== '') {
            return (float)$row['ultimo_precio_usd'];
        }
        $bs = isset($row['ultimo_precio_bs']) ? (float)$row['ultimo_precio_bs'] : 0.0;
        $tasa = isset($row['ultima_tasa']) ? (float)$row['ultima_tasa'] : 0.0;
        if ($bs > 0 && $tasa > 0) {
            return InventoryMovement::usdFromBs($bs, $tasa);
        }
        return null;
    }

    private static function parseDecimalCell(string $raw): ?float {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        if (strlen($raw) >= 2 && $raw[0] === '"' && substr($raw, -1) === '"') {
            $raw = substr($raw, 1, -1);
        }
        $raw = preg_replace('/[\s\x{00A0}\x{202F}]/u', '', $raw) ?? $raw;
        $raw = preg_replace('/\p{Sc}|\b(?:Bs\.?|VEF|USD)\b/ui', '', $raw) ?? $raw;
        $raw = str_replace(["\u{FF0E}", "\u{066B}"], '.', $raw);
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        if (!preg_match('/^-?\d[\d.,]*/', $raw, $m)) {
            return null;
        }
        $raw = $m[0];
        $hasComma = str_contains($raw, ',');
        $hasDot = str_contains($raw, '.');
        if ($hasComma && $hasDot) {
            if (strrpos($raw, ',') > strrpos($raw, '.')) {
                $raw = str_replace('.', '', $raw);
                $raw = str_replace(',', '.', $raw);
            } else {
                $raw = str_replace(',', '', $raw);
            }
        } elseif ($hasComma) {
            $raw = str_replace(',', '.', $raw);
        }
        if (!is_numeric($raw)) {
            return null;
        }
        return (float)$raw;
    }

    /** @param array<int,string|false> $idx */
    private static function alignDonationCsvRow(array $row, int $headerLen, array $idx): array {
        if ($headerLen <= 0) {
            return $row;
        }
        if (count($row) < $headerLen) {
            return array_pad($row, $headerLen, '');
        }
        $cIdx = $idx['cantidad'];
        $pIdx = $idx['precio_bs'];
        if ($cIdx === false || $pIdx === false) {
            return $row;
        }
        $cIdx = (int)$cIdx;
        $pIdx = (int)$pIdx;
        while (count($row) > $headerLen) {
            $before = count($row);
            if (isset($row[$cIdx + 1]) && self::csvDecimalSplitFragment($row[$cIdx], $row[$cIdx + 1])) {
                $row[$cIdx] = $row[$cIdx] . ',' . $row[$cIdx + 1];
                array_splice($row, $cIdx + 1, 1);
            } elseif (isset($row[$pIdx + 1]) && self::csvDecimalSplitFragment($row[$pIdx], $row[$pIdx + 1])) {
                $row[$pIdx] = $row[$pIdx] . ',' . $row[$pIdx + 1];
                array_splice($row, $pIdx + 1, 1);
            } else {
                break;
            }
            if (count($row) === $before) {
                break;
            }
        }
        if (count($row) > $headerLen) {
            $row = array_slice($row, 0, $headerLen);
        }
        return $row;
    }

    private static function csvDecimalSplitFragment(string $whole, string $frac): bool {
        $whole = trim($whole);
        $frac = trim($frac);
        return preg_match('/^\d+$/', $whole) === 1 && preg_match('/^\d{1,3}$/', $frac) === 1;
    }

    private static function parseDonationCsvDate(string $raw): ?string {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) === 1) {
            return $raw;
        }
        foreach (['d/m/Y', 'd-m-Y', 'd/m/y', 'j/n/Y', 'j/n/y'] as $fmt) {
            $dt = DateTime::createFromFormat($fmt, $raw);
            if ($dt instanceof DateTime) {
                $errors = DateTime::getLastErrors();
                if ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
                    continue;
                }
                return $dt->format('Y-m-d');
            }
        }
        $ts = strtotime($raw);
        if ($ts !== false) {
            return date('Y-m-d', $ts);
        }
        return null;
    }

    /** @param array<int,string> $row */
    private static function importMovementPayload(
        array $row,
        array $idx,
        string $defaultFecha
    ): array {
        $precioBs = $idx['precio_bs'] !== false ? self::parseDecimalCell((string)($row[$idx['precio_bs']] ?? '')) : null;
        $tasa = $idx['tasa'] !== false ? self::parseDecimalCell((string)($row[$idx['tasa']] ?? '')) : null;
        $fechaRaw = $idx['fecha_ingreso'] !== false ? trim((string)($row[$idx['fecha_ingreso']] ?? '')) : '';
        $fecha = $fechaRaw !== '' ? $fechaRaw : $defaultFecha;
        $payload = ['fecha_ingreso' => $fecha];
        if ($precioBs !== null) {
            $payload['precio_bs'] = $precioBs;
        }
        if ($tasa !== null) {
            $payload['tasa'] = $tasa;
        }
        if ($idx['precio_usd'] !== false) {
            $usd = self::parseDecimalCell((string)($row[$idx['precio_usd']] ?? ''));
            if ($usd !== null) {
                $payload['precio_usd'] = $usd;
            }
        }
        return $payload;
    }

    /** @param array<int,string|int|float> $fields */
    private static function csvLine(array $fields, string $delimiter = ';'): string {
        $parts = [];
        foreach ($fields as $field) {
            $value = (string)$field;
            if (str_contains($value, $delimiter) || str_contains($value, '"') || str_contains($value, "\n") || str_contains($value, "\r")) {
                $value = '"' . str_replace('"', '""', $value) . '"';
            }
            $parts[] = $value;
        }
        return implode($delimiter, $parts);
    }

    /** @return array<int,array<int,string>> */
    private static function readCsvRows(string $tmpPath): array {
        $raw = file_get_contents($tmpPath);
        if ($raw === false || $raw === '') {
            throw new InvalidArgumentException('Archivo vacío');
        }
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);
        }
        $lines = preg_split('/\R/u', $raw, -1, PREG_SPLIT_NO_EMPTY);
        if (!$lines) {
            throw new InvalidArgumentException('Archivo vacío');
        }
        $headerDelimiter = self::detectCsvDelimiter(trim($lines[0]));
        $rows = [];
        foreach ($lines as $i => $line) {
            $row = self::expandCsvRow($line, $headerDelimiter);
            if ($i > 0 && $rows !== [] && count($row) < count($rows[0])) {
                $alt = $headerDelimiter === ';' ? ',' : ';';
                $retry = self::expandCsvRow($line, $alt);
                if (count($retry) > count($row)) {
                    $row = $retry;
                }
            }
            if ($row !== []) {
                $rows[] = $row;
            }
        }
        if ($rows === []) {
            throw new InvalidArgumentException('Archivo vacío');
        }
        return $rows;
    }

    /** @return array<int,string> */
    private static function expandCsvRow(string $line, ?string $delimiter = null): array {
        $line = trim($line);
        if ($line === '') {
            return [];
        }
        $delimiter ??= self::detectCsvDelimiter($line);
        $row = str_getcsv($line, $delimiter, '"', '\\');
        if (count($row) > 1) {
            return array_map(fn($c) => trim((string)$c), $row);
        }
        $single = trim((string)($row[0] ?? ''));
        if ($single === '') {
            return [];
        }
        // Excel a veces guarda toda la fila como un solo campo entre comillas.
        if (strlen($single) >= 2 && $single[0] === '"' && substr($single, -1) === '"') {
            $single = str_replace('""', '"', substr($single, 1, -1));
        }
        $innerDelim = $delimiter;
        $expanded = str_getcsv($single, $innerDelim, '"', '\\');
        if (count($expanded) > 1) {
            return array_map(fn($c) => trim((string)$c), $expanded);
        }
        $altDelim = $innerDelim === ',' ? ';' : ',';
        if (str_contains($single, $altDelim)) {
            $expanded = str_getcsv($single, $altDelim, '"', '\\');
            if (count($expanded) > 1) {
                return array_map(fn($c) => trim((string)$c), $expanded);
            }
        }
        return [$single];
    }

    private static function detectCsvDelimiter(string $line): string {
        $comma = substr_count($line, ',');
        $semi = substr_count($line, ';');
        $tab = substr_count($line, "\t");
        if ($semi > $comma && $semi >= $tab) {
            return ';';
        }
        if ($tab > $comma && $tab > $semi) {
            return "\t";
        }
        return ',';
    }

    private static function normalizeCsvHeaderName(string $cell): string {
        $cell = trim($cell);
        $cell = preg_replace('/^\xEF\xBB\xBF/u', '', $cell) ?? $cell;
        $cell = strtolower($cell);
        $cell = str_replace([' ', '-'], '_', $cell);
        return $cell;
    }

    /** @param array<int,string> $header */
    private static function csvColumnIndex(array $header, string $canonical, array $aliases): int|false {
        $keys = array_merge([$canonical], $aliases);
        foreach ($keys as $key) {
            $pos = array_search($key, $header, true);
            if ($pos !== false) {
                return $pos;
            }
        }
        return false;
    }

    public static function importStore(): void {
        self::requireInventoryRole();
        if (!self::ensureTables()) {
            return;
        }
        if (!csrf_verify()) {
            flash('error', 'CSRF inválido');
            redirect('inventory');
        }
        if (empty($_FILES['csv']) || !is_uploaded_file($_FILES['csv']['tmp_name'])) {
            flash('error', 'Seleccione un archivo CSV');
            redirect('inventory_import');
            return;
        }
        $tmp = $_FILES['csv']['tmp_name'];
        try {
            $allRows = self::readCsvRows($tmp);
        } catch (InvalidArgumentException $e) {
            flash('error', $e->getMessage());
            redirect('inventory_import');
            return;
        }
        $header = array_map([self::class, 'normalizeCsvHeaderName'], $allRows[0]);
        $idx = [
            'sku' => self::csvColumnIndex($header, 'sku', []),
            'producto' => self::csvColumnIndex($header, 'producto', ['product', 'nombre', 'name']),
            'unidad_metrica' => self::csvColumnIndex($header, 'unidad_metrica', ['unidad', 'unit', 'metrica']),
            'stock' => self::csvColumnIndex($header, 'stock', ['cantidad', 'existencia']),
            'precio_bs' => self::csvColumnIndex($header, 'precio_bs', ['precio_unitario_bs', 'precio_unitario', 'precio']),
            'tasa' => self::csvColumnIndex($header, 'tasa', ['tasa_cambio', 'rate']),
            'fecha_ingreso' => self::csvColumnIndex($header, 'fecha_ingreso', ['fecha', 'fecha_de_ingreso']),
            'precio_usd' => self::csvColumnIndex($header, 'precio_usd', ['precio_unitario_usd']),
        ];
        if ($idx['sku'] === false || $idx['producto'] === false || $idx['unidad_metrica'] === false) {
            $found = implode(', ', array_filter($header, fn($h) => $h !== ''));
            flash('error', 'El CSV debe incluir columnas: sku, producto, unidad_metrica (stock opcional). Encabezados detectados: ' . ($found !== '' ? $found : '(ninguno)'));
            redirect('inventory_import');
            return;
        }
        $created = 0;
        $updated = 0;
        $userId = Auth::user()['id'] ?? null;
        $fecha = date('Y-m-d');
        $errors = [];
        $lineNum = 1;
        for ($r = 1, $rowCount = count($allRows); $r < $rowCount; $r++) {
            $lineNum++;
            $row = $allRows[$r];
            if (count(array_filter($row, fn($c) => trim((string)$c) !== '')) === 0) {
                continue;
            }
            $sku = trim((string)($row[$idx['sku']] ?? ''));
            $name = trim((string)($row[$idx['producto']] ?? ''));
            $unit = trim((string)($row[$idx['unidad_metrica']] ?? ''));
            $stockRaw = $idx['stock'] !== false ? trim((string)($row[$idx['stock']] ?? '')) : '';
            $targetStock = $stockRaw !== '' ? self::parseDecimalCell($stockRaw) : null;
            $costPayload = self::importMovementPayload($row, $idx, $fecha);
            $hasCost = isset($costPayload['precio_bs']) || isset($costPayload['tasa']) || isset($costPayload['precio_usd']);
            if ($sku === '' || $name === '' || $unit === '') {
                $errors[] = "Línea $lineNum: sku, producto y unidad obligatorios";
                continue;
            }
            $existing = InventoryProduct::findBySku($sku);
            try {
                if ($existing) {
                    $pid = (int)$existing['id'];
                    InventoryProduct::update($pid, ['name' => $name, 'unidad_metrica' => $unit]);
                    $updated++;
                    $movementRegistered = false;
                    if ($targetStock !== null) {
                        $current = InventoryProduct::stockOf($pid);
                        $delta = $targetStock - $current;
                        if (abs($delta) > 0.0001) {
                            InventoryMovement::register(array_merge([
                                'product_id' => $pid,
                                'tipo' => 'ajuste',
                                'cantidad' => $delta,
                                'notes' => 'Ajuste por importación CSV',
                                'created_by' => $userId,
                            ], $costPayload));
                            $movementRegistered = true;
                        }
                    }
                    if ($hasCost && !$movementRegistered) {
                        InventoryMovement::register(array_merge([
                            'product_id' => $pid,
                            'tipo' => 'ajuste',
                            'cantidad' => 0,
                            'notes' => 'Precio/tasa por importación CSV',
                            'created_by' => $userId,
                        ], $costPayload));
                    }
                } else {
                    $newId = InventoryProduct::create(['name' => $name, 'sku' => $sku, 'unidad_metrica' => $unit]);
                    $created++;
                    $qty = ($targetStock !== null && $targetStock > 0) ? $targetStock : 0.0;
                    if ($qty > 0 || $hasCost) {
                        InventoryMovement::register(array_merge([
                            'product_id' => $newId,
                            'tipo' => 'stock_inicial',
                            'cantidad' => $qty,
                            'notes' => 'Stock inicial por importación CSV',
                            'created_by' => $userId,
                        ], $costPayload));
                    }
                }
            } catch (Throwable $e) {
                $errors[] = "Línea $lineNum (SKU $sku): " . $e->getMessage();
            }
        }
        $msg = "Importación: $created creados, $updated actualizados.";
        if ($errors) {
            flash('error', $msg . ' Errores: ' . implode(' | ', array_slice($errors, 0, 5)));
        } else {
            flash('success', $msg);
        }
        redirect('inventory');
    }
}
