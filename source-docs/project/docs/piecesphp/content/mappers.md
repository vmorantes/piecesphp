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
Los mappers pueden gestionar traducciones automáticamente usando `$translatableProperties`:

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
Para realizar consultas complejas, se utiliza el método estático `model()`:
```php
$listado = PublicationMapper::model()->select()->where('status', 1)->execute()->result();
```
