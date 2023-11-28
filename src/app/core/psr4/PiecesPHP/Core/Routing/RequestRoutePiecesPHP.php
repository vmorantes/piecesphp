<?php

/**
 * RequestRoutePiecesPHP.php
 */
namespace PiecesPHP\Core\Routing;

use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Request;
use Slim\Routing\Route;

/**
 * RequestRoutePiecesPHP
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2023
 */
class RequestRoutePiecesPHP extends Request
{

    /**
     * @return Route|null
     */
    public function getRoute()
    {
        return $this->getAttribute('route');
    }

    /**
     * {@inheritdoc}
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        //En caso de que se pida 'route'
        if ($name == 'route') {
            if (!array_key_exists($name, $this->attributes)) {
                $routeContext = \Slim\Routing\RouteContext::fromRequest($this);
                $route = $routeContext->getRoute();
                if (empty($route)) {
                    throw new HttpNotFoundException($this);
                }
                return $route;
            }
        }
        return $this->attributes[$name] ?? $default;
    }

    /**
     * Fetch parameter value from request body.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getParsedBodyParam($key, $default = null)
    {
        $postParams = $this->getParsedBody();
        $result = $default;
        if (is_array($postParams) && isset($postParams[$key])) {
            $result = $postParams[$key];
        } elseif (is_object($postParams) && property_exists($postParams, $key)) {
            $result = $postParams->$key;
        }

        return $result;
    }

    /**
     * Fetch parameter value from query string.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getQueryParam($key, $default = null)
    {
        $getParams = $this->getQueryParams();
        $result = $default;
        if (isset($getParams[$key])) {
            $result = $getParams[$key];
        }

        return $result;
    }

    /**
     * Is this an XHR request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isXhr()
    {
        return mb_strtolower($this->getHeaderLine('X-Requested-With')) === mb_strtolower('XMLHttpRequest');
    }
}
