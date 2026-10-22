<?php
class PaymentController {
    public static function canAccessPaymentsModule(): bool {
        if (!Auth::isAdmin() && !Auth::isCoordinator() && !Auth::isJornadaAdmin()) {
            return false;
        }
        return Auth::isAdmin() || Auth::can('pagos', 'create') || Auth::can('pagos', 'edit');
    }

    /** @return array<string,mixed> */
    public static function indexData(
        ?int $jornadaId,
        ?int $missionId,
        string $paginationRoute = 'payments',
        array $paginationExtra = []
    ): array {
        $where = 'WHERE (e.hallacas_entregadas - e.hallacas_pagadas) > 0';
        $params = [];
        if ($jornadaId) {
            $where .= ' AND e.jornada_id = ?';
            $params[] = $jornadaId;
        }
        if ($missionId) {
            $where .= ' AND e.mission_id = ?';
            $params[] = $missionId;
        }
        if (Auth::isCoordinator() && !Auth::isAdmin()) {
            $where .= ' AND e.mission_id = ?';
            $params[] = Auth::missionId();
        }

        $perCancelar = 10;
        $perConfirmar = 10;
        $perRecientes = 10;
        $pageCancelar = pagination_page('pc');
        $pageConfirmar = pagination_page('pf');
        $pageRecientes = pagination_page('pr');

        $totalPendientes = (int)DB::query(
            "SELECT COUNT(*) c FROM entregas e $where",
            $params
        )->fetch()['c'];
        $pendientes = DB::query(
            "SELECT e.*, u.name AS member_name, m.name AS mission_name, j.name AS jornada_name, j.fecha
             FROM entregas e
             JOIN users u ON u.id=e.member_id
             JOIN missions m ON m.id=e.mission_id
             JOIN jornadas j ON j.id=e.jornada_id
             $where
             ORDER BY j.fecha DESC, m.name, u.name
             LIMIT " . (int)$perCancelar . ' OFFSET ' . (int)(($pageCancelar - 1) * $perCancelar),
            $params
        )->fetchAll();

        $whereP = 'WHERE p.status="pendiente"';
        $paramsP = [];
        if ($jornadaId) {
            $whereP .= ' AND e.jornada_id = ?';
            $paramsP[] = $jornadaId;
        }
        if ($missionId) {
            $whereP .= ' AND e.mission_id = ?';
            $paramsP[] = $missionId;
        }
        if (Auth::isCoordinator() && !Auth::isAdmin()) {
            $whereP .= ' AND e.mission_id = ?';
            $paramsP[] = Auth::missionId();
        }
        $totalPagosPendientes = (int)DB::query(
            "SELECT COUNT(*) c FROM pagos p JOIN entregas e ON e.id=p.entrega_id $whereP",
            $paramsP
        )->fetch()['c'];
        $pagosPendientes = DB::query(
            "SELECT p.*, e.id AS entrega_id, u.name AS member_name, m.name AS mission_name, j.name AS jornada_name, j.fecha, uc.name AS creado_por
             FROM pagos p
             JOIN entregas e ON e.id=p.entrega_id
             JOIN users u ON u.id=e.member_id
             JOIN missions m ON m.id=e.mission_id
             JOIN jornadas j ON j.id=e.jornada_id
             LEFT JOIN users uc ON uc.id=p.created_by
             $whereP
             ORDER BY p.fecha ASC
             LIMIT " . (int)$perConfirmar . ' OFFSET ' . (int)(($pageConfirmar - 1) * $perConfirmar),
            $paramsP
        )->fetchAll();

        $w2 = 'WHERE 1=1';
        $p2 = [];
        if ($jornadaId) {
            $w2 .= ' AND e.jornada_id = ?';
            $p2[] = $jornadaId;
        }
        if ($missionId) {
            $w2 .= ' AND e.mission_id = ?';
            $p2[] = $missionId;
        }
        if (Auth::isCoordinator() && !Auth::isAdmin()) {
            $w2 .= ' AND e.mission_id = ?';
            $p2[] = Auth::missionId();
        }
        $totalRecientes = (int)DB::query(
            "SELECT COUNT(*) c FROM pagos p JOIN entregas e ON e.id=p.entrega_id $w2",
            $p2
        )->fetch()['c'];
        $recientes = DB::query(
            "SELECT p.*, e.id AS entrega_id, e.mission_id, e.jornada_id, u.name AS member_name, m.name AS mission_name, j.name AS jornada_name, uc.name AS creado_por, ucf.name AS confirmado_por_name
             FROM pagos p
             JOIN entregas e ON e.id=p.entrega_id
             JOIN users u ON u.id=e.member_id
             JOIN missions m ON m.id=e.mission_id
             JOIN jornadas j ON j.id=e.jornada_id
             LEFT JOIN users uc ON uc.id=p.created_by
             LEFT JOIN users ucf ON ucf.id=p.confirmado_por
             $w2
             ORDER BY p.fecha DESC
             LIMIT " . (int)$perRecientes . ' OFFSET ' . (int)(($pageRecientes - 1) * $perRecientes),
            $p2
        )->fetchAll();

        $filterPreserve = array_merge(
            pagination_query_preserve([
                'jornada_id' => $jornadaId,
                'mission_id' => $missionId,
            ]),
            $paginationExtra
        );
        $pagination = [
            'confirmar' => ['total' => $totalPagosPendientes, 'page' => $pageConfirmar, 'per' => $perConfirmar, 'param' => 'pf'],
            'cancelar' => ['total' => $totalPendientes, 'page' => $pageCancelar, 'per' => $perCancelar, 'param' => 'pc'],
            'recientes' => ['total' => $totalRecientes, 'page' => $pageRecientes, 'per' => $perRecientes, 'param' => 'pr'],
            'params' => ['pf', 'pc', 'pr'],
            'filters' => $filterPreserve,
            'route' => $paginationRoute,
        ];

        return compact('pendientes', 'pagosPendientes', 'recientes', 'pagination');
    }

