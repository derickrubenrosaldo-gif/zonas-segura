# Zona Segura

Plataforma para ubicar afectaciones reportadas por la comunidad e instituciones, y consultar mediciones de estaciones IoT en el mismo mapa. Cada marcador identifica su origen, estado y hora de actualización. La vista de demostración usa registros y lecturas simulados; no son datos de emergencias reales.

## Stack

- PHP 8.2+ y Laravel 12
- PostgreSQL compatible con Neon
- Leaflet y OpenStreetMap
- Vercel con runtime PHP comunitario

## Puesta en marcha

Configura `APP_KEY`, `DATABASE_URL`, `ADMIN_API_KEY` e `IOT_API_KEY`. Ejecuta `php artisan migrate --force` desde el entorno autorizado que tenga acceso a Neon, antes de habilitar registros reales. Las migraciones crean las tablas de reportes, estaciones e historial. En producción, el sitio muestra ejemplos identificados si una fuente de datos no está disponible.

## API

- `GET /api/bulletins`: avisos locales configurados por el operador con `LOCAL_BULLETINS_JSON`. Cada entrada incluye `title`, `area`, `issued_at`, `expires_at` y `url` oficial. Usa fechas ISO 8601 con zona horaria. Solo se muestran entradas vigentes.
- `GET /api/incidents`: incidencias públicas; permite filtros `category`, `status` y `from`.
- `POST /api/incidents`: recepción ciudadana pausada (503).
- `POST /api/admin/incidents`: alta de un aviso institucional, con `Authorization: Bearer <ADMIN_API_KEY>` y origen `firefighters`, `public_security` o `civil_protection`.
- Para publicar un perímetro, adjunta al alta institucional `affected_area` (GeoJSON Polygon con un anillo cerrado y coordenadas longitud/latitud) y un `source_url` HTTPS de un dominio oficial mexicano. En el mapa solo aparece para reportes en seguimiento.
- `PATCH /api/admin/incidents/{id}/status`: actualiza el estado; usa `ADMIN_API_KEY`.
- `GET /api/stations`: estaciones activas y última lectura. Si pasan 20 minutos sin lectura, se muestran sin señal.
- `GET /api/stations/{stationCode}/history`: devuelve hasta 24 lecturas de las últimas 24 horas, con unidad y hora.
- `POST /api/admin/stations`: registra una estación; usa `ADMIN_API_KEY`.
- `POST /api/iot/stations/{stationCode}/readings`: recibe `value` y `read_at`, autentica con `IOT_API_KEY` y determina nivel normal, atención o alerta a partir de umbrales configurados.

El prototipo no se conecta por sí solo a los sistemas de Bomberos, Seguridad Pública, Protección Civil ni a dispositivos físicos. Las instituciones deben publicar sus avisos por la API y cada equipo IoT debe enviar sus lecturas autenticadas.

## Panel privado

El panel se abre en `/admin` y permite administrar reportes, estaciones, boletines oficiales y textos de portada. Para activarlo:

1. Conecta una base Neon propia con `DATABASE_URL`. La compilación de producción en Vercel aplica las migraciones. En otros entornos ejecútalas con `php artisan migrate --force`.
2. Genera una contraseña aleatoria y guarda el valor original en el gestor de contraseñas de la persona administradora.
3. Configura en Vercel `ADMIN_PANEL_USERNAME` y `ADMIN_PANEL_PASSWORD_HASH`. Genera el hash con `password_hash($password, PASSWORD_BCRYPT)`. Si existe una variable `SESSION_DRIVER` explícita, debe ser `database`.
4. Despliega de nuevo e inicia sesión con el usuario y la contraseña original.

La sesión de administración dura 30 minutos. La contraseña del panel es independiente de las claves de las API institucional e IoT.
