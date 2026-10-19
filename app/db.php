<?php
class DB {
    private static ?PDO $pdo = null;
    public static function connect(): PDO {
        if (self::$pdo === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            try {
                self::$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                error_log('DB connection failed: ' . $e->getMessage());
                http_response_code(500);
                $msg = 'No se pudo conectar a la base de datos.';
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    $msg .= ' Revise DB_HOST, DB_NAME, DB_USER y DB_PASS (.env o variables de entorno). Detalle: ' . $e->getMessage();
                } else {
                    $msg .= ' Configure el archivo .env en el servidor o las variables de entorno de MySQL.';
                }
                die($msg);
            }
        }
        return self::$pdo;
    }
    public static function query(string $sql, array $params = []): PDOStatement {
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
