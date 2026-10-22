<?php
class DistributionController {
    private static function remainingHallacas(int $jornadaId, int $missionId, ?int $excludeEntregaId = null): int {
        $assigned = 0;
        foreach (Jornada::missionsFor($jornadaId) as $jm) {
            if ((int)$jm['mission_id'] === $missionId) $assigned = (int)$jm['hallacas_asignadas'];
        }
        $sql = 'SELECT COALESCE(SUM(hallacas_entregadas),0) s FROM entregas WHERE jornada_id=? AND mission_id=?';
        $p = [$jornadaId,$missionId];
        if ($excludeEntregaId) { $sql .= ' AND id <> ?'; $p[] = $excludeEntregaId; }
        $entregado = (int)DB::query($sql,$p)->fetch()['s'];
        return $assigned - $entregado;
    }

    public static function storeEntrega(): void {
        require_role('admin','coordinator','jornada_admin');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('jornadas'); }
        $jid = (int)($_POST['jornada_id'] ?? 0);
        $mid = (int)($_POST['mission_id'] ?? 0);
        $memberId = (int)($_POST['member_id'] ?? 0);
        $hall = (int)($_POST['hallacas_entregadas'] ?? 0);
        if (Auth::isAdmin()) {
            // ok
        } elseif (Auth::isCoordinator()) {
            if (Auth::missionId() !== $mid) { flash('error','No autorizado'); redirect('jornada_show',['id'=>$jid]); return; }
        } elseif (Auth::isJornadaAdmin()) {
            if (!in_array($jid, Auth::jornadaIds(), true)) { flash('error','No autorizado'); redirect('jornadas'); return; }
            $ms = array_column(Jornada::missionsFor($jid), 'mission_id');
            if (!in_array($mid, $ms, true)) { flash('error','No autorizado'); redirect('jornada_show',['id'=>$jid]); return; }
        } else {
            flash('error','No autorizado'); redirect('jornadas'); return;
        }
        if ($hall < 0) $hall = 0;
        $jornada = Jornada::find($jid);
        $precio = price_for_jornada($jornada);
        // validate remaining
        $rem = self::remainingHallacas($jid,$mid);
        if ($hall > $rem) { flash('error','Solo restan '.$rem.' hallacas por distribuir en esta misión'); redirect('jornada_show',['id'=>$jid]); return; }
        if (!User::canReceiveHallacaDistribution($memberId, $mid)) {
            flash('error', 'Seleccione un miembro o coordinador válido de la misión');
            redirect('jornada_show', ['id' => $jid]);
            return;
        }
        // check not duplicate
        $exist = Entrega::byMemberJornada($jid,$memberId);
        if ($exist) { flash('error','Ya existe una entrega para este miembro en la jornada'); redirect('jornada_show',['id'=>$jid]); return; }
        $hp = (int)($_POST['hallacas_pagadas'] ?? 0);
        if ($hp > $hall) $hp = $hall;
        $monto = (float)($_POST['monto_pagado'] ?? 0);
        if ($monto < 0) $monto = 0;
        Entrega::create([
            'jornada_id' => $jid,
            'mission_id' => $mid,
            'member_id' => $memberId,
            'hallacas_entregadas' => $hall,
            'hallacas_pagadas' => $hp,
            'precio_unitario' => $precio,
            'monto_pagado' => $monto,
            'metodo' => $_POST['metodo'] ?? 'inicial',
            'notes' => trim($_POST['notes'] ?? ''),
            'created_by' => Auth::user()['id'],
        ]);
        flash('success','Entrega registrada');
        redirect('jornada_show', ['id'=>$jid]);
    }

    public static function abono(): void {
        require_role('admin','coordinator','jornada_admin');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('jornadas'); }
        $eid = (int)($_POST['entrega_id'] ?? 0);
        $e = Entrega::find($eid);
        if (!$e) { flash('error','Entrega no encontrada'); redirect('jornadas'); return; }
        if (Auth::isAdmin()) {
            // ok
        } elseif (Auth::isCoordinator()) {
            if (Auth::missionId() !== (int)$e['mission_id']) { flash('error','No autorizado'); redirect('jornada_show',['id'=>$e['jornada_id']]); return; }
        } elseif (Auth::isJornadaAdmin()) {
            if (!in_array((int)$e['jornada_id'], Auth::jornadaIds(), true)) { flash('error','No autorizado'); redirect('jornadas'); return; }
        } else {
            flash('error','No autorizado'); redirect('jornadas'); return;
        }
        $hall = (int)($_POST['hallacas'] ?? 0);
        $monto = (float)($_POST['monto'] ?? 0);
        $restantes = (int)$e['hallacas_entregadas'] - (int)$e['hallacas_pagadas'];
        if ($hall > $restantes) $hall = $restantes;
                $metodo = $_POST['metodo'] ?? 'pago_movil';
                if ($metodo === 'otro') {
                    $metodo = trim($_POST['metodo_otro'] ?? '');
                    if ($metodo === '') $metodo = 'Otro';
                    $moneda = (($_POST['metodo_otro_currency'] ?? 'VES') === 'USD') ? 'USD' : 'VES';
                } else {
                    $moneda = payment_method_currency($metodo);
                }
        if (!Auth::can('pagos', 'create')) {
            flash('error', 'No tiene permiso para registrar pagos');
            self::redirectAfterAbono($e, $_POST['return_to'] ?? '');
            return;
        }
        $referencia = trim($_POST['referencia'] ?? '');
        if (payment_requires_referencia($metodo) && $referencia === '') {
            flash('error', 'La referencia es obligatoria para pago móvil');
            self::redirectAfterAbono($e, $_POST['return_to'] ?? '');
            return;
        }
        $tasa = (isset($_POST['tasa']) && $_POST['tasa'] !== '') ? (float)$_POST['tasa'] : null;
        $captura = self::captureFromUpload();
        Entrega::registrarAbono($eid, $hall, $monto, $metodo, trim($_POST['notes'] ?? '') ?: null, Auth::user()['id'], $moneda, $tasa, $captura, $referencia !== '' ? $referencia : null);
        flash('success','Abono registrado');
        self::redirectAfterAbono($e, $_POST['return_to'] ?? '');
    }

    private static function redirectAfterAbono(array $entrega, string $returnTo): void {
        if ($returnTo === 'payments') {
            redirect('payments');
            return;
        }
        if ($returnTo === 'jornada_pagos') {
            redirect('jornada_show', ['id' => (int)$entrega['jornada_id'], 'tab' => 'pagos']);
            return;
        }
        $jid = (int)($_POST['return_jornada_id'] ?? 0);
        if ($jid > 0 && $jid === (int)$entrega['jornada_id']) {
            redirect('jornada_show', ['id' => $jid, 'tab' => 'pagos']);
            return;
        }
        redirect('jornada_show', ['id' => (int)$entrega['jornada_id']]);
    }

    public static function deleteEntrega(): void {
        require_role('admin','coordinator','jornada_admin');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('jornadas'); }
        $eid = (int)($_POST['entrega_id'] ?? 0);
        $e = Entrega::find($eid);
        if ($e && self::canManageEntrega($e)) {
            Entrega::delete($eid);
            flash('success','Entrega eliminada');
            redirect('jornada_show', ['id'=>$e['jornada_id']]);
        } else {
            flash('error','No autorizado');
            redirect('jornadas');
        }
    }

    public static function correctEntrega(): void {
        require_role('admin', 'coordinator', 'jornada_admin');
        if (!csrf_verify()) {
            flash('error', 'CSRF inválido');
            redirect('jornadas');
        }
        $eid = (int)($_POST['entrega_id'] ?? 0);
        $e = Entrega::find($eid);
        if (!$e || !self::canManageEntrega($e)) {
            flash('error', 'No autorizado');
            redirect('jornadas');
            return;
        }
        $jornada = Jornada::find((int)$e['jornada_id']);
        if (!$jornada) {
            flash('error', 'Jornada no encontrada');
            redirect('jornadas');
            return;
        }
        if (($jornada['status'] ?? '') !== 'open' && !Auth::isAdmin()) {
            flash('error', 'La jornada está cerrada; solo administrador puede corregir');
            redirect('jornada_show', ['id' => (int)$e['jornada_id']]);
            return;
        }
        $nueva = (int)($_POST['hallacas_entregadas'] ?? -1);
        if ($nueva < 0) {
            flash('error', 'Indique la cantidad corregida');
            redirect('jornada_show', ['id' => (int)$e['jornada_id']]);
            return;
        }
        $err = Entrega::setHallacasEntregadas($eid, $nueva);
        if ($err !== null) {
            flash('error', $err);
            redirect('jornada_show', ['id' => (int)$e['jornada_id']]);
            return;
        }
        $nota = trim($_POST['correccion_nota'] ?? '');
        if ($nota !== '') {
            $prev = trim((string)($e['notes'] ?? ''));
            $line = 'Corrección entregadas: ' . $nota;
            $merged = $prev !== '' ? $prev . ' — ' . $line : $line;
            DB::query('UPDATE entregas SET notes=? WHERE id=?', [$merged, $eid]);
        }
        flash('success', 'Hallacas entregadas actualizadas');
        redirect('jornada_show', ['id' => (int)$e['jornada_id']]);
    }

    private static function assertDistributionMission(int $jornadaId, int $missionId): ?string {
        if (Auth::isAdmin()) {
            return null;
        }
        if (Auth::isCoordinator()) {
            return Auth::missionId() === $missionId ? null : 'No autorizado';
        }
        if (Auth::isJornadaAdmin()) {
            if (!in_array($jornadaId, Auth::jornadaIds(), true)) {
                return 'No autorizado';
            }
            $missionIds = array_map('intval', array_column(Jornada::missionsFor($jornadaId), 'mission_id'));
            return in_array($missionId, $missionIds, true) ? null : 'No autorizado';
        }
        return 'No autorizado';
    }

    public static function quickAddRecipient(): void {
        header('Content-Type: application/json; charset=utf-8');
        require_login();
        if (!Auth::isAdmin() && !Auth::isCoordinator() && !Auth::isJornadaAdmin()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'No autorizado']);
            return;
        }
        if (!csrf_verify()) {
            echo json_encode(['ok' => false, 'error' => 'CSRF inválido']);
            return;
        }
        $jid = (int)($_POST['jornada_id'] ?? 0);
        $mid = (int)($_POST['mission_id'] ?? 0);
        $authErr = self::assertDistributionMission($jid, $mid);
        if ($authErr !== null) {
            echo json_encode(['ok' => false, 'error' => $authErr]);
            return;
        }
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $role = (string)($_POST['role'] ?? 'member');
        $role = $role === 'coordinator' ? 'coordinator' : 'member';
        if ($role === 'coordinator' && !Auth::isAdmin()) {
            echo json_encode(['ok' => false, 'error' => 'Solo administrador puede crear coordinadores']);
            return;
        }
        if ($name === '' || $email === '') {
            echo json_encode(['ok' => false, 'error' => 'Nombre y correo son obligatorios']);
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['ok' => false, 'error' => 'Correo inválido']);
            return;
        }
        if (User::findByEmail($email)) {
            echo json_encode(['ok' => false, 'error' => 'Ya existe un usuario con ese correo']);
            return;
        }
        $tempPassword = null;
        if ($password === '') {
            $password = bin2hex(random_bytes(4));
            $tempPassword = $password;
        }
        $uid = User::create([
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'mission_id' => $mid,
            'jornada_id' => null,
            'phone' => trim($_POST['phone'] ?? ''),
            'status' => 'active',
        ]);
        $label = $name . ($role === 'coordinator' ? ' (Coordinador)' : '');
        $payload = [
            'ok' => true,
            'member' => ['id' => $uid, 'name' => $label],
            'mission_id' => $mid,
        ];
        if ($tempPassword !== null) {
            $payload['temp_password'] = $tempPassword;
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    private static function canManageEntrega(array $e): bool {
        if (Auth::isAdmin()) {
            return true;
        }
        if (Auth::isCoordinator() && Auth::missionId() === (int)$e['mission_id']) {
            return true;
        }
        if (Auth::isJornadaAdmin() && in_array((int)$e['jornada_id'], Auth::jornadaIds(), true)) {
            return true;
        }
        return false;
    }

    private static function captureFromUpload(): ?string {
        if (empty($_FILES['captura']) || !is_array($_FILES['captura']) || ($_FILES['captura']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($_FILES['captura']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) return null;
        $tmp = $_FILES['captura']['tmp_name'];
        if (!is_uploaded_file($tmp)) return null;
        $size = (int)($_FILES['captura']['size'] ?? 0);
        if ($size <= 0 || $size > 4 * 1024 * 1024) return null;
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $tmp) : 'application/octet-stream';
        if ($finfo) finfo_close($finfo);
        if (strpos($mime, 'image/') !== 0) return null;
        $raw = file_get_contents($tmp);
        if ($raw === false) return null;
        return 'data:' . $mime . ';base64,' . base64_encode($raw);
    }
}