    public static function redirectToList(): void {
        $jid = (int)($_POST['return_jornada_id'] ?? 0);
        if ($jid > 0) {
            redirect('jornada_show', ['id' => $jid, 'tab' => 'pagos']);
            return;
        }
        redirect('payments');
    }

    public static function index(): void {
        require_login();
        if (!self::canAccessPaymentsModule()) {
            http_response_code(403);
            die('Acceso denegado.');
        }
        $jornadaId = isset($_GET['jornada_id']) && $_GET['jornada_id'] !== '' ? (int)$_GET['jornada_id'] : null;
        $missionId = isset($_GET['mission_id']) && $_GET['mission_id'] !== '' ? (int)$_GET['mission_id'] : null;
        extract(self::indexData($jornadaId, $missionId));
        $paymentsEmbedded = false;
        $returnJornadaId = null;
        $jornadas = Jornada::all();
        $missions = Mission::all(true);
        include BASE_PATH . '/app/views/payments/index.php';
    }

    private static function assertCanAccessPayment(array $p): bool {
        if (Auth::isAdmin()) {
            return true;
        }
        if (Auth::isCoordinator() && Auth::missionId() === (int)($p['mission_id'] ?? 0)) {
            return true;
        }
        if (Auth::isJornadaAdmin() && in_array((int)($p['jornada_id'] ?? 0), Auth::jornadaIds(), true)) {
            return true;
        }
        return false;
    }

    public static function canEditPayment(array $p): bool {
        if (!self::assertCanAccessPayment($p)) {
            return false;
        }
        if (($p['status'] ?? '') === 'confirmado') {
            return Auth::isAdmin();
        }
        return Auth::isAdmin() || Auth::can('pagos', 'edit');
    }

    /** @return array{0: string, 1: string} */
    private static function parseMetodoMonedaFromPost(): array {
        $metodo = (string)($_POST['metodo'] ?? '');
        if ($metodo === 'otro') {
            $metodo = trim($_POST['metodo_otro'] ?? '');
            if ($metodo === '') {
                $metodo = 'Otro';
            }
            $moneda = (($_POST['metodo_otro_currency'] ?? 'VES') === 'USD') ? 'USD' : 'VES';
        } else {
            $moneda = payment_method_currency($metodo);
        }
        return [$metodo, $moneda];
    }

