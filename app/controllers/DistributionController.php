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
        require_role('admin','coordinator');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('jornadas'); }
        $jid = (int)($_POST['jornada_id'] ?? 0);
        $mid = (int)($_POST['mission_id'] ?? 0);
        $memberId = (int)($_POST['member_id'] ?? 0);
        $hall = (int)($_POST['hallacas_entregadas'] ?? 0);
        if (!Auth::isAdmin() && Auth::missionId() !== $mid) { flash('error','No autorizado'); redirect('jornada_show',['id'=>$jid]); return; }
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
        require_role('admin','coordinator');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('jornadas'); }
        $eid = (int)($_POST['entrega_id'] ?? 0);
        $e = Entrega::find($eid);
        if (!$e) { flash('error','Entrega no encontrada'); redirect('jornadas'); return; }
        if (!Auth::isAdmin() && Auth::missionId() !== (int)$e['mission_id']) { flash('error','No autorizado'); redirect('jornada_show',['id'=>$e['jornada_id']]); return; }
        $hall = (int)($_POST['hallacas'] ?? 0);
        $monto = (float)($_POST['monto'] ?? 0);
        $restantes = (int)$e['hallacas_entregadas'] - (int)$e['hallacas_pagadas'];
        if ($hall > $restantes) $hall = $restantes;
        Entrega::registrarAbono($eid, $hall, $monto, $_POST['metodo'] ?? 'abono', trim($_POST['notes'] ?? ''), Auth::user()['id']);
        flash('success','Abono registrado');
        redirect('jornada_show', ['id'=>$e['jornada_id']]);
    }

    public static function deleteEntrega(): void {
        require_role('admin','coordinator');
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
}
