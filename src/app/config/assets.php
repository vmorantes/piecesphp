<?php

/**
 * @pcsphp-config ambos
 * Qué conviene editar aquí: las librerías propias de tu proyecto, en `app_front_libraries` (sección del clon). Las del framework —Fomantic, DataTables y demás— las actualiza él.
 */
use PiecesPHP\Core\Config;

/**
 * Librerías front cargadas por defecto
 */

/**
 * Librerías externas
 */

$assets = [];

/**
 * Fomantic UI v2.9.4 (https://fomantic-ui.com/), el de antes del tema. EN DESUSO: el framework ya no lo carga; solo
 * import_semantic(), que sale con esta entrada y su carpeta en la 11.0.0.
 */
$assets['semantic']['css'] = [
    'statics/plugins/semantic/semantic.min.css',
];
$assets['semantic']['js'] = [
    'statics/plugins/semantic/semantic.min.js',
];
$assets['semantic']['plugins'] = [
];

/**
 * El tema del panel (P245 b; ADR 0069): un fork de Fomantic UI 2.9.4 con el tema piecesphp, copiado por bin/traer-tema
 * (su ORIGEN.json dice de dónde salió). Lo cargan el panel, el acceso y la zona pública, con import_fomantic().
 */
$assets['fomantic-piecesphp']['css'] = [
    'statics/plugins/fomantic-piecesphp/semantic.min.css',
    //Los íconos SVG del tema (`<i class="panel svg icon">`), después de semantic.min.css como pide su README. Sus url()
    //son relativas a la hoja.
    'statics/plugins/fomantic-piecesphp/themes/piecesphp/assets/svg-icons/svg-icons.css',
    //Los íconos SVG propios del proyecto, tras los del tema: statics/svg-icons/ (la hoja de diseño, «Íconos SVG»).
    'statics/svg-icons/svg-icons.css',
];
$assets['fomantic-piecesphp']['js'] = [
    'statics/plugins/fomantic-piecesphp/semantic.min.js',
];
$assets['fomantic-piecesphp']['plugins'] = [
];

/**
 * DataTables v3.1.4 (https://datatables.net/), con su integración de Fomantic (datatables.net-se 3.1.4) y Responsive v4.1.1
 * con la suya. Licencia MIT. Copiados de https://cdn.datatables.net/ con la versión exacta en la dirección.
 */
//Sin hojas: las tablas las dibuja el tema (su componente «datatables», en semantic.min.css).
$assets['datatables']['css'] = [];
$assets['datatables']['js'] = [
    'statics/plugins/datatables/dataTables.min.js',
    'statics/plugins/datatables/dataTables.semanticui.min.js',
    //Buttons 4.0.0: «Exportar listado actual» (solo CSV, sin JSZip).
    'statics/plugins/datatables/dataTables.buttons.min.js',
    'statics/plugins/datatables/buttons.semanticui.min.js',
];
$assets['datatables']['plugins'] = [
    //Solo si un listado lo pide: import_datatables(['responsive']).
    'responsive' => [
        'js' => [
            'statics/plugins/datatables/dataTables.responsive.min.js',
            'statics/plugins/datatables/responsive.semanticui.min.js',
        ],
    ],
];

/**
 * CropperJS v1.6.1
 * https://github.com/fengyuanchen/cropperjs
 */
$assets['cropper']['css'] = [
    'statics/plugins/cropper/cropper.min.css',
];
$assets['cropper']['js'] = [
    'statics/plugins/cropper/cropper.min.js',
];

/**
 * @link project://src/statics/core/own-plugins/CropperAdapterComponent.js
 * @link project://src/statics/core/own-plugins/sass/cropper-adapter.scss
 */
$assets['cropper']['plugins'] = [
    'cropperAdapter' => [
        'css' => [
            'statics/core/own-plugins/css/cropper-adapter.css',
        ],
        'js' => [
            'statics/core/own-plugins/CropperAdapterComponent.js',
        ],
    ],
    'simpleCropperAdapter' => [
        'css' => [
            'statics/core/own-plugins/css/simple-cropper-adapter.css',
        ],
        'js' => [
            'statics/core/own-plugins/SimpleCropperAdapter.js',
        ],
    ],
    'contentImageEditor' => [
        'js' => [
            'statics/core/own-plugins/ContentImageEditor.js',
        ],
    ],
];

/**
 * ApexCharts v3.50.0
 * https://apexcharts.com/
 */
$assets['apexcharts']['css'] = [
    'statics/plugins/apexcharts/apexcharts.css',
];
$assets['apexcharts']['js'] = [
    'statics/plugins/apexcharts/apexcharts.min.js',
];

