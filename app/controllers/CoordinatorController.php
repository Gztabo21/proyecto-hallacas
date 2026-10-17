<?php
class CoordinatorController {
    public static function index(): void {
        require_role('admin');
        $coordinators = User::all('coordinator');
        include BASE_PATH . '/app/views/coordinators/index.php';
    }
    public static function create(): void {
        require_role('admin');
        $missions = Mission::all();
        include BASE_PATH . '/app/views/coordinators/form.php';
    }
    public static function store(): void {
        require_role('admin');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('coordinator_create'); }
        User::create([
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password_hash' => password_hash($_POST['password'] ?? 'password', PASSWORD_DEFAULT),
            'role' => 'coordinator',
            'mission_id' => !empty($_POST['mission_id']) ? (int)$_POST['mission_id'] : null,
            'phone' => trim($_POST['phone'] ?? ''),
            'status' => $_POST['status'] ?? 'active',
        ]);
        flash('success','Coordinador creado');
        redirect('coordinators');
    }
}
