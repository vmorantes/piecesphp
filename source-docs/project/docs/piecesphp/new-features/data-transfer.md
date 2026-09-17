# Importar y exportar datos (DataTransfer)

Un motor de importación y exportación agnóstico que vive en el núcleo (`PiecesPHP\Core\DataTransfer`) y un panel,
«Importar y exportar», que lo usa. Cada tipo de dato que se importa o exporta es una **definición**: una clase que dice
qué columnas tiene, quién puede usarla y cómo se guarda. El framework trae la de usuarios.

Decisión y alternativas: ADR 0022 de la documentación de agentes.

## Cómo se comporta una importación

- **Solo XLSX y CSV**, con el lector elegido por la extensión, nunca adivinado. Tamaño y número de filas máximos por
  definición (5 MB y 5.000 filas por defecto).
- **Cabeceras** por clave, etiqueta o alias, sin distinguir mayúsculas. Una columna del archivo que la definición no
  declara **se ignora**.
- **Celdas**: se recortan espacios; una celda vacía es «ausente». Las fechas de XLSX llegan en ISO (`2026-09-17`, con
  hora si la tienen); el resto, en crudo (`1234.5`), sea cual sea la configuración regional del libro.
- **Todo o nada**: se validan todas las filas y, si alguna tiene errores, **no se guarda ninguna**. El informe dice cada
  error con su fila.
- **Mensajes en texto**, sin HTML.
- **Entregables de un solo uso**: si la importación genera algo que solo se entrega una vez (por ejemplo, credenciales),
  el navegador lo descarga en el momento. No se guarda en disco, base, sesión ni logs.

## Usarlo desde el panel

El menú «Importar y exportar» lleva a una portada con los importadores y exportadores que tu tipo de usuario puede usar.
Cada importador tiene su formulario, con sus columnas, sus límites y una **plantilla** CSV para descargar.

## Importar usuarios

- Columnas: Usuario, Correo, Primer nombre y Primer apellido (obligatorias); Segundo nombre, Segundo apellido,
  Contraseña, Tipo y Organización (opcionales).
- **Tipos importables**: por defecto solo usuarios generales. La lista está en
  `UsersImportDefinition::IMPORTABLE_TYPES`; añade ahí los que quieras, nunca administradores. Además, solo se importa un
  tipo con **menos** prioridad que el de quien importa (`UsersModel::TYPES_USER_PRIORITY`): ni mayor ni igual.
- **Organización**: si la fila no la trae, la del usuario que importa; si no tiene, la global. Solo se asigna a los tipos
  que requieren organización (no a los de `UsersModel::TYPES_USER_DONT_REQUIRE_ORGANIZATION`).
- **Duplicados**: usuario o correo que ya existen, o repetidos dentro del archivo, son errores de fila.
- **Contraseñas**: si una fila no trae contraseña, se genera. Las generadas se entregan **una sola vez** en unas
  **fichas imprimibles** (nombre completo, usuario, contraseña y enlace de acceso), pensadas para sistemas que reparten credenciales, como
  escuelas. Si no se descargan en ese momento, se pierden: hay que generarlas otra vez.

## Exportar usuarios

El exportador de usuarios descarga un XLSX o un CSV con las **mismas columnas** que el importador (sin contraseña), así
que un archivo exportado se puede volver a importar. En CSV, las celdas que empiezan por `=`, `+`, `-`, `@`, tabulador o retorno de carro (salvo un
número suelto) llevan un `'` delante para que una hoja de cálculo no las ejecute como fórmulas; al importar, ese `'` se
quita.

## Importar desde la terminal

```bash
bin/cli data-transfer-import definition=users file=/ruta/usuarios.xlsx as-user=<id> credentials-out=/fuera/del/proyecto/credenciales.html
```

- `as-user`: el usuario con cuyos permisos se importa (debe poder usar esa definición).
- `credentials-out`: obligatorio si la importación puede generar credenciales. Tiene que estar **fuera del repositorio**
  y no existir; se escribe con permisos `0600`. En pantalla solo se muestra su ruta.
- Sale con 0 si se guardó y con 1 si no.

## Crear tu propio importador

```php
use PiecesPHP\Core\DataTransfer\Import\Column;
use PiecesPHP\Core\DataTransfer\Import\ImportArtifacts;
use PiecesPHP\Core\DataTransfer\Import\ImportDefinition;
use PiecesPHP\Core\DataTransfer\Import\ImportPersistException;

class ProductsImportDefinition extends ImportDefinition
{
    public function key(): string { return 'products'; }              // parte del nombre de la ruta (kebab-case)
    public function title(): string { return 'Productos'; }
    public function allowedUserTypes(): array { return [UsersModel::TYPE_USER_ROOT]; }

    public function columns(): array
    {
        return [
            new Column('code', 'Código', true, ['sku']),
            new Column('name', 'Nombre', true),
            new Column('price', 'Precio', false, [], fn(?string $v) => is_numeric($v) ? null : 'Precio: debe ser un número'),
        ];
    }

    public function persist(array $rows): ?ImportArtifacts
    {
        // Se llama solo si TODAS las filas son válidas. Guárdalas en una transacción:
        // si algo falla, deshaz y lanza ImportPersistException con un mensaje para el usuario.
        return null;
    }
}
```

Y regístralo en el `routes()` de tu módulo:

```php
DataImportExportUtilityRoutes::importer($groupAdministration, ProductsImportDefinition::class);
```

Si el módulo está apagado (`DATA_IMPORT_EXPORT_MODULE` en `false`), el registro no hace nada y no da error. Con el módulo
encendido, crea tres rutas con nombre propio (`data-transfer-import-products`, `-action` y `-template`), así que **el permiso es
el nombre de la ruta**, como en el resto del framework.

Opcional: `acceptedExtensions()`, `maxSizeMB()`, `maxRows()`, `validateAll(array $rows)` (errores entre filas, como
duplicados; devuelve `posición => [errores]`) y `mayProduceArtifacts()` (true si puede generar entregables; la terminal
exige entonces `credentials-out`).

## Crear tu propio exportador

Extiende `PiecesPHP\Core\DataTransfer\Export\ExportDefinition` (`key()`, `title()`, `allowedUserTypes()`, `columns()` con
`ExportColumn($key, $label)` y `rows()`, que devuelve un iterable; pagínalo si hay muchos registros) y regístralo con
`DataImportExportUtilityRoutes::exporter($grupo, TuClase::class)`. Se descarga en `<zona administrativa>/data-transfer/export/<key>/?format=xlsx|csv` (por defecto, `/admin/…`).
