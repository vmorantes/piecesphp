<?php

/**
 * TestRun.php
 */

namespace Terminal;

use PiecesPHP\Core\CustomErrorsHandlers\GenericHandler;

/**
 * TestRun - Las peticiones de las suites llevan el id de su corrida (ADR 0063 §1).
 *
 * Un error que escribe el servidor mientras atiende a una suite lleva así la marca de esa corrida y se poda al
 * terminar. Sustituyen a curl_init(), curl_setopt() y curl_setopt_array() en las suites: la cabecera se añade también
 * cuando la suite fija después su propio CURLOPT_HTTPHEADER, que de otro modo la borraría.
 *
 * **Lo que NO cubre**: una petición que no pase por aquí (file_get_contents con contexto, bin/navegador) sale sin
 * marca, y lo que escriba se queda en el registro como si fuera real.
 *
 * @package     Terminal
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class TestRun
{

    /**
     * @param string|null $url
     * @return \CurlHandle
     * @throws \RuntimeException Si cURL no da un manejador: la suite no puede pedir nada
     */
    public static function curlInit(?string $url = null): \CurlHandle
    {
        $handle = curl_init($url);
        if ($handle === false) {
            throw new \RuntimeException('curl_init() no dio un manejador');
        }
        self::curlSetopt($handle, CURLOPT_HTTPHEADER, []);
        return $handle;
    }

    /**
     * @param \CurlHandle $handle
     * @param int $option
     * @param mixed $value
     * @return bool
     */
    public static function curlSetopt(\CurlHandle $handle, int $option, mixed $value): bool
    {
        if ($option === CURLOPT_HTTPHEADER && is_array($value)) {
            $value = self::withHeader($value);
        }
        return curl_setopt($handle, $option, $value);
    }

    /**
     * @param \CurlHandle $handle
     * @param array<int,mixed> $options
     * @return bool
     */
    public static function curlSetoptArray(\CurlHandle $handle, array $options): bool
    {
        if (array_key_exists(CURLOPT_HTTPHEADER, $options) && is_array($options[CURLOPT_HTTPHEADER])) {
            $options[CURLOPT_HTTPHEADER] = self::withHeader($options[CURLOPT_HTTPHEADER]);
        }
        return curl_setopt_array($handle, $options);
    }

    /**
     * Las cabeceras con la del id de la corrida, si hay una.
     *
     * @param array<mixed> $headers
     * @return array<mixed>
     */
    public static function withHeader(array $headers): array
    {
        $run = getenv(GenericHandler::TEST_RUN_ENV);
        if (!is_string($run) || preg_match(GenericHandler::TEST_RUN_PATTERN, $run) !== 1) {
            return $headers;
        }
        $headers[] = GenericHandler::TEST_RUN_HEADER . ': ' . $run;
        return $headers;
    }
}
