<?php

/**
 * ResponseRouteFactory.php
 */
namespace PiecesPHP\Core\Routing;

use PiecesPHP\Core\Routing\Slim3Compatibility\Http\StatusCode;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * ResponseRouteFactory
 *
 * @package     PiecesPHP\Core\Routing
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2023
 */
class ResponseRouteFactory implements ResponseFactoryInterface
{
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return (new ResponseRoute())->withStatus($code, $reasonPhrase);
    }
    public static function notFoundResponse(string $reasonPhrase = 'Not Found'): ResponseInterface
    {
        return (new ResponseRoute())->withStatus(StatusCode::HTTP_NOT_FOUND, $reasonPhrase);
    }
}
