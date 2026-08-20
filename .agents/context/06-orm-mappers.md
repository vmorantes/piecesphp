# 06 — Base de datos, ORM y Mappers

El acceso a datos se hace con un ORM propio que vive en el paquete Composer
`piecesphp/database` (`src/vendor/piecesphp/database`, namespace
`PiecesPHP\Core\Database\*`) más extensiones locales en
`src/app/core/psr4/PiecesPHP/Core/`.

## Jerarquía de clases

```
PDO
 └── PiecesPHP\Core\Database\Database
      └── ...ORM\ActiveRecord            select/insert/update/delete/join/where/...
           └── ...ActiveRecordModel      abstracción de tabla (fields, prefix, db pools)
                └── PiecesPHP\Core\BaseModel        + config automática por grupo de BD
                                                    + lc_time_names según idioma

PiecesPHP\Core\Database\EntityMapper     abstracción de ENTIDAD (fila como objeto)
 ├── PiecesPHP\Core\BaseEntityMapper     + eventos saving/saved/updating/updated
 │                                        + inyección de systemApprovalStatus
 └── PiecesPHP\Core\Database\EntityMapperExtensible   + meta properties (campo JSON)
```

**Regla práctica:** para código nuevo usa **`EntityMapperExtensible`**. Es lo que
usan todos los módulos modernos (`NewsMapper`, `PublicationsMapper`, etc.).
`BaseModel` queda para consultas crudas o modelos de sistema antiguos
(`UsersModel`, `AppConfigModel`, …, en `App\Model`).

## Anatomía de un Mapper

Ubicación: `src/app/classes/<Modulo>/Mappers/<Nombre>Mapper.php`.

```php
namespace News\Mappers;

use PiecesPHP\Core\Database\EntityMapperExtensible;
use PiecesPHP\Core\Database\Meta\MetaProperty;

/**
 * @property int|null $id
 * @property string $newsTitle
 * @property int|NewsCategoryMapper $category
 * @property \stdClass|null $langData
 */
class NewsMapper extends EntityMapperExtensible
{
    const TABLE = 'news_elements';
    protected $table = self::TABLE;

    protected $fields = [
        'id'        => ['type' => 'int', 'primary_key' => true],
        'newsTitle' => ['type' => 'text'],
        'profilesTarget' => ['type' => 'json', 'default' => []],
        'category'  => [
            'type' => 'int',
            'reference_table'        => NewsCategoryMapper::TABLE,
            'reference_field'        => 'id',
            'reference_primary_key'  => 'id',
            'human_readable_reference_field' => 'id',
            'mapper' => NewsCategoryMapper::class,
        ],
        'createdAt' => ['type' => 'datetime', 'default' => 'timestamp'],
        'updatedAt' => ['type' => 'datetime', 'null' => true],
        'createdBy' => [
            'type' => 'int',
            'reference_table' => UsersModel::TABLE,
            'reference_field' => 'id',
            'human_readable_reference_field' => 'username',
            'mapper' => UsersModel::class,
        ],
        'status' => ['type' => 'int', 'default' => self::ACTIVE],
        'meta'   => ['type' => 'json', 'null' => true],
    ];

    const ACTIVE = 1;
    const INACTIVE = 0;
    const STATUSES = [self::ACTIVE => 'Activo', self::INACTIVE => 'Inactiva'];
    const STATUSES_COLORS = [self::ACTIVE => 'brand-color', ...];

    public function __construct($value = null, string $fieldCompare = 'primary_key')
    {
        parent::__construct($value, $fieldCompare);
        // Meta-propiedades (se serializan dentro de la columna JSON `meta`)
        $this->addMetaProperty(new MetaProperty(MetaProperty::TYPE_INT,  0, false), 'draft');
        $this->addMetaProperty(new MetaProperty(MetaProperty::TYPE_JSON, new \stdClass, true), 'langData');
        $this->addMetaProperty(new MetaProperty(MetaProperty::TYPE_TEXT, Config::get_default_lang(), true), 'baseLang');
    }
}
```

### Claves de `$fields`

| Clave | Significado |
| :-- | :-- |
| `type` | `int`, `text`, `varchar`, `datetime`, `date`, `json`, `float`, … |
| `length` | Longitud (para `varchar`) |
| `primary_key` | `true` en la PK |
| `null` | Permite `NULL` |
| `default` | Valor por defecto (`'timestamp'` para `CURRENT_TIMESTAMP`) |
| `reference_table` / `reference_field` / `reference_primary_key` | Relación FK |
| `human_readable_reference_field` | Campo a mostrar al humanizar la relación |
| `mapper` | Clase del mapper relacionado — permite carga automática del objeto |

Cuando un campo tiene `mapper`, al leer la propiedad se obtiene **la instancia del
mapper relacionado**, no el entero. De ahí los docblocks tipo
`@property int|UsersModel $createdBy`.

### Meta-propiedades (`EntityMapperExtensible`)

Permiten añadir atributos sin migrar la tabla: se serializan en una columna JSON
(por convención `meta`).

