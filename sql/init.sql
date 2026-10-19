CREATE DATABASE IF NOT EXISTS hallacas_jireh
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hallacas_jireh;

CREATE TABLE IF NOT EXISTS settings (
  `key` VARCHAR(64) PRIMARY KEY,
  `value` VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

INSERT INTO settings (`key`,`value`) VALUES
  ('precio_hallaca','1.00'),
  ('app_name','Hallacas Jireh')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

CREATE TABLE IF NOT EXISTS missions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  code VARCHAR(40) NOT NULL UNIQUE,
  description TEXT NULL,
  coordinator_id INT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','coordinator','jornada_admin','member') NOT NULL DEFAULT 'member',
  mission_id INT NULL,
  jornada_id INT NULL,
  phone VARCHAR(40) NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS jornadas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  fecha DATE NOT NULL,
  precio_hallaca DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  total_hallacas_hechas INT NULL DEFAULT NULL,
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  notes TEXT NULL,
  created_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  closed_at DATETIME NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

ALTER TABLE users
  ADD CONSTRAINT fk_users_jornada
  FOREIGN KEY (jornada_id) REFERENCES jornadas(id) ON DELETE SET NULL;

ALTER TABLE missions
  ADD CONSTRAINT fk_missions_coordinator
  FOREIGN KEY (coordinator_id) REFERENCES users(id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS jornada_mision (
  id INT AUTO_INCREMENT PRIMARY KEY,
  jornada_id INT NOT NULL,
  mission_id INT NOT NULL,
  hallacas_asignadas INT NOT NULL DEFAULT 0,
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_jm (jornada_id, mission_id),
  FOREIGN KEY (jornada_id) REFERENCES jornadas(id) ON DELETE CASCADE,
  FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS entregas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  jornada_id INT NOT NULL,
  mission_id INT NOT NULL,
  member_id INT NOT NULL,
  hallacas_entregadas INT NOT NULL DEFAULT 0,
  hallacas_pagadas INT NOT NULL DEFAULT 0,
  precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  monto_pagado DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  status ENUM('pendiente','parcial','pagado') NOT NULL DEFAULT 'pendiente',
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_entrega (jornada_id, member_id),
  FOREIGN KEY (jornada_id) REFERENCES jornadas(id) ON DELETE CASCADE,
  FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE,
  FOREIGN KEY (member_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pagos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  entrega_id INT NOT NULL,
  hallacas INT NOT NULL DEFAULT 0,
  monto DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  moneda ENUM('USD','VES') NOT NULL DEFAULT 'VES',
  metodo VARCHAR(60) NULL,
  tasa DECIMAL(12,4) NULL,
  captura LONGTEXT NULL,
  status ENUM('pendiente','confirmado') NOT NULL DEFAULT 'pendiente',
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  confirmado_at DATETIME NULL,
  notes TEXT NULL,
  created_by INT NULL,
  confirmado_por INT NULL,
  FOREIGN KEY (entrega_id) REFERENCES entregas(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (confirmado_por) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_jornada (
  user_id INT NOT NULL,
  jornada_id INT NOT NULL,
  PRIMARY KEY (user_id, jornada_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (jornada_id) REFERENCES jornadas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inventory_products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  sku VARCHAR(64) NOT NULL,
  unidad_metrica VARCHAR(40) NOT NULL,
  stock DECIMAL(14,3) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_inventory_sku (sku)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inventory_purchases (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fecha DATE NOT NULL,
  tasa DECIMAL(12,4) NOT NULL,
  notes TEXT NULL,
  created_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inventory_movements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  tipo ENUM('stock_inicial','compra','salida_jornada','devolucion_jornada','ajuste') NOT NULL,
  cantidad DECIMAL(14,3) NOT NULL,
  precio_bs DECIMAL(14,4) NULL,
  precio_usd DECIMAL(14,4) NULL,
  tasa DECIMAL(12,4) NULL,
  fecha_ingreso DATE NOT NULL,
  jornada_id INT NULL,
  purchase_id INT NULL,
  notes TEXT NULL,
  created_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES inventory_products(id) ON DELETE RESTRICT,
  FOREIGN KEY (jornada_id) REFERENCES jornadas(id) ON DELETE SET NULL,
  FOREIGN KEY (purchase_id) REFERENCES inventory_purchases(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  KEY idx_mov_product (product_id),
  KEY idx_mov_jornada (jornada_id),
  KEY idx_mov_fecha (fecha_ingreso)
) ENGINE=InnoDB;

-- Seed
INSERT INTO missions (id, name, code, description, status) VALUES
  (1,'Mision Centro','MC','Mision encargada del centro de la ciudad','active'),
  (2,'Mision Norte','MN','Mision zona norte','active')
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO users (id, name, email, password_hash, role, mission_id, status) VALUES
  (1,'Administrador','admin@jireh.local','$2y$10$5s4AbW7m0fY1QumaMlTFS.K2e62Kd0uw6orOxJjAo.FwXaagivqf6','admin',NULL,'active'),
  (2,'Coordinator Centro','coord.centro@jireh.local','$2y$10$rs38E5Aj5BXJaJCs0mRPyOJ8Rjgq1oJj7/aH5WgZQxoIL8qfU/QGO','coordinator',1,'active'),
  (3,'Coordinator Norte','coord.norte@jireh.local','$2y$10$rs38E5Aj5BXJaJCs0mRPyOJ8Rjgq1oJj7/aH5WgZQxoIL8qfU/QGO','coordinator',2,'active'),
  (4,'Miembro Ana','ana@jireh.local','$2y$10$rs38E5Aj5BXJaJCs0mRPyOJ8Rjgq1oJj7/aH5WgZQxoIL8qfU/QGO','member',1,'active'),
  (5,'Miembro Luis','luis@jireh.local','$2y$10$rs38E5Aj5BXJaJCs0mRPyOJ8Rjgq1oJj7/aH5WgZQxoIL8qfU/QGO','member',1,'active'),
  (6,'Miembro Maria','maria@jireh.local','$2y$10$rs38E5Aj5BXJaJCs0mRPyOJ8Rjgq1oJj7/aH5WgZQxoIL8qfU/QGO','member',2,'active')
ON DUPLICATE KEY UPDATE name=VALUES(name);

UPDATE missions SET coordinator_id=2 WHERE id=1;
UPDATE missions SET coordinator_id=3 WHERE id=2;

ALTER TABLE users AUTO_INCREMENT=7;
ALTER TABLE missions AUTO_INCREMENT=3;
ALTER TABLE jornadas AUTO_INCREMENT=1;
