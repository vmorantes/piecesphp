# Crear un exportador o un informe

Un exportador es una clase que extiende `PiecesPHP\Core\DataTransfer\Export\ExportDefinition`. Puede ser un listado
sencillo o un **informe**: filtros, columnas con formato, varias hojas, títulos, totales, logo y una acción tras
exportar. **No tiene por qué poder reimportarse**: eso es opcional y se declara.

El código de esta página es el de `ExampleUsersReportExportDefinition`
(`src/app/classes/DataImportExportUtility/Examples/`), un informe de usuarios probado por la suite
`data-transfer-examples`.

## 1. Lo mínimo

```php
class PaymentsExportDefinition extends ExportDefinition
{
    public function key(): string { return 'payments'; }
    public function title(): string { return __('payments', 'Histórico de pagos'); }
    public function allowedUserTypes(): array { return [UsersModel::TYPE_USER_ROOT]; }
    public function columns(ExportContext $context): array { /* … paso 3 … */ }
    public function rows(ExportContext $context): iterable { /* … paso 4 … */ }
}
```

Con esto ya hay formulario, descarga en XLSX y CSV y estilo de informe. Todo lo demás es opcional; por ejemplo,
`description()` devuelve la frase que sale en la portada junto al título.

## 2. Filtros: `parameters()`

Declara los filtros; el motor los lee de la URL, los valida y te los entrega ya convertidos. **Un valor inválido nunca
llega a tu código**: la descarga responde 400 con la lista de errores y el formulario los enseña.

```php
--8<-- "src/app/classes/DataImportExportUtility/Examples/ExampleUsersReportExportDefinition.php:export-parameters"
```

| Constructor | En la URL | Lo que recibes |
| :-- | :-- | :-- |
| `ExportParameter::text($key, $label)` | `key=texto` | `string` recortado (máx. 500) o `null` |
| `ExportParameter::integer($key, $label, $min, $max)` | `key=42` | `int` o `null` |
| `ExportParameter::boolean($key, $label)` | `key=yes\|no` (o `1`/`0`, `true`/`false`) | `true`, `false` o `null` («todos») |
| `ExportParameter::choice($key, $label, [valor => etiqueta])` | `key=valor` | La clave elegida |
| `ExportParameter::multiChoice($key, $label, [valor => etiqueta])` | `key[]=a&key[]=b` o `key=a,b` | Lista de claves; `[]` si ninguna |
| `ExportParameter::date($key, $label)` | `key=2026-09-18` | `\DateTimeImmutable` a las 00:00:00 |
| `ExportParameter::dateRange($key, $label)` | `key_from=…&key_to=…` | `DateRange`: `from()` 00:00:00, `to()` 23:59:59, cada uno opcional; **al revés, se intercambian** |

Encadenables: `->required()`, `->defaultValue($valor)` y `->help('texto')`. `format` y `columns` son claves reservadas.

## 3. Columnas: `columns()`

```php
--8<-- "src/app/classes/DataImportExportUtility/Examples/ExampleUsersReportExportDefinition.php:export-columns"
```

Una columna es texto salvo que digas otra cosa:

| Método | XLSX | CSV |
| :-- | :-- | :-- |
| `asText()` (por defecto) | Texto; nunca se evalúa | El texto, neutralizado |
| `asInteger()` | Número `#,##0` | En crudo |
| `asDecimal($dec = 2)` | Número `#,##0.00` | Con punto decimal |
| `asMoney('COP', $dec = 2)` | Número `#,##0.00 "COP"`: **se puede sumar** | El número, sin moneda |
| `asPercent($dec = 0)` | `0.25` se ve como 25 % | `0.25` |
| `asDate($formato = 'dd/mm/yyyy')` | Fecha de Excel | `2026-09-18` |
| `asDateTime($formato = 'dd/mm/yyyy hh:mm')` | Fecha y hora de Excel | `2026-09-18 14:30:00` |
| `asBoolean($si = 'Sí', $no = 'No')` | La etiqueta | La etiqueta |
| `format('formato de Excel')` | Sustituye el formato del tipo | — |
| `width(18.5)` / `align('right')` | Ancho fijo / alineación | — |
| `transform(fn(array $fila, ExportContext $c) => …)` | Calcula el valor desde la fila | Igual |
| `formula('={a}*{b}')` | Fórmula por fila (`{clave}` = su celda) | Vacía |

