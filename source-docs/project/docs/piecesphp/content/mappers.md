# Mappers y Modelos (v2)

El framework utiliza un sistema de Mapeo de Entidades basado en la clase `PiecesPHP\Core\Database\EntityMapperExtensible` (o `BaseEntityMapper` para casos simples).

## Estructura de un Mapper profesional

Los mappers se encuentran habitualmente en `src/app/classes/[Modulo]/Mappers/`.

### Definición del Schema

A diferencia de un modelo básico, los Mappers definen su esquema mediante la propiedad `$fields`. Esto permite validación automática y gestión de tipos.

```php
namespace MiModulo\Mappers;

use PiecesPHP\Core\Database\EntityMapperExtensible;
use App\Model\UsersModel;

class MiMapper extends EntityMapperExtensible {
    // Definición de campos
    protected $fields = [
        'id' => [
            'type' => 'int',
            'primary_key' => true,
        ],
        'title' => [
            'type' => 'varchar',
            'length' => 255,
        ],
        'author' => [
            'type' => 'int',
            'reference_table' => UsersModel::TABLE,
            'reference_field' => 'id',
            'mapper' => UsersModel::class,
        ],
        'meta' => [
            'type' => 'json',
            'null' => true,
        ],
    ];

    const TABLE = 'mi_tabla_db';
    protected $table = self::TABLE;

    public function __construct(int $value = null, string $fieldCompare = 'primary_key') {
        parent::__construct($value, $fieldCompare);
    }
}
```

## Características Avanzadas

### Multi-idioma
**No es automático.** `EntityMapperExtensible` no declara `$translatableProperties`: es un patrón que cada mapper
con traducciones implementa por su cuenta (`PublicationMapper.php:252`, `DocumentsMapper`, `CategoriesMapper`,
`PublicationCategoryMapper`), con `$noTranslatableProperties` y los métodos que leen y guardan la versión de cada
idioma en `meta`. Para un módulo nuevo con traducciones, copia el patrón de `PublicationMapper`:

```php
protected $translatableProperties = [
    'title',
    'content',
];
```

### Meta Propiedades
Al extender de `EntityMapperExtensible`, se pueden añadir propiedades dinámicas que se guardan en un campo JSON (habitualmente llamado `meta`):

```php
$this->addMetaProperty(new MetaProperty(MetaProperty::TYPE_JSON, new \stdClass), 'mis_ajustes');
```

## Operaciones Comunes

### Carga de Datos
```php
$publicacion = new PublicationMapper(1); // Carga por ID
$publicacion = new PublicationMapper('mi-slug', 'preferSlug'); // Carga por otro campo
```

### Guardar y Actualizar
```php
$nuevo = new PublicationMapper();
$nuevo->title = "Nuevo Título";
$nuevo->save();

$existente = new PublicationMapper(1);
$existente->title = "Título Editado";
$existente->update();
```

### Consultas (Select)
Para realizar consultas complejas, se utiliza el método estático `model()`. `where()` recibe **un** argumento, y
`execute()` devuelve `bool`: el resultado se pide después con `result()`.
```php
$model = PublicationMapper::model();
$model->select()->where(['status' => 1]); // array: el valor va por marcador
$model->execute();
$listado = $model->result(); // array|null
```

> **Nunca concatenes un valor de la petición en `where()` como texto** (`->where("title = '$titulo'")`): la forma
> de cadena va tal cual al SQL. Con un array o un `WhereSegment` el valor viaja por marcador (ADR 0009 de la
> documentación de agentes).

## La tabla: se genera, no se escribe

La tabla de un mapper **no se escribe a mano**. Sale de sus `$fields`:

```bash
bin/cli scheme-create module=<Nombre>   # imprime el CREATE TABLE, padres antes que hijas
bin/cli scheme-drop module=<Nombre>     # su inverso
```

Las dos **emiten** el SQL y no lo ejecutan: lo aplicas tú, después de `bin/cli db-backup`. Si cambias `$fields`,
vuelve a generarlo.
