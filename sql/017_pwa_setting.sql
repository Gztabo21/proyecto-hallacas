USE hallacas_jireh;

INSERT INTO settings (`key`, `value`) VALUES ('pwa_enabled', '1')
ON DUPLICATE KEY UPDATE `key` = `key`;
