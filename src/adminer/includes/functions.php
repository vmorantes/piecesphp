<?php

/**
 * Carga las variables de entorno desde un archivo .env
 *
 * @param string $path Ruta del archivo .env
 * @return array|false Array con las variables de entorno o false si no se pudo cargar el archivo
 */
function loadEnv($path)
{
    if (!file_exists($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];

    foreach ($lines as $line) {
        // Ignorar comentarios que empiecen con #
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Separar por el primer signo "="
        list($name, $value) = explode('=', $line, 2);

        $name = trim($name);
        $value = trim($value);

        if (mb_strlen($name) == 0) {
            continue;
        }
        if (mb_strlen($value) == 0) {
            $value = null;
        }

        // Eliminar comillas si existen
        $value = trim($value, '"\'');

        // Validar booleanos
        $lowerValue = strtolower($value);
        if ($lowerValue === 'true') {
            $value = true;
        } elseif ($lowerValue === 'false') {
            $value = false;
        } elseif ($lowerValue === 'null' || $value === '') {
            $value = null;
        }

        // Inyectar en el entorno
        if (!array_key_exists($name, $_ENV)) {
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $env[$name] = $value;
        } else {
            $env[$name] = $_ENV[$name];
            putenv($name . "=" . $_ENV[$name]);
        }
    }
    return $env;
}