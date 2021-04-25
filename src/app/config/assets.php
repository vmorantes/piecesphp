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
    'statics/plugins/jquery-ui/jquery-ui.min.js',
    'statics/plugins/elfinder/js/elfinder.full.js',
    'statics/plugins/quilljs/quill.min.js',
];
$assets['quilljs']['css'] = [
    'statics/plugins/quilljs/quill.snow.css',
    'statics/plugins/jquery-ui/jquery-ui.min.css',
    'statics/plugins/elfinder/css/elfinder.full.css',
    'statics/plugins/elfinder/css/theme.css',
];
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
 */
$assets['dialgo_pcs']['js'] = [
    'statics/core/own-plugins/DialogPCS.js',
];
$assets['dialgo_pcs']['css'] = [];
$assets['dialgo_pcs']['plugins'] = [];

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
 * JQuery
 * https://jquery.com/
 */
$assets['jquery']['js'] = [
    'statics/plugins/jquery/jquery-3.5.1.min.js',
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

set_config('global_assets', [
    'js' => [],
    'css' => [],
]);
set_config('custom_assets', [
    'js' => [],
    'css' => [],
]);
set_config('default_assets', $assets);
set_config('global_requireds_assets', [
    'css' => [],
    'js' => [],
]);
set_config('imported_assets', []);
set_config('lock_assets', false);
