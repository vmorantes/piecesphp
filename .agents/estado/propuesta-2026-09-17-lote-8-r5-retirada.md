# Plan: retirada del importador viejo (lote 8, R5)

Arquitecto, 2026-09-17. **Regla de los diez:** borra o mueve declaraciones en más de diez archivos, así que se enseña al
PO antes de commitear. Censo con canario en `#246` (R0); lo nuevo que lo sustituye, en `#250`-`#264` (R2-R4).

## Qué sustituye a qué

| Viejo | Nuevo, ya commiteado y probado |
| :-- | :-- |
| Motor `PiecesPHP\Core\Importer` (7 archivos) | `PiecesPHP\Core\DataTransfer` (R2, suite 31/31) |
| Módulo `Importers/` con rutas `importer-form`, `importer-action`, `importer-template` | Panel «Importar y exportar»: rutas `data-transfer-import-users`, `-action`, `-template` (R3a-R3b, suites 16/16 y 19/19) |
| `DataImportExportUtilityController` viejo (exógena por GET desde `ODS_Usuarios`, fichas con contraseñas en claro en disco, export con columnas de otro proyecto) | Exportador `data-transfer-export-users` e importación por terminal `bin/cli data-transfer-import` con credenciales 0600 fuera del proyecto (R4, suites 16/16 y 9/9) |
| Constante `IMPORTS_MODULE_ENABLED` | `DATA_IMPORT_EXPORT_MODULE` |

## Qué se borra (28 archivos)

- `src/app/core/psr4/PiecesPHP/Core/Importer/` entero (7).
- `src/app/classes/Importers/` entero (controlador, gestor de usuarios, vistas, estáticos).
- Del módulo `DataImportExportUtility/`: el controlador viejo, `HelperController`, `ExportHandlers/` y
  `GeneratedData/` (las fichas y datos generados por la exógena).
- Las dos suites que describen lo viejo: `UnitTest-ImportersLegacy.php` (caracterización) y
  `UnitTest-ImporterUsersGuards.php` (las guardas S1/S2 de `#132`, que el importador nuevo cubre por diseño: no acepta
  `id` ni `type` fuera de la lista, con sus pruebas en `users-import`).

## Qué se edita fuera (10 archivos)

- `src/app/config/constants.php`: fuera `IMPORTS_MODULE_ENABLED`.
- `src/app/config/routes.php`: fuera los `use` y la llamada a `ImporterController::routes()`.
- `src/app/view/panel/layout/topbar.php`: el enlace «Importar usuarios» apunta al panel nuevo (`data-transfer-hub`), con
  `allowedRoute` en vez de `get_route` directo.
- `bin/phpstan.neon`: fuera `IMPORTS_MODULE_ENABLED` de las constantes dinámicas y el comentario del módulo viejo.
- `files/dev/integrity-signatures.json` y `files/dev/narrative-comments.json`: fuera las entradas de lo borrado.
- `src/app/logs/missing-lang-messages.json`: fuera los grupos de idioma muertos.
- `PHPStanResult.*` y la línea base: baja lo que muere con el código borrado, con `[REPARTO]` «murieron».
- `CHANGELOG.md` (arquitecto): las rupturas.

## Rupturas para quien clona

1. `PiecesPHP\Core\Importer\*` desaparece: un importador propio se reescribe como `ImportDefinition`.
2. Mueren el módulo `Importers`, `IMPORTS_MODULE_ENABLED` y las rutas `importer-*`.
3. La respuesta JSON de importación cambia de forma y sus mensajes van sin HTML.
4. Un archivo se importa entero o no se importa (antes, fila a fila).
5. Solo XLSX y CSV, con tamaño y filas máximos.
6. La importación exógena por GET (`ODS_Usuarios`) desaparece; la sustituye `bin/cli data-transfer-import`.
7. El export de usuarios tiene otras columnas (las del importador, sin las cuatro vacías de otro proyecto).
8. Por defecto solo se importan usuarios generales; ni `id` ni `type` de administrador desde el archivo.

## Cómo se verifica

`bin/cli verify-integrity`, `bin/phpstan` con su reparto, `bin/cli gates` (las suites nuevas siguen en verde y las dos
viejas ya no existen), `bin/cli route-inventory` sin rutas `importer-*`, y la portada del panel respondiendo 200.

## Reversión

Un solo commit de retirada: se revierte restaurando esos archivos desde el commit anterior. Lo nuevo no depende de lo
viejo.
