# Exportador Nativos de Base de Datos

La versión de PiecesPHP (v7.0.6 en adelante) introdujo un nuevo motor nativo en PHP para la exportación y volcado de la base de datos (`PiecesPHP\Core\Database\Export\Exporter`). Este motor reemplaza la dependencia del binario del sistema `mysqldump`, lo que garantiza que los respaldos funcionen **en cualquier sistema operativo** independientemente de si los utilitarios de línea de comandos de bases de datos de bajo nivel están instalados.

---

## ⚡ Opciones de Exportación

El sistema de exportación (`Exporter`) soporta configuraciones flexibles mediante dos interfaces principales: `FormatPluginInterface` (Cómo se formatea la salida) y `OutputPluginInterface` (A dónde y cómo se guarda / comprime).

### Formatos Soportados (`PiecesPHP\Core\Database\Export\Plugins\`)
- `SqlFormat`: El tradicional dump relacional DDL / DML (`.sql`).
- `JsonFormat`: Exportación serializada de array list (`.json`).
- `CsvFormat`: Valores separados por comas para cada tabla (`.csv`).
- `XmlFormat`: Esquema de jerarquía clásica (`.xml`).
- `PhpFormat`: Exportación en arreglos de sintaxis PHP (`.php`).

### Motores de Compresión / Salida
- `FileOutput`: Escritura de texto plano al disco.
- `GzipFileOutput`: Stream comprimido (`.gz`).
- `ZipFileOutput`: Archivo ZIP estándar (`.zip`).
- `Bz2FileOutput`: Compresión Bzip2 (`.bz2`).
- `MemoryOutput`: Guardar el resultado en memoria sin tocar disco permanente.

---

## 🛠️ Uso Básico

```php
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\Export\Exporter;
use PiecesPHP\Core\Database\Export\Plugins\SqlFormat;
use PiecesPHP\Core\Database\Export\Plugins\GzipFileOutput;
use PiecesPHP\Core\Database\Export\Enums\DataStyle;
use PiecesPHP\Core\Database\Export\Enums\TableStyle;

// 1. Obtener la conexión a DB actual
// getDatabase() devuelve null si no hay conexión
$db = (new BaseModel())->getDatabase();
if ($db === null) {
    throw new \Exception('No hay conexión a la base de datos.');
}
$dbName = $db->getDatabaseName();

// 2. Crear instancia del exportador
$exporter = new Exporter($db, $dbName);

// 3. Elegir formato y medio de salida (SQL + GZIP)
$exporter->setFormatPlugin(new SqlFormat());
$outputPlugin = new GzipFileOutput();
$exporter->setOutputPlugin($outputPlugin);

// 4. Ejecutar la exportación
$exporter->export([
    'filename' => basepath('dumps/respaldo-hoy.sql.gz'),
    'include_data' => true,
    'include_views' => true,
    'routines' => true,
    'remove_definer' => true, // Para facilitar la importación en otros hosts
    'table_style' => TableStyle::DROP_CREATE,
    'data_style' => DataStyle::INSERT,
    'single_transaction' => true,
    'triggers' => true,
]);

// 5. Validar resultado
if (file_exists($outputPlugin->getFilename())) {
    echo "¡Respaldo Exitoso!";
} else {
    print_r($exporter->getErrors());
}
```

---

## 🎚️ Todas las opciones de `export()`

`export()` mezcla lo que le pasas con estos valores por omisión (`Exporter::export()`), así que solo hay que declarar
lo que se quiera cambiar. Una clave que no esté en la tabla se ignora.

| Opción | Tipo | Por omisión | Efecto |
| :--- | :--- | :--- | :--- |
| `tables` | `string[]` | `[]` (todas) | Tablas y vistas a exportar. Vacío: todas las de la base. |
| `exclude_tables` | `string[]` | `[]` | Se quitan de `tables`. |
| `table_style` | `TableStyle` o `string` | `'DROP+CREATE'` | `'DROP+CREATE'` antepone `DROP TABLE IF EXISTS`; `'CREATE'` solo crea. |
| `data_style` | `DataStyle` o `string` | `'INSERT'` | `'INSERT'`, `'REPLACE'` o `'TRUNCATE+INSERT'` (vacía la tabla antes de insertar). |
| `auto_increment` | `bool` | `true` | Con `false`, `SqlFormat` quita el ` AUTO_INCREMENT=<n>` del `CREATE TABLE` (el contador de la tabla; la columna sigue siendo autoincremental). No afecta a las vistas. |
| `triggers` | `bool` | `true` | Incluye los disparadores de cada tabla. |
| `routines` | `bool` | `true` | Incluye funciones y procedimientos, antes de las tablas. |
| `remove_definer` | `bool` | `true` | Quita `DEFINER=…` de tablas, vistas, rutinas y disparadores (y `SQL SECURITY DEFINER` de las vistas). |
| `drop_if_exists_on_functions` | `bool` | `false` | Con `true`, `SqlFormat` escribe `DROP FUNCTION IF EXISTS` o `DROP PROCEDURE IF EXISTS` antes de cada rutina. Solo tiene efecto con `routines` en `true`. |
| `create_if_not_exists` | `bool` | `true` | Con `true`, `SqlFormat` escribe `CREATE TABLE IF NOT EXISTS`. No toca las vistas. |
| `include_data` | `bool` | `true` | Con `false`, solo la estructura. |
| `include_views` | `bool` | `true` | Con `false`, las vistas se descartan de la lista. |
| `single_transaction` | `bool` | `true` | Lee todo dentro de una transacción `REPEATABLE READ` (y `READ ONLY` si el servidor lo admite). |
| `filename` | `string` | según el plugin de salida | Lo leen los plugins de archivo: `FileOutput` y `GzipFileOutput` usan `export.sql` si falta; `ZipFileOutput` y `Bz2FileOutput`, `database.sql`. |
| `where` | `array<string,string>` | `[]` | Condición por tabla (ver más abajo). |
| `transformations` | `array<string,array<string,callable>>` | `[]` | Closure por tabla y columna (ver más abajo). |
| `hex_blob` | `bool` | `true` | Cómo salen los datos binarios; ver abajo. |

