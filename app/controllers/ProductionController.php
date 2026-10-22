<?php
class ProductionController {
    private static function canManageJornada(int $jornadaId): bool {
        if (Auth::isAdmin()) {
            return true;
        }
        if (Auth::isJornadaAdmin() && in_array($jornadaId, Auth::jornadaIds(), true)) {
            return true;
        }
        return false;
    }

    private static function assertManage(int $jornadaId): void {
        if (!self::canManageJornada($jornadaId)) {
            flash('error', 'No autorizado');
            redirect('jornada_show', ['id' => $jornadaId]);
            exit;
        }
    }

    private static function mesaJornadaId(int $mesaId): ?int {
        $m = JornadaMesa::find($mesaId);
        return $m ? (int)$m['jornada_id'] : null;
    }

    private static function redirectProduccion(int $jornadaId): void {
        redirect('jornada_show', ['id' => $jornadaId, 'tab' => 'produccion']);
    }

    public static function storeMesa(): void {
        require_login();
        if (!csrf_verify()) {
            flash('error', 'CSRF inválido');
            redirect('jornadas');
        }
        $jid = (int)($_POST['jornada_id'] ?? 0);
        self::assertManage($jid);
        if (!JornadaMesa::tablesReady()) {
            flash('error', 'Falta migración sql/012_jornada_produccion.sql');
            self::redirectProduccion($jid);
            return;
        }
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            flash('error', 'Indique el nombre de la mesa');
            self::redirectProduccion($jid);
            return;
        }
        JornadaMesa::create($jid, $name, trim($_POST['notes'] ?? '') ?: null);
        flash('success', 'Mesa registrada');
        self::redirectProduccion($jid);
    }

    public static function deleteMesa(): void {
        require_login();
        if (!csrf_verify()) {
            flash('error', 'CSRF inválido');
            redirect('jornadas');
        }
        $mesaId = (int)($_POST['mesa_id'] ?? 0);
        $jid = self::mesaJornadaId($mesaId);
        if (!$jid) {
            flash('error', 'Mesa no encontrada');
            redirect('jornadas');
            return;
        }
        self::assertManage($jid);
        JornadaMesa::delete($mesaId);
        flash('success', 'Mesa eliminada');
        self::redirectProduccion($jid);
    }

    public static function storePersona(): void {
        require_login();
        if (!csrf_verify()) {
            flash('error', 'CSRF inválido');
            redirect('jornadas');
        }
        $mesaId = (int)($_POST['mesa_id'] ?? 0);
        $jid = self::mesaJornadaId($mesaId);
        if (!$jid) {
            flash('error', 'Mesa no encontrada');
            redirect('jornadas');
            return;
        }
        self::assertManage($jid);
        $nombre = trim($_POST['persona_nombre'] ?? '');
        if ($nombre === '') {
            flash('error', 'Indique el nombre de la persona');
            self::redirectProduccion($jid);
            return;
        }
        $userId = isset($_POST['user_id']) && $_POST['user_id'] !== '' ? (int)$_POST['user_id'] : null;
        JornadaMesa::addPersona($mesaId, $nombre, $userId);
        flash('success', 'Persona agregada a la mesa');
        self::redirectProduccion($jid);
    }

    public static function deletePersona(): void {
        require_login();
        if (!csrf_verify()) {
            flash('error', 'CSRF inválido');
            redirect('jornadas');
        }
        $personaId = (int)($_POST['persona_id'] ?? 0);
        $row = DB::query(
            'SELECT p.id, m.jornada_id, m.id AS mesa_id FROM jornada_mesa_personas p
             JOIN jornada_mesas m ON m.id=p.mesa_id WHERE p.id=?',
            [$personaId]
        )->fetch();
        if (!$row) {
            flash('error', 'Persona no encontrada');
            redirect('jornadas');
            return;
        }
        $jid = (int)$row['jornada_id'];
        self::assertManage($jid);
        JornadaMesa::deletePersona($personaId);
        flash('success', 'Persona quitada de la mesa');
        self::redirectProduccion($jid);
    }

    public static function storeRetiro(): void {
        require_login();
        if (!csrf_verify()) {
            flash('error', 'CSRF inválido');
            redirect('jornadas');
        }
        $mesaId = (int)($_POST['mesa_id'] ?? 0);
        $jid = self::mesaJornadaId($mesaId);
        if (!$jid) {
            flash('error', 'Mesa no encontrada');
            redirect('jornadas');
            return;
        }
        self::assertManage($jid);
        $cantidad = (int)($_POST['cantidad'] ?? 0);
        if ($cantidad <= 0) {
            flash('error', 'Indique la cantidad retirada');
            self::redirectProduccion($jid);
            return;
        }
        $retiradoAt = trim($_POST['retirado_at'] ?? '');
        if ($retiradoAt === '') {
            $retiradoAt = date('Y-m-d H:i:s');
        } else {
            $retiradoAt = str_replace('T', ' ', $retiradoAt);
            if (strlen($retiradoAt) === 16) {
                $retiradoAt .= ':00';
            }
        }
        JornadaMesa::addRetiro(
            $mesaId,
            $cantidad,
            $retiradoAt,
            trim($_POST['notes'] ?? '') ?: null,
            (int)(Auth::user()['id'] ?? 0) ?: null
        );
        flash('success', 'Retiro de mesa registrado');
        self::redirectProduccion($jid);
    }

    public static function deleteRetiro(): void {
        require_login();
        if (!csrf_verify()) {
            flash('error', 'CSRF inválido');
            redirect('jornadas');
        }
        $retiroId = (int)($_POST['retiro_id'] ?? 0);
        $row = DB::query(
            'SELECT r.id, m.jornada_id FROM jornada_mesa_retiros r
             JOIN jornada_mesas m ON m.id=r.mesa_id WHERE r.id=?',
            [$retiroId]
        )->fetch();
        if (!$row) {
            flash('error', 'Registro no encontrado');
            redirect('jornadas');
            return;
        }
        $jid = (int)$row['jornada_id'];
        self::assertManage($jid);
        JornadaMesa::deleteRetiro($retiroId);
        flash('success', 'Retiro eliminado');
        self::redirectProduccion($jid);
    }
}
