-- Carga inicial: 16 misiones (1–16), 16 coordinadores, 12 admins de jornada, compras.
-- Contraseña por defecto de usuarios nuevos: password
-- Ejecutar después de 005_roles_extend.sql

USE hallacas_jireh;

SET @pwd = '$2y$10$ETbiYBiqv4rdWTw4ZE0RguS96aiBu37fCnrGo7Jfc/OL1YShY.deK';

INSERT INTO missions (name, code, description, status) VALUES
('Misión 1', 'M01', 'Misión 1', 'active'),
('Misión 2', 'M02', 'Misión 2', 'active'),
('Misión 3', 'M03', 'Misión 3', 'active'),
('Misión 4', 'M04', 'Misión 4', 'active'),
('Misión 5', 'M05', 'Misión 5', 'active'),
('Misión 6', 'M06', 'Misión 6', 'active'),
('Misión 7', 'M07', 'Misión 7', 'active'),
('Misión 8', 'M08', 'Misión 8', 'active'),
('Misión 9', 'M09', 'Misión 9', 'active'),
('Misión 10', 'M10', 'Misión 10', 'active'),
('Misión 11', 'M11', 'Misión 11', 'active'),
('Misión 12', 'M12', 'Misión 12', 'active'),
('Misión 13', 'M13', 'Misión 13', 'active'),
('Misión 14', 'M14', 'Misión 14', 'active'),
('Misión 15', 'M15', 'Misión 15', 'active'),
('Misión 16', 'M16', 'Misión 16', 'active')
AS m
ON DUPLICATE KEY UPDATE name = m.name, status = m.status;

INSERT INTO users (name, email, password_hash, role, mission_id, status) VALUES
('Coordinador Misión 1', 'coord.m01@jireh.local', @pwd, 'coordinator', (SELECT id FROM missions WHERE code='M01' LIMIT 1), 'active'),
('Coordinador Misión 2', 'coord.m02@jireh.local', @pwd, 'coordinator', (SELECT id FROM missions WHERE code='M02' LIMIT 1), 'active'),
('Coordinador Misión 3', 'coord.m03@jireh.local', @pwd, 'coordinator', (SELECT id FROM missions WHERE code='M03' LIMIT 1), 'active'),
('Coordinador Misión 4', 'coord.m04@jireh.local', @pwd, 'coordinator', (SELECT id FROM missions WHERE code='M04' LIMIT 1), 'active'),
('Coordinador Misión 5', 'coord.m05@jireh.local', @pwd, 'coordinator', (SELECT id FROM missions WHERE code='M05' LIMIT 1), 'active'),
('Coordinador Misión 6', 'coord.m06@jireh.local', @pwd, 'coordinator', (SELECT id FROM missions WHERE code='M06' LIMIT 1), 'active'),
('Coordinador Misión 7', 'coord.m07@jireh.local', @pwd, 'coordinator', (SELECT id FROM missions WHERE code='M07' LIMIT 1), 'active'),
('Coordinador Misión 8', 'coord.m08@jireh.local', @pwd, 'coordinator', (SELECT id FROM missions WHERE code='M08' LIMIT 1), 'active'),
('Coordinador Misión 9', 'coord.m09@jireh.local', @pwd, 'coordinator', (SELECT id FROM missions WHERE code='M09' LIMIT 1), 'active'),
('Coordinador Misión 10', 'coord.m10@jireh.local', @pwd, 'coordinator', (SELECT id FROM missions WHERE code='M10' LIMIT 1), 'active'),
('Coordinador Misión 11', 'coord.m11@jireh.local', @pwd, 'coordinator', (SELECT id FROM missions WHERE code='M11' LIMIT 1), 'active'),
('Coordinador Misión 12', 'coord.m12@jireh.local', @pwd, 'coordinator', (SELECT id FROM missions WHERE code='M12' LIMIT 1), 'active'),
('Coordinador Misión 13', 'coord.m13@jireh.local', @pwd, 'coordinator', (SELECT id FROM missions WHERE code='M13' LIMIT 1), 'active'),
('Coordinador Misión 14', 'coord.m14@jireh.local', @pwd, 'coordinator', (SELECT id FROM missions WHERE code='M14' LIMIT 1), 'active'),
('Coordinador Misión 15', 'coord.m15@jireh.local', @pwd, 'coordinator', (SELECT id FROM missions WHERE code='M15' LIMIT 1), 'active'),
('Coordinador Misión 16', 'coord.m16@jireh.local', @pwd, 'coordinator', (SELECT id FROM missions WHERE code='M16' LIMIT 1), 'active')
AS u
ON DUPLICATE KEY UPDATE name = u.name, role = u.role, mission_id = u.mission_id, status = u.status;