**Un dato que no encaja con su tipo no tumba el informe:** `null` deja la celda vacía; un texto en una columna numérica
o de fecha (`'abc'`, `2026-02-30`) se escribe como texto, tal cual.

**Las fórmulas salen solo del código, nunca de un dato:** un valor que empiece por `=` sigue siendo texto.

## 4. Filas: `rows()`

Devuelve las filas como `clave => valor`. **Usa `yield` y pagina por id**: así la memoria no crece con el número de
filas. Consulta **siempre por marcadores**:

```php
--8<-- "src/app/classes/DataImportExportUtility/Examples/ExampleUsersReportExportDefinition.php:export-rows"
```

- `$context->get('clave')` da el filtro ya validado; **lanza si pides uno que no declaraste**, para que una errata no
  pase por «sin filtro».
- `$context->user()` es quien exporta (`null` en terminal o pruebas): úsalo para limitar los datos a su organización si
  el informe lo necesita.
- `IN (…)` no pasa por marcador en el ORM: por eso el ejemplo filtra los tipos con un igual por cada uno, en un grupo OR.
- Un `WhereSegment` sin criterios produce SQL inválido: comprueba `countCriteria()` antes de usarlo.

## 5. El libro: `sheets()`

Solo afecta al **XLSX**; el CSV es siempre la tabla de `columns()` y `rows()`.

```php
--8<-- "src/app/classes/DataImportExportUtility/Examples/ExampleUsersReportExportDefinition.php:export-sheets"
```

`mainSheet()` es la hoja de `columns()` y `rows()`, **ya con la elección de columnas del formulario**. A una hoja se le
encadena:

| Método | Qué añade |
| :-- | :-- |
| `heading('…')` / `subheading('…')` | Título y subtítulos encima de la tabla |
| `appliedFilters($context)` | «Filtros: Creado: del 01/01/2026 al 31/03/2026; Tipos: …», con las etiquetas |
| `generatedAt()` | «Generado el 18/09/2026 14:30» |
| `image($ruta, 'F1', $altoPx)` | PNG o JPG **del proyecto**, anclado en esa celda |
| `total('clave', ExportSheet::AGGREGATE_SUM)` | Fila de totales con fórmula: `SUM`, `AVERAGE`, `MIN`, `MAX` (numéricas) y `COUNT` |
| `totalsLabel('…')` / `merge('A1:D1')` / `style(…)` | Etiqueta de totales, celdas combinadas a mano, estilo propio |

Encima de la tabla van, **siempre en este orden y combinadas a lo ancho**: título, subtítulos, filtros y fecha; luego
una fila vacía, el encabezado, los datos y los totales. Los nombres de pestaña se limpian como pide Excel.

**Estilo:** `ExportStyle::report()` (por defecto: encabezado oscuro, bordes, encabezado fijo al desplazarse, autofiltro,
ajuste de texto, ancho automático con tope 40) o `ExportStyle::plain()`; se ajusta con `headerColors()`,
`borderColor()`, `freezeHeader()`, `autoFilter()`, `wrapText()` y `maxAutoWidth()`. Para una definición entera,
sobrescribe `style()`.

**Vía de escape:** `afterBuild(Spreadsheet $book, ExportContext $context)` recibe el libro de PhpSpreadsheet montado,
antes de guardarlo, para lo que el motor no cubra.

## 6. El nombre del archivo: `fileName()`

```php
--8<-- "src/app/classes/DataImportExportUtility/Examples/ExampleUsersReportExportDefinition.php:export-file-name"
```

Sin extensión. Puede llevar tildes y espacios: el motor quita lo que no vale en un nombre de archivo, lo recorta a 150
caracteres y lo envía bien codificado a cualquier navegador.

