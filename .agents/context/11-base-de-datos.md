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

```bash
bin/cli scheme-create module=MiModulo              # el CREATE, ordenado: padres antes que hijas
bin/cli scheme-create module=all output=todo.sql   # el esquema entero
bin/cli scheme-drop   module=MiModulo              # el DROP, ordenado: hijas antes que padres
```

Las dos **descubren los mappers** (`Mappers/`, `SubMappers/`, `ORM/` y `app/model`), sacan el
orden del grafo que los propios `$fields` declaran en `reference_table`, y **emiten: no
ejecutan**. El script se revisa y se aplica a mano.

Para una sola tabla sigue valiendo:

```php
echo (new \PiecesPHP\Core\Database\SchemeCreator(new MiMapper()))->getSQL();
```

> **Ojo, y es importante: hoy el esquema generado NO se aplica entero.** 20 de las 33 tablas
> las rechaza MariaDB, casi todas por `errno 150`: **38 claves ajenas declaran `int` cuando la
> columna que referencian es `bigint`**. Once módulos lo tapan con un `strReplaceTemplate` en
> su bloque `$showSQL` (`'createdBy` int' => 'createdBy` bigint'`); el resto no lo tapa. La
> comprobación está en `bin/cli unit-tests:core/scheme-sql-round-trip` y el análisis en T52 de
> `18-siguientes-ventanas.md`.

> **Y el DDL que genera `SchemeCreator` es `CHARSET=utf8 COLLATE=utf8_bin`, o sea `utf8mb3`**,
> escrito a fuego en el paquete. La *conexión* va en `utf8mb4`; las tablas recién generadas,
> no.

## Backups

```bash
bin/cli db-backup gz=yes data=yes routines=yes views=yes definer=no
```

Salida en `dumps/`. Desde 7.0.6 el motor es
`PiecesPHP\Core\Database\Export\Exporter` (sin dependencia de `mysqldump`), con
formatos SQL/JSON/CSV/PHP/XML y compresión ZIP/Gzip/Bzip2.

### Restaurar

El volcado SQL se carga como cualquier otro:

```bash
mysql -u <usuario> -p <base> < dumps/<archivo>.sql
```

> **SI TU COPIA ES ANTERIOR A ESTA VERSIÓN, NO RESTAURA Y HAY QUE ARREGLARLA ANTES.**
> `db-backup` cifraba la columna `password` al exportar y nada la descifraba al restaurar,
> así que **una restauración dejaba a todos los usuarios sin poder entrar** —
> `password_verify` sobre un hash cifrado falla siempre. Medido, no deducido.
>
> **Los datos NO están perdidos.** El cifrado es reversible con la clave literal que usaba:
>
> ```php
> $hashReal = PiecesPHP\Core\BaseHashEncryption::decrypt($valorDelVolcado, 'ENCRYPTION_KEY');
> ```
>
> Restaura la copia y luego recorre `pcsphp_users` aplicando eso a cada `password`, o
> transforma el `.sql` antes de cargarlo. Comprobado: devuelve el hash `$2y$…` exacto.
>
> **Las copias hechas desde esta versión no necesitan nada de esto.**
> `bin/cli unit-tests:core/db-backup-round-trip` comprueba el viaje entero —exportar,
> restaurar en una base de usar y tirar, y entrar— para que no vuelva a pasar.

## Administración

`src/adminer/` trae Adminer embebido para desarrollo. **Se elimina en despliegue**
(está en la lista de exclusiones del bundle y del script de despliegue).

## Localización en la conexión

`BaseModel` y `BaseEntityMapper` ejecutan `SET lc_time_names = '<locale>'` en la
primera conexión, según `get_config('lc_time_names_mysql')` y el idioma actual.