UPDATE missions SET coordinator_id = (SELECT id FROM users WHERE email = 'coord.m01@jireh.local' LIMIT 1) WHERE code = 'M01';
UPDATE missions SET coordinator_id = (SELECT id FROM users WHERE email = 'coord.m02@jireh.local' LIMIT 1) WHERE code = 'M02';
UPDATE missions SET coordinator_id = (SELECT id FROM users WHERE email = 'coord.m03@jireh.local' LIMIT 1) WHERE code = 'M03';
UPDATE missions SET coordinator_id = (SELECT id FROM users WHERE email = 'coord.m04@jireh.local' LIMIT 1) WHERE code = 'M04';
UPDATE missions SET coordinator_id = (SELECT id FROM users WHERE email = 'coord.m05@jireh.local' LIMIT 1) WHERE code = 'M05';
UPDATE missions SET coordinator_id = (SELECT id FROM users WHERE email = 'coord.m06@jireh.local' LIMIT 1) WHERE code = 'M06';
UPDATE missions SET coordinator_id = (SELECT id FROM users WHERE email = 'coord.m07@jireh.local' LIMIT 1) WHERE code = 'M07';
UPDATE missions SET coordinator_id = (SELECT id FROM users WHERE email = 'coord.m08@jireh.local' LIMIT 1) WHERE code = 'M08';
UPDATE missions SET coordinator_id = (SELECT id FROM users WHERE email = 'coord.m09@jireh.local' LIMIT 1) WHERE code = 'M09';
UPDATE missions SET coordinator_id = (SELECT id FROM users WHERE email = 'coord.m10@jireh.local' LIMIT 1) WHERE code = 'M10';
UPDATE missions SET coordinator_id = (SELECT id FROM users WHERE email = 'coord.m11@jireh.local' LIMIT 1) WHERE code = 'M11';
UPDATE missions SET coordinator_id = (SELECT id FROM users WHERE email = 'coord.m12@jireh.local' LIMIT 1) WHERE code = 'M12';
UPDATE missions SET coordinator_id = (SELECT id FROM users WHERE email = 'coord.m13@jireh.local' LIMIT 1) WHERE code = 'M13';
UPDATE missions SET coordinator_id = (SELECT id FROM users WHERE email = 'coord.m14@jireh.local' LIMIT 1) WHERE code = 'M14';
UPDATE missions SET coordinator_id = (SELECT id FROM users WHERE email = 'coord.m15@jireh.local' LIMIT 1) WHERE code = 'M15';
UPDATE missions SET coordinator_id = (SELECT id FROM users WHERE email = 'coord.m16@jireh.local' LIMIT 1) WHERE code = 'M16';

INSERT INTO users (name, email, password_hash, role, mission_id, status) VALUES
('Admin jornada 01', 'jornada.admin01@jireh.local', @pwd, 'jornada_admin', NULL, 'active'),
('Admin jornada 02', 'jornada.admin02@jireh.local', @pwd, 'jornada_admin', NULL, 'active'),
('Admin jornada 03', 'jornada.admin03@jireh.local', @pwd, 'jornada_admin', NULL, 'active'),
('Admin jornada 04', 'jornada.admin04@jireh.local', @pwd, 'jornada_admin', NULL, 'active'),
('Admin jornada 05', 'jornada.admin05@jireh.local', @pwd, 'jornada_admin', NULL, 'active'),
('Admin jornada 06', 'jornada.admin06@jireh.local', @pwd, 'jornada_admin', NULL, 'active'),
('Admin jornada 07', 'jornada.admin07@jireh.local', @pwd, 'jornada_admin', NULL, 'active'),
('Admin jornada 08', 'jornada.admin08@jireh.local', @pwd, 'jornada_admin', NULL, 'active'),
('Admin jornada 09', 'jornada.admin09@jireh.local', @pwd, 'jornada_admin', NULL, 'active'),
('Admin jornada 10', 'jornada.admin10@jireh.local', @pwd, 'jornada_admin', NULL, 'active'),
('Admin jornada 11', 'jornada.admin11@jireh.local', @pwd, 'jornada_admin', NULL, 'active'),
('Admin jornada 12', 'jornada.admin12@jireh.local', @pwd, 'jornada_admin', NULL, 'active')
AS u
ON DUPLICATE KEY UPDATE name = u.name, role = u.role, status = u.status;

INSERT INTO users (name, email, password_hash, role, mission_id, status) VALUES
('Administrador de compras', 'admin.compras@jireh.local', @pwd, 'purchase_admin', NULL, 'active'),
('Coordinador de compras', 'coord.compras@jireh.local', @pwd, 'purchase_coordinator', NULL, 'active')
AS u
ON DUPLICATE KEY UPDATE name = u.name, role = u.role, status = u.status;
