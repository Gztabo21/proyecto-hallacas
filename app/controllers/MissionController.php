<?php
class MissionController {
    public static function index(): void {
        require_role('admin');
        $missions = Mission::all();
        include BASE_PATH . '/app/views/missions/index.php';
    }
    public static function create(): void {
        require_role('admin');
        $coordinators = User::all('coordinator');
        include BASE_PATH . '/app/views/missions/form.php';
    }
    public static function store(): void {
        require_role('admin');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('missions'); }
        Mission::create([
            'name' => trim($_POST['name'] ?? ''),
            'code' => trim($_POST['code'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'coordinator_id' => !empty($_POST['coordinator_id']) ? (int)$_POST['coordinator_id'] : null,
            'status' => $_POST['status'] ?? 'active',
        ]);
        flash('success','Misión creada');
        redirect('missions');
    }
    public static function edit(): void {
        require_role('admin');
        $id = (int)($_GET['id'] ?? 0);
        $mission = Mission::find($id);
        if (!$mission) { flash('error','Misión no encontrada'); redirect('missions'); }
        $coordinators = User::all('coordinator');
        include BASE_PATH . '/app/views/missions/form.php';
    }
    public static function update(): void {
        require_role('admin');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('missions'); }
        $id = (int)($_POST['id'] ?? 0);
        Mission::update($id, [
            'name' => trim($_POST['name'] ?? ''),
            'code' => trim($_POST['code'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'coordinator_id' => !empty($_POST['coordinator_id']) ? (int)$_POST['coordinator_id'] : null,
            'status' => $_POST['status'] ?? 'active',
        ]);
        flash('success','Misión actualizada');
        redirect('missions');
    }
    public static function delete(): void {
        require_role('admin');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('missions'); }
        $id = (int)($_POST['id'] ?? 0);
        Mission::delete($id);
        flash('success','Misión eliminada');
        redirect('missions');
    }
}
