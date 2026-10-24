<?php
class User {
    public static function ensureEmailNullable(): void {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;
        try {
            $row = DB::query("SHOW COLUMNS FROM users LIKE 'email'")->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException) {
            return;
        }
        if (!$row) {
            return;
        }
        if (stripos((string)($row['Null'] ?? ''), 'YES') === 0) {
            return;
        }
        DB::query('ALTER TABLE users MODIFY email VARCHAR(190) NULL');
    }

    public static function all(string $role = '', ?int $missionId = null): array {
        $sql = 'SELECT u.*, m.name AS mission_name, (SELECT GROUP_CONCAT(j.name SEPARATOR ", ") FROM user_jornada uj JOIN jornadas j ON j.id=uj.jornada_id WHERE uj.user_id=u.id) AS jornada_name FROM users u LEFT JOIN missions m ON m.id=u.mission_id WHERE 1=1';
        $p = [];
        if ($role) { $sql .= ' AND u.role = ?'; $p[] = $role; }
        if ($missionId) { $sql .= ' AND u.mission_id = ?'; $p[] = $missionId; }
        $sql .= ' ORDER BY u.name';
        return DB::query($sql, $p)->fetchAll();
    }
    public static function find(int $id): ?array {
        $stmt = DB::query('SELECT * FROM users WHERE id = ?', [$id]);
        $r = $stmt->fetch();
        return $r ?: null;
    }
    public static function findByEmail(string $email): ?array {
        $email = trim($email);
        if ($email === '') {
            return null;
        }
        $stmt = DB::query('SELECT * FROM users WHERE email = ?', [$email]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public static function findMemberByNameInMission(int $missionId, string $name): ?array {
        $name = trim($name);
        if ($name === '') {
            return null;
        }
        $stmt = DB::query(
            'SELECT * FROM users WHERE role = ? AND mission_id = ? AND LOWER(TRIM(name)) = LOWER(?) LIMIT 1',
            ['member', $missionId, $name]
        );
        $r = $stmt->fetch();
        return $r ?: null;
    }

    /** Crea o reutiliza miembro para distribución (correo opcional). */
    public static function ensureMemberForDistribution(string $name, int $missionId, ?string $email = null): int {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('Nombre de miembro vacío');
        }
        $email = $email !== null ? trim($email) : '';
        if ($email !== '') {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Correo inválido: ' . $email);
            }
            $byEmail = self::findByEmail($email);
            if ($byEmail) {
                if (($byEmail['role'] ?? '') !== 'member') {
                    throw new InvalidArgumentException('El correo ya existe con otro rol');
                }
                if ((int)($byEmail['mission_id'] ?? 0) !== $missionId) {
                    self::update((int)$byEmail['id'], ['mission_id' => $missionId, 'name' => $name]);
                }
                return (int)$byEmail['id'];
            }
        }
        $existing = self::findMemberByNameInMission($missionId, $name);
        if ($existing) {
            if ($email !== '' && ($existing['email'] ?? '') === '') {
                self::update((int)$existing['id'], ['email' => $email]);
            }
            return (int)$existing['id'];
        }
        return self::create([
            'name' => $name,
            'email' => $email !== '' ? $email : null,
            'password_hash' => password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT),
            'role' => 'member',
            'mission_id' => $missionId,
            'jornada_id' => null,
            'phone' => null,
            'status' => 'active',
        ]);
    }
    public static function create(array $data): int {
        DB::query(
            'INSERT INTO users (name,email,password_hash,role,mission_id,jornada_id,phone,status) VALUES (?,?,?,?,?,?,?,?)',
            [$data['name'], $data['email'], $data['password_hash'], $data['role'], $data['mission_id'] ?? null, $data['jornada_id'] ?? null, $data['phone'] ?? null, $data['status'] ?? 'active']
        );
        return (int)DB::connect()->lastInsertId();
    }
    public static function update(int $id, array $data): void {
        $fields = ['name','email','role','mission_id','jornada_id','phone','status'];
        $sets = []; $p = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) { $sets[] = "$f = ?"; $p[] = $data[$f]; }
        }
        if (!empty($data['password'])) {
            $sets[] = 'password_hash = ?';
            $p[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        if (!$sets) return;
        $p[] = $id;
        DB::query('UPDATE users SET ' . implode(',', $sets) . ' WHERE id = ?', $p);
    }
    public static function membersOfMission(int $missionId): array {
        return DB::query(
            'SELECT u.*, m.name AS mission_name
             FROM users u
             LEFT JOIN missions m ON m.id = u.mission_id
             WHERE u.role = ? AND u.mission_id = ? AND u.status = ?
             ORDER BY u.name',
            ['member', $missionId, 'active']
        )->fetchAll();
    }

    /** Miembros activos + coordinador de la misión (para distribuir hallacas). */
    public static function distributionRecipientsForMission(int $missionId): array {
        $byId = [];
        foreach (self::membersOfMission($missionId) as $u) {
            $byId[(int)$u['id']] = $u;
        }
        $coords = DB::query(
            'SELECT u.*, m.name AS mission_name
             FROM users u
             LEFT JOIN missions m ON m.id = u.mission_id
             WHERE u.role = ? AND u.mission_id = ? AND u.status = ?',
            ['coordinator', $missionId, 'active']
        )->fetchAll();
        foreach ($coords as $u) {
            $byId[(int)$u['id']] = $u;
        }
        $mission = Mission::find($missionId);
        if ($mission && !empty($mission['coordinator_id'])) {
            $cid = (int)$mission['coordinator_id'];
            if (!isset($byId[$cid])) {
                $coord = self::find($cid);
                if ($coord && ($coord['status'] ?? '') === 'active') {
                    $byId[$cid] = $coord;
                }
            }
        }
        $list = array_values($byId);
        usort($list, static fn(array $a, array $b): int => strcasecmp((string)$a['name'], (string)$b['name']));
        return $list;
    }

    public static function canReceiveHallacaDistribution(int $userId, int $missionId): bool {
        $u = self::find($userId);
        if (!$u || ($u['status'] ?? '') !== 'active') {
            return false;
        }
        if ((int)($u['mission_id'] ?? 0) === $missionId && in_array($u['role'] ?? '', ['member', 'coordinator'], true)) {
            return true;
        }
        $mission = Mission::find($missionId);
        return $mission && (int)($mission['coordinator_id'] ?? 0) === $userId;
    }
    public static function delete(int $id): void {
        DB::query('DELETE FROM users WHERE id = ?', [$id]);
    }

    public static function jornadaIds(int $userId): array {
        $stmt = DB::query('SELECT jornada_id FROM user_jornada WHERE user_id = ?', [$userId]);
        return array_values(array_map('intval', array_column($stmt->fetchAll(), 'jornada_id')));
    }
    public static function syncJornadas(int $userId, array $ids): void {
        DB::query('DELETE FROM user_jornada WHERE user_id = ?', [$userId]);
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($v) => $v > 0)));
        foreach ($ids as $jid) {
            DB::query('INSERT IGNORE INTO user_jornada (user_id, jornada_id) VALUES (?, ?)', [$userId, $jid]);
        }
    }
}