**Las tres que más se echan en falta al restaurar sobre una base que ya tiene cosas:**

- **`drop_if_exists_on_functions`.** Por omisión el volcado hace `CREATE FUNCTION`/`CREATE PROCEDURE` a secas: si la
  rutina ya existe en la base destino, el servidor rechaza la sentencia. Con `true` cada rutina va precedida de su
  `DROP … IF EXISTS`. Con `routines => false` no hay rutinas y la opción no hace nada.
- **`create_if_not_exists`.** Vale `true` aunque no lo escribas. Con `table_style => 'DROP+CREATE'` el volcado trae
  `DROP TABLE IF EXISTS` y luego `CREATE TABLE IF NOT EXISTS`; con `table_style => 'CREATE'` y la tabla ya presente, el
  `CREATE` no hace nada y los `INSERT` se aplican sobre lo que había. Ponlo en `false` si prefieres que un `CREATE TABLE`
  sobre una tabla existente falle en voz alta.
- **`auto_increment`.** Con `false` el volcado no arrastra el valor actual del contador, útil para un respaldo que
  viaja a otra base y no debe heredar la numeración.

**`hex_blob` actúa distinto según el formato:**

| Formato | Con `hex_blob => true` (por omisión) |
| :--- | :--- |
| `SqlFormat` | Las columnas de tipo `binary`, `varbinary` o `blob` (y derivados) salen como literal `0x<hex>`. |
| `XmlFormat` | Igual: se decide por el tipo de la columna. |
| `JsonFormat`, `CsvFormat`, `PhpFormat` | Todo valor de texto que **no sea UTF-8 válido** sale como `"0x<hex>"`. |

Con `false` esa conversión no se hace. En `SqlFormat` los binarios pasan entonces por `quote()` de PDO como cualquier
texto; en `JsonFormat`, `CsvFormat` y `PhpFormat` el valor se escribe tal cual, aunque no sea UTF-8 válido.

```php
// Un respaldo de estructura y datos pensado para restaurar encima de una base que ya existe
$exporter->export([
    'filename' => basepath('dumps/restaurable.sql'),
    'drop_if_exists_on_functions' => true, // DROP … IF EXISTS antes de cada función y procedimiento
    'create_if_not_exists' => true,        // CREATE TABLE IF NOT EXISTS
    'auto_increment' => false,             // sin el contador de cada tabla
    'hex_blob' => true,                    // binarios como 0x…
]);
```

---

## 🔒 Filtros Avanzados (Transformaciones y Exclusiones)

Una de las principales ventajas funcionales de la librería nativa es la capacidad de modificar los datos **durante la lectura**, antes de que se escriban en el dump (muy útil para pseudo-anonimizar u ocultar secretos empresariales al mandar respaldos de producción a desarrollo).

### Excluir tablas enteras o filtrar filas con `where`
Puedes ignorar tablas por completo con `exclude_tables`, o restringir las filas de una tabla con `where`.

**`where` no es global: se indexa por el nombre de la tabla** y el valor es la condición SQL cruda (sin la palabra `WHERE`). Una tabla que no aparece en el arreglo se exporta entera. Los formatos `SqlFormat`, `JsonFormat`, `CsvFormat`, `XmlFormat` y `PhpFormat` leen `$options['where'][$tabla]`. La condición se concatena tal cual en el `SELECT`: no le pases datos de una petición.

```php
$exporter->export([
    // ...
    'exclude_tables' => [
        'mi_tabla_de_registros',   // nombres de ejemplo: pon los de tus tablas
        'mi_tabla_de_cache',
    ],
    'where' => [
        // El nombre real de la tabla, no un comodín
        'mi_tabla' => "id > 100 AND estado = 'ACTIVO'",
    ],
]);
```

### Mutaciones en tiempo real (`transformations`)
Puedes enviar un closure para transformar el valor de una columna antes de serializarlo. Se indexa por tabla y luego por columna, y el closure recibe el valor y el nombre de la columna. Úsalo, por ejemplo, para ocultar datos sensibles al llevar un respaldo de producción a desarrollo.

Para anonimizar, **sustituye** el valor por uno fijo o derivado sin retorno posible. `BaseHashEncryption::encryptBidirectionalHash()` no sirve para esto: es **reversible** (con la misma llave, `BaseHashEncryption::decryptBidirectionalHash()` recupera el original), y `BaseHashEncryption::obfuscate()` tampoco.

```php
use PiecesPHP\UserSystem\ORM\UsersModel;

$exporter->export([
    // ...
    'transformations' => [
        // Apuntar a una columna específica de una tabla concreta
        UsersModel::TABLE => [
            'password' => function ($val, $column) {
                // Un hash de un solo sentido de un valor aleatorio descartado: nadie conoce la clave
                return password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            },
        ],
    ],
]);
```
