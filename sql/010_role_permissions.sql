USE hallacas_jireh;

-- Roles extendidos (jornada_admin, purchase_*, etc.) requieren VARCHAR, no ENUM antiguo.
ALTER TABLE users
  MODIFY COLUMN role VARCHAR(40) NOT NULL DEFAULT 'member';

CREATE TABLE IF NOT EXISTS role_permissions (
  role VARCHAR(40) NOT NULL,
  resource VARCHAR(60) NOT NULL,
  can_create TINYINT(1) NOT NULL DEFAULT 0,
  can_edit TINYINT(1) NOT NULL DEFAULT 0,
  can_delete TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (role, resource)
) ENGINE=InnoDB;

-- Valores por defecto (misma lógica que RolePermission::defaultsForRole)
INSERT INTO role_permissions (role, resource, can_create, can_edit, can_delete) VALUES
('coordinator', 'pagos', 1, 0, 0),
('coordinator', 'entregas', 1, 1, 0),
('coordinator', 'miembros', 1, 1, 0),
('coordinator', 'reportes', 1, 0, 0),
('jornada_admin', 'pagos', 1, 0, 0),
('jornada_admin', 'entregas', 1, 1, 0),
('jornada_admin', 'jornadas', 0, 1, 0),
('jornada_admin', 'inventario', 1, 1, 0),
('purchase_admin', 'inventario', 1, 1, 1),
('purchase_coordinator', 'inventario', 1, 1, 0)
AS rp
ON DUPLICATE KEY UPDATE
  can_create = rp.can_create,
  can_edit = rp.can_edit,
  can_delete = rp.can_delete;