```php
$this->addMetaProperty(new MetaProperty(MetaProperty::TYPE_JSON, new \stdClass, true), 'langData');
$this->getMetaProperty('langData');  $this->hasMetaProperty('x');  $this->removeMetaProperty('x');
```
Tipos: `MetaProperty::TYPE_INT|TYPE_TEXT|TYPE_JSON|...`. Se acceden como propiedades
normales (`$mapper->langData`) gracias a `__get`/`__set`.

### Multi-idioma en los datos

Dos mecanismos conviven:

1. **`$translatableProperties`** — propiedad protegida en mappers como
   `GenericContentPseudoMapper` y `BuiltInBannerMapper`; los campos listados se
   traducen automáticamente (los no listados caen en `$noTranslatableProperties`).
2. **`baseLang` + `langData`** (meta-propiedades) — patrón de `NewsMapper`:
   `baseLang` guarda el idioma original y `langData` es un objeto
   `{ lang: { propiedad: valor } }` con las traducciones. El mapper expone métodos
   para leer la propiedad en el idioma actual con caída al idioma base.

## Operaciones

```php
// Cargar
$n = new NewsMapper(1);                    // por primary key
$n = new NewsMapper('mi-slug', 'preferSlug');  // por otro campo

// Crear / actualizar
$n = new NewsMapper();
$n->newsTitle = 'Título';
$n->save();
$n->getInsertIDOnSave();

$n = new NewsMapper(1);
$n->newsTitle = 'Editado';
$n->update();

// Consultar (ActiveRecord)
$rows = NewsMapper::model()
    ->select()
    ->where('status', NewsMapper::ACTIVE)
    ->orderBy('createdAt DESC')
    ->execute()
    ->result();
```

`Mapper::model()` devuelve el `ActiveRecordModel` subyacente. La API de
`ActiveRecord` incluye: `select, insert, update, delete, join, leftJoin, rightJoin,
innerJoin, where, having, groupBy, orderBy, row, getAll, get, setTypeResult,
setSelectClass, execute, result, rowCount, lastInsertId, getCompiledSQL,
getLastSQLExecuted, resetWhere/resetJoins/resetAll`.

## Eventos automáticos

`BaseEntityMapper` (y por herencia los mappers) dispara vía `BaseEventDispatcher`:

- `saving` / `saved` en `save()`
- `updating` / `updated` en `update()`

El contexto es el nombre de la clase del mapper. Ver [10-cli-y-tareas.md](./10-cli-y-tareas.md).

## `systemApprovalStatus`

`BaseEntityMapper::__callStatic` intercepta `fieldsToSelect()` y añade una subconsulta
que expone la columna virtual **`systemApprovalStatus`** en todos los `SELECT`,
tomada de la tabla `system_approvals_elements` (módulo de Aprobaciones). Si
`SystemApprovalsRoutes::ENABLE` es `false`, se homologa a `'APPROVED'`.
Consecuencia práctica: **todos los mappers exponen `systemApprovalStatus`** aunque
no lo declaren.

## Generar el SQL de una tabla

```php
(new \PiecesPHP\Core\Database\SchemeCreator(new NewsMapper()))->getSQL();
```

Los módulos incluyen un bloque `$showSQL = true;` comentado en su `<Modulo>Routes`
para volcar el `CREATE TABLE` de todos sus mappers. Ese es el método canónico para
crear tablas nuevas: **define `$fields` primero, luego genera el SQL**.

## Múltiples conexiones

`Config::app_db($grupo)` lee `$config['database'][$grupo]`. Los constructores de
`BaseModel` y `BaseEntityMapper` aceptan `$db_group` (por defecto `'default'`).

Para procesos largos (cronjobs), gestiona la conexión manualmente:

```php
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Config;

BaseModel::destroyDb(Config::app_db('default')['db'], Config::app_db('default')['host']);
// ... trabajo pesado ...
BaseModel::restoreInstancesDb(Config::app_db('default')['db'], Config::app_db('default')['host']);
```

Ambos métodos vienen de `ActiveRecordModel`. Hay un caso real en
`config/final-configurations-includes/cronjobs.php` (tarea «Respaldar base de datos»).

## Paginación y DataTables

- `PiecesPHP\Core\Pagination\{PageQuery, PaginationResult}`.
- `PiecesPHP\Core\Utilities\Helpers\DataTablesHelper` — construye la respuesta JSON
  que espera DataTables desde el endpoint `-datatables` de cada módulo.

## Validación

- `PiecesPHP\Core\Validation\Validator`
- `PiecesPHP\Core\Validation\Parameters\{Parameter, Parameters}` con excepciones
  `MissingRequiredParamaterException`, `InvalidParameterValueException`,
  `ParsedValueException`. Es el patrón usado en los métodos `action()` de los
  controladores para validar el body.

## Exportación / backup

`PiecesPHP\Core\Database\Export\Exporter` (desde 7.0.6) reemplaza `mysqldump`.
Formatos: SQL, JSON, CSV, PHP, XML. Salidas: `FileOutput`, `ZipFileOutput`,
`GzipFileOutput`, `Bz2FileOutput`, `MemoryOutput`.
