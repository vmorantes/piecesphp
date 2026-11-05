# 09 — Frontend, assets y build

No hay SPA ni bundler moderno: son **vistas PHP + jQuery + Fomantic/Semantic UI**,
con SASS y TypeScript compilados por Gulp, y un servidor de estáticos propio.

## Vistas

`BaseController::render($nombre, $data, $mode, $format)` hace `require` del archivo
`.php` dentro del directorio de vistas y `extract()` de `$data` +
`$this->global_variables`. `_render()` es igual pero recibe el nombre **con**
extensión.

- Directorio global: `src/app/view/` (fijado en `bootstrap.php`).
- Directorio por módulo: `$this->setInstanceViewDir(__DIR__ . '/../Views/')`.
- `setVariables([...])` define variables disponibles en todas las vistas del
  controlador (sobrescriben a las de `render()`).
- Si `cache_stamp_render_files` está activo, `render()` marca con la versión de su archivo cada URL de imagen de la
  salida, por atributo (`src` de `img` y `source`, `srcset`, `poster`, `url(…)` de un `style` en línea; ADR 0034 §3,
  `BaseController::addStaticVersions()`). Las URL de DENTRO de una hoja SCSS llevan la huella con `static-url()`
  (ADR 0067, `content/gulp.md`).
- `format = true` pasa la salida por `PiecesPHP\Core\HTML\FormatHtml`.

> Cualquier `Throwable` dentro de una vista se captura, se registra con
> `log_exception()` y se enruta a `global_custom_exception_handler()`.

## Sistema de assets

Registro de librerías en `src/app/config/assets.php`:

```php
$assets['nombre_lib']['css'] = ['statics/path/style.css'];
$assets['nombre_lib']['js']  = ['statics/path/script.js'];
$assets['nombre_lib']['plugins'] = ['plugin_name' => ['js' => ['...']]];
```

### Helpers (definidos en `src/app/core/AppHelpers.php`)

| Helper | Uso |
| :-- | :-- |
| `add_global_asset($url, 'css'\|'js')` | Añade un asset global |
| `add_global_assets([...], $tipo)` | Varios de golpe |
| `add_global_required_asset(...)` / `add_global_requireds_assets(...)` | Assets obligatorios (se cargan siempre) |
| `set_custom_assets([...], 'js')` | Assets propios de la acción actual |
| `add_as_module_asset(...)` | Cargar como `<script type="module">` |
| `remove_global_asset(...)`, `remove_imported_asset(...)` | Quitar |
| `clear_global_assets()`, `clear_assets_imports()` | Limpiar (los hace `BaseController::__construct` salvo `lock_assets`) |
| `load_js`, `load_css`, `load_font` | Emitir las etiquetas |
| `add_cache_stamp_to_url()`, `static_files_cache_stamp()` | Cache busting |

### Importadores de librerías

`import_jquery`, `import_jqueryui`, `import_semantic`, `import_datatables`,
`import_swal2`, `import_cropper`,
`import_echarts`, `import_apexcharts` (en desuso desde JDL; sale en la `11.0.0`),
`import_default_rich_editor`,
`import_simple_upload_placeholder`, `import_elfinder`,
`import_google_captcha_v3_adapter`, `import_locations`, `import_mapbox`,
`import_qrcodejs`, `import_indexeDB_adapter`,
`import_front_library`, `import_app_front_libraries`, `import_app_libraries`.

**Volver a importar una librería sustituye su lista de plugins** (`import_front_library()`, `src/app/core/AppHelpers.php`):
los plugins que no están en la lista nueva se quitan con sus archivos **exclusivos**; un archivo que pide otro plugin
todavía importado (de esa librería o de otra, según `imported_assets`) se queda. Hasta GG (2026-10-16) se quitaba
también, y por eso las páginas de acceso nunca tuvieron `ui-pcs.css`: el constructor del panel
(`AdminPanelController.php:590-593`) importa `adminStyle` y el acceso, al pedir otra lista, la perdía. La vigila
`core/import-front-library`. Dos defectos previos del mismo método, sin arreglar (`abierto.md` G7): con `$all = true`
el `foreach` pisa el parámetro `$name`, y la comprobación de «ya importado» busca el plugin como clave cuando se guarda
como valor.

