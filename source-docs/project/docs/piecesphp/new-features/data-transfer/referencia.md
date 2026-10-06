# Referencia

Todas las clases públicas del motor. Espacios de nombres: `PiecesPHP\Core\DataTransfer\Import`,
`PiecesPHP\Core\DataTransfer\Export` y `PiecesPHP\Core\DataTransfer`. Los errores de programación (una opción mal
declarada) lanzan `\InvalidArgumentException`; los del usuario (un archivo o un filtro inválidos) llegan como informe o
como respuesta 400, nunca como excepción sin tratar.

## Común

**`InterfaceLevel`**: `NONE = 0`, `AUTO = 1`, `EXTENDED = 2`, `CUSTOM = 3`. `ImportDefinition::INTERFACE_*` y
`ExportDefinition::INTERFACE_*` son alias de estas constantes.

## Importación

**`ImportDefinition`** (abstracta)

| Método | Obligatorio | Por defecto |
| :-- | :-- | :-- |
| `key(): string` | sí | — |
| `title(): string` | sí | — |
| `allowedUserTypes(): int[]` | sí | — |
| `columns(): Column[]` | sí | — |
| `persist(ParsedRow[] $rows): ?ImportArtifacts` | sí | — |
| `validateAll(ParsedRow[] $rows): array<int,string[]>` | no | `[]` |
| `acceptedExtensions(): string[]` | no | `['xlsx']` |
| `maxSizeMB(): int` | no | 5 |
| `maxRows(): int` | no | 5.000 |
| `mayProduceArtifacts(): bool` | no | `false` |
| `interfaceLevel(): int` | no | `INTERFACE_AUTO` |
| `formPartial(): ?string` / `customView(): ?string` | nivel 2 / 3 | `null` |
| `description(): string` | no | `''` (la frase de la portada) |

**`Column`**: `new Column(string $key, string $label, bool $required = false, array $aliases = [], ?callable $validator = null)`;
`help(string)`, `example(string)`; lectura `key()`, `label()`, `required()`, `aliases()`, `helpText()`,
`exampleValue()`. El validador recibe `(?string $value, ParsedRow $row)` y devuelve el mensaje o `null`.

**`ParsedRow`**: `get(string $key): ?string`, `position(): int` (el número de fila del archivo).

**`ImportRunner`**: `run(ImportDefinition $d, RowSource $s, bool $dryRun = false): ImportReport`. Con `$dryRun` valida
todo y nunca llama a `persist()`.

**`ImportReport`**: el resultado, con `isDryRun()`; en JSON lleva `persisted`, `dryRun`, `headerErrors` y los errores por
fila. **`ImportArtifacts`**: entregables de un solo uso. **`ImportPersistException`**: lánzala desde `persist()` con un
mensaje para el usuario.

## Exportación

**`ExportDefinition`** (abstracta)

| Método | Obligatorio | Por defecto |
| :-- | :-- | :-- |
| `key(): string` | sí | — |
| `title(): string` | sí | — |
| `allowedUserTypes(): int[]` | sí | — |
| `columns(ExportContext $c): ExportColumn[]` | sí | — |
| `rows(ExportContext $c): iterable` | sí | — |
| `parameters(): ExportParameter[]` | no | `[]` |
| `fileName(ExportContext $c): string` | no | `<key>-<AAAAMMDD-HHMMSS>` |
| `sheets(ExportContext $c): ExportSheet[]` | no | `[mainSheet()]` |
| `style(ExportContext $c): ExportStyle` | no | `ExportStyle::report()` |
| `afterBuild(Spreadsheet $book, ExportContext $c): void` | no | nada |
| `afterExport(ExportContext $c, ExportResult $r): void` | no | nada |
| `importDefinition(): ?string` | no | `null` |
| `interfaceLevel(): int` | no | `INTERFACE_AUTO` |
| `formPartial(): ?string` / `customView(): ?string` | nivel 2 / 3 | `null` |
| `description(): string` | no | `''` (la frase de la portada) |

Finales: `mainSheet(ExportContext)`, `buildContext(array $query, ?object $user)`, `roundTripProblems(ExportContext)`.
Constantes: `PREVIEW_ROWS = 20`, `RESERVED_QUERY_KEYS = ['format', 'columns']`.

