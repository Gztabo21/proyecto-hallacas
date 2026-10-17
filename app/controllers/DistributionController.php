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
        $tasa = (isset($_POST['tasa']) && $_POST['tasa'] !== '') ? (float)$_POST['tasa'] : null;
        $captura = self::captureFromUpload();
        Entrega::registrarAbono($eid, $hall, $monto, $metodo, trim($_POST['notes'] ?? ''), Auth::user()['id'], $moneda, $tasa, $captura);
        flash('success','Abono registrado');
        redirect('jornada_show', ['id'=>$e['jornada_id']]);
    }

    public static function deleteEntrega(): void {
        require_role('admin','coordinator','jornada_admin');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('jornadas'); }
        $eid = (int)($_POST['entrega_id'] ?? 0);
        $e = Entrega::find($eid);
        if ($e && (Auth::isAdmin() || Auth::missionId() === (int)$e['mission_id'])) {
            Entrega::delete($eid);
            flash('success','Entrega eliminada');
            redirect('jornada_show', ['id'=>$e['jornada_id']]);
        } else {
            flash('error','No autorizado');
            redirect('jornadas');
        }
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