Uso típico en un método de controlador:

```php
set_custom_assets([ NewsRoutes::staticRoute(self::BASE_JS_DIR . '/add-form.js') ], 'js');
import_default_rich_editor();
```

**El explorador del editor enriquecido** toma sus dos URL de `richEditorFileManagerURLs`, que inyecta
`FileManagerRoutes::routes()` con `routeName()`. Estuvo roto desde `fec3528c` (v6.3.0) porque el adaptador las
concatenaba con `admin_url`, que vale `''`. Lo vigila `core/rich-editor-file-manager-urls`.

### Configuración expuesta al front

```php
add_to_front_configurations('NombreClave', $valor);
get_front_configurations(); get_front_configuration('NombreClave');
```

Sirve para pasar endpoints y flags del backend al JavaScript
(ej. `NewsModuleMarkAsReadedEndpoint`).

### Atributos que el JS del núcleo reconoce sin que ninguna vista los ponga

Contrato vivo aunque hoy no tenga sujeto: una vista de un clon puede usarlos. Medido el 2026-09-16 con
`git grep -n -F '<atributo>' -- src/app src/statics/core`: ninguna vista PHP de `src/app` los emite.

| Atributo | Lo lee | Qué hace |
| :-- | :-- | :-- |
| `lang-group="<grupo>"` | `autoTranslateFromLangGroupHTML()`, `src/statics/core/js/configurations.js:1357` | Traduce el `innerHTML` del elemento con el grupo dado (`registerDynamicLocalizationMessages`). No usa el idioma por defecto; `autoTranslateFromLangGroupHTMLIgnoreLangs` excluye idiomas. **No se anida**: el atributo interior se borra y se informa error |
| `datatable-js` | `configDataTables()`, `configurations.js:499` | Aplica `DataTable(pcsphpGlobals.configDataTables)` a la tabla. Si DataTables no está cargado, no avisa |
| `container-steps` + `step="<n>"` y `data-to-step="<n>\|next\|previous"` | `GenericStepsViewHandler`, `src/statics/core/js/helpers-lib/GenericStepsViewHandler.js` | Pasos de un formulario; los selectores **no son automáticos**: los pasa quien construye el manejador (ver el `@example` del archivo) |
| `element-location-module-data` | `src/app/classes/MySpace/Statics/js/my-profile.js:161` y `my-organization-profile.js:243` | Si existe, vacía los filtros de países, estados y ciudades de `LocationsAdapter`: el selector de ubicación ofrece todos |

## `ServerStatics`

`PiecesPHP\Core\Statics\ServerStatics` sirve todos los estáticos a través de la ruta
`/statics/[{params:.*}]` (nombre de ruta `statics-files`). El directorio base es
`get_config('statics_path')` → `src/statics`.

Cada módulo registra su propio servidor de estáticos:

```php
// en <Modulo>Routes::staticResolver()
$server = new ServerStatics();
return $server->serve($request, $response, $args, __DIR__ . '/Statics');
```

> ⚠️ **Aquí hubo un método propio, `compileScssServe()`, que no compilaba SCSS** y nunca lo
> hizo, desde que alguien dejó `$enableSassCompilation = false;` escrito a fuego. **El bloque
> muerto se retiró el 2026-08-26**, con su `//TODO: Implementar la compilación de scss` dentro:
> no era una funcionalidad a medias, era una DUPLICACIÓN de lo que gulp ya hace. Sin el
> bloque, el método quedaba idéntico a `serve()`, así que **se borró entero**: los módulos
> llaman a `serve()`. Ver T112 y T115.
>
> `scssphp/scssphp` no tenía consumidor posible y salió de `src/composer.json` en la 9.2.0 (P227).
>
> El CSS de los módulos lo genera **Gulp** (`gulp sass-modules`), con Dart Sass,
> en tiempo de desarrollo. No hay compilación en servidor.

