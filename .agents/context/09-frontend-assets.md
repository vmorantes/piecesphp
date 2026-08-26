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
- Si `cache_stamp_render_files` está activo, `render()` parsea el HTML con
  `DOMDocument` y añade `?cacheStamp=...` a todos los `<img src>`.
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
`import_nprogress`, `import_swal2`, `import_izitoast`, `import_cropper`,
`import_jquerymask`, `import_quilljs`, `import_apexcharts`,
`import_default_rich_editor`, `import_spectrum`, `import_dialog_pcs`,
`import_simple_upload_placeholder`, `import_fancybox3`, `import_elfinder`,
`import_google_captcha_v3_adapter`, `import_locations`, `import_mapbox`,
`import_openlayers`, `import_qrcodejs`, `import_indexeDB_adapter`,
`import_front_library`, `import_app_front_libraries`, `import_app_libraries`.

Uso típico en un método de controlador:

```php
set_custom_assets([ NewsRoutes::staticRoute(self::BASE_JS_DIR . '/add-form.js') ], 'js');
import_default_rich_editor();
```

### Configuración expuesta al front

```php
add_to_front_configurations('NombreClave', $valor);
get_front_configurations(); get_front_configuration('NombreClave');
```

Sirve para pasar endpoints y flags del backend al JavaScript
(ej. `NewsModuleMarkAsReadedEndpoint`).

## `ServerStatics`

`PiecesPHP\Core\ServerStatics` sirve todos los estáticos a través de la ruta
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
> Consecuencia: **`scssphp/scssphp` es peso muerto.** Es requisito directo en
> `src/composer.json`, pero su única mención en todo el código es una línea de
> créditos en `app/view/panel/pages/about-framework.php:106`. Ningún PHP lo
> instancia.
>
> El CSS de los módulos lo genera **Gulp** (`gulp sass-modules`), con Dart Sass,
> en tiempo de desarrollo. No hay compilación en servidor.

`ServerStatics::protectFileMiddleware` aplica las restricciones registradas con
`ProtectFileMiddleware::protect($dir, fn(Request $r, string $path) => bool)` desde
`config/final-configurations-includes/protected-files.php`.

## Variables CSS

`PiecesPHP\CSSVariables::instance('global')` recoge la paleta desde `config.php`
(en el servicio DI `foundHandler`) y la expone como CSS:

`--meta-theme-color`, `--main-brand-color` (+ variantes de opacidad
`-opacity`, `-0-5`, `-0-8`), `--second-brand-color`, `--bg-tools-buttons`,
`--font-color-one`, `--font-color-two`, `--menu-color-background` (+ `-opacity`),
`--menu-color-mark`, `--menu-color-font`, `--body-gradient`, y variables del loader.

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
| `sass-vendor:init` / `sass-vendor:watch` | SASS del núcleo/área administrativa (plugins propios, general, users, users2, avatars) |
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

## Estilo de archivos (`.editorconfig`)

- UTF-8, `insert_final_newline = true`.
- **`end_of_line = crlf`** por defecto (y en `.js`, `.yaml`, `.neon`);
  `lf` en `.sh` y en los ejecutables de `bin/`.
- Indentación: 4 espacios en general; **tabs** en `.js`, `.yaml`, `.neon`.