## 7. Después de exportar: `afterExport()`

```php
--8<-- "src/app/classes/DataImportExportUtility/Examples/ExampleUsersReportExportDefinition.php:export-after-export"
```

- Se llama **una vez, con el archivo ya generado entero y antes de enviarlo**. Si la generación falla, no se llama.
- **Si lanza, no se descarga nada**: el usuario ve «No se pudo completar la exportación. No se descargó nada.» y el
  error va al log de errores. Por eso la acción debe ser atómica.
- `ExportResult` trae `format()`, `fileName()`, `bytes()` y `rowCount()`.
- Para «marcar como exportado», guarda los ids en una propiedad mientras `rows()` los entrega y úsalos aquí.

## 8. Registrar

```php
--8<-- "src/app/classes/DataImportExportUtility/Examples/register-examples.php:register"
```

Rutas que crea, según el nivel (el nombre es el permiso):

| Ruta | Nivel | Qué hace |
| :-- | :-- | :-- |
| `data-transfer-export-<key>` | todos | La descarga, `?format=xlsx\|csv` y los filtros |
| `data-transfer-export-<key>-form` | 1, 2, 3 | El formulario |
| `data-transfer-export-<key>-preview` | 2, 3 | La vista previa (JSON) |
| `data-transfer-export-<key>-presets-save` y `-presets-delete` | 2, 3 | Filtros guardados |

## 9. Niveles de interfaz

| Nivel | Qué ve el usuario |
| :-- | :-- |
| **0 · `INTERFACE_NONE`** | Nada en el panel: solo la URL de descarga |
| **1 · `INTERFACE_AUTO`** (por defecto) | El formulario de filtros y formato |
| **2 · `INTERFACE_EXTENDED`** | Lo del 1, más vista previa, elegir y ordenar columnas, filtros guardados y el hueco de `formPartial()` |
| **3 · `INTERFACE_CUSTOM`** | Tu vista (`customView()`) en lugar del formulario, con las mismas rutas |

- **Vista previa:** las primeras 20 filas de la hoja principal con los filtros y columnas elegidos, sin descargar, sin
  `afterExport()` y sin consumir el resto de filas. Devuelve `{"columns": […], "rows": [[…]], "truncated": true}`.
- **Elegir columnas:** clave `columns` (`columns[]=a&columns[]=b`). El orden de la URL es el del archivo; una columna
  inexistente, o una fórmula sin las columnas que usa, da error; los totales de columnas quitadas se omiten.
- **Filtros guardados:** por usuario, en su columna `meta` bajo `dataTransferPresets`, sin tocar el resto. Nombre de 1 a
  60 caracteres, 20 como mucho por exportador, solo filtros válidos. Gana la última escritura.
- **Vistas propias:** un `.php` **del proyecto**; recibe `$definition`, `$parameters`, `$columns`, `$downloadURL`,
  `$previewURL`, `$langGroup`, `$title` y `$breadcrumbs`.

## 10. Reimportable, si quieres: `importDefinition()`

Un exportador **puede** declarar el importador que lee su archivo (el de usuarios lo hace). Entonces la suite
`data-transfer-roundtrip` exige que cada columna exportada exista en el importador, que se exporten sus obligatorias y
que ninguna exportada sea una fórmula, y el formulario enlaza al importador. Un informe devuelve `null`.

## 11. Memoria

- **CSV:** fila a fila; con `yield`, no crece con las filas.
- **XLSX:** PhpSpreadsheet monta el libro entero en memoria. Medido el 2026-09-18: +22 MiB de pico para 7 KB de
  archivo. Para exportaciones muy grandes, ofrece CSV.
- La descarga se envía por trozos desde un temporal, que se borra al terminar.

## La organización, en la exportación de usuarios

La columna de organización lleva el **código** de la organización (`ORG…`), no su identificador interno. Así el archivo
se puede editar a mano y volver a importar sin traducir nada.

Si una organización todavía no tuviera código —una instalación a medio actualizar—, en su lugar sale el identificador,
para no perder el dato: la importación acepta las dos formas.
