<?php
class UserController {
    public static function index(): void {
        require_role('admin');
        $users = User::all();
        include BASE_PATH . '/app/views/users/index.php';
    }
    public static function create(): void {
        require_role('admin');
        $missions = Mission::all();
        include BASE_PATH . '/app/views/users/form.php';
    }
    public static function store(): void {
        require_role('admin');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('users'); }
        User::create([
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'password_hash' => password_hash($_POST['password'] ?? 'password', PASSWORD_DEFAULT),
            'role' => $_POST['role'] ?? 'member',
            'mission_id' => !empty($_POST['mission_id']) ? (int)$_POST['mission_id'] : null,
            'phone' => trim($_POST['phone'] ?? ''),
            'status' => $_POST['status'] ?? 'active',
        ]);
        flash('success','Usuario creado');
        redirect('users');
    }
    public static function edit(): void {
        require_role('admin');
        $id = (int)($_GET['id'] ?? 0);
        $user = User::find($id);
        if (!$user) { flash('error','Usuario no encontrado'); redirect('users'); }
        $missions = Mission::all();
        include BASE_PATH . '/app/views/users/form.php';
    }
    public static function update(): void {
        require_role('admin');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('users'); }
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'role' => $_POST['role'] ?? 'member',
            'mission_id' => !empty($_POST['mission_id']) ? (int)$_POST['mission_id'] : null,
            'phone' => trim($_POST['phone'] ?? ''),
            'status' => $_POST['status'] ?? 'active',
        ];
        if (!empty($_POST['password'])) $data['password'] = $_POST['password'];
        User::update($id, $data);
        flash('success','Usuario actualizado');
        redirect('users');
    }
    public static function delete(): void {
        require_role('admin');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('users'); }
        $id = (int)($_POST['id'] ?? 0);
        User::delete($id);
        flash('success','Usuario eliminado');
        redirect('users');
    }
}
