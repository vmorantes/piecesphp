<?php
/**
 * custom-autoloads-config.php
 */

$autoloads = include $directories['custom_autoloads'];

if (is_array($autoloads)) {

    /** @ignore  */
    function custom_autoloads_pcs($class)
    {
        global $autoloads;

        foreach ($autoloads as $element) {
            $psr4 = isset($element['psr4']) ? $element['psr4'] : true;
            $namespaces = isset($element['namespaces']) ? $element['namespaces'] : [];
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

                    $path = $element['path'] . '/' . $namespace . $file_name;

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

    spl_autoload_register("custom_autoloads_pcs");
}
