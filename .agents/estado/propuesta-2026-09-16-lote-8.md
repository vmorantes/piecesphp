# Propuesta de diseño del lote 8 (E5): `DataImportExportUtility` absorbe `Importers`

- **Fecha:** 2026-09-16. **Estado:** DECIDIDA el 2026-09-17: ADR 0022 manda sobre esta propuesta donde difieran (P-b fichas con entrega única, P-c todo o nada por archivo, P-d lista de tipos importables). No es un ADR: el
  ADR se escribe con sus respuestas, con el primer número libre (el 0018 lo tomó la recuperación de contraseña).
- **Origen:** subagente de arquitectura, en solo lectura. El arquitecto verificó en el código S1 y S2 antes
  de aceptarla (§2), y los corrige en `#132`, fuera del lote, porque son una trampa activa.
- **Lo que queda SIN VERIFICAR** se dice en cada punto.

## 0. Contradicciones del registro

- El 18 (`18-siguientes-ventanas.md:937`) decía fusionar `DataImportExportUtility` en `Importers`. El PO lo
  invirtió el 2026-08-29 (`pendientes.md:311`): vale la del 29.
- El 18 (`:934`) dice que el motor `PiecesPHP\Core\Importer` se queda. Esta propuesta conserva la idea (el
  motor vive en el núcleo) pero lo **reescribe con otro espacio de nombres** y retira el viejo. Va al PO
  (P-a).

## 1. Qué hay hoy

- **`Importers` (activo, `IMPORTS_MODULE_ENABLED = true`).**
  - Tres rutas con `{type}` dinámico: `importer-form`, `importer-action` e `importer-template`, para root y
    administrador general. Un solo permiso para todos los importadores; `overwritePermissions()` esquiva
    «el nombre de la ruta es el permiso».
  - Lee `$_FILES['archivo']` sin `UploadedFileAdapter`: no valida tipo, tamaño ni `is_uploaded_file`, y
    `IOFactory::load` adivina el lector entre todos (HTML, SLK, XML…). Una celda nula se lee como la cadena
    `'NULL'`.
  - Motor `PiecesPHP\Core\Importer` (7 archivos): `Field` guarda el valor dentro de la columna (estado entre
    filas), `Response` concatena HTML, los contadores se reinician al leerlos. Único consumidor: `Importers`.
- **`DataImportExportUtility` (apagado, `ENABLE = false`).**
  - Importación «exógena» por un GET que escribe, desde la tabla `ODS_Usuarios`: contraseñas con `rand()`,
    y **escritas en claro** en `GeneratedData/*.json` dentro de `src/`, sin ignorar en git.
  - Vista de fichas que lee **una ruta de archivo en base64 desde la query** y pinta contraseñas sin escapar.
  - Exportador de usuarios en XLSX (`TYPE_STRING2`, que evita fórmulas), con 4 columnas vacías de otro
    proyecto. Su carpeta `lang/` no existe.
- `LoginAttemptsController` tiene un tercer exportador XLSX propio: no entra en el lote.

## 2. Defectos

| # | Qué | Verificado | Destino |
| --- | --- | --- | --- |
| **S1** | **Inyección SQL:** `UsersModel::getByID()` hace `where("id = '" . $id . "'")`, y el importador le pasa la celda `id` del archivo | **Sí, por el arquitecto** | **`#132`** |
| **S2** | **Escalada de privilegios:** el importador acepta la columna `type` del archivo sin validar; un administrador general sube `type = 0` y crea un root | **Sí, por el arquitecto** | **`#132`** |
| S3 | Subida sin validar, y lector adivinado | Por lectura | Lote 8 |
| S4 | XSS: los mensajes llevan valores del archivo y el JS los inserta como HTML | Por lectura | Lote 8 |
| S5 | Lectura de archivos arbitrarios por la query (módulo apagado) | Por lectura | Lote 8 |
| S6 | Contraseñas en claro en disco y en pantalla (módulo apagado) | Por lectura | Lote 8 |
| S7 | GET que escribe | Por lectura | Lote 8 |
| S8 | Contraseña generada con `rand()` | Por lectura | Lote 8 |
| S9 | Permiso por importador fuera del nombre de la ruta | Por lectura | Lote 8 |

`getByID()` tiene otros llamadores: `UserDataPackage::setUserID(int)` y `updateAttempts()` reciben enteros y
no son explotables (medido).

## 3. Diseño propuesto

