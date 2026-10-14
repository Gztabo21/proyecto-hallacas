<?php
class User {
    public static function all(string $role = '', ?int $missionId = null): array {
        $sql = 'SELECT u.*, m.name AS mission_name FROM users u LEFT JOIN missions m ON m.id=u.mission_id WHERE 1=1';
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
        $stmt = DB::query('SELECT * FROM users WHERE email = ?', [$email]);
        $r = $stmt->fetch();
        return $r ?: null;
    }
    public static function create(array $data): int {
        DB::query(
            'INSERT INTO users (name,email,password_hash,role,mission_id,phone,status) VALUES (?,?,?,?,?,?,?)',
            [$data['name'], $data['email'], $data['password_hash'], $data['role'], $data['mission_id'] ?? null, $data['phone'] ?? null, $data['status'] ?? 'active']
        );
        return (int)DB::connect()->lastInsertId();
    }
    public static function update(int $id, array $data): void {
        $fields = ['name','email','role','mission_id','phone','status'];
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
        return self::all('member', $missionId);
    }
    public static function delete(int $id): void {
        DB::query('DELETE FROM users WHERE id = ?', [$id]);
    }
}
