USE hallacas_jireh;

CREATE TABLE IF NOT EXISTS jornada_mesas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  jornada_id INT NOT NULL,
  name VARCHAR(80) NOT NULL,
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_jm_jornada (jornada_id),
  FOREIGN KEY (jornada_id) REFERENCES jornadas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS jornada_mesa_personas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mesa_id INT NOT NULL,
  persona_nombre VARCHAR(120) NOT NULL,
  user_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_jmp_mesa (mesa_id),
  FOREIGN KEY (mesa_id) REFERENCES jornada_mesas(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS jornada_mesa_retiros (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mesa_id INT NOT NULL,
  cantidad INT NOT NULL,
  retirado_at DATETIME NOT NULL,
  notes TEXT NULL,
  created_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_jmr_mesa (mesa_id),
  KEY idx_jmr_retirado (retirado_at),
  FOREIGN KEY (mesa_id) REFERENCES jornada_mesas(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
