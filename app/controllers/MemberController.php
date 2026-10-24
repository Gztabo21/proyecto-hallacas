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
        if ($name === '') {
            flash('error', 'El nombre es obligatorio');
            redirect('member_create');
            return;
        }
        if ($email !== '') {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                flash('error', 'Correo inválido');
                redirect('member_create');
                return;
            }
            if (User::findByEmail($email)) {
                flash('error', 'Ya existe un usuario con ese correo');
                redirect('member_create');
                return;
            }
        }
        if ($email === '' && $password === '') {
            $password = bin2hex(random_bytes(4));
        }
        if ($password === '') {
            flash('error', 'Indique contraseña o deje el correo vacío para generar una automática');
            redirect('member_create');
            return;
        }
        User::ensureEmailNullable();
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
            'email' => $email !== '' ? $email : null,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'member',
            'mission_id' => $missionId,
            'jornada_id' => null,
            'phone' => trim($_POST['phone'] ?? ''),
            'status' => $_POST['status'] ?? 'active',
        ]);
        flash('success', $email === '' ? 'Miembro creado (sin correo; no podrá iniciar sesión)' : 'Miembro creado');
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
        if ($name === '') {
            flash('error', 'El nombre es obligatorio');
            redirect('member_edit', ['id' => $id]);
            return;
        }
        User::ensureEmailNullable();
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Correo inválido');
            redirect('member_edit', ['id' => $id]);
            return;
        }
        $existing = $email !== '' ? User::findByEmail($email) : null;
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
            'email' => $email !== '' ? $email : null,
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

    public static function importForm(): void {
        self::requireManager();
        include BASE_PATH . '/app/views/members/import.php';
    }

    public static function importTemplate(): void {
        self::requireManager();
        $header = ['nombre', 'email', 'telefono', 'mision', 'password', 'estado'];
        $sample = [
            'Ana Pérez',
            'ana.perez@ejemplo.com',
            '04141234567',
            'M01',
            '',
            'active',
        ];
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="plantilla_miembros.csv"');
        echo "\xEF\xBB\xBF";
        echo csv_line($header) . "\n";
        echo csv_line($sample) . "\n";
        exit;
    }

    public static function importStore(): void {
        self::requireManager();
        if (!csrf_verify()) {
            flash('error', 'CSRF inválido');
            redirect('members');
        }
        if (empty($_FILES['csv']) || !is_uploaded_file($_FILES['csv']['tmp_name'])) {
            flash('error', 'Seleccione un archivo CSV');
            redirect('member_import');
            return;
        }
        $defaultPassword = (string)($_POST['default_password'] ?? '');
        if ($defaultPassword === '') {
            flash('error', 'Indique la contraseña por defecto para filas sin columna password');
            redirect('member_import');
            return;
        }
        if (strlen($defaultPassword) < 6) {
            flash('error', 'La contraseña por defecto debe tener al menos 6 caracteres');
            redirect('member_import');
            return;
        }
        try {
            $allRows = csv_read_rows($_FILES['csv']['tmp_name']);
        } catch (InvalidArgumentException $e) {
            flash('error', $e->getMessage());
            redirect('member_import');
            return;
        }
        $header = array_map('csv_normalize_header_name', $allRows[0]);
        $idx = [
            'nombre' => csv_column_index($header, 'nombre', ['name', 'miembro']),
            'email' => csv_column_index($header, 'email', ['correo', 'e_mail']),
            'telefono' => csv_column_index($header, 'telefono', ['phone', 'tel', 'celular']),
            'mision' => csv_column_index($header, 'mision', ['mission', 'codigo_mision', 'mission_code', 'codigo']),
            'password' => csv_column_index($header, 'password', ['contraseña', 'contrasena', 'clave']),
            'estado' => csv_column_index($header, 'estado', ['status', 'activo']),
        ];
        if ($idx['nombre'] === false) {
            $found = implode(', ', array_filter($header, fn($h) => $h !== ''));
            flash('error', 'El archivo debe incluir columna nombre. Detectado: ' . ($found !== '' ? $found : '(ninguno)'));
            redirect('member_import');
            return;
        }
        User::ensureEmailNullable();
        if (Auth::isAdmin() && $idx['mision'] === false) {
            flash('error', 'Como administrador debe incluir la columna mision (código M01, nombre o número de misión).');
            redirect('member_import');
            return;
        }
        $coordinatorMission = Auth::isAdmin() ? null : (int)Auth::missionId();
        if (!Auth::isAdmin() && !$coordinatorMission) {
            flash('error', 'No tiene misión asignada');
            redirect('members');
            return;
        }
        $created = 0;
        $updated = 0;
        $errors = [];
        $lineNum = 1;
        for ($r = 1, $n = count($allRows); $r < $n; $r++) {
            $lineNum++;
            $row = $allRows[$r];
            if (count(array_filter($row, fn($c) => trim((string)$c) !== '')) === 0) {
                continue;
            }
            $name = trim((string)($row[$idx['nombre']] ?? ''));
            $email = trim((string)($row[$idx['email']] ?? ''));
            $phone = $idx['telefono'] !== false ? trim((string)($row[$idx['telefono']] ?? '')) : '';
            $missionRaw = $idx['mision'] !== false ? trim((string)($row[$idx['mision']] ?? '')) : '';
            $rowPassword = $idx['password'] !== false ? trim((string)($row[$idx['password']] ?? '')) : '';
            $statusRaw = $idx['estado'] !== false ? strtolower(trim((string)($row[$idx['estado']] ?? ''))) : 'active';
            $status = in_array($statusRaw, ['active', 'inactive', 'activo', 'inactivo', '1', '0'], true)
                ? (in_array($statusRaw, ['inactive', 'inactivo', '0'], true) ? 'inactive' : 'active')
                : 'active';
            if ($name === '') {
                $errors[] = "Línea $lineNum: nombre obligatorio";
                continue;
            }
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Línea $lineNum: correo inválido ($email)";
                continue;
            }
            if (Auth::isAdmin()) {
                $missionId = Mission::resolveImportReference($missionRaw);
                if (!$missionId) {
                    $errors[] = "Línea $lineNum: misión no reconocida («$missionRaw»). Use M01, «Misión 1» o id.";
                    continue;
                }
            } else {
                if ($missionRaw !== '') {
                    $mid = Mission::resolveImportReference($missionRaw);
                    if ($mid !== $coordinatorMission) {
                        $errors[] = "Línea $lineNum: la misión debe ser la suya";
                        continue;
                    }
                }
                $missionId = $coordinatorMission;
            }
            $password = $rowPassword !== '' ? $rowPassword : $defaultPassword;
            if (strlen($password) < 6) {
                $errors[] = "Línea $lineNum: contraseña demasiado corta";
                continue;
            }
            if ($email !== '') {
                $existing = User::findByEmail($email);
            } else {
                $existing = User::findMemberByNameInMission($missionId, $name);
            }
            if ($existing) {
                if (($existing['role'] ?? '') !== 'member') {
                    $errors[] = "Línea $lineNum: el correo ya existe con otro rol";
                    continue;
                }
                User::update((int)$existing['id'], [
                    'name' => $name,
                    'email' => $email !== '' ? $email : ($existing['email'] ?? null),
                    'phone' => $phone !== '' ? $phone : null,
                    'mission_id' => $missionId,
                    'status' => $status,
                    'role' => 'member',
                    'password' => $password,
                ]);
                $updated++;
            } else {
                User::create([
                    'name' => $name,
                    'email' => $email !== '' ? $email : null,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => 'member',
                    'mission_id' => $missionId,
                    'jornada_id' => null,
                    'phone' => $phone !== '' ? $phone : null,
                    'status' => $status,
                ]);
                $created++;
            }
        }
        $msg = "Importación: $created creados, $updated actualizados.";
        if ($errors !== []) {
            $preview = array_slice($errors, 0, 8);
            $msg .= ' Errores: ' . implode(' · ', $preview);
            if (count($errors) > 8) {
                $msg .= ' … (' . count($errors) . ' en total)';
            }
            flash('error', $msg);
        } else {
            flash('success', $msg);
        }
        redirect('members');
    }
}
