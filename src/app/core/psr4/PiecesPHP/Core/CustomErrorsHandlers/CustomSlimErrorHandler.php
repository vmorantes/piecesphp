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
     * @param RequestRoute $request
     * @return ResponseRoute
     */
    public function getResponse(RequestRoute $request)
    {

        $response = new ResponseRoute();
        $exception = $this->handler->getException();
        $class_exception = get_class($exception);
        $trace = $exception->getTrace();

        if (json_encode($trace) === false) {
            $trace = [];
        }

        $isLocal = is_local();

        $file = $exception->getFile();
        $line = $exception->getLine();

        if (!$isLocal) {
            $file = str_replace(basepath(), '{BASE_PATH}', $exception->getFile());
            foreach ($trace as $i => $t) {
                if (isset($t['file'])) {
                    $trace[$i]['file'] = str_replace(basepath(), '{BASE_PATH}', $t['file']);
                }
                if (isset($t['args'])) {
                    $trace[$i]['args'] = 'HIDDEN';
                }
            }
        }

        $codeException = '-';
        try{
            $codeException = $exception->getCode();
        }catch(\Throwable $e){}

        $jsonData = [
            'success' => false,
            'message' => $exception->getMessage(),
            'handlerContext' => $this->contextDescription,
            'detail' => [
                'type' => $class_exception,
                'code' => $codeException,
                'line' => $exception->getLine(),
                'file' => $file,
                'trace' => $trace,
                'extraData' => method_exists($exception, 'extraData') ? call_user_func(array($exception, 'extraData')) : [],
            ],
        ];

        $requestTypeIsJSON = mb_strtolower($request->getHeaderLine('Accept')) == 'application/json';
        if ($request->isXhr() || TerminalData::getInstance()->isTerminal() || $requestTypeIsJSON) {
            return $response->withStatus(500)->withJson($jsonData);
        } else {

            unset($jsonData['detail']['line']);
            unset($jsonData['detail']['file']);
            $message = $exception->getMessage();
            $html = var_dump_pretty([
                $jsonData['detail'],
            ], '', true);
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
            $html = !$isLocal ? str_replace(basepath(), '{BASE_PATH}', $html) : $html;

            return $response->withStatus(500)->write($html);

        }
    }
}
