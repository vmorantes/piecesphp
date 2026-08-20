# 11 — Base de datos

Motor: **MySQL / MariaDB**, charset `utf8mb4`, acceso vía PDO.
Configuración en `src/app/config/database.php` (multi-grupo, grupo `default`).

## Scripts SQL — `databases/`

| Archivo | Contenido |
| :-- | :-- |
| `piecesphp_structure.sql` | Estructura de todas las tablas (`CREATE TABLE`) |
| `piecesphp_data.sql` | Datos iniciales |
| `piecesphp_views.sql` | Vistas |
| `piecesphp_functions.sql` | Funciones y rutinas almacenadas |
| `locations/locations.sql` | Catálogo de países / estados / ciudades |
| `Utilidades_Datos_iniciales/Tablas.sql` | Tablas utilitarias con datos iniciales |

## Tablas

### Núcleo / sistema (prefijo `pcsphp_`)

| Tabla | Para qué |
| :-- | :-- |
| `pcsphp_users` | Usuarios (`App\Model\UsersModel`) |
| `pcsphp_users_otp_secrets` | Secretos OTP/TOTP (2FA) |
| `pcsphp_user_problems` | Reportes de problemas de usuarios |
| `pcsphp_recovery_password` | Tokens de recuperación de contraseña |
| `pcsphp_tokens` | Tokens genéricos |
| `pcsphp_app_config` | Configuraciones editables (sobrescriben `config.php`) |
| `pcsphp_tickets_log` | Log de tickets (integración osTicket) |
| `pcsphp_jobs_queue` | Cola de trabajos asíncronos |
| `user_system_profile` | Perfiles extendidos de usuario (`UserProfileMapper`) |
| `login_attempts` | Intentos de inicio de sesión |
| `time_on_platform` | Tiempo de permanencia en plataforma |
| `actions_log` | Log de acciones (módulo EventsLog) |
| `system_approvals_elements` | Flujo de aprobaciones (transversal) |

### Contenidos

| Tabla | Módulo |
| :-- | :-- |
| `news_elements`, `news_categories`, `news_readed_relationship` | News |
| `publications_elements`, `publications_categories`, `publications_attachments` | Publications |
| `documents_elements` | Documents |
| `built_in_banner_elements` | BuiltIn\Banner |
| `image_repository_images` | ImagesRepository |
| `newsletter_sucribers` | Newsletter *(sic — el nombre tiene la errata en el esquema)* |
| `application_calls_elements`, `application_calls_attachments` | ApplicationCalls |
| `interest_research_area` | InterestResearchAreas |
| `forms_categories`, `forms_document_types` | Forms |

### Organizaciones y experiencia

`organizations_elements`, `organization_previous_experiences`,
`previous_experiences`.

### Ubicaciones

`locations_countries`, `locations_states`, `locations_cities`, `locations_points`.

## Convenciones

- Nombres de tablas y columnas **en inglés**; tablas en `snake_case` plural o
  `<modulo>_<entidad>`; columnas en `camelCase` (`newsTitle`, `createdAt`,
  `createdBy`, `preferSlug`, `profilesTarget`).
- Campos de auditoría casi universales: `createdAt` (default `timestamp`),
  `updatedAt` (nullable), `createdBy` / `modifiedBy` → FK a `pcsphp_users`.
- `status` como entero con constantes en el mapper (`ACTIVE = 1`, `INACTIVE = 0`).
- Columna `meta` de tipo JSON para las meta-propiedades de
  `EntityMapperExtensible`.
- `preferSlug` como identificador público alternativo al ID.
- La columna virtual `systemApprovalStatus` aparece en los SELECT de todos los
  mappers (la inyecta `BaseEntityMapper`).

## Crear o modificar tablas

**No escribas el `CREATE TABLE` a mano.** Define `$fields` en el mapper y genera el
SQL:

```php
echo (new \PiecesPHP\Core\Database\SchemeCreator(new MiMapper()))->getSQL();
```

Los módulos incluyen ese bloque tras un `$showSQL = false;` en su
`<Modulo>Routes::routes()`; se pone en `true` temporalmente para volcarlo.
Ojo con los ajustes manuales posteriores (p. ej. `News` reemplaza
`createdBy int` → `createdBy bigint` con `strReplaceTemplate`).

## Backups

```bash
bin/cli db-backup gz=yes data=yes routines=yes views=yes definer=no
```

Salida en `dumps/`. Desde 7.0.6 el motor es
`PiecesPHP\Core\Database\Export\Exporter` (sin dependencia de `mysqldump`), con
formatos SQL/JSON/CSV/PHP/XML y compresión ZIP/Gzip/Bzip2.

## Administración

`src/adminer/` trae Adminer embebido para desarrollo. **Se elimina en despliegue**
(está en la lista de exclusiones del bundle y del script de despliegue).

## Localización en la conexión

`BaseModel` y `BaseEntityMapper` ejecutan `SET lc_time_names = '<locale>'` en la
primera conexión, según `get_config('lc_time_names_mysql')` y el idioma actual.
