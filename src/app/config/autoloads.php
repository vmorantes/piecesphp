<?php

/**
 * @pcsphp-config ambos
 * Qué conviene editar aquí: las entradas de autocarga de tus propios espacios de nombres, debajo de la del framework.
 */

/**
 * Cargadores automáticos propios, complementarios a Composer; el archivo se llama como la clase, más .php.
 * Formato y ejemplos: source-docs/project/docs/piecesphp/content/configuration.md, «autoloads.php».
 */
//── Del framework: no lo edites ──
return [
    [
        'psr4' => true,
        'path' => app_basepath('classes'),
    ],
    //── Del clon ──
    //Tus espacios de nombres: ['namespaces' => 'Tu\\Espacio', 'psr4' => true, 'path' => __DIR__ . '/...'],
];
