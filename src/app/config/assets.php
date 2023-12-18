<?php

/**
 * Librerías front cargadas por defecto
 */

/**
 * Librerías externas
 */

$assets = [];

/**
 * Fomantic UI v2.9.2
 * https://fomantic-ui.com/
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
 * DataTables v1.13.5
 * https://datatables.net/
 * Con algunos plugins:
 * RowReorder v1.4.0
 * ColReorder v1.7.0
 * Responsive v2.5.0
 */
$assets['datatables']['css'] = [
    'statics/plugins/datatables/dataTables.semanticui.min.css',
];
$assets['datatables']['js'] = [
    'statics/plugins/datatables/jquery.dataTables.min.js',
    'statics/plugins/datatables/dataTables.semanticui.min.js',
];
$assets['datatables']['plugins'] = [
    'rowReorder' => [
        'css' => [
            'statics/plugins/datatables/rowReorder.semanticui.min.css',
        ],
        'js' => [
            'statics/plugins/datatables/dataTables.rowReorder.min.js',
        ],
    ],
    'colReorder' => [
        'css' => [
            'statics/plugins/datatables/colReorder.semanticui.min.css',
        ],
        'js' => [
            'statics/plugins/datatables/dataTables.colReorder.min.js',
        ],
    ],
    'responsive' => [
        'css' => [
            'statics/plugins/datatables/responsive.semanticui.min.css',
        ],
        'js' => [
            'statics/plugins/datatables/dataTables.responsive.min.js',
            'statics/plugins/datatables/responsive.semanticui.min.js',
        ],
    ],
];

/**
 * NProgress
 * https://github.com/rstacruz/nprogress
 */
$assets['nprogress']['css'] = [
    'statics/plugins/nprogress/nprogress.css',
];
$assets['nprogress']['js'] = [
    'statics/plugins/nprogress/nprogress.js',
];
$assets['nprogress']['plugins'] = [];

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
 * JQueryMask
 * https://igorescobar.github.io/jQuery-Mask-Plugin/docs.html
 */
$assets['jquerymask']['js'] = [
    'statics/plugins/jquery-mask/jquery.mask.min.js',
];
$assets['jquerymask']['plugins'] = [];

/**
 * QuillJS
 * https://quilljs.com/
 */
$assets['quilljs']['js'] = [
    'statics/plugins/quilljs/quill.min.js',
];
$assets['quilljs']['css'] = [
    'statics/plugins/quilljs/quill.snow.css',
];
/**
 * @link project://src/statics/core/own-plugins/QuillAdapterComponent.js
 */
$assets['quilljs']['plugins'] = [
    'imageResize' => [
        'js' => [
            'statics/plugins/quilljs/plugins/image-resize.min.js',
        ],
    ],
    'videoResize' => [
        'js' => [
            'statics/plugins/quilljs/plugins/video-resize.min.js',
        ],
    ],
    'adapter' => [
        'js' => [
            'statics/plugins/tidy-html5/tidy.js',
            'statics/core/own-plugins/QuillAdapterComponent.js',
        ],
    ],
];

/**
 * CKEditor5
 * https://ckeditor.com/ckeditor-5/
 */
$assets['ckeditor']['js'] = [
    'statics/plugins/ckeditor/ckeditor.js',
];
$assets['ckeditor']['css'] = [];
/**
 * @link project://src/statics/core/own-plugins/RichEditorAdapterComponent.js
 * @link project://src/statics/core/own-plugins/sass/rich-editor-adapter.scss
 */
