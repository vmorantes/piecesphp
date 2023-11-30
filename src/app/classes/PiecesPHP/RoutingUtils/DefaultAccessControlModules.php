<?php
/**
 * DefaultAccessControlModules.php
 */
namespace PiecesPHP\RoutingUtils;

use PiecesPHP\Core\Routing\RequestRoutePiecesPHP;
use PiecesPHP\Core\Routing\ResponseRoutePiecesPHP;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * DefaultAccessControlModules
 *
 * @package     PiecesPHP\RoutingUtils
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2023
 */
class DefaultAccessControlModules
{

    /**
     * @var string
     */
    protected string $baseRouteName = '';
    /**
     * @var callable
     */
    protected $routeNameValidator = null;

    public function __construct(string $baseRouteName, callable $routeNameValidator)
    {
        $this->baseRouteName = $baseRouteName;
        $this->routeNameValidator = $routeNameValidator;
    }

    /**
     * @param  RequestRoutePiecesPHP $request PSR-7 request
     * @param  RequestHandlerInterface $handler PSR-15 request handler
     * @return ResponseRoutePiecesPHP
     */
    public function getResponse(RequestRoutePiecesPHP $request, RequestHandlerInterface $handler): ResponseRoutePiecesPHP
    {
        $route = $request->getRoute();
        $routeName = $route->getName();
        $routeArguments = $route->getArguments();
        $routeArguments = is_array($routeArguments) ? $routeArguments : [];
        $basenameRoute = $this->baseRouteName;

        if (strpos($routeName, $basenameRoute) !== false) {

            $simpleName = str_replace($basenameRoute, '', $routeName);
            $routeURL = ($this->routeNameValidator)($simpleName, $routeArguments);
            $allowed = mb_strlen($routeURL) > 0;
            if (!$allowed) {
                return throw403($request, []);
            }

        }

        $response = $handler->handle($request);
        return $response;
    }

}
