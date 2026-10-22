<?php
class RolePermissionController {
    public static function index(): void {
        require_role('admin');
        if (!RolePermission::tableExists()) {
            flash('error', 'Falta la tabla role_permissions. Ejecute sql/010_role_permissions.sql');
        }
        $matrix = RolePermission::matrix();
        $resources = RolePermission::resources();
        $roles = app_roles();
        include BASE_PATH . '/app/views/role_permissions/index.php';
    }

    public static function update(): void {
        require_role('admin');
        if (!csrf_verify()) {
            flash('error', 'CSRF inválido');
            redirect('role_permissions');
        }
        try {
            RolePermission::saveMatrix($_POST['perm'] ?? []);
            flash('success', 'Permisos actualizados');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('role_permissions');
    }
}