$assets['ckeditor']['plugins'] = [
    'ckfinder' => [
        'css' => [],
        'js' => [
            'statics/plugins/ckeditor/ckfinder/ckfinder.js',
        ],
    ],
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
$assets['defaultRichEditor']['plugins'] = $assets[$defaultRichEditorName]['plugins'];

/**
 * iziToast v1.4
 * http://izitoast.marcelodolce.com
 */
$assets['izitoast']['js'] = [
    'statics/plugins/izitoast/iziToast.min.js',
];
$assets['izitoast']['css'] = [
    'statics/plugins/izitoast/iziToast.min.css',
];
$assets['izitoast']['plugins'] = [];

/**
 * Spectrum Colorpicker v1.8.0
 * https://github.com/bgrins/spectrum
 */
$assets['spectrum']['js'] = [
    'statics/plugins/spectrum/spectrum.js',
];
$assets['spectrum']['css'] = [
    'statics/plugins/spectrum/spectrum.css',
];
$assets['spectrum']['plugins'] = [];

/**
 * Dialog PCS
 * Es una modal con comportamiento de ventana (arrastrable)
 * @link project://src/statics/core/own-plugins/DialogPCS.js
 */
$assets['dialgo_pcs']['js'] = [
    'statics/core/own-plugins/DialogPCS.js',
];
$assets['dialgo_pcs']['css'] = [];
$assets['dialgo_pcs']['plugins'] = [];

/**
 * Simple Upload Placeholder
 * Manejador de comportamiento genérico de un formulario de subida de archivos con vista previa
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
 * fancyBox v3.5.7
 * https://fancyapps.com/fancybox/3/
 */
$assets['fancybox3']['js'] = [
    'statics/plugins/fancybox/jquery.fancybox.min.js',
];
$assets['fancybox3']['css'] = [
    'statics/plugins/fancybox/jquery.fancybox.min.css',
];
$assets['fancybox3']['plugins'] = [];

/**
 * ElFinder
 * https://github.com/Studio-42/elFinder
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
 * JQuery UI
 * Sin accordion
 * https://github.com/Studio-42/elFinder
 */
$assets['jqueryui']['js'] = [
    'statics/plugins/jquery-ui/jquery-ui.min.js',
];
$assets['jqueryui']['css'] = [
    'statics/plugins/jquery-ui/jquery-ui.min.css',
];
$assets['jqueryui']['plugins'] = [];

/**
 * GoogleCaptchaV3Adapter
 * https://developers.google.com/recaptcha/docs/v3
 * @link project://src/statics/core/own-plugins/GoogleCaptchaV3Adapter.js
 */
$assets['google_captcha_v3_adapter']['css'] = [
];
$assets['google_captcha_v3_adapter']['js'] = [
    'statics/core/own-plugins/GoogleCaptchaV3Adapter.js',
];
$assets['google_captcha_v3_adapter']['plugins'] = [];

/**
 * MapBox
 * https://docs.mapbox.com/
 */
$assets['mapbox']['css'] = [
    'statics/plugins/mapbox/v2.6.0/mapbox-gl.css',
    'statics/plugins/mapbox/geocoder/v2.3.0/mapbox-gl-geocoder.css',
];
$assets['mapbox']['js'] = [
    'statics/plugins/mapbox/v2.6.0/mapbox-gl.js',
    'statics/plugins/mapbox/geocoder/v2.3.0/mapbox-gl-geocoder.min.js',
];
/**
 * @link project://src/statics/core/own-plugins/MapBoxAdapter.js
 */
$assets['mapbox']['plugins'] = [
    'mapBoxAdapter' => [
        'css' => [],
        'js' => [
            'statics/core/own-plugins/MapBoxAdapter.js',
        ],
    ],
];

/**
 * OpenLayers
 * https://openlayers.org/
 */
$assets['openlayers']['css'] = [
    'statics/plugins/open-layers/6.14.1/ol.css',
    'statics/plugins/open-layers/ol-ext/3.2.26/ol-ext.min.css',
];
$assets['openlayers']['js'] = [
    'statics/plugins/open-layers/6.14.1/ol.js',
    'statics/plugins/open-layers/ol-ext/3.2.26/ol-ext.min.js',
];
/**
 * @link project://src/statics/core/own-plugins/MapBoxAdapter.js
 */
$assets['openlayers']['plugins'] = [
    'openLayersAdapter' => [
        'css' => [],
        'js' => [
            'statics/core/own-plugins/OpenLayersAdapter/olImports.js',
            'statics/core/own-plugins/OpenLayersAdapter/GeoJSONVectorLayer.js',
            'statics/core/own-plugins/OpenLayersAdapter/WMSTileLayer.js',
            'statics/core/own-plugins/OpenLayersAdapter/MapManager.js',
            'statics/core/own-plugins/OpenLayersAdapter/OpenLayersAdapter.js',
        ],
    ],
];

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
 * LocationsAdapter
 * Adaptador para el módulo integrado de ubicación
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
 * Utilidades de la aplicación como:
 * Manejo de string, de fechas, etc...
 * Configuraciones de los plugins anteriores tales como:
 * Traducción de mensajes, configuración de valores por defecto, etc...
 */
$assets['app_libraries']['css'] = [
    'statics/core/css/helpers.css',
];
$assets['app_libraries']['js'] = [
    'statics/core/js/configurations.min.js',
];
$assets['app_libraries']['plugins'] = [
    'adminStyle' => [
        'css' => [
            'statics/core/css/ui-pcs.css',
        ],
    ],
    'formJsonSchema' => [
        'js' => [
            'statics/core/own-plugins/FormJsonSchema.js',
        ],
    ],
];

//Utilidades para el front, depende de app_libraries
$assets['app_front_libraries']['css'] = [
];
$assets['app_front_libraries']['js'] = [
    'statics/js/CustomNamespace.js',
];
$assets['app_front_libraries']['plugins'] = [
];

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
set_config('lock_assets', false);