**Una ruta con `..` o con un byte nulo da 404 antes de nada** (`ServerStatics::pathHasTraversal()`, al principio de
`serve()` y de `getSymbolicLink()`; JH, P210). Hace falta ahí porque **el camino delegado no pasa por
`isValidResourcePath()`**: enlaza en `statics/server-delegated/` y redirige, y esa lista blanca de caracteres solo la
aplica `verifyFile()`. No la lleves al camino delegado: rechazaría nombres con espacio o `@` que hoy se sirven. Y
cualquier comprobación nueva ahí, de texto y sin disco (P210: «lo que mantenga el framework súper veloz»). Probar
`serve()` delegado escribe en `server-delegated/`, que es de `www-data`: las pruebas usan `getSymbolicLink()` sobre un
enlace que ya existe (`core/server-statics` §6).

Las subidas protegidas se deciden por el NOMBRE en disco (`foto.jpg.protected`; lote 4b). Si
se pide `foto.jpg` y no existe, `PiecesPHP\Core\Statics\ServerStatics` busca el privado, aplica
la política de su carpeta y lo sirve con caché privada.
- Las políticas se registran en `config/extensions/protected-files.php` con
  `ProtectFileMiddleware::protectWithSession($dir)` o `protect($dir, $validador)`.
- Solo se comprime el texto; lo demás va en streaming y admite `Range`.
- Guía: `source-docs/project/docs/piecesphp/new-features/protected-files.md`.

## Variables CSS

`PiecesPHP\CSSVariables::instance('global')` recoge la paleta desde `config.php`
(en el servicio DI `foundHandler`) y la expone como CSS:

los tokens del contrato del tema que devuelve `SettingsController::themeTokens()` (JCP y JCQ, H106): siempre `--accent`,
`--accent-2` y `--accent-3`, y `--data-2..5`, `--font-body`, `--font-heading`, `--font-size-base` y `--r-xs/sm/md/lg` solo si
difieren de fábrica. Una fuente que no sea Inter añade su hoja con `themeFontStylesheet()`. Las 20 variables propias de antes
salieron; las hojas del panel y del acceso leen los tokens del tema, y la zona pública y las páginas de error llevan sus
valores fijos en su hoja.

Se sirven por módulo en `<modulo>/statics/globals-vars.css`.
**Para cambiar la marca visual, se editan los valores en `config.php` o en las
configuraciones de BD — no los SCSS.**

## Build con Gulp

Desde `src/`:

```bash
gulp <tarea>
```

| Tarea | Qué hace |
| :-- | :-- |
| `sass:init` / `sass` / `sass:watch` | SASS de `src/statics/sass` |
| `sass-vendor:init` / `sass-vendor:watch` | SASS del núcleo/área administrativa (plugins propios, general, users) |
| `sass-modules:init` / `sass-modules` / `sass-modules:watch` | SASS de los módulos (`app/classes/*/Statics/sass`) |
| `sass-all` / `sass-all:watch` | Todo lo anterior + limpieza de caché |
| `ts-vendor` / `ts-vendor:watch` | TypeScript del core |
| `js-vendor` / `js-vendor:watch` | JavaScript del core (concat + uglify) |
| `init-project` / `init-project:watch` | ⭐ Todas las tareas — lo normal en desarrollo |

Node recomendado: **22.12.x** (vía fnm). `npm install` sin `sudo`.
`npm run deps` ejecuta `bin/node/copyDependencies.sh`.

