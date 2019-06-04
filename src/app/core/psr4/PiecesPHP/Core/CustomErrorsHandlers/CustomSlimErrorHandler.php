<?php

/**
 * CustomSlimErrorHandler.php
 */
namespace PiecesPHP\Core\CustomErrorsHandlers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

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
     * $container
     *
     * @var \Slim\Container
     */
    protected $container;
    /**
     * $handler
     *
     * @var GenericHandler
     */
    protected $handler = null;

    public function __construct($c)
    {
        $this->container = $c;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param Exception $exception
     */
    public function __invoke($request, $response, $exception)
    {

        $this->handler = new GenericHandler($exception);
        $this->handler->logging();

        $class_exception = get_class($exception);
        $trace = $exception->getTrace();

        if (json_encode($trace) === false) {
            $trace = [];
        }

        $message = [
            'success' => false,
            'message' => $exception->getMessage(),
            'detail' => [],
        ];

        if (!is_local()) {
            foreach ($trace as $i => $t) {
                if (isset($t['file'])) {
                    $trace[$i]['file'] = str_replace(basepath(), '', $t['file']);
                }
                if (isset($t['args'])) {
                    $trace[$i]['args'] = 'HIDDEN';
                }
            }
        }

        $file = is_local() ? $exception->getFile() : str_replace(basepath(), '', $exception->getFile());

        $message['detail'] = [
            'type' => $class_exception,
            'code' => $exception->getCode(),
            'line' => $exception->getLine(),
            'file' => $file,
            'trace' => $trace,
        ];

        if ($request->isXhr()) {
            return $response->withStatus(500)->withJson($message);
        } else {
            $html = "";
            $html .= "<div style='box-sizing: border-box;width:800px;text-align:center;max-width:100%;margin:0 auto;padding:0 0.5rem;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);word-break: break-word;'>";
            $html .= "<h1>" . 'Error 500:' . "</h1>";
            $html .= "<p>" . $message['message'] . "</p>";
            $html .= "</div>";
            return $response->withStatus(500)->write($html);
        }
    }
}
