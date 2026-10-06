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
| `actualizaciones/<AAAA-MM-DD>-<tema>.sql` | **El SQL para una instalación que YA existe**, con sus pasos numerados y su prosa dentro (convención del 2026-09-22, `#391`). Los volcados de arriba describen una instalación NUEVA; hasta esa fecha los `ALTER` solo vivían en la prosa del `CHANGELOG`, que se lee pero no se ejecuta |

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
| `actions_log` | Log de acciones (módulo EventsLog). **Ver la nota de abajo: `createdBy` y el actor real** |
| `system_approvals_elements` | Flujo de aprobaciones (transversal) |

> **El registro de acciones y quién actuó de verdad** (bloque CW, 2026-10-02). `LogsMapper::save()` pone en
> `createdBy` el usuario que la aplicación tenía por conectado; **cuando root suplanta, ese es el SUPLANTADO**, y sin
> sesión pone **`1`**, que es el principal. Desde el CW, `meta.actor` declara quién actuaba de verdad
> (`kind: impersonation|system`, `id`, `onBehalfOf`), la pantalla enseña «Sistema» o «quien actuaba, en nombre de
> quien figura», y el principio y el fin de la suplantación dejan su entrada. Medido ese día: 32 filas en la tabla,
> **12 con `createdBy` = 1 y todas del mensaje genérico** (`%message%`), o sea de quien registra sin sesión; `createdBy`
> es `bigint` **NOT NULL con clave ajena**, así que dejarlo sin usuario es cambio de esquema y está **pendiente**.
> **Ojo al buscar en esta tabla:** `addLog()` guarda la **plantilla** en `textMessage` (`%message%`) y el texto en
> `textMessageVariables`, así que buscar por el texto visible no encuentra nada; y `LogsMapper::update()` devuelve
> `false` a propósito: una fila de registro no se edita.

### Contenidos

| Tabla | Módulo |
| :-- | :-- |
| `news_elements`, `news_categories`, `news_readed_relationship` | News |
| `publications_elements`, `publications_categories`, `publications_attachments` | Publications |
| `documents_elements` | Documents |
| `built_in_banner_elements` | BuiltIn\Banner |
| `newsletter_sucribers` | Newsletter *(sic — el nombre tiene la errata en el esquema)* |
| `forms_categories`, `forms_document_types` | Forms |

### Organizaciones

`organizations_elements`.

> Hasta el lote 6 (E3), el esquema versionado creaba además
> `organization_previous_experiences` y `previous_experiences`, del módulo `experience`, que se
> borró hace tiempo. **Ya no las crea.** Ningún mapper las declaraba. En una instalación que las
> tenga, siguen ahí: no hay migración que las borre.

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

**Trampa medida (`#509`, 2026-09-24): para `pcsphp_users`, el orden TABLA -> MAPPER es obligatorio, no
preferencia.** Declarar un campo en el mapper cuya columna aún no existe **deja inservible `bin/cli` entero**, no solo
la tarea que se ejecute: `src/index.php:315` construye `new UsersModel(1)` para la sesión automática de root del
terminal, el `SELECT` ya incluye la columna nueva y la tabla no la tiene. Medido: `bin/cli version` también reventaba.
Primero el `ALTER`, después `$fields`, y `bin/cli version` como canario.

**Y `scheme-create` no sirve para el núcleo** (`#509`): solo cubre módulos de `src/app/classes`, y **`Users` no es un
módulo** —`UsersModel` vive en `src/app/model/`—. Además **emite el SQL, no lo aplica**. Para una tabla del núcleo o
para modificar una que ya existe: `ALTER TABLE` en `databases/actualizaciones/` con la forma del precedente
(`2026-09-22-organizaciones-code.sql`), **aplicado** contra la base local, y `databases/piecesphp_structure.sql`
actualizado en el mismo commit para que un clon nuevo nazca con la columna.

**Trampa medida (`#391`, 2026-09-22): `scheme-create` NO emite índices únicos.** `SchemeCreator`, del paquete
`piecesphp/database`, solo lee `type`, `length`, `null`, `primary_key`, `auto_increment`, `reference_table`,
`reference_field` y `meta`. Una columna que deba ser única lleva su `UNIQUE KEY` **a mano** en
`databases/piecesphp_structure.sql` y en el archivo de actualización; si no, la unicidad queda solo en PHP. Primer caso:
`organizations_elements.code`.

