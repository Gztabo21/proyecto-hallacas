<?php
define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', BASE_PATH . '/public');
define('APP_NAME', 'Hallacas Jireh');

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'hallacas_jireh';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

define('DB_HOST', $dbHost);
define('DB_PORT', $dbPort);
define('DB_NAME', $dbName);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);

define('APP_ENV', getenv('APP_ENV') ?: 'local');
define('APP_DEBUG', APP_ENV === 'local');
define('DEFAULT_PRICE', 1.00);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Mission.php';
require_once __DIR__ . '/models/Jornada.php';
require_once __DIR__ . '/models/Entrega.php';
require_once __DIR__ . '/models/Pago.php';
