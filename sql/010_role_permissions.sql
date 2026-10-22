USE hallacas_jireh;

CREATE TABLE IF NOT EXISTS role_permissions (
  role VARCHAR(40) NOT NULL,
  resource VARCHAR(60) NOT NULL,
  can_create TINYINT(1) NOT NULL DEFAULT 0,
  can_edit TINYINT(1) NOT NULL DEFAULT 0,
  can_delete TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (role, resource)
) ENGINE=InnoDB;
