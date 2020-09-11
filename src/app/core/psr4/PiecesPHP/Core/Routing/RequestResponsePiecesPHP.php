<?php
/**
 * RequestResponsePiecesPHP.php
 */
namespace PiecesPHP\Core\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\InvocationStrategyInterface;

/**
 * RequestResponsePiecesPHP
 *
 * Sobreescritura de foundHandler de Slim
 *
 * @package     PiecesPHP\Core\Routing
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2020
 */
class RequestResponsePiecesPHP implements InvocationStrategyInterface
{

    /**
     * @var callable $beforeCallMethod
     */
    protected static $beforeCallMethod = null;
    /**
     * @var callable $afterCallMethod
     */
    protected static $afterCallMethod = null;

    /**
     * Invoke a route callable with request, response, and all route parameters
     * as an array of arguments.
     *
     * @param array|callable         $callable
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param array                  $routeArguments
     *
     * @return ResponseInterface
     */
    public function __invoke(
        callable $callable,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments
    ) {

        foreach ($routeArguments as $k => $v) {
            $request = $request->withAttribute($k, $v);
        }

        if (self::$beforeCallMethod !== null) {
            (self::$beforeCallMethod)();
        }

        $invokeResult = call_user_func($callable, $request, $response, $routeArguments);

        if (self::$afterCallMethod !== null) {
            (self::$afterCallMethod)();
        }

        return $invokeResult;
    }

    /**
     * setBeforeCallMethod
     *
     * @param callable $do
     * @return void
     */
    public static function setBeforeCallMethod(callable $do)
    {
        self::$beforeCallMethod = $do;
    }

    /**
     * setAfterCallMethod
     *
     * @param callable $do
     * @return void
     */
    public static function setAfterCallMethod(callable $do)
    {
        self::$afterCallMethod = $do;
    }

}
