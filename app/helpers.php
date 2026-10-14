<?php
function url(string $route = '', array $params = []): string {
    $q = array_merge(['r' => $route], $params);
    if ($route === '') unset($q['r']);
    return '/index.php?' . http_build_query($q);
}
function redirect(string $route = '', array $params = []): void {
    header('Location: ' . url($route, $params));
    exit;
}
function h($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}
function money($v): string {
    return number_format((float)$v, 2, '.', ',');
}
function flash(string $key, ?string $msg = null): ?string {
    if ($msg !== null) {
        $_SESSION['flash'][$key] = $msg;
        return null;
    }
    $v = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $v;
}
function setting(string $key, $default = null) {
    static $cache = null;
    if ($cache === null) {
        try {
            $rows = DB::query('SELECT `key`,`value` FROM settings')->fetchAll();
            $cache = [];
            foreach ($rows as $r) $cache[$r['key']] = $r['value'];
        } catch (Throwable $e) {
            $cache = [];
        }
    }
    return array_key_exists($key, $cache) ? $cache[$key] : $default;
}
function set_setting(string $key, string $value): void {
    DB::query('INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)', [$key,$value]);
}
function price_for_jornada(array $jornada): float {
    $p = (float)($jornada['precio_hallaca'] ?? 0);
    if ($p <= 0) $p = (float)setting('precio_hallaca', DEFAULT_PRICE);
    return $p;
}
function status_badge(string $status): string {
    $map = [
        'pendiente' => 'danger',
        'parcial' => 'warning',
        'pagado' => 'success',
        'open' => 'primary',
        'closed' => 'secondary',
        'active' => 'success',
        'inactive' => 'secondary',
    ];
    $c = $map[$status] ?? 'secondary';
    return '<span class="badge bg-' . $c . '">' . h(ucfirst($status)) . '</span>';
}
function require_login(): void {
    if (!Auth::check()) {
        header('Location: ' . url('login'));
        exit;
    }
}
function require_role(string ...$roles): void {
    require_login();
    if (!in_array(Auth::role(), $roles, true)) {
        http_response_code(403);
        die('Acceso denegado.');
    }
}
