<?php
/** Botón «Volver» consistente en formularios y vistas secundarias. */
function page_back(string $route, string $label = 'Volver', array $params = []): void {
    echo '<a href="' . h(url($route, $params)) . '" class="btn btn-outline-secondary btn-sm mb-3">&larr; ' . h($label) . '</a>';
}

function asset(string $path): string {
    $path = ltrim($path, '/');
    static $base = null;
    if ($base === null) {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $dir = dirname($script);
        if ($dir === '/' || $dir === '.') {
            $base = '';
        } else {
            $base = rtrim($dir, '/');
        }
    }
    return ($base !== '' ? $base . '/' : '/') . $path;
}

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

/** @return array<string,string> slug => etiqueta */
function app_roles(): array {
    return [
        'admin' => 'Administrador',
        'coordinator' => 'Coordinador de misión',
        'jornada_admin' => 'Administrador de jornada',
        'purchase_admin' => 'Administrador de compras',
        'purchase_coordinator' => 'Coordinador de compras',
        'member' => 'Miembro',
    ];
}

function role_label(string $role): string {
    return app_roles()[$role] ?? $role;
}

function payment_methods(): array {
    $raw = setting('payment_methods', null);
    $res = [];
    if ($raw) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            foreach ($decoded as $item) {
                if (is_array($item) && isset($item['name'])) {
                    $res[] = ['name' => (string)$item['name'], 'currency' => (($item['currency'] ?? 'VES') === 'USD') ? 'USD' : 'VES'];
                } elseif (is_string($item) && trim($item) !== '') {
                    $name = trim($item);
                    $res[] = ['name' => $name, 'currency' => payment_method_guess_currency($name)];
                }
            }
        } else {
            foreach (array_filter(array_map('trim', explode(',', (string)$raw))) as $name) {
                $res[] = ['name' => $name, 'currency' => payment_method_guess_currency($name)];
            }
        }
    }
    if (!$res) {
        $res = [
            ['name' => 'Pago móvil', 'currency' => 'VES'],
            ['name' => 'Efectivo bolívares', 'currency' => 'VES'],
            ['name' => 'Efectivo dólares', 'currency' => 'USD'],
        ];
    }
    // unique by name
    $seen = [];
    $unique = [];
    foreach ($res as $r) {
        if (isset($seen[$r['name']])) continue;
        $seen[$r['name']] = true;
        $unique[] = $r;
    }
    return $unique;
}

function payment_method_guess_currency(string $name): string {
    $lower = mb_strtolower($name);
    if (strpos($lower, 'usd') !== false || strpos($lower, 'dolar') !== false || strpos($lower, 'dólar') !== false || strpos($lower, 'dollar') !== false) {
        return 'USD';
    }
    return 'VES';
}

function payment_method_currency(string $name): string {
    foreach (payment_methods() as $pm) {
        if ($pm['name'] === $name) return $pm['currency'];
    }
    // Otro/custom
    return 'VES';
}

function pagination_page(string $param, int $default = 1): int {
    return max(1, (int)($_GET[$param] ?? $default));
}

/** Conserva otros parámetros de paginación en la misma pantalla. */
function pagination_preserve(array $paramNames, string $skip): array {
    $out = [];
    foreach ($paramNames as $p) {
        if ($p === $skip) continue;
        $v = (int)($_GET[$p] ?? 0);
        if ($v > 1) $out[$p] = $v;
    }
    return $out;
}

function pagination_query_preserve(array $extra = []): array {
    $out = [];
    foreach ($extra as $k => $v) {
        if ($v === null || $v === '') continue;
        $out[$k] = $v;
    }
    return $out;
}

function pagination_render(int $total, int $page, int $perPage, string $route, string $param, array $preserve = []): string {
    if ($total <= 0 || $perPage <= 0) return '';
    $totalPages = (int)ceil($total / $perPage);
    if ($totalPages <= 1) return '';

    $page = min(max(1, $page), $totalPages);
    $from = ($page - 1) * $perPage + 1;
    $to = min($total, $page * $perPage);

    $href = function (int $p) use ($route, $param, $preserve): string {
        $q = $preserve;
        if ($p > 1) $q[$param] = $p;
        return url($route, $q);
    };

    $prevDisabled = $page <= 1;
    $nextDisabled = $page >= $totalPages;

    $html = '<nav class="d-flex justify-content-between align-items-center flex-wrap gap-2 px-3 py-2 border-top bg-light">';
    $html .= '<span class="text-muted small">' . (int)$from . '–' . (int)$to . ' de ' . (int)$total . '</span>';
    $html .= '<ul class="pagination pagination-sm mb-0">';
    $html .= '<li class="page-item' . ($prevDisabled ? ' disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . h($prevDisabled ? '#' : $href($page - 1)) . '">Anterior</a></li>';
    $html .= '<li class="page-item disabled"><span class="page-link">' . (int)$page . ' / ' . (int)$totalPages . '</span></li>';
    $html .= '<li class="page-item' . ($nextDisabled ? ' disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . h($nextDisabled ? '#' : $href($page + 1)) . '">Siguiente</a></li>';
    $html .= '</ul></nav>';
    return $html;
}
