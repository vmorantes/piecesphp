<?php

/**
 * CustomSlimErrorHandler.php
 */
namespace PiecesPHP\Core\CustomErrorsHandlers;

use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\TerminalData;
use Throwable;

/**
 * CustomSlimErrorHandler - ....
 *
 * @category     ErrorsHandlers
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class CustomSlimErrorHandler
{
    /**
     * @var GenericHandler
     */
    protected $handler = null;
    /**
     * @var string
     */
    protected $contextDescription = null;

    /**
     * @param Throwable $exception
     * @param string $contextDescription Información sobre el lugar de donde fue manejado
     */
    public function __construct(Throwable $exception, string $contextDescription = 'no_information')
    {
        $this->handler = new GenericHandler($exception);
        $this->handler->logging();
        $this->contextDescription = $contextDescription;
    }

    /**
     * El código de referencia del error registrado en el constructor.
     *
     * @return string
     */
    public function reference(): string
    {
        return $this->handler->reference();
    }

    /**
     * @param RequestRoute $request
     * @param bool|null $isLocal null: lo decide is_local(); las pruebas lo fijan
     * @return ResponseRoute
     */
    public function getResponse(RequestRoute $request, ?bool $isLocal = null)
    {

        $response = new ResponseRoute();
        $isLocal = $isLocal ?? self::isLocal();
        $reference = $this->handler->reference();
        $requestTypeIsJSON = mb_strtolower($request->getHeaderLine('Accept')) == 'application/json';
        $wantsJSON = $request->isXhr() || TerminalData::getInstance()->isTerminal() || $requestTypeIsJSON;

        //FUERA DE LOCAL NO SALE NADA DE LA EXCEPCIÓN (P56): ni mensaje, ni tipo, ni archivo, ni traza. Solo la
        //referencia, que el usuario reporta y que lleva a la entrada completa del log.
        if (!$isLocal) {
            $message = self::genericMessage($reference);
            if ($wantsJSON) {
                return $response->withStatus(500)->withJson([
                    'success' => false,
                    'message' => $message,
                    'reference' => $reference,
                ]);
            }
            $title = function_exists('__') ? __('general', 'Error interno') : 'Error interno';
            $safe = fn(string $text): string => htmlspecialchars($text, \ENT_QUOTES, 'UTF-8');
            $html = "
                <html>
                    <style>
                        *{
                            box-sizing:border-box;
                        }
                    </style>
                    <body style='margin: 0px auto;'>
                        <div style='min-height: 100vh; background-color: whitesmoke;'>
                            <div style='width: 100%; max-width: 1200px; margin: 0px auto; padding:15px;'>
                                <h2>{$safe($title)}</h2>
                                <p>{$safe($message)}</p>
                                <p><strong>{$safe($reference)}</strong></p>
                            </div>
                        </div>
                    </body>
                </html>
            ";
            return $response->withStatus(500)->write($html);
        }

        $exception = $this->handler->getException();
        $class_exception = get_class($exception);
        $trace = $exception->getTrace();

        if (json_encode($trace) === false) {
            $trace = [];
        }

        $file = $exception->getFile();
        $line = $exception->getLine();

        //Desde P56 la respuesta de fuera de local se devuelve arriba, en el `if (!$isLocal)` que abre el método: de
        //aquí hacia abajo $isLocal es siempre true, así que no hay nada que enmascarar ni que ocultar.

        $codeException = $exception->getCode();

        $jsonData = [
            'success' => false,
            'message' => $exception->getMessage(),
            'reference' => $reference,
            'handlerContext' => $this->contextDescription,
            'detail' => [
                'type' => $class_exception,
                'code' => $codeException,
                'line' => $exception->getLine(),
                'file' => $file,
                'trace' => $trace,
                'extraData' => method_exists($exception, 'extraData') ? call_user_func([$exception, 'extraData']) : [],
            ],
        ];

        if ($wantsJSON) {
            return $response->withStatus(500)->withJson($jsonData);
        } else {

            unset($jsonData['detail']['line']);
            unset($jsonData['detail']['file']);
            $message = $exception->getMessage();
            $html = function_exists('var_dump_pretty') ? var_dump_pretty([
                $jsonData['detail'],
            ], '', true) : '<pre>' . json_encode($jsonData['detail'], \JSON_UNESCAPED_SLASHES  | \JSON_UNESCAPED_UNICODE  | \JSON_PRETTY_PRINT) . '</pre>';
            $html = "
                <html>
                    <style>
                        *{
                            box-sizing:border-box;
                        }
                    </style>
                    <body style='margin: 0px auto;'>
                        <div style='min-height: 100vh; background-color: whitesmoke;'>
                            <div style='width: 100%; max-width: 1200px; margin: 0px auto; padding:15px;'>
                                <h2>Error summary</h2>
                                <ul style='max-width: 100%; word-break: break-all;'>
                                    <li>File: {$file}</li>
                                    <li>Line: {$line}</li>
                                    <li>Message: {$message}</li>
                                    <li>Reference: {$reference}</li>
                                    <li>Handler context: {$this->contextDescription}</li>
                                </ul>
                                <div style='overflow:auto;'>
                                    $html
                                </div>
                            </div>
                        </div>
                    </body>
                </html>
            ";

            return $response->withStatus(500)->write($html);

        }
    }

    /**
     * Lo único que ve el usuario fuera de local: un aviso genérico con la referencia.
     *
     * @param string $reference
     * @return string
     */
    public static function genericMessage(string $reference): string
    {
        $template = function_exists('__') ? __('general', 'Ocurrió un error interno. Si lo reporta, indique la referencia %s.') : 'Ocurrió un error interno. Si lo reporta, indique la referencia %s.';
        return sprintf($template, $reference);
    }

    /**
     * @return boolean
     */
    public static function isLocal()
    {
        if (!function_exists('is_local')) {
            //La misma regla que is_local(): el entorno, nunca la cabecera Host (P58).
            $isLocal = \PiecesPHP\Core\AppEnvironment::get() === \PiecesPHP\Core\AppEnvironment::LOCAL;
            $pcsPhpTerminalData = $_SERVER['PCSPHP_TERMINAL_DATA'] ?? [];
            if ($pcsPhpTerminalData['isTerminal'] ?? false) {
                $isLocal = $pcsPhpTerminalData['local'] ?? false;
            }
            return $isLocal;
        } else {
            return is_local();
        }
    }

    /**
     * @param string $resource
     * @return string
     */
    public static function getBasePath(string $resource = "")
    {
        if (!function_exists('basepath')) {
            $basePath = realpath(__DIR__ . '/../../../../../../');
            return is_string($basePath) && is_dir($basePath) ? $basePath : '';
        } else {
            return basepath($resource);
        }
    }

}
