# Crear un importador

Un importador es una clase que extiende `PiecesPHP\Core\DataTransfer\Import\ImportDefinition`. El motor lee el archivo,
reconoce las cabeceras, valida cada celda y cada fila, y **solo si todas son válidas** llama a tu `persist()`. Tú
declaras las columnas, las reglas y cómo se guarda.

El código de esta página es el de `ExampleUserNamesImportDefinition`
(`src/app/classes/DataImportExportUtility/Examples/`), que actualiza el nombre y los apellidos de usuarios existentes.
Está probado por la suite `data-transfer-examples`.

## 1. Lo mínimo

```php
namespace MiModulo\DataTransfer;

use PiecesPHP\Core\DataTransfer\Import\ImportDefinition;

class ProductsImportDefinition extends ImportDefinition
{
    public function key(): string { return 'products'; }          // kebab-case: forma parte del nombre de sus rutas
    public function title(): string { return __('products', 'Productos'); }
    public function allowedUserTypes(): array { return [UsersModel::TYPE_USER_ROOT]; }
    public function columns(): array { /* … paso 2 … */ }
    public function persist(array $rows): ?ImportArtifacts { /* … paso 4 … */ }
}
```

## 2. Las columnas

Cada columna es un `Column($clave, $etiqueta, $obligatoria, $alias, $validador)`, más su ayuda y un ejemplo:

```php
--8<-- "src/app/classes/DataImportExportUtility/Examples/ExampleUserNamesImportDefinition.php:import-columns"
```

- **Cabeceras:** el archivo puede usar la clave, la etiqueta o un alias, sin distinguir mayúsculas. Una columna del
  archivo que no declaras **se ignora**.
- **Obligatoria:** si falta la columna entera, error de cabecera; si falta el valor en una fila, error de esa fila.
- **Celdas:** llegan recortadas; una vacía es «ausente» (`null`). Las fechas de XLSX llegan como `2026-09-17` (con hora
  si la tienen) y los números en crudo (`1234.5`), sea cual sea la configuración regional del libro.
- **Validador:** recibe el valor (ya recortado y no vacío) y la fila, y devuelve el mensaje de error o `null`. El
  mensaje sale al usuario: escríbelo con `__()` y con el nombre de la columna delante.
- **`help()` y `example()`:** salen en la tabla del formulario y como **comentario del encabezado en la plantilla XLSX**.

## 3. Reglas entre filas: `validateAll()`

Lo que no se ve mirando una fila sola, como un valor repetido dentro del archivo. Devuelve `posición de la fila => [errores]`:

```php
--8<-- "src/app/classes/DataImportExportUtility/Examples/ExampleUserNamesImportDefinition.php:import-validate-all"
```

## 4. Guardar: `persist()`

Se llama **solo si todas las filas son válidas**, y **nunca en un simulacro**. Tiene que ser **todo o nada**: abre una
transacción propia con la conexión compartida y, si algo falla, deshaz y lanza `ImportPersistException` con un mensaje
para el usuario, sin datos internos:

```php
--8<-- "src/app/classes/DataImportExportUtility/Examples/ExampleUserNamesImportDefinition.php:import-persist"
```

- **Actualiza solo las columnas que toca y por marcador** (`update()` con `WhereSegment`), nunca concatenando valores
  en el SQL.
- **Trampa conocida:** `EntityMapper::save()` no deja el `id` nuevo en el objeto. Si tu `persist()` crea registros y
  necesita su id, léelo con `getInsertIDOnSave()` o `getLastInsertID()` (así lo hace `UsersModel`).
- **Entregables de un solo uso:** si tu importación genera algo que solo se entrega una vez (como las contraseñas del
  importador de usuarios), devuélvelo en un `ImportArtifacts` y declara `mayProduceArtifacts()`. El panel lo descarga en
  el momento, la terminal lo escribe en `credentials-out`, y **nunca** se guarda en disco, base, sesión ni log.

## 5. Registrar

