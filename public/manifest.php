<?php
require_once __DIR__ . '/../app/config.php';

header('Content-Type: application/manifest+json; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

if (!pwa_is_enabled()) {
    http_response_code(404);
    echo json_encode(['name' => 'PWA disabled'], JSON_UNESCAPED_UNICODE);
    exit;
}

$name = setting('app_name', APP_NAME);
$short = mb_strlen($name) > 12 ? mb_substr($name, 0, 12, 'UTF-8') : $name;
$iconSvg = asset('assets/icons/icon.svg');
$icon192 = asset('assets/icons/icon-192.png');
$icon512 = asset('assets/icons/icon-512.png');

$manifest = [
    'name' => $name,
    'short_name' => $short,
    'description' => 'Gestión de jornadas, entregas, pagos e inventario.',
    'start_url' => url('login'),
    'scope' => pwa_scope(),
    'display' => 'standalone',
    'orientation' => 'portrait-primary',
    'background_color' => '#212529',
    'theme_color' => pwa_theme_color(),
    'lang' => 'es',
    'icons' => [
        [
            'src' => $icon192,
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any',
        ],
        [
            'src' => $icon512,
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any',
        ],
        [
            'src' => $iconSvg,
            'sizes' => 'any',
            'type' => 'image/svg+xml',
            'purpose' => 'any',
        ],
    ],
];

echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
