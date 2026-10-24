-- Miembros pueden existir sin correo (no ingresan a la plataforma).
USE hallacas_jireh;

ALTER TABLE users MODIFY email VARCHAR(190) NULL;