En el `routes()` de tu módulo:

```php
--8<-- "src/app/classes/DataImportExportUtility/Examples/register-examples.php:register"
```

Crea tres rutas con nombre propio, y el nombre es el permiso:

| Ruta | Qué hace |
| :-- | :-- |
| `data-transfer-import-<key>` | El formulario |
| `data-transfer-import-<key>-action` | Recibe el archivo (y el simulacro) |
| `data-transfer-import-<key>-template` | La plantilla, `?format=xlsx` (por defecto) o `csv` |

Con el módulo apagado (`DATA_IMPORT_EXPORT_MODULE` en `false`), registrar no hace nada ni da error.

## 6. Opciones

| Método | Por defecto | Para qué |
| :-- | :-- | :-- |
| `acceptedExtensions()` | `['xlsx']` | Añade `'csv'` si tu importador lo admite (el de usuarios lo hace) |
| `maxSizeMB()` | 5 | Tamaño máximo del archivo |
| `maxRows()` | 5.000 | Filas máximas |
| `mayProduceArtifacts()` | `false` | `true` si puede generar entregables; la terminal exige entonces `credentials-out` |
| `interfaceLevel()` | `INTERFACE_AUTO` | Ver abajo |
| `description()` | `''` | La frase que sale en la portada, junto al título |
| `formPartial()` / `customView()` | `null` | Vistas propias en los niveles 2 y 3 |

## 7. Niveles de interfaz

| Nivel | Qué hay |
| :-- | :-- |
| **0 · `INTERFACE_NONE`** | Ninguna ruta web: solo la terminal |
| **1 · `INTERFACE_AUTO`** | Formulario, subida y plantilla |
| **2 · `INTERFACE_EXTENDED`** | Lo del 1, más **«Solo validar»** (simulacro) y el hueco de `formPartial()` |
| **3 · `INTERFACE_CUSTOM`** | Tu vista (`customView()`) en lugar del formulario, con las mismas rutas |

Las vistas propias tienen que ser un `.php` **dentro del proyecto**; otra ruta es un error de programación que salta al
registrar o al pintar.

**El simulacro** valida todo (cabeceras, filas, `validateAll()`) y nunca llama a `persist()`: el informe dice
«simulacro», y no hay entregables. En la terminal está siempre disponible con `dry-run=yes`.

## 8. Probarlo

Copia la suite `UnitTest-DataTransferExamples.php` (`src/app/core/system-controllers/local-tests/`) como punto de
partida. Lo que conviene probar:

- la plantilla y sus comentarios;
- un simulacro con un archivo válido: cero errores y `persist()` sin llamar;
- un archivo con errores: cada uno en su fila;
- una importación real y **una con un fallo a mitad que no deje nada cambiado**. Esta escribe de verdad: antes, `bin/cli
  db-backup`; usa datos con un prefijo reconocible y bórralos al terminar.

## La organización, en la importación de usuarios

La columna **«organización»** (también vale la cabecera «código de organización») se rellena con el **código** de la
organización, del estilo `ORG8545741`. Lo encuentras en el listado de organizaciones del panel y en su ficha.

Reglas, por orden:

1. si la celda trae un código válido, el usuario va a esa organización;
2. si la celda está vacía y **quien importa** tiene organización, hereda la suya;
3. si la celda está vacía y quien importa tampoco tiene organización, **esa fila falla** y el informe lo dice:
   «Organización: la fila N no trae ninguna y hay que poner su código, porque quien importa no tiene una que heredar.»;
4. si la celda trae algo que no existe, **la fila falla**; no se sustituye por la de quien importa:
   «Organización: la fila N trae «X», que no es el código ni el id de ninguna organización.»

La celda admite también el identificador interno de la organización, para que las plantillas repartidas antes de la
v8 sigan funcionando. Lo que se recomienda, y lo que escribe la exportación, es el código.

**Un fallo en una fila impide la importación entera:** no se guarda ninguna. Corrige el archivo y vuelve a subirlo.
