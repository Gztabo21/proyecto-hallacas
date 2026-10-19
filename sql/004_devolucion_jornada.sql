USE hallacas_jireh;
ALTER TABLE inventory_movements
  MODIFY COLUMN tipo ENUM('stock_inicial','compra','salida_jornada','devolucion_jornada','ajuste') NOT NULL;
