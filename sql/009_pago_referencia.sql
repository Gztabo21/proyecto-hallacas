USE hallacas_jireh;

ALTER TABLE pagos
  ADD COLUMN referencia VARCHAR(120) NULL AFTER metodo;
