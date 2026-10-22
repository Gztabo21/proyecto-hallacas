# Hallacas Jireh

Sistema web para controlar la distribución de hallacas por jornadas y misiones.

## Funcionalidad
- Gestión de misiones y coordinadores.
- Gestión de usuarios/equipos (administradores, coordinadores, miembros).
- Jornadas de distribución con precio por hallaca.
- Asignación de hallacas a cada misión por jornada.
- Distribución a miembros del equipo por el coordinador.
- Registro de pagos por pago móvil, efectivo bolívares y efectivo dólares, con estado pendiente por confirmar/confirmado (confirmación por administrador).
- Dashboard para coordinador: canceladas vs pendientes.
- Reportes por misión, jornada y miembro (exportación PDF).
- Inventario de ingredientes: productos, compras, movimientos, entrega a jornada y devolución de excedentes al almacén.
- Control de acceso por roles.

## Requisitos
- Docker y Docker Compose.

## Ejecución
```bash
docker compose up -d
```
Abrir: http://localhost:8080

Puerto MySQL en el host: **3307** (usuario `hallacas` / `hallacas_secret`). Root: `root_secret`

### DBeaver CE (con Docker Compose)

1. Levante la base: `docker compose up -d db` (o `docker compose up -d`).
2. Nueva conexión → **MySQL**:
   - **Host:** `localhost` (misma máquina) o la IP del servidor si conecta remoto
   - **Puerto:** `3307` (o el valor de `MYSQL_PUBLISH_PORT` en su `.env`)
   - **Base de datos:** `hallacas_jireh`
   - **Usuario:** `hallacas`
   - **Contraseña:** `hallacas_secret` (la del `docker-compose.yml`)
3. Driver MySQL: use **SSL desactivado** en entorno local si no configura certificados.

Para usar otro puerto en el host, cree `.env` con `MYSQL_PUBLISH_PORT=3308` y ejecute `docker compose up -d`.

**Remoto:** abra el puerto en el firewall solo si lo necesita; en producción prefiera túnel SSH en lugar de exponer MySQL a internet.

## Credenciales demo
- Admin: admin@jireh.local / admin123
- Coordinador: coord.centro@jireh.local / password
- Coordinador Norte: coord.norte@jireh.local / password
- Miembros: ana@jireh.local, luis@jireh.local, maria@jireh.local / password

**Importar miembros:** menú Miembros → **Importar CSV** (columnas `nombre`, `email`, `mision`, etc.; plantilla descargable).

