USE hallacas_jireh;

ALTER TABLE inventory_purchases
  ADD COLUMN donante VARCHAR(160) NULL AFTER es_donacion;
