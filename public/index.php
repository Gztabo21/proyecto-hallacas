<?php
require_once __DIR__ . '/../app/config.php';

// controllers
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/DashboardController.php';
require_once __DIR__ . '/../app/controllers/MissionController.php';
require_once __DIR__ . '/../app/controllers/UserController.php';
require_once __DIR__ . '/../app/controllers/CoordinatorController.php';
require_once __DIR__ . '/../app/controllers/JornadaController.php';
require_once __DIR__ . '/../app/controllers/DistributionController.php';
require_once __DIR__ . '/../app/controllers/ReportController.php';
require_once __DIR__ . '/../app/controllers/PaymentController.php';

$route = $_GET['r'] ?? '';

$public = ['login'];

if (!in_array($route, $public, true) && !Auth::check()) {
    redirect('login');
}

switch ($route) {
    case '':
    case 'dashboard':
        DashboardController::index();
        break;
    case 'login':
        AuthController::login();
        break;
    case 'logout':
        AuthController::logout();
        break;
    case 'missions':
        MissionController::index();
        break;
    case 'mission_create':
        MissionController::create();
        break;
    case 'mission_store':
        MissionController::store();
        break;
    case 'mission_edit':
        MissionController::edit();
        break;
    case 'mission_update':
        MissionController::update();
        break;
    case 'mission_delete':
        MissionController::delete();
        break;
    case 'users':
        UserController::index();
        break;
    case 'user_create':
        UserController::create();
        break;
    case 'user_store':
        UserController::store();
        break;
    case 'user_edit':
        UserController::edit();
        break;
    case 'user_update':
        UserController::update();
        break;
    case 'user_delete':
        UserController::delete();
        break;
    case 'coordinators':
        CoordinatorController::index();
        break;
    case 'coordinator_create':
        CoordinatorController::create();
        break;
    case 'coordinator_store':
        CoordinatorController::store();
        break;
    case 'jornadas':
        JornadaController::index();
        break;
    case 'jornada_create':
        JornadaController::create();
        break;
    case 'jornada_store':
        JornadaController::store();
        break;
    case 'jornada_show':
        JornadaController::show();
        break;
    case 'jornada_edit':
        JornadaController::edit();
        break;
    case 'jornada_update':
        JornadaController::update();
        break;
    case 'jornada_close':
        JornadaController::close();
        break;
    case 'jornada_reopen':
        JornadaController::reopen();
        break;
    case 'jornada_delete':
        JornadaController::delete();
        break;
    case 'jornada_asignar':
        JornadaController::asignar();
        break;
    case 'jornada_total_hechas':
        JornadaController::updateTotalHechas();
        break;
    case 'jornada_quitar':
        JornadaController::quitar();
        break;
    case 'entrega_store':
        DistributionController::storeEntrega();
        break;
    case 'entrega_abono':
        DistributionController::abono();
        break;
    case 'entrega_delete':
        DistributionController::deleteEntrega();
        break;
    case 'payments':
        PaymentController::index();
        break;
    case 'payment_method_store':
        PaymentController::storeMethod();
        break;
    case 'payment_confirm':
        PaymentController::confirm();
        break;
    case 'reports':
        ReportController::index();
        break;
    default:
        http_response_code(404);
        echo 'Página no encontrada';
        break;
}
