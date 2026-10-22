USE hallacas_jireh;

ALTER TABLE inventory_purchases
  ADD COLUMN referencia VARCHAR(120) NULL AFTER es_donacion;
