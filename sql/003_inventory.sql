USE hallacas_jireh;

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
  es_donacion TINYINT(1) NOT NULL DEFAULT 0,
  donante VARCHAR(160) NULL,
  referencia VARCHAR(120) NULL,
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