    private static function captureFromUpload(): ?string {
        if (empty($_FILES['captura']) || !is_array($_FILES['captura']) || ($_FILES['captura']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($_FILES['captura']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return null;
        }
        $tmp = $_FILES['captura']['tmp_name'];
        if (!is_uploaded_file($tmp)) {
            return null;
        }
        $size = (int)($_FILES['captura']['size'] ?? 0);
        if ($size <= 0 || $size > 4 * 1024 * 1024) {
            return null;
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $tmp) : 'application/octet-stream';
        if ($finfo) {
            finfo_close($finfo);
        }
        if (strpos($mime, 'image/') !== 0) {
            return null;
        }
        $raw = file_get_contents($tmp);
        if ($raw === false) {
            return null;
        }
        return 'data:' . $mime . ';base64,' . base64_encode($raw);
    }

    public static function edit(): void {
        require_login();
        if (!Auth::can('pagos', 'edit') && !Auth::isAdmin()) {
            http_response_code(403);
            die('Acceso denegado.');
        }
        $id = (int)($_GET['id'] ?? 0);
        $pago = Pago::findDetailed($id);
        if (!$pago || !self::canEditPayment($pago)) {
            flash('error', 'No autorizado o pago no encontrado');
            redirect('payments');
            return;
        }
        $returnJornadaId = isset($_GET['jornada_id']) && $_GET['jornada_id'] !== '' ? (int)$_GET['jornada_id'] : null;
        include BASE_PATH . '/app/views/payments/edit.php';
    }

    public static function update(): void {
        require_login();
        if (!csrf_verify()) {
            flash('error', 'CSRF inválido');
            redirect('payments');
        }
        if (!Auth::can('pagos', 'edit') && !Auth::isAdmin()) {
            flash('error', 'No tiene permiso para corregir pagos');
            redirect('payments');
            return;
        }
        $id = (int)($_POST['pago_id'] ?? 0);
        $pago = Pago::findDetailed($id);
        if (!$pago || !self::canEditPayment($pago)) {
            flash('error', 'No autorizado');
            self::redirectToList();
            return;
        }
        [$metodo, $moneda] = self::parseMetodoMonedaFromPost();
        $referencia = trim($_POST['referencia'] ?? '');
        if (payment_requires_referencia($metodo) && $referencia === '') {
            flash('error', 'La referencia es obligatoria para pago móvil');
            redirect('payment_edit', array_filter([
                'id' => $id,
                'jornada_id' => (int)($_POST['return_jornada_id'] ?? 0) ?: null,
            ]));
            return;
        }
        $tasa = (isset($_POST['tasa']) && $_POST['tasa'] !== '') ? (float)$_POST['tasa'] : null;
        $captura = self::captureFromUpload();
        $payload = [
            'hallacas' => (int)($_POST['hallacas'] ?? 0),
            'monto' => (float)($_POST['monto'] ?? 0),
            'moneda' => $moneda,
            'metodo' => $metodo,
            'referencia' => $referencia !== '' ? $referencia : null,
            'tasa' => $tasa,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
            'correccion_nota' => trim($_POST['correccion_nota'] ?? ''),
        ];
        if ($captura !== null) {
            $payload['captura'] = $captura;
        }
        $err = Pago::update($id, $payload);
        if ($err !== null) {
            flash('error', $err);
            redirect('payment_edit', array_filter([
                'id' => $id,
                'jornada_id' => (int)($_POST['return_jornada_id'] ?? 0) ?: null,
            ]));
            return;
        }
        flash('success', 'Pago corregido');
        self::redirectToList();
    }

    public static function show(): void {
        require_login();
        if (!Auth::can('pagos', 'create') && !Auth::can('pagos', 'edit') && !Auth::isAdmin()) {
            http_response_code(403);
            die('Acceso denegado.');
        }
        $id = (int)($_GET['id'] ?? 0);
        $pago = Pago::findDetailed($id);
        if (!$pago || !self::assertCanAccessPayment($pago)) {
            flash('error', 'Pago no encontrado');
            redirect('payments');
            return;
        }
        $montoUsd = Pago::montoUsd($pago);
        include BASE_PATH . '/app/views/payments/show.php';
    }

    public static function captura(): void {
        require_login();
        if (!Auth::can('pagos', 'create') && !Auth::can('pagos', 'edit') && !Auth::isAdmin()) {
            http_response_code(403);
            die('Acceso denegado.');
        }
        $id = (int)($_GET['id'] ?? 0);
        $pago = Pago::findDetailed($id);
        if (!$pago || !self::assertCanAccessPayment($pago)) {
            http_response_code(404);
            die('No encontrado');
        }
        $raw = (string)($pago['captura'] ?? '');
        if ($raw === '') {
            http_response_code(404);
            die('Sin captura');
        }
        if (!str_starts_with($raw, 'data:')) {
            http_response_code(400);
            die('Formato no soportado');
        }
        if (!preg_match('#^data:([^;]+);base64,(.+)$#s', $raw, $m)) {
            http_response_code(400);
            die('Formato inválido');
        }
        $mime = $m[1];
        $bin = base64_decode($m[2], true);
        if ($bin === false) {
            http_response_code(400);
            die('Datos inválidos');
        }
        $ext = 'jpg';
        if (str_contains($mime, 'png')) {
            $ext = 'png';
        } elseif (str_contains($mime, 'webp')) {
            $ext = 'webp';
        }
        $download = isset($_GET['download']);
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . strlen($bin));
        if ($download) {
            header('Content-Disposition: attachment; filename="captura-pago-' . $id . '.' . $ext . '"');
        } else {
            header('Content-Disposition: inline; filename="captura-pago-' . $id . '.' . $ext . '"');
        }
        echo $bin;
        exit;
    }

    public static function confirm(): void {
        require_login();
        if (!Auth::can('pagos', 'edit')) {
            flash('error', 'No tiene permiso para confirmar pagos');
            redirect('payments');
            return;
        }
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('payments'); }
        $id = (int)($_POST['pago_id'] ?? 0);
        if (Pago::confirmar($id, (int)Auth::user()['id'])) {
            flash('success','Pago confirmado y aplicado a la entrega');
        } else {
            flash('error','No se pudo confirmar el pago');
        }
        self::redirectToList();
    }


