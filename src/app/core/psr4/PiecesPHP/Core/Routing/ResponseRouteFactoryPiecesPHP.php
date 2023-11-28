<?php

/**
 * ResponseRouteFactoryPiecesPHP.php
 */
namespace PiecesPHP\Core\Routing;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * ResponseRouteFactoryPiecesPHP
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2023
 */
class ResponseRouteFactoryPiecesPHP implements ResponseFactoryInterface
{
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return (new ResponseRoutePiecesPHP())->withStatus($code, $reasonPhrase);
    }
}
