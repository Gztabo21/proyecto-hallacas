-- Etiquetas (categorías) en compras/donaciones, almacenadas como JSON array.
USE hallacas_jireh;

ALTER TABLE inventory_purchases ADD COLUMN etiquetas JSON NULL AFTER notes;
