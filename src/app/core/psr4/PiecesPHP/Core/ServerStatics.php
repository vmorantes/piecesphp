<?php

/**
 * ServerStatics.php
 */

namespace PiecesPHP\Core;

use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

/**
 * ServerStatics - Implementación básica de un servidor de archivos.
 *
 * Servidor de archivos estáticos
 *
 * @category    Server
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class ServerStatics
{
    /**
     * $static_path
     *
     * @var string
     */
    public static $static_path = __DIR__ . '/../../statics';

    /**
     * __construct
     *
     * @param mixed $static_path
     * @return void
     */
    public function __construct($static_path = null)
    {
        if (is_string($static_path)) {
            self::setStaticPath($static_path);
        }
    }

    /**
     * serve
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return void
     */
    public function serve(Request $request, Response $response, array $args)
    {
        $resource = $args['params'];
        return self::verifyFile($resource, $response);
    }

    public static function setStaticPath(string $path)
    {
        if (string_compare(last_char($path), ['/', '\\'])) {
            $path = remove_last_char($path);
        }

        if (file_exists($path)) {
            self::$static_path = realpath($path);
        } else {
            self::$static_path = $path;
        }
    }

    /**
     * getStaticPath
     *
     * @return string
     */
    public static function getStaticPath()
    {
        return self::$static_path;
    }

    /**
     * verifyFile
     *
     * @param string $resource
     * @param Response $response
     * @return array|false
     */
    private function verifyFile(string $resource, Response $response)
    {

        $file_info = finfo_open(FILEINFO_MIME_TYPE);

        $file_path = self::getStaticPath() . "/$resource";

        if (file_exists($file_path) && is_string($resource)) {

            $extension = pathinfo($file_path, PATHINFO_EXTENSION);

            switch ($extension) {
                case 'css':
                    $mime_type = 'text/css';
                    break;
                case 'json':
                    $mime_type = 'application/json';
                    break;
                default:
                    $mime_type = finfo_file($file_info, $file_path);
                    break;
            }

            readfile($file_path);

            return $response->withHeader('Content-type', $mime_type);

        } else {

            return $response->withStatus(404)->write('<h1>404 el recurso no existe.</h1>');

        }
    }
}
