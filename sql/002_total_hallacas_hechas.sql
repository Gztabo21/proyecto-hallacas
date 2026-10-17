USE hallacas_jireh;
ALTER TABLE jornadas
  ADD COLUMN total_hallacas_hechas INT NULL DEFAULT NULL
  AFTER precio_hallaca;
