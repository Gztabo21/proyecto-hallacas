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
        $jornadas = Jornada::all();
        $userJornadaIds = [];
        include BASE_PATH . '/app/views/users/form.php';
    }
    private static function sanitizeRole(string $role): string {
        $allowed = array_keys(app_roles());
        return in_array($role, $allowed, true) ? $role : 'member';
    }

    public static function store(): void {
        require_role('admin');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('users'); }
        $newId = User::create([
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'password_hash' => password_hash($_POST['password'] ?? 'password', PASSWORD_DEFAULT),
            'role' => self::sanitizeRole($_POST['role'] ?? 'member'),
            'mission_id' => !empty($_POST['mission_id']) ? (int)$_POST['mission_id'] : null,
            'jornada_id' => null,
            'phone' => trim($_POST['phone'] ?? ''),
            'status' => $_POST['status'] ?? 'active',
        ]);
        User::syncJornadas($newId, $_POST['jornada_ids'] ?? []);
        flash('success','Usuario creado');
        redirect('users');
    }
    public static function edit(): void {
        require_role('admin');
        $id = (int)($_GET['id'] ?? 0);
        $user = User::find($id);
        if (!$user) { flash('error','Usuario no encontrado'); redirect('users'); }
        $missions = Mission::all();
        $jornadas = Jornada::all();
        $userJornadaIds = User::jornadaIds($id);
        include BASE_PATH . '/app/views/users/form.php';
    }
    public static function update(): void {
        require_role('admin');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('users'); }
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'role' => self::sanitizeRole($_POST['role'] ?? 'member'),
            'mission_id' => !empty($_POST['mission_id']) ? (int)$_POST['mission_id'] : null,
            'jornada_id' => !empty($_POST['jornada_id']) ? (int)$_POST['jornada_id'] : null,
            'phone' => trim($_POST['phone'] ?? ''),
            'status' => $_POST['status'] ?? 'active',
        ];
        if (!empty($_POST['password'])) $data['password'] = $_POST['password'];
        User::update($id, $data);
        User::syncJornadas($id, $_POST['jornada_ids'] ?? []);
        flash('success','Usuario actualizado');
        redirect('users');
    }
    public static function delete(): void {
        require_role('admin');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('users'); }
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)(Auth::user()['id'] ?? 0)) {
            flash('error', 'No puede eliminar su propio usuario');
            redirect('users');
            return;
        }
        User::delete($id);
        flash('success','Usuario eliminado');
        redirect('users');
    }

    public static function toggleStatus(): void {
        require_role('admin');
        if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('users'); }
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)(Auth::user()['id'] ?? 0)) {
            flash('error', 'No puede desactivar su propio usuario');
            redirect('users');
            return;
        }
        $user = User::find($id);
        if (!$user) {
            flash('error', 'Usuario no encontrado');
            redirect('users');
            return;
        }
        $next = ($user['status'] ?? 'active') === 'active' ? 'inactive' : 'active';
        User::update($id, ['status' => $next]);
        flash('success', $next === 'active' ? 'Usuario activado' : 'Usuario desactivado');
        redirect('users');
    }
}
