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

Para anonimizar, **sustituye** el valor por uno fijo o derivado sin retorno posible. `BaseHashEncryption::encrypt()` no sirve para esto: es **reversible** (existe `BaseHashEncryption::decrypt()`, y con la misma llave se recupera el original).

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
