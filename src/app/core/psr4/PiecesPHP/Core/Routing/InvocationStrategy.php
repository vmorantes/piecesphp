<?php
/**
 * InvocationStrategy.php
 */
namespace PiecesPHP\Core\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\InvocationStrategyInterface;
use Throwable;

/**
 * InvocationStrategy
 *
 * @package     PiecesPHP\Core\Routing
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2023
 */
class InvocationStrategy implements InvocationStrategyInterface
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
     * Invoke a route callable with request, response and all route parameters
     * as individual arguments.
     *
     * @param array<string, string>  $routeArguments
     */
    public function __invoke(
        callable $callable,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments
    ): ResponseInterface {

        foreach ($routeArguments as $k => $v) {
            $request = $request->withAttribute($k, $v);
        }

        if (self::$beforeCallMethods !== null && is_array(self::$beforeCallMethods) && !empty(self::$beforeCallMethods)) {

            foreach (self::$beforeCallMethods as $beforeCallMethods) {

                if (is_callable($beforeCallMethods)) {

                    ($beforeCallMethods)();

                }

            }

        }

        try {
            ob_start();
            $invokeResult = $callable($request, $response, $routeArguments);
            ob_end_flush();
        } catch (Throwable $e) {
            $errorType = get_class($e);
            $errorsTypes = [
                \ErrorException::class,
                \Error::class,
                \TypeError::class,
                \Throwable::class,
            ];
            if (in_array($errorType, $errorsTypes)) {
                $errorMiddleware = get_error_middleware();
                return $errorMiddleware->getErrorHandler($errorType)($request, $e, true);
            } else {
                throw $e;
            }
        }

        if (self::$afterCallMethods !== null && is_array(self::$afterCallMethods) && !empty(self::$afterCallMethods)) {

            foreach (self::$afterCallMethods as $afterCallMethods) {

                if (is_callable($afterCallMethods)) {

                    ($afterCallMethods)();

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

}
