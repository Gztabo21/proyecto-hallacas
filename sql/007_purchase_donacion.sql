USE hallacas_jireh;

ALTER TABLE inventory_purchases
  ADD COLUMN es_donacion TINYINT(1) NOT NULL DEFAULT 0 AFTER notes;
