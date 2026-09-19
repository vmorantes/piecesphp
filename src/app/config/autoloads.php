<?php

/**
 * Autocarga de clases
 *
 * Array que contiene las clases que se desean cargar
 *
 * [
 *     'namespaces' => string|array, Espacio de nombre o espacios de nombre que se buscarán en la ruta.
 *     'path' => string La ruta absoluta
 * ],
 *
 * Ejemplo:
 *
 * Dada la siguiente clase ubicada en el directorio actual:
 *
 * namespace es Espacio\De\Nombre\Ejemplo
 * class Clase{}
 *
 * La forma de registrarla sería:
 *
 * [
 *     'namespaces' => "Espacio\\De\\Nombre\\Ejemplo",
 *     'psr4' => true,
 *     'path' => __DIR__
 * ],
 *
 * NOTA IMPORTANTATE: Se asume que el nombre del archivo es tal cual como el nombre de la clase y su extensión es .php, es decir,
 * para el ejemplo anterior; el archivo debe llamarse Clase.php
 */
return [
    [
        'psr4' => true,
        'path' => app_basepath('classes'),
    ],
];