- **Contrato y motor en el núcleo**, espacio de nombres nuevo `PiecesPHP\Core\DataTransfer`, para que un clon
  con `extends Importer` falle alto en vez de cambiar de semántica en silencio:
  - `Import/ImportDefinition` (abstracta): `key()`, `title()`, `allowedRoles()`, `columns()`,
    `acceptedTypes()`, `maxSizeMB()`, `maxRows()`, `chunkSize()`, `validateChunk()` y `persist()`;
  - `Import/Column` inmutable, `ParsedRow`, `RowStatus` (enum), `RowResult`, `ImportReport`,
    `ImportRowException` e `ImportRunner`;
  - `Source/RowSource` (interfaz), `SpreadsheetRowSource` (XLSX/CSV, lector explícito, `setReadDataOnly`,
    por trozos) y `ArrayRowSource` (pruebas);
  - `Export/ExportDefinition`, `ExportColumn`, `ExportFile` y `SpreadsheetExportWriter` (XLSX con
    `TYPE_STRING2`; CSV escapando `=`, `+`, `-` y `@`).
- **Semántica:** cabeceras por nombre o etiqueta; **una columna que no está en `columns()` se ignora** (cierra
  S1 y S2 por diseño); celda vacía = ausente; filas independientes; mensajes sin HTML; una excepción no
  prevista da un mensaje genérico y va al log; la subida pasa por `UploadedFileAdapter` antes de abrir nada.
- **El módulo `DataImportExportUtility`** es el único panel: hub, formulario, plantilla, informe y
  exportación. Un módulo propio registra lo suyo con
  `DataImportExportUtilityRoutes::importer($grupo, MiImportDefinition::class)` y `::exporter(...)`, que crean
  **una ruta con nombre propio por importador**: el permiso vuelve a ser el nombre de la ruta.
- **Ejemplos que funcionan:** `UsersImportDefinition` (sin `id` ni `type`, tipo general fijo, duplicados por
  lote y dentro del archivo), `UsersExportDefinition` (paginado, cabeceras iguales a las etiquetas del
  importador) y la importación exógena como acción CLI.
- **Se retiran** `Importers/`, `PiecesPHP\Core\Importer\*` e `IMPORTS_MODULE_ENABLED`, tras caracterizarlos.

## 4. Plan en rondas

- **R0 · Censo** de todas las formas (rutas, constantes, espacios de nombres), con canario. Dirá si pasa de
  diez archivos (casi seguro).
- **R1 · Caracterización** del comportamiento viejo en una suite propia, commiteada antes de mover: cada caso
  marcado «se conserva» o «cambia a propósito → ruptura».
- **R2 · Motor del núcleo**, sin consumidores, con su suite. **Punto serio: núcleo.**
- **R3 · Módulo nuevo en paralelo**, con el ejemplo de usuarios y guardas de rechazo; paridad con R1.
- **R4 · Exportador y exógena por CLI.**
- **R5 · Retirada** de lo viejo. **Regla de los diez.**
- **R6 · Documentación**, ADR 0018 y receta.

## 5. Rupturas previstas

1. `PiecesPHP\Core\Importer\*` desaparece; un importador propio se reescribe como `ImportDefinition`.
2. Mueren el módulo `Importers`, `IMPORTS_MODULE_ENABLED` y las rutas `importer-*`.
3. `overwritePermissions()` desaparece: los roles salen de `allowedRoles()`.
4. La respuesta JSON cambia de forma, y sus mensajes van sin HTML.
5. El importador de usuarios ignora `id` y `type`, y una celda vacía ya no se guarda como «NULL».
6. Solo XLSX y CSV, con tamaño y filas máximos.
7. La importación exógena deja de ser un GET.
8. El export de usuarios pierde las 4 columnas vacías.

## 6. Decisiones para el PO

- **P-a · El motor en el núcleo con espacio de nombres nuevo, retirando `Core\Importer`.** Es núcleo.
  *Predeterminado:* sí. Alternativa: todo dentro del módulo, lo que haría depender de un módulo a todo módulo
  con importador.
- **P-b · Las fichas imprimibles con contraseñas generadas.** *Predeterminado:* se retiran; el CLI entrega las
  credenciales una vez, en un archivo no versionado fuera de `src/`.
- **P-c · Atomicidad.** *Predeterminado:* filas independientes, como hoy. Alternativa: todo o nada por archivo.
- **P-d · ¿El administrador general sigue importando?** *Predeterminado:* sí, pero solo usuarios generales.

## 7. SIN VERIFICAR

- Si `finfo` clasifica un CSV como `text/plain`, que `FileValidator` no acepta para CSV.
- Si Apache sirve `src/app/classes/DataImportExportUtility/GeneratedData/*.json`.
- Si el formulario de usuarios deja a un administrador general crear un root (el importador sí lo deja hoy).
- Qué efectos del alta por formulario (perfil, aprobaciones, eventos) se salta la importación.
- El coste de `password_hash` con 5.000 filas.