/**
 * Apache ECharts v5.5.1
 * https://echarts.apache.org/
 */
$assets['echarts']['js'] = [
    'statics/plugins/echarts/echarts.min.js',
];

/**
 * SweetAlert2
 * https://sweetalert2.github.io/
 */
$assets['sweetalert2']['js'] = [
    'statics/plugins/sweetalert2/sweetalert2.js',
];
$assets['sweetalert2']['plugins'] = [];

/**
 * CKEditor5 27.1.0 (https://ckeditor.com/ckeditor-5/).
 */
//No hay 'es' porque este CKEditor trae el español DENTRO de `ckeditor.js` —el bundle abre con
//`t["es"]` y su diccionario—. Estos son los idiomas ADICIONALES. Ver T147.
$ckeditorLangs = [
    'en' => 'statics/plugins/ckeditor/translations/en.js',
    'fr' => 'statics/plugins/ckeditor/translations/fr.js',
    'de' => 'statics/plugins/ckeditor/translations/de.js',
    'it' => 'statics/plugins/ckeditor/translations/it.js',
    'pt' => 'statics/plugins/ckeditor/translations/pt.js',
];
$assets['ckeditor']['js'] = [
    'statics/plugins/ckeditor/ckeditor.js',
    array_key_exists(Config::get_lang(), $ckeditorLangs) ? $ckeditorLangs[Config::get_lang()] : '',
];
$assets['ckeditor']['css'] = [];
$assets['ckeditor']['asModules'] = [];
/**
 * @link project://src/statics/core/own-plugins/RichEditorAdapterComponent.js
 * @link project://src/statics/core/own-plugins/sass/rich-editor-adapter.scss
 */
$assets['ckeditor']['plugins'] = [
    'adapter' => [
        'css' => [
            'statics/core/own-plugins/css/rich-editor-adapter.css',
        ],
        'js' => [
            'statics/core/own-plugins/RichEditorAdapterComponent.js',
        ],
    ],
];

/**
 * Editor por defecto
 */
$defaultRichEditorName = 'ckeditor';
$assets['defaultRichEditor']['js'] = $assets[$defaultRichEditorName]['js'];
$assets['defaultRichEditor']['css'] = $assets[$defaultRichEditorName]['css'];
$assets['defaultRichEditor']['asModules'] = array_key_exists('asModules', $assets[$defaultRichEditorName]) ? $assets[$defaultRichEditorName]['asModules'] : [];
$assets['defaultRichEditor']['plugins'] = $assets[$defaultRichEditorName]['plugins'];

//Simple Upload Placeholder: el comportamiento de un formulario de subida de archivos con vista previa.
/**
 * @link project://src/statics/core/own-plugins/SimpleUploadPlaceholder.js
 * @link project://src/statics/core/own-plugins/sass/simple-upload-placeholder.scss
 */
$assets['simple_upload_placeholder']['js'] = [
    'statics/core/own-plugins/SimpleUploadPlaceholder.js',
];
$assets['simple_upload_placeholder']['css'] = [
    'statics/core/own-plugins/css/simple-upload-placeholder.css',
];
$assets['simple_upload_placeholder']['plugins'] = [];

/**
 * ElFinder (https://github.com/Studio-42/elFinder).
 * @link project://src/statics/plugins/elfinder/js/elfinder.full.js
 */
$assets['elfinder']['js'] = [
    'statics/plugins/jquery-ui/jquery-ui.min.js',
    'statics/plugins/elfinder/js/elfinder.full.js',
];
$assets['elfinder']['css'] = [
    'statics/plugins/jquery-ui/jquery-ui.min.css',
    'statics/plugins/elfinder/css/elfinder.full.css',
    'statics/plugins/elfinder/css/theme.css',
];
$assets['elfinder']['plugins'] = [];

/**
 * jQuery UI, una compilación sin el widget accordion.
 */
$assets['jqueryui']['js'] = [
    'statics/plugins/jquery-ui/jquery-ui.min.js',
];
$assets['jqueryui']['css'] = [
    'statics/plugins/jquery-ui/jquery-ui.min.css',
];
$assets['jqueryui']['plugins'] = [];

/**
 * GoogleCaptchaV3Adapter (https://developers.google.com/recaptcha/docs/v3).
 * @link project://src/statics/core/own-plugins/GoogleCaptchaV3Adapter.js
 */
$assets['google_captcha_v3_adapter']['css'] = [
];
$assets['google_captcha_v3_adapter']['js'] = [
    'statics/core/own-plugins/GoogleCaptchaV3Adapter.js',
];
$assets['google_captcha_v3_adapter']['plugins'] = [];

