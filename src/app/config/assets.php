<?php

/**
 * Librerías front cargadas por defecto
 */

/**
 * Librerías externas
 */

$assets = [];

/**
 * Semantic UI
 * https://semantic-ui.com/
 * Aquí está incluido Semantic Calendar
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
 * DataTables
 * https://datatables.net/
 * Con algunos plugins
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
            'statics/plugins/datatables/rowReorder.dataTables.min.css',
        ],
        'js' => [
            'statics/plugins/datatables/dataTables.rowReorder.min.js',
        ],
    ],
    'colReorder' => [
        'css' => [
            'statics/plugins/datatables/colReorder.dataTables.min.css',
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
            'statics/plugins/datatables/dataTables.colReorder.min.js',
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
 * CropperJS
 * https://github.com/fengyuanchen/cropperjs
 */
$assets['cropper']['css'] = [
    'statics/plugins/cropper/cropper.min.css',
];
$assets['cropper']['js'] = [
    'statics/plugins/cropper/cropper.min.js',
];
$assets['cropper']['plugins'] = [
    'cropperAdapter' => [
        'css' => [
            'statics/core/own-plugins/css/cropper-adapter.css',
        ],
        'js' => [
            'statics/core/own-plugins/CropperAdapterComponent.js',
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
$assets['quilljs']['plugins'] = [
    'imageResize' => [
        'js' => [
            'statics/plugins/quilljs/plugins/image-resize.min.js',
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
 */
$assets['dialgo_pcs']['js'] = [
    'statics/core/own-plugins/DialogPCS.js',
];
$assets['dialgo_pcs']['css'] = [];
$assets['dialgo_pcs']['plugins'] = [];

/**
 * JQuery
 * https://igorescobar.github.io/jQuery-Mask-Plugin/docs.html
 */
$assets['jquery']['js'] = [
    'statics/plugins/jquery/jquery.3.3.1.min.js',
];
$assets['jquery']['plugins'] = [];

/**
 * Utilidades de la aplicación como:
 * Manejo de string, de fechas, etc...
 * Configuraciones de los plugins anteriores tales como:
 * Traducción de mensajes, configuración de valores por defecto, etc...
 */
$assets['app_libraries']['css'] = [];
$assets['app_libraries']['js'] = [
    'statics/core/js/util-pieces.js',
    'statics/core/js/helpers.js',
    'statics/core/js/configurations.js',
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

set_config('default_assets', $assets);
set_config('imported_assets', []);
