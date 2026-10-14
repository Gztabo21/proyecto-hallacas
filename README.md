# Hallacas Jireh

Sistema web para controlar la distribución de hallacas por jornadas y misiones.

## Funcionalidad
- Gestión de misiones y coordinadores.
- Gestión de usuarios/equipos (administradores, coordinadores, miembros).
- Jornadas de distribución con precio por hallaca.
- Asignación de hallacas a cada misión por jornada.
- Distribución a miembros del equipo por el coordinador.
- Registro de pagos (totales o parciales) y hallacas pendientes por cancelar.
- Reportes por misión, jornada y miembro.
- Control de acceso por roles.

## Requisitos
- Docker y Docker Compose.

## Ejecución
```bash
docker compose up -d
```
Abrir: http://localhost:8080

Puerto MySQL: localhost:3307 (usuario hallacas / hallacas_secret). Root: root_secret

## Credenciales demo
- Admin: admin@jireh.local / admin123
- Coordinador: coord.centro@jireh.local / password
- Coordinador Norte: coord.norte@jireh.local / password
- Miembros: ana@jireh.local, luis@jireh.local, maria@jireh.local / password

## Desarrollo local (sin Docker)
Configure variables de entorno o edite app/config.php.
SQLite no soportado, requiere MySQL.

## Estructura
- public/: punto de entrada y assets
- app/: configuración, controladores, modelos, vistas
- sql/init.sql: esquema y datos demo
- docker-compose.yml, Dockerfile
