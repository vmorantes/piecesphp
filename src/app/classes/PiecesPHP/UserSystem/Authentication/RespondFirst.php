<?php

/**
 * RespondFirst.php
 */

namespace PiecesPHP\UserSystem\Authentication;

use PiecesPHP\Core\Routing\ResponseRoute as Response;

/**
 * RespondFirst - Responde primero y hace después lo que depende del usuario (P314 a).
 *
 * Existe porque el tiempo de respuesta no puede delatar si una cuenta existe: el correo con el código tarda, y si se
 * envía dentro de la petición, quien pide por una cuenta que existe espera más que quien pide por una que no.
 *
 * Con PHP-FPM el trabajo corre en el cierre de PHP (register_shutdown_function), después de que Slim haya emitido la
 * respuesta ENTERA con todos sus middlewares y de cerrar la conexión con fastcgi_finish_request(). Por eso:
 * - lo que corre después ya no puede cambiar la respuesta: la respuesta tiene que ser la misma pase lo que pase;
 * - su correo llega unos milisegundos DESPUÉS de la respuesta (una prueba que lo lea tiene que esperarlo);
 * - un exit() a mitad de la petición también lo dispara, porque el cierre de PHP corre igual.
 * Sin FPM (CLI, mod_php) corre antes de responder, como antes de esta clase.
 *
 * @package     PiecesPHP\UserSystem\Authentication
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class RespondFirst
{

    /**
     * Responde $body en JSON y deja $afterResponse para después de cerrar la conexión, si se puede.
     *
     * @param Response $response
     * @param mixed $body
     * @param \Closure $afterResponse
     * @return Response
     */
    public static function json(Response $response, mixed $body, \Closure $afterResponse): Response
    {
        if (self::canFinishEarly(function_exists('fastcgi_finish_request'), \PHP_SAPI)) {
            register_shutdown_function(static function () use ($afterResponse): void {
                fastcgi_finish_request();
                //RETORNO-IGNORADO: la conexión ya está cerrada; el fallo queda en el registro de errores.
                self::runSafely($afterResponse);
            });
        } else {
            $afterResponse();
        }
        return $response->withJson($body);
    }

    /**
     * ¿Se puede cerrar la conexión antes de hacer el trabajo? Pura, para probarla sin FPM.
     *
     * @param bool $hasFinishRequest Si existe fastcgi_finish_request()
     * @param string $sapi PHP_SAPI
     * @return bool
     */
    public static function canFinishEarly(bool $hasFinishRequest, string $sapi): bool
    {
        return $hasFinishRequest && $sapi !== 'cli';
    }

    /**
     * Corre el trabajo sin dejar escapar nada: una excepción queda en el registro de errores.
     *
     * @param \Closure $work
     * @return bool true si terminó sin excepción
     */
    public static function runSafely(\Closure $work): bool
    {
        try {
            $work();
            return true;
        } catch (\Throwable $exception) {
            //RETORNO-IGNORADO: la referencia no la ve nadie; la respuesta ya salió.
            log_exception($exception);
            return false;
        }
    }

}
