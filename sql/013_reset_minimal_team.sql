-- Reset operativo: sin jornadas, miembros ni inventario.
-- Conserva admin@jireh.local y deja 16 misiones + 16 coordinadores (password: password).
-- Uso: mysql -u... hallacas_jireh < sql/013_reset_minimal_team.sql

USE hallacas_jireh;

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM pagos;
DELETE FROM entregas;
DELETE FROM jornada_mision;
DELETE FROM user_jornada;
DELETE FROM jornada_mesa_retiros;
DELETE FROM jornada_mesa_personas;
DELETE FROM jornada_mesas;
DELETE FROM jornadas;
DELETE FROM inventory_movements;
DELETE FROM inventory_purchases;
DELETE FROM inventory_products;

UPDATE missions SET coordinator_id = NULL;
DELETE FROM missions;

DELETE FROM users WHERE email <> 'admin@jireh.local';

SET FOREIGN_KEY_CHECKS = 1;

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
('Misión 16', 'M16', 'Misión 16', 'active');

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
('Coordinador Misión 16', 'coord.m16@jireh.local', @pwd, 'coordinator', (SELECT id FROM missions WHERE code='M16' LIMIT 1), 'active');

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

UPDATE users SET jornada_id = NULL WHERE jornada_id IS NOT NULL;
