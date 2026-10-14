<?php
class Auth {
    public static function user(): ?array { return $_SESSION['user'] ?? null; }
    public static function check(): bool { return isset($_SESSION['user']); }
    public static function role(): string { return self::user()['role'] ?? 'guest'; }
    public static function isAdmin(): bool { return self::role() === 'admin'; }
    public static function isCoordinator(): bool { return in_array(self::role(), ['admin','coordinator'], true); }
    public static function missionId(): ?int {
        $u = self::user(); return $u ? ($u['mission_id'] ?? null) : null;
    }
    public static function canAccessMission(int $missionId): bool {
        if (self::isAdmin()) return true;
        if (self::role() === 'coordinator' && self::missionId() === $missionId) return true;
        return false;
    }
    public static function login(array $user): void {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'mission_id' => $user['mission_id'] !== null ? (int)$user['mission_id'] : null,
        ];
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    public static function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
    public static function attempt(string $email, string $password): bool {
        $stmt = DB::query('SELECT * FROM users WHERE email = ? AND status = "active"', [$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            self::login($user);
            return true;
        }
        return false;
    }
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_verify(): bool {
    $token = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($token) && hash_equals($_SESSION['csrf'] ?? '', $token);
}
