<?php

/**
 * autoload.php - Autocargadores
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */

/**
 * Autocargador de las clases en app/core
 * @param string $core Nombre de la clase
 * @ignore
 */
function loader_classes($class)
{
    $scheme = [
        [
            'namespaces' => 'PiecesPHP\\Core',
            'path' => __DIR__ . '/system-controllers',
        ],
        [
            'namespaces' => 'PiecesPHP\\Core',
            'path' => __DIR__ . '/system-controllers',
            'psr4' => true,
        ],
        [
            'namespaces' => 'PiecesPHP\\Core',
            'path' => __DIR__ . '/system-models',
        ],
        [
            'namespaces' => ['App\\Controllers', 'App\\Controller'],
            'path' => __DIR__ . "/../controller",
        ],
        [
            'namespaces' => ['App\\Model', 'App\\Models'],
            'path' => __DIR__ . "/../model",
        ],
        [
            'path' => __DIR__ . "/psr4",
            'psr4' => true,
        ],
    ];

    foreach ($scheme as $element) {
        $psr4 = isset($element['psr4']) ? $element['psr4'] : false;
        $namespaces = isset($element['namespaces']) ? $element['namespaces'] : [];
        $ignore_namespace = isset($element['ignore_namespace']) ? $element['ignore_namespace'] : false;
        $namespaces = is_array($namespaces) ? $namespaces : [$namespaces];

        foreach ($namespaces as $index => $namespace) {
            if (is_string($namespace)) {
                $namespaces[$index] = $namespace . "\\";
            }
        }

        $class_name = str_replace($namespaces, "", $class);
        $file_name = $class_name . ".php";

        if (!isset($element['path']) || !is_string($element['path'])) {
            break;
        }

        if ($psr4) {

            if (count($namespaces) > 0) {
                foreach ($namespaces as $index => $namespace) {

                    $namespace = str_replace('\\', '/', $namespace);
                    $path = $element['path'] . '/' . $namespace . $file_name;

                    if (file_exists($path)) {
                        require_once $path;
                        break;
                    }
                }
            } else {

                $class_path_parts = explode('\\', $class);
                $class_name_position = count($class_path_parts) - 1;
                $class_name = $class_path_parts[$class_name_position];
                $file_name = $class_name . ".php";

                unset($class_path_parts[$class_name_position]);

                $namespace = implode('\\', $class_path_parts) . '\\';
                $namespace = str_replace('\\', '/', $namespace);

                if ($ignore_namespace !== true) {

                    $path = $element['path'] . '/' . $namespace . $file_name;

                } else {

                    $path = $element['path'] . '/' . $namespace . $file_name;
                }

                if (file_exists($path)) {
                    require_once $path;
                    break;
                }
            }

        } else {
            $path = $element['path'] . '/' . $file_name;
            if (file_exists($path)) {
                require_once $path;
                break;
            }
        }

    }
}

spl_autoload_register("loader_classes");