    public static function delete(): void {
        require_login();
        if (!csrf_verify()) {
            flash('error', 'CSRF inválido');
            redirect('payments');
        }
        if (!Auth::can('pagos', 'delete')) {
            flash('error', 'No tiene permiso para eliminar pagos');
            redirect('payments');
            return;
        }
        $id = (int)($_POST['pago_id'] ?? 0);
        $p = Pago::find($id);
        if (!$p) {
            flash('error', 'Pago no encontrado');
            redirect('payments');
            return;
        }
        $e = Entrega::find((int)$p['entrega_id']);
        if (!$e) {
            flash('error', 'Entrega no encontrada');
            redirect('payments');
            return;
        }
        if (!Auth::isAdmin()) {
            if (Auth::isCoordinator() && Auth::missionId() !== (int)$e['mission_id']) {
                flash('error', 'No autorizado');
                redirect('payments');
                return;
            }
            if (Auth::isJornadaAdmin() && !in_array((int)$e['jornada_id'], Auth::jornadaIds(), true)) {
                flash('error', 'No autorizado');
                redirect('payments');
                return;
            }
            if ($p['status'] === 'confirmado') {
                flash('error', 'Solo el administrador puede eliminar pagos ya confirmados');
                redirect('payments');
                return;
            }
        }
        try {
            Pago::delete($id);
            flash('success', 'Pago eliminado');
        } catch (Throwable $ex) {
            flash('error', $ex->getMessage());
        }
        self::redirectToList();
    }

    public static function storeMethod(): void {
        require_role('admin');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('payments'); }
        $method = trim($_POST['new_payment_method'] ?? '');
        $currency = (($_POST['new_payment_method_currency'] ?? 'VES') === 'USD') ? 'USD' : 'VES';
        if ($method === '') { flash('error','Indique el nombre del método'); redirect('payments'); return; }
        $methods = payment_methods();
        $names = array_column($methods, 'name');
        if (!in_array($method, $names, true)) {
            $methods[] = ['name' => $method, 'currency' => $currency];
            set_setting('payment_methods', json_encode($methods, JSON_UNESCAPED_UNICODE));
        }
        flash('success','Método de pago agregado');
        redirect('payments');
    }
}