**Trampa medida (`#391`): una columna nueva obligatoria tumba la aplicación durante su propia migración.** Si `$fields`
la declara no nula y las filas viejas están vacías, el `__set` del EntityMapper aborta al hidratarlas y cae TODO
`bin/cli`, incluida la tarea que iba a rellenarlas. Forma correcta: `$fields` con `'null' => true` (con su comentario),
la TABLA con `NOT NULL`, y `save()` poniendo siempre el valor. Así la migración va con la aplicación en marcha.

Las dos **descubren los mappers** (`Mappers/`, `SubMappers/`, `ORM/` y `app/model`), sacan el
orden del grafo que los propios `$fields` declaran en `reference_table`, y **emiten: no
ejecutan**. El script se revisa y se aplica a mano.

Para una sola tabla sigue valiendo:

```php
echo (new \PiecesPHP\Core\Database\SchemeCreator(new MiMapper()))->getSQL();
```

El bloque `$showSQL` que había en diez `<Modulo>Routes` **ya no existe**: era un literal en
`false` que había que editar en el código fuente para sacar el DDL de un módulo, y solo estaba
en diez de ellos. La comprobación de que el esquema entero se genera y se deshace está en
`bin/cli unit-tests:core/scheme-sql-round-trip`.

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

### La política (ADR 0038, bloque CQ)

`PiecesPHP\Core\Backups\BackupPolicy` (opción `backup_policy`) y `BackupRotation`, en
`src/app/core/psr4/PiecesPHP/Core/Backups/`. La edita el usuario principal en «Configuración → Sistema → Respaldos»;
sin nada guardado rigen los valores por omisión (un día de intervalo; 24 recientes, 30 días, 12 semanas, 24 meses;
rotación activa). Un módulo declara tablas que salen sin filas con `BackupPolicy::excludeDataOf()`. Detalle para
desarrolladores en `source-docs/project/docs/piecesphp/new-features/backups.md`.

> **Trampa CONFIRMADA (medida el 2026-10-02, `#759`): el nombre de un respaldo no se parsea con su propio formato.**
> Lo escribe `date('d-m-Y_H-i-s-A')`: hora de 24 **y** sufijo AM/PM redundante. Con PHP 8.5,
> `createFromFormat('d-m-Y_H-i-s-A', '01-09-2026_13-16-22-PM')` devuelve **2026-09-02 01:16:22** (el `A` suma otras 12
> horas a `H`=13). Usa `BackupRotation::dateFromName()`, que parsea `!d-m-Y_H-i-s` y comprueba la vuelta con
> `format()`. Si se hubiera creído el formato natural, la conservación habría ordenado al día siguiente todos los
> respaldos de la tarde y habría borrado los equivocados.

> **La conservación solo toca lo que escribió el framework**, por su nombre y en la raíz de `dumps/`: el `.htaccess`
> y cualquier volcado a mano caen en `ignored`, que nunca entra en `delete`. **Y nunca rota tras un respaldo
> fallido** (`BackupRotation::afterBackup()`), para que un fallo repetido no se lleve los buenos.

> **CONFIRMADO: cada `bin/verify` deja un respaldo nuevo en `dumps/`.** Lo escribe
> `core/db-backup-round-trip` y no lo retira (preexistente; el 2026-10-02 fueron cuatro en una jornada). Con la
> rotación activa se autolimita; con `rotate` en no, crece sin freno. Pendiente de decidir si una suite debe retirar
> lo que crea (`pendientes.md` 295.6).

> **El `where` del exportador NO quita la estructura.** Solo entra en `SqlFormat::getTableData()`; la tabla sigue
> emitiendo su `CREATE TABLE`. Por eso una tabla «sin filas» se restaura vacía y no desaparece.

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

El framework **ya no trae Adminer** (lo retiró el PO el 2026-10-01). Un cliente de base de datos se instala aparte en
el servidor: `source-docs/project/docs/environments/content/lamp/content/Adminer.md`.

## Localización en la conexión

`BaseModel` y `BaseEntityMapper` ejecutan `SET lc_time_names = '<locale>'` en la
primera conexión, según `get_config('lc_time_names_mysql')` y el idioma actual.
