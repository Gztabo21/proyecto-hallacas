<?php
class MemberController {
    private static function requireManager(): void {
        require_role('admin', 'coordinator');
    }

    private static function findMember(int $id): ?array {
        $user = User::find($id);
        if (!$user || ($user['role'] ?? '') !== 'member') {
            return null;
        }
        return $user;
    }

    private static function assertCanAccessMember(array $member): void {
        if (Auth::isAdmin()) {
            return;
        }
        if (Auth::role() === 'coordinator' && (int)$member['mission_id'] === (int)Auth::missionId()) {
            return;
        }
        flash('error', 'No autorizado');
        redirect('members');
        exit;
    }

    public static function index(): void {
        self::requireManager();
        if (Auth::isAdmin()) {
            $members = User::all('member');
        } else {
            $mid = (int)Auth::missionId();
            if (!$mid) {
                flash('error', 'No tiene misión asignada');
                redirect('dashboard');
                return;
            }
            $members = User::membersOfMission($mid);
        }
        include BASE_PATH . '/app/views/members/index.php';
    }

    public static function create(): void {
        self::requireManager();
        if (Auth::role() === 'coordinator' && !Auth::missionId()) {
            flash('error', 'No tiene misión asignada');
            redirect('dashboard');
            return;
        }
        $member = null;
        $missions = Mission::all(true);
        include BASE_PATH . '/app/views/members/form.php';
    }

    public static function store(): void {
        self::requireManager();
        if (!csrf_verify()) {
            flash('error', 'CSRF inválido');
            redirect('members');
        }
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($name === '' || $email === '' || $password === '') {
            flash('error', 'Nombre, correo y contraseña son obligatorios');
            redirect('member_create');
            return;
        }
        if (User::findByEmail($email)) {
            flash('error', 'Ya existe un usuario con ese correo');
            redirect('member_create');
            return;
        }
        if (Auth::isAdmin()) {
            $missionId = !empty($_POST['mission_id']) ? (int)$_POST['mission_id'] : null;
            if (!$missionId) {
                flash('error', 'Seleccione una misión');
                redirect('member_create');
                return;
            }
        } else {
            $missionId = (int)Auth::missionId();
        }
        User::create([
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'member',
            'mission_id' => $missionId,
            'jornada_id' => null,
            'phone' => trim($_POST['phone'] ?? ''),
            'status' => $_POST['status'] ?? 'active',
        ]);
        flash('success', 'Miembro creado');
        redirect('members');
    }

    public static function edit(): void {
        self::requireManager();
        $id = (int)($_GET['id'] ?? 0);
        $member = self::findMember($id);
        if (!$member) {
            flash('error', 'Miembro no encontrado');
            redirect('members');
            return;
        }
        self::assertCanAccessMember($member);
        $missions = Mission::all(true);
        include BASE_PATH . '/app/views/members/form.php';
    }

    public static function update(): void {
        self::requireManager();
        if (!csrf_verify()) {
            flash('error', 'CSRF inválido');
            redirect('members');
        }
        $id = (int)($_POST['id'] ?? 0);
        $member = self::findMember($id);
        if (!$member) {
            flash('error', 'Miembro no encontrado');
            redirect('members');
            return;
        }
        self::assertCanAccessMember($member);

        $email = trim($_POST['email'] ?? '');
        $name = trim($_POST['name'] ?? '');
        if ($name === '' || $email === '') {
            flash('error', 'Nombre y correo son obligatorios');
            redirect('member_edit', ['id' => $id]);
            return;
        }
        $existing = User::findByEmail($email);
        if ($existing && (int)$existing['id'] !== $id) {
            flash('error', 'Ya existe otro usuario con ese correo');
            redirect('member_edit', ['id' => $id]);
            return;
        }

        if (Auth::isAdmin()) {
            $missionId = !empty($_POST['mission_id']) ? (int)$_POST['mission_id'] : null;
            if (!$missionId) {
                flash('error', 'Seleccione una misión');
                redirect('member_edit', ['id' => $id]);
                return;
            }
        } else {
            $missionId = (int)Auth::missionId();
        }

        $data = [
            'name' => $name,
            'email' => $email,
            'phone' => trim($_POST['phone'] ?? ''),
            'status' => $_POST['status'] ?? 'active',
            'mission_id' => $missionId,
            'role' => 'member',
        ];
        if (!empty($_POST['password'])) {
            $data['password'] = $_POST['password'];
        }
        User::update($id, $data);
        flash('success', 'Miembro actualizado');
        redirect('members');
    }
}
