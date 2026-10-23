<?php
class RolePermission {
    /** @return array<string,string> resource slug => label */
    public static function resources(): array {
        return [
            'pagos' => 'Pagos',
            'entregas' => 'Entregas de hallacas',
            'jornadas' => 'Jornadas',
            'misiones' => 'Misiones',
            'usuarios' => 'Usuarios',
            'miembros' => 'Miembros',
            'inventario' => 'Inventario',
            'reportes' => 'Reportes',
        ];
    }

    /** @return array<string,array{can_create:bool,can_edit:bool,can_delete:bool}> */
    public static function defaultsForRole(string $role): array {
        $all = array_fill_keys(array_keys(self::resources()), ['can_create' => false, 'can_edit' => false, 'can_delete' => false]);
        if ($role === 'admin') {
            foreach ($all as $k => $_) {
                $all[$k] = ['can_create' => true, 'can_edit' => true, 'can_delete' => true];
            }
            return $all;
        }
        if ($role === 'coordinator') {
            $all['pagos'] = ['can_create' => true, 'can_edit' => false, 'can_delete' => false];
            $all['entregas'] = ['can_create' => true, 'can_edit' => true, 'can_delete' => false];
            $all['miembros'] = ['can_create' => true, 'can_edit' => true, 'can_delete' => false];
            $all['jornadas'] = ['can_create' => false, 'can_edit' => false, 'can_delete' => false];
            $all['reportes'] = ['can_create' => true, 'can_edit' => false, 'can_delete' => false];
        }
        if ($role === 'jornada_admin') {
            $all['pagos'] = ['can_create' => true, 'can_edit' => false, 'can_delete' => false];
            $all['entregas'] = ['can_create' => true, 'can_edit' => true, 'can_delete' => false];
            $all['jornadas'] = ['can_create' => false, 'can_edit' => true, 'can_delete' => false];
            $all['inventario'] = ['can_create' => true, 'can_edit' => true, 'can_delete' => false];
        }
        if ($role === 'purchase_admin') {
            $all['inventario'] = ['can_create' => true, 'can_edit' => true, 'can_delete' => true];
            $all['jornadas'] = ['can_create' => false, 'can_edit' => false, 'can_delete' => false];
        }
        if ($role === 'purchase_coordinator') {
            $all['inventario'] = ['can_create' => true, 'can_edit' => true, 'can_delete' => false];
        }
        return $all;
    }

    public static function tableExists(): bool {
        try {
            DB::query('SELECT 1 FROM role_permissions LIMIT 1');
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /** @return array{can_create:bool,can_edit:bool,can_delete:bool} */
    public static function for(string $role, string $resource): array {
        if ($role === 'admin') {
            return ['can_create' => true, 'can_edit' => true, 'can_delete' => true];
        }
        if (self::tableExists()) {
            $row = DB::query(
                'SELECT can_create, can_edit, can_delete FROM role_permissions WHERE role = ? AND resource = ?',
                [$role, $resource]
            )->fetch();
            if ($row) {
                return [
                    'can_create' => (bool)$row['can_create'],
                    'can_edit' => (bool)$row['can_edit'],
                    'can_delete' => (bool)$row['can_delete'],
                ];
            }
        }
        $defaults = self::defaultsForRole($role);
        return $defaults[$resource] ?? ['can_create' => false, 'can_edit' => false, 'can_delete' => false];
    }

    public static function allows(string $role, string $resource, string $action): bool {
        $action = strtolower($action);
        if (!in_array($action, ['create', 'edit', 'delete'], true)) {
            return false;
        }
        $perms = self::for($role, $resource);
        return (bool)$perms['can_' . $action];
    }

    /** @return array<string,array<string,array{can_create:bool,can_edit:bool,can_delete:bool}>> */
    public static function matrix(): array {
        $roles = array_keys(app_roles());
        $resources = self::resources();
        $out = [];
        foreach ($roles as $role) {
            $defaults = self::defaultsForRole($role);
            foreach ($resources as $slug => $_label) {
                $out[$role][$slug] = self::for($role, $slug);
                if ($role !== 'admin' && !self::tableExists()) {
                    $out[$role][$slug] = $defaults[$slug] ?? $out[$role][$slug];
                }
            }
        }
        return $out;
    }

    /** @param array<string,array<string,array{can_create?:bool,can_edit?:bool,can_delete?:bool}>> $posted */
    public static function saveMatrix(array $posted): void {
        if (!self::tableExists()) {
            throw new RuntimeException('Ejecute sql/010_role_permissions.sql');
        }
        $roles = array_keys(app_roles());
        $resources = array_keys(self::resources());
        DB::connect()->beginTransaction();
        try {
            foreach ($roles as $role) {
                if ($role === 'admin') {
                    continue;
                }
                foreach ($resources as $resource) {
                    $row = $posted[$role][$resource] ?? [];
                    $c = !empty($row['create']) ? 1 : 0;
                    $e = !empty($row['edit']) ? 1 : 0;
                    $d = !empty($row['delete']) ? 1 : 0;
                    DB::query(
                        'INSERT INTO role_permissions (role, resource, can_create, can_edit, can_delete) VALUES (?,?,?,?,?) AS rp
                         ON DUPLICATE KEY UPDATE can_create=rp.can_create, can_edit=rp.can_edit, can_delete=rp.can_delete',
                        [$role, $resource, $c, $e, $d]
                    );
                }
            }
            DB::connect()->commit();
        } catch (Throwable $e) {
            DB::connect()->rollBack();
            throw $e;
        }
    }
}
