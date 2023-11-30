<?php

/**
 * ResponseRoutePiecesPHP.php
 */
namespace PiecesPHP\Core\Routing;

use PiecesPHP\Core\Routing\Slim3Compatibility\Http\Body;
use Slim\Psr7\Response;

/**
 * ResponseRoutePiecesPHP
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2023
 */
class ResponseRoutePiecesPHP extends Response
{

    /**
     * Json.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * This method prepares the response object to return an HTTP Json
     * response to the client.
     *
     * @param  mixed $data   The data
     * @param  int   $status The HTTP status code.
     * @param  int   $encodingOptions Json encoding options
     *
     * @return static
     *
     * @throws \RuntimeException
     */
    public function withJson($data, $status = null, $encodingOptions = 0)
    {
        $json = json_encode($data, $encodingOptions);

        if ($json === false) {
            throw new \RuntimeException(json_last_error_msg(), json_last_error());
        }

        $responseWithJson = $this->write($json);
        $responseWithJson = $responseWithJson->withHeader('Content-Type', 'application/json');
        if (isset($status)) {
            $responseWithJson = $responseWithJson->withStatus($status);
        }

        return $responseWithJson;
    }

    /**
     * Redirect.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * This method prepares the response object to return an HTTP Redirect
     * response to the client.
     *
     * @param  string|UriInterface $url    The redirect destination.
     * @param  int            $status The redirect HTTP status code.
     *
     * @return static
     */
    public function withRedirect($url, $status = 302)
    {
        return $this->withHeader('Location', $url)
            ->withStatus($status);
    }

    /**
     * Write data to the response body.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * Proxies to the underlying stream and writes the provided data to it.
     *
     * @param string $data
     *
     * @return static
     */
    public function write($data)
    {
        $this->getBody()->write($data);

        return $this;
    }

}
