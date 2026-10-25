<?php
require_once __DIR__ . '/../app/config.php';

// controllers
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/DashboardController.php';
require_once __DIR__ . '/../app/controllers/MissionController.php';
require_once __DIR__ . '/../app/controllers/UserController.php';
require_once __DIR__ . '/../app/controllers/MemberController.php';
require_once __DIR__ . '/../app/controllers/CoordinatorController.php';
require_once __DIR__ . '/../app/controllers/JornadaController.php';
require_once __DIR__ . '/../app/controllers/DistributionController.php';
require_once __DIR__ . '/../app/controllers/ReportController.php';
require_once __DIR__ . '/../app/controllers/PaymentController.php';
require_once __DIR__ . '/../app/controllers/InventoryController.php';
require_once __DIR__ . '/../app/controllers/RolePermissionController.php';
require_once __DIR__ . '/../app/controllers/ProductionController.php';

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
    case 'mission_toggle_status':
        MissionController::toggleStatus();
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
    case 'user_toggle_status':
        UserController::toggleStatus();
        break;
    case 'members':
        MemberController::index();
        break;
    case 'member_create':
        MemberController::create();
        break;
    case 'member_store':
        MemberController::store();
        break;
    case 'member_edit':
        MemberController::edit();
        break;
    case 'member_update':
        MemberController::update();
        break;
    case 'member_import':
        MemberController::importForm();
        break;
    case 'member_import_store':
        MemberController::importStore();
        break;
    case 'member_import_template':
        MemberController::importTemplate();
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
    case 'jornada_corregir_asignacion':
        JornadaController::corregirAsignacion();
        break;
    case 'jornada_mesa_store':
        ProductionController::storeMesa();
        break;
    case 'jornada_mesa_delete':
        ProductionController::deleteMesa();
        break;
    case 'jornada_mesa_persona_store':
        ProductionController::storePersona();
        break;
    case 'jornada_mesa_persona_delete':
        ProductionController::deletePersona();
        break;
    case 'jornada_mesa_retiro_store':
        ProductionController::storeRetiro();
        break;
    case 'jornada_mesa_retiro_delete':
        ProductionController::deleteRetiro();
        break;
    case 'entrega_store':
        DistributionController::storeEntrega();
        break;
    case 'entrega_import_template':
        DistributionController::importEntregasTemplate();
        break;
    case 'entrega_import_example':
        DistributionController::importEntregasExample();
        break;
    case 'entrega_import_store':
        DistributionController::importEntregasStore();
        break;
    case 'distribution_quick_member':
        DistributionController::quickAddRecipient();
        break;
    case 'entrega_abono':
        DistributionController::abono();
        break;
    case 'entrega_delete':
        DistributionController::deleteEntrega();
        break;
    case 'entrega_correct':
        DistributionController::correctEntrega();
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
    case 'payment_delete':
        PaymentController::delete();
        break;
    case 'payment_show':
        PaymentController::show();
        break;
    case 'payment_edit':
        PaymentController::edit();
        break;
    case 'payment_update':
        PaymentController::update();
        break;
    case 'payment_captura':
        PaymentController::captura();
        break;
    case 'role_permissions':
        RolePermissionController::index();
        break;
    case 'role_permissions_update':
        RolePermissionController::update();
        break;
    case 'reports':
        ReportController::index();
        break;
    case 'reports_pdf':
        ReportController::pdf();
        break;
    case 'inventory':
        InventoryController::index();
        break;
    case 'inventory_movements':
        InventoryController::movements();
        break;
    case 'inventory_pdf':
        InventoryController::catalogPdf();
        break;
    case 'inventory_movements_pdf':
        InventoryController::movementsPdf();
        break;
    case 'inventory_product_create':
        InventoryController::productCreate();
        break;
    case 'inventory_product_store':
        InventoryController::productStore();
        break;
    case 'inventory_product_edit':
        InventoryController::productEdit();
        break;
    case 'inventory_product_update':
        InventoryController::productUpdate();
        break;
    case 'inventory_initial':
        InventoryController::initialStockForm();
        break;
    case 'inventory_initial_store':
        InventoryController::initialStockStore();
        break;
    case 'inventory_purchases':
        InventoryController::purchasesIndex();
        break;
    case 'inventory_purchase_json':
        InventoryController::purchaseJson();
        break;
    case 'inventory_purchase_update':
        InventoryController::purchaseUpdateStore();
        break;
    case 'inventory_purchase_delete':
        InventoryController::purchaseDeleteStore();
        break;
    case 'inventory_purchase':
        InventoryController::purchaseForm();
        break;
    case 'inventory_purchase_store':
        InventoryController::purchaseStore();
        break;
    case 'inventory_jornada_deliver':
        InventoryController::jornadaDeliverStore();
        break;
    case 'inventory_jornada_salida_update':
        InventoryController::jornadaSalidaUpdate();
        break;
    case 'inventory_jornada_salida_delete':
        InventoryController::jornadaSalidaDelete();
        break;
    case 'inventory_jornada_return':
        InventoryController::jornadaReturnStore();
        break;
    case 'inventory_export':
        InventoryController::exportCsv();
        break;
    case 'inventory_template':
        InventoryController::templateCsv();
        break;
    case 'inventory_import':
        InventoryController::importForm();
        break;
    case 'inventory_import_store':
        InventoryController::importStore();
        break;
    case 'inventory_purchase_donations_template':
        InventoryController::purchaseDonationsTemplate();
        break;
    case 'inventory_purchase_donations_import':
        InventoryController::purchaseDonationsImportStore();
        break;
    default:
        http_response_code(404);
        echo 'Página no encontrada';
        break;
}
