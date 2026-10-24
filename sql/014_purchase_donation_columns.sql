-- Compras: donación, donante y referencia (si la BD se creó antes de 007–011).
USE hallacas_jireh;

ALTER TABLE inventory_purchases
  ADD COLUMN es_donacion TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE inventory_purchases
  ADD COLUMN donante VARCHAR(160) NULL;

ALTER TABLE inventory_purchases
  ADD COLUMN referencia VARCHAR(120) NULL;
