USE hallacas_jireh;

ALTER TABLE users
  MODIFY COLUMN role VARCHAR(40) NOT NULL DEFAULT 'member';