**`ExportParameter`**: `text`, `integer($key, $label, ?int $min, ?int $max)`, `boolean`, `choice($key, $label, array
$options)`, `multiChoice(…)`, `date`, `dateRange`; encadenables `required(bool = true)`, `defaultValue(mixed)`,
`help(string)`; lectura `key()`, `label()`, `type()`, `options()`, `min()`, `max()`, `isRequired()`, `getDefault()`,
`helpText()`, `queryKeys()`, `parse(array $query)`.

**`DateRange`**: `from(): ?\DateTimeImmutable`, `to(): ?\DateTimeImmutable`, `isEmpty(): bool`.

**`ExportContext`**: `get(string $key)` (lanza si no está declarado), `all()`, `user(): ?object`, `parameters()`,
`parameter(string $key)`, `selectedColumns(): ?string[]`.

**`ExportParameterException`**: `errors(): string[]` (los mensajes para el usuario).

**`ExportColumn`**: `new ExportColumn(string $key, string $label)`; tipos `asText()`, `asInteger()`, `asDecimal(int = 2)`,
`asMoney(string $currency, int = 2)`, `asPercent(int = 0)`, `asDate(string = 'dd/mm/yyyy')`,
`asDateTime(string = 'dd/mm/yyyy hh:mm')`, `asBoolean(?string $yes, ?string $no)`; además `format(string)`,
`width(?float)`, `align('left'|'center'|'right')`, `transform(callable)`, `formula(string)`. Decimales entre 0 y 10.

**`ExportSheet`**: `new ExportSheet(string $title, array $columns, iterable $rows)`; `heading()`, `subheading()`,
`appliedFilters(ExportContext)`, `generatedAt(bool = true)`, `image(string $path, string $cell = 'A1', ?int $heightPx)`,
`total(string $key, string $aggregate)`, `totalsLabel()`, `merge(string $range)`, `style(ExportStyle)`,
`dropColumns(string[])`. Agregados: `AGGREGATE_SUM`, `AGGREGATE_AVERAGE`, `AGGREGATE_COUNT`, `AGGREGATE_MIN`,
`AGGREGATE_MAX`.

**`ExportStyle`**: `report()`, `plain()`; `headerColors(string $font, string $fill)`, `borderColor(?string)`,
`freezeHeader(bool)`, `autoFilter(bool)`, `wrapText(bool)`, `maxAutoWidth(?float)`. Colores `RRGGBB`.

**`ExportResult`**: `format()`, `fileName()`, `bytes()`, `rowCount()`.

**`ExportPresetStore`** (interfaz): `all(int $userID, string $key)`, `save(int, string, string $name, array $query)`,
`delete(int, string, string $name)`. El panel usa `UserMetaPresetStore` (en `users.meta`).

**`SpreadsheetExportWriter`**: `toXlsx(…): int`, `toCsv(…): int` (filas de datos de la hoja principal) y
`firstRows(…)` (la vista previa).

## Rutas

| Nombre | Método | Nivel |
| :-- | :-- | :-- |
| `data-transfer-hub` | GET | — (la portada) |
| `data-transfer-toggle` | POST | — (solo root: `kind=import\|export`, `key`, `enabled=yes\|no`) |
| `data-transfer-import-<key>` | GET | 1-3 |
| `data-transfer-import-<key>-action` | POST | 1-3 |
| `data-transfer-import-<key>-template` | GET | 1-3 |
| `data-transfer-export-<key>` | GET | 0-3 |
| `data-transfer-export-<key>-form` | GET | 1-3 |
| `data-transfer-export-<key>-preview` | GET | 2-3 |
| `data-transfer-export-<key>-presets-save` | POST | 2-3 |
| `data-transfer-export-<key>-presets-delete` | POST | 2-3 |

Todas bajo `<zona administrativa>/data-transfer/…`, con sesión y con los roles de `allowedUserTypes()` (salvo
`data-transfer-toggle`, solo root). Con una entidad apagada, todas las suyas responden 403.

**Configuración:** `data_transfer_disabled` (constante `DataTransferController::DISABLED_CONFIG`, guardada con `PiecesPHP\Settings\ORM\SettingsModel`) = `{"import": [<keys>], "export": [<keys>]}`; ausente o
mal formada, todo encendido. `DataTransferController::isDisabled($kind, $key)` es la única comprobación.

## Terminal

`bin/cli data-transfer-import definition=<key> file=<ruta> as-user=<id> [credentials-out=<ruta>] [dry-run=yes]`.
Detalle en [Usar el panel](panel.md#desde-la-terminal).
