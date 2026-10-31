-- Marca migración de costos congelados en salidas/devoluciones de jornada (backfill vía app).
INSERT INTO settings (`key`, `value`) VALUES ('inventory_jornada_cost_frozen', '0')
ON DUPLICATE KEY UPDATE `key` = `key`;