/**
 * MapBox v3.19.0
 * https://docs.mapbox.com/
 */
$assets['mapbox']['css'] = [
    'statics/plugins/mapbox/v3.19.0/mapbox-gl.css',
    'statics/plugins/mapbox/geocoder/v2.3.0/mapbox-gl-geocoder.css',
];
$assets['mapbox']['js'] = [
    'statics/plugins/mapbox/v3.19.0/mapbox-gl.js',
    'statics/plugins/mapbox/geocoder/v2.3.0/mapbox-gl-geocoder.min.js',
];
/**
 * @link project://src/statics/core/own-plugins/MapBoxAdapter.js
 */
$assets['mapbox']['plugins'] = [
    'mapBoxAdapter' => [
        'css' => [
            'statics/core/own-plugins/css/mapbox-adapter.css',
        ],
        'js' => [
            'statics/core/own-plugins/MapBoxAdapter.js',
            'statics/core/own-plugins/MapBoxAdapterCustomControls.js',
        ],
    ],
];

/**
 * QRCodeJS
 * https://github.com/davidshimjs/qrcodejs
 */
$assets['qrcodejs']['css'] = [];
$assets['qrcodejs']['js'] = [
    'statics/plugins/qrcode/qrcode.min.js',
];
$assets['qrcodejs']['plugins'] = [];

/**
 * IndexedDBAdapter
 * @link project://src/statics/core/own-plugins/IndexedDBAdapter.js
 */
$assets['indexeDB_adapter']['css'] = [
];
$assets['indexeDB_adapter']['js'] = [
    'statics/core/own-plugins/IndexedDBAdapter.js',
];
$assets['indexeDB_adapter']['plugins'] = [];

/**
 * LocationsAdapter: el adaptador del módulo integrado de ubicaciones.
 * @link project://src/statics/core/own-plugins/LocationsAdapter.js
 */
$assets['locations']['css'] = [];
$assets['locations']['js'] = [
    'statics/core/own-plugins/LocationsAdapter.js',
];
$assets['locations']['plugins'] = [
    //Un comportamiento general predeterminado, útil para replicar el comportamiento
    //que tiene el módulo de ubicaciones en Puntos en otras partes de la aplicación
    'autoInit' => [
        'css' => [],
        'js' => [
            'statics/features/locations/js/locations-config.js',
        ],
    ],
];

/**
 * JQuery v3.7.0
 * https://jquery.com/
 */
$assets['jquery']['js'] = [
    'statics/plugins/jquery/jquery.min.js',
];
$assets['jquery']['plugins'] = [];

/**
 * Utilidades de la aplicación (cadenas, fechas…) y la configuración de los plugins anteriores (traducciones, valores por
 * defecto). Formato del catálogo: source-docs/project/docs/piecesphp/content/configuration.md, «assets.php».
 */
$uiPcsStyle = 'statics/core/css/ui-pcs.css';
$assets['app_libraries']['css'] = [
    'statics/core/css/helpers.css',
];
$assets['app_libraries']['js'] = [
    'statics/core/js/configurations.min.js',
];
$assets['app_libraries']['plugins'] = [
    'adminStyle' => [
        'css' => [
            $uiPcsStyle,
            'statics/core/css/admin_app_base.css',
        ],
    ],
    //La base común: el panel la trae en adminStyle; las páginas sueltas (acceso) la importan sola.
    'baseStyle' => [
        'css' => [
            $uiPcsStyle,
        ],
    ],
    'formJsonSchema' => [
        'js' => [
            'statics/core/own-plugins/FormJsonSchema.js',
        ],
    ],
    'attachmentPlaceholder' => [
        'js' => [
            'statics/core/own-plugins/AttachmentPlaceholder.js',
        ],
    ],
];

//Utilidades para el front, depende de app_libraries
//── Del clon ──
$assets['app_front_libraries']['css'] = [
];
$assets['app_front_libraries']['js'] = [
    'statics/js/CustomNamespace.js',
];
$assets['app_front_libraries']['plugins'] = [
];

//── Del framework: no lo edites ──
set_config('global_assets', [
    'js' => [],
    'css' => [],
    'font' => [],
]);
set_config('custom_assets', [
    'js' => [],
    'css' => [],
    'font' => [],
]);
set_config('default_assets', $assets);
set_config('global_requireds_assets', [
    'css' => [],
    'js' => [],
    'font' => [],
]);
set_config('imported_assets', []);
set_config('as_modules_assets', []);
set_config('lock_assets', false);
