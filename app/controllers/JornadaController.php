<?php
class JornadaController {
    public static function index(): void {
        require_login();
        $jornadas = Jornada::all();
        include BASE_PATH . '/app/views/jornadas/index.php';
    }
    public static function create(): void {
        require_role('admin','coordinator');
        include BASE_PATH . '/app/views/jornadas/form.php';
    }
    public static function store(): void {
        require_role('admin','coordinator');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('jornadas'); }
        Jornada::create([
            'name' => trim($_POST['name'] ?? ''),
            'fecha' => $_POST['fecha'] ?? date('Y-m-d'),
            'precio_hallaca' => (float)($_POST['precio_hallaca'] ?? 1.00),
            'notes' => trim($_POST['notes'] ?? ''),
            'created_by' => Auth::user()['id'],
        ]);
        flash('success','Jornada creada');
        redirect('jornadas');
    }
    public static function show(): void {
        require_login();
        $id = (int)($_GET['id'] ?? 0);
        $jornada = Jornada::find($id);
        if (!$jornada) { flash('error','Jornada no encontrada'); redirect('jornadas'); }
        // coordinator can only see their mission's? Show assigned missions; if coordinator, filter missions assigned to them maybe
        $assigned = Jornada::missionsFor($id);
        $allMissions = Mission::all(true);
        // members grouped
        $membersByMission = [];
        foreach ($assigned as $jm) {
            $membersByMission[$jm['mission_id']] = User::membersOfMission((int)$jm['mission_id']);
        }
        $entregas = Entrega::allByJornada($id);
        // price
        $precio = price_for_jornada($jornada);
        include BASE_PATH . '/app/views/jornadas/show.php';
    }
    public static function edit(): void {
        require_role('admin');
        $id = (int)($_GET['id'] ?? 0);
        $jornada = Jornada::find($id);
        if (!$jornada) { flash('error','Jornada no encontrada'); redirect('jornadas'); }
        include BASE_PATH . '/app/views/jornadas/form.php';
    }
    public static function update(): void {
        require_role('admin');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('jornadas'); }
        $id = (int)($_POST['id'] ?? 0);
        Jornada::update($id, [
            'name' => trim($_POST['name'] ?? ''),
            'fecha' => $_POST['fecha'] ?? date('Y-m-d'),
            'precio_hallaca' => (float)($_POST['precio_hallaca'] ?? 1.00),
            'notes' => trim($_POST['notes'] ?? ''),
        ]);
        flash('success','Jornada actualizada');
        redirect('jornada_show', ['id'=>$id]);
    }
    public static function close(): void {
        require_role('admin','coordinator');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('jornadas'); }
        $id = (int)($_POST['id'] ?? 0);
        Jornada::close($id);
        flash('success','Jornada cerrada');
        redirect('jornada_show', ['id'=>$id]);
    }
    public static function reopen(): void {
        require_role('admin');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('jornadas'); }
        $id = (int)($_POST['id'] ?? 0);
        Jornada::reopen($id);
        flash('success','Jornada reabierta');
        redirect('jornada_show', ['id'=>$id]);
    }
    public static function delete(): void {
        require_role('admin');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('jornadas'); }
        $id = (int)($_POST['id'] ?? 0);
        Jornada::delete($id);
        flash('success','Jornada eliminada');
        redirect('jornadas');
    }
    public static function asignar(): void {
        require_role('admin','coordinator');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('jornadas'); }
        $jid = (int)($_POST['jornada_id'] ?? 0);
        $mid = (int)($_POST['mission_id'] ?? 0);
        $hall = (int)($_POST['hallacas_asignadas'] ?? 0);
        $jm = Jornada::missionsFor($jid);
        // coordinator can only assign own mission
        if (!Auth::isAdmin() && Auth::missionId() !== $mid) { flash('error','No autorizado'); redirect('jornada_show',['id'=>$jid]); }
        Jornada::asignarMision($jid, $mid, $hall, trim($_POST['notes'] ?? ''));
        flash('success','Misión asignada a la jornada');
        redirect('jornada_show', ['id'=>$jid]);
    }
    public static function quitar(): void {
        require_role('admin','coordinator');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('jornadas'); }
        $jid = (int)($_POST['jornada_id'] ?? 0);
        $mid = (int)($_POST['mission_id'] ?? 0);
        if (!Auth::isAdmin() && Auth::missionId() !== $mid) { flash('error','No autorizado'); redirect('jornada_show',['id'=>$jid]); }
        Jornada::quitarMision($jid,$mid);
        flash('success','Misión removida de la jornada');
        redirect('jornada_show', ['id'=>$jid]);
    }
}
