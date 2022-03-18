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
     * @var callable[]|null
     */
    protected static $beforeCallMethods = null;
    /**
     * @var callable[]|null
     */
    protected static $afterCallMethods = null;

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

        $nameControllerMethod = null;

        if (is_array($callable) && count($callable) == 2) {

            $classname = @get_class($callable[0]);
            $method = $callable[1];

            if (mb_strlen(trim($classname)) > 0 && mb_strlen(trim($method)) > 0) {
                $nameControllerMethod = trim($classname) . ':' . trim($method);
            }

        }

        if (self::$beforeCallMethods !== null && is_array(self::$beforeCallMethods) && !empty(self::$beforeCallMethods)) {

            foreach (self::$beforeCallMethods as $beforeCallMethods) {

                if (is_callable($beforeCallMethods)) {

                    ($beforeCallMethods)($nameControllerMethod);

                }

            }

        }

        $invokeResult = call_user_func($callable, $request, $response, $routeArguments);

        if (self::$afterCallMethods !== null && is_array(self::$afterCallMethods) && !empty(self::$afterCallMethods)) {

            foreach (self::$afterCallMethods as $afterCallMethods) {

                if (is_callable($afterCallMethods)) {

                    ($afterCallMethods)($nameControllerMethod);

                }

            }

        }

        return $invokeResult;
    }

    /**
     * @param callable $do
     * @return void
     */
    public static function appendBeforeCallMethod(callable $do)
    {
        if (!is_array(self::$beforeCallMethods)) {
            self::$beforeCallMethods = [];
        }
        self::$beforeCallMethods[] = $do;
    }

    /**
     * @param callable $do
     * @return void
     */
    public static function appendAfterCallMethod(callable $do)
    {
        if (!is_array(self::$afterCallMethods)) {
            self::$afterCallMethods = [];
        }
        self::$afterCallMethods[] = $do;
    }

    /**
     * @param callable[] $do
     * @return void
     */
    public static function setBeforeCallMethods(array $do)
    {
        self::$beforeCallMethods = [];

        foreach ($do as $i) {
            self::appendBeforeCallMethod($i);
        }

    }

    /**
     * @param callable[] $do
     * @return void
     */
    public static function setAfterCallMethods(array $do)
    {
        self::$afterCallMethods = [];

        foreach ($do as $i) {
            self::appendAfterCallMethod($i);
        }

    }

    /**
     * @return int
     */
    public static function countAfterCallMethods()
    {
        return is_array(self::$afterCallMethods) ? count(self::$afterCallMethods) : 0;
    }

    /**
     * @return int
     */
    public static function countBeforeCallMethods()
    {
        return is_array(self::$beforeCallMethods) ? count(self::$beforeCallMethods) : 0;
    }

}