## Desarrollo local (sin Docker)
Cree un archivo `.env` en la raíz con `APP_ENV`, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` y `DB_PASS` (no se versiona).
SQLite no soportado, requiere MySQL.

## Despliegue en servidor

### Opción A: Docker Compose (recomendado)
La imagen `app` usa **PHP 8.2.35** (Apache). Actualice tras parches de seguridad:
```bash
docker compose build --no-cache app
docker compose up -d
docker compose exec app php -v   # debe mostrar 8.2.35
```

En el servidor, con el proyecto clonado:
```bash
docker compose up -d --build
docker compose ps   # app y db deben estar "Up"
```
Abra el puerto **8080** (o el que mapee en `docker-compose.yml`). La app usa `DB_HOST=db` dentro de Docker; no hace falta `.env` si usa el `docker-compose.yml` del repo.

Si la base está vacía (primer arranque), MySQL carga `sql/init.sql` solo la **primera** vez que se crea el volumen. Si el contenedor `db` ya existía sin datos, importe manualmente:
```bash
docker compose exec -T db mysql -uhallacas -phallacas_secret hallacas_jireh < sql/init.sql
```

### Opción B: PHP + Apache/Nginx (sin Docker)
1. Document root apuntando a la carpeta **`public/`**.
2. MySQL instalado; crear base y usuario:
   ```bash
   mysql -u root -p < sql/init.sql
   ```
3. En la raíz del proyecto: cree `.env` con las variables de MySQL (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, etc.).
4. PHP **8.2.35+** (rama 8.2), **8.3.36+**, **8.4.27+** o **8.5.12+** según su rama — parche del 21-oct-2026 para CVE-2026-109618 / CVE-2026-109619 (RCE; muy grave con **Apache mod_php**).
5. Extensiones `pdo_mysql` y `mysqli`.

### Si aparece «No se pudo conectar a la base de datos»
- MySQL está corriendo y escucha en `DB_HOST` / `DB_PORT`.
- La base `DB_NAME` existe y el usuario tiene permisos.
- En Docker: levantó **ambos** servicios (`app` y `db`), no solo la web.
- Si la app corre **fuera** de Docker pero MySQL en el servidor: `DB_HOST=127.0.0.1`, no `db`.
- Para depurar: en `.env` ponga `APP_ENV=local` un momento; el mensaje mostrará el error de MySQL (luego vuelva a `production`).

## Inventario

Roles **admin** y **admin de jornada** pueden gestionar inventario desde el menú **Inventario** y desde la ficha de cada **jornada**.

### Ciclo de stock

| Tipo de movimiento | Efecto en almacén |
|--------------------|-------------------|
| Stock inicial / Compra | Entrada (+) |
| Salida a jornada | Salida (−) al entregar ingredientes a elaboradores |
| Excedente / devolución | Entrada (+) cuando sobra material y vuelve al almacén |
| Ajuste | Delta positivo o negativo según el valor registrado |

En la jornada, por cada producto entregado se muestra: **entregado**, **devuelto**, **consumido (estimado)** (= entregado − devuelto) y **pendiente por devolver** (máximo que aún puede registrarse como excedente).

### Migraciones en bases ya existentes

Si el proyecto se desplegó antes del módulo de inventario, aplique en orden:

```bash
docker compose exec -T db mysql -uhallacas -phallacas_secret hallacas_jireh < sql/003_inventory.sql
docker compose exec -T db mysql -uhallacas -phallacas_secret hallacas_jireh < sql/004_devolucion_jornada.sql
```

Instalaciones nuevas con `sql/init.sql` ya incluyen inventario y el tipo `devolucion_jornada`.

### Roles de compras

- **purchase_admin** (Administrador de compras): inventario completo, catálogo de productos, entregas/devoluciones en jornadas.
- **purchase_coordinator** (Coordinador de compras): inventario, compras, movimientos y entregas/devoluciones en jornadas (sin crear productos en catálogo).

Migración de roles: `sql/005_roles_extend.sql`. Carga de equipo (16 misiones, 16 coordinadores, 12 admins de jornada, usuarios de compras): `sql/006_seed_missions_team.sql` (contraseña inicial `password`).

### Usuarios y misiones

En **Usuarios** y **Misiones** (admin): **Editar** cambia datos y estado; **Desactivar/Activar** sin borrar; **Eliminar** borra definitivamente (use desactivar si hay historial).

### CSV

- **Inventario → Exportar / Importar CSV**: `sku`, `producto`, `unidad_metrica`, `stock`, `precio_bs`, `tasa`, `fecha_ingreso` (USD calculado con la tasa).
- **Inventario / Historial → Descargar PDF**: catálogo con stock y precios, o movimientos (respeta filtro por producto).

## Reportes PDF

Los PDF usan [Dompdf](https://github.com/dompdf/dompdf) (`composer.json`). Debe existir `vendor/autoload.php` en la raíz del proyecto.

### Docker Compose (desarrollo y servidor)

El servicio `app` monta el código local sobre `/var/www/html`, por lo que la carpeta `vendor/` del host **reemplaza** la de la imagen (y suele estar vacía porque no se versiona). Al arrancar, el **entrypoint** del contenedor ejecuta `composer install` automáticamente si falta `vendor/autoload.php`.

Tras clonar o actualizar dependencias:

```bash
docker compose up -d --build
docker compose logs app   # debe verse "Instalando dependencias PHP..." la primera vez
```

Si el mensaje «PDF no disponible» persiste:

```bash
docker compose exec app composer install --no-dev --no-interaction
docker compose restart app
```

### Sin Docker

En la raíz del proyecto, con PHP y [Composer](https://getcomposer.org/) instalados:

```bash
composer install --no-dev
```

## Estructura
- public/: punto de entrada y assets
- app/: configuración, controladores, modelos, vistas
- sql/init.sql: esquema y datos demo
- sql/003_inventory.sql, sql/004_devolucion_jornada.sql: migraciones de inventario
- docker-compose.yml, Dockerfile