Dependencias front instaladas por npm: `cropperjs`, `mapbox-gl` y
`@mapbox/mapbox-gl-geocoder`. Los alias de `mapbox-gl` van fijados a **versión exacta** y
la carpeta desplegada lleva esa misma versión —hoy `mapbox-v3.19.0`—: con un acento, el
nombre de la carpeta sería el suelo de la restricción y no su contenido. Ver T90.

## Convenciones de archivos estáticos por módulo

```
Statics/
├── sass/<modulo>.scss     ← se edita esto
├── css/<modulo>.css       ← generado (ignorado por git)
└── js/
    ├── <Modulo>Adapter.js       Adaptador/clase JS del módulo
    └── <entidad>/{list,add-form,edit-form,delete-config,utils}.js
```

Un JS por vista, con el mismo nombre que la acción del controlador. Se cargan con
`set_custom_assets([Routes::staticRoute('js/<entidad>/<archivo>.js')], 'js')`.

### Consolidar SCSS sin cambiar el aspecto (bloques CU y DH)

- **La puerta**: `bin/huella-css --comparar` IDÉNTICA, y además la **hora** de los `.css`: `writeIfChanged` solo
  reescribe si el contenido cambia, así que un `.css` que no se reescribe es igual byte a byte. Si uno se reescribe,
  guarda su copia y su `sha1sum` ANTES y compara con `cmp`.
- **Las cifras**: `bin/censo-scss` antes y después; los renglones bajan y las **reglas compiladas** no se mueven.
- **Dos hojas casi iguales** van a un parcial con `@mixin` (lo distinto, por parámetro o interpolación) y la hoja que
  añade un bloque lo pasa como `@content`. **El contenido HEREDA el selector del sitio donde está `@content` dentro del
  mixin** (medido con dart-sass 1.105.1 en `#1169`): el bloque se pasa SIN su selector padre, y tiene que llevar las
  llaves equilibradas. Un `diff` alinea por llaves y engaña sobre dónde empieza y acaba un bloque: léelo compilado.
- **Los parciales de un módulo van en `Statics/sass/includes/`**, como los del núcleo
  (`src/statics/core/sass/includes/`). **`Statics/sass/imports/` de `MySpace` es una ruta que la campaña movió**: volver a
  crearla pone 160 «restos» en `verify-integrity`.
- **Un parcial que usan varios módulos va a `src/statics/core/sass/includes/`**, declarado «global» con su motivo en
  `files/dev/global-files.json` (comprobación 49). Ejemplo: `_profile-view.scss` y `_profile-form.scss`, de los perfiles
  de `MySpace`, `Organizations` y `Directory`: **desde la `10.3.0` el framework ya no los carga** (las vistas usan los
  componentes del tema, JEE y JEH) y quedan `@en-desuso 11.0.0` para los clones que los incluyan.
- **Podar reglas muertas** (bloque DI): la lista de lo que se espera que desaparezca va a `bin/huella-css --comparar
  --esperado <archivo>` con **un selector por línea, partidos** —una regla con varios selectores solo cuenta como
  prevista si están TODOS (`bin/huella-css:283`)—. Y antes de quitar una regla, sus clases se buscan también como
  **subcadena** en `.php`, `.js` y `.ts`: «muerta» en el censo es que no aparece como palabra, y una clase concatenada
  sí llega a la página.
- **`bin/huella-css` y `bin/censo-scss` se paran si una hoja raíz VERSIONADA no está en disco**: toman las raíces de lo
  versionado. Para quitar una hoja entera, se mide la poda con la hoja puesta, su ausencia con `gulp sass-all` y el sha1
  de los demás `.css`, y la huella completa después del `git rm`.

## Estilo de archivos (`.editorconfig`)

- UTF-8, `insert_final_newline = true`.
- **`end_of_line = crlf`** por defecto (y en `.js`, `.yaml`, `.neon`);
  `lf` en `.sh` y en los ejecutables de `bin/`.
- Indentación: 4 espacios en general; **tabs** en `.js`, `.yaml`, `.neon`.
