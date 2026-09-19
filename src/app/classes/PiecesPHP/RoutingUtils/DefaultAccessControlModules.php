<?php
/**
 * DefaultAccessControlModules.php
 */
namespace PiecesPHP\RoutingUtils;

use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
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
     * @param  RequestRoute $request PSR-7 request
     * @param  RequestHandlerInterface $handler PSR-15 request handler
     * @return ResponseRoute
     */
    public function getResponse(RequestRoute $request, RequestHandlerInterface $handler): ResponseRoute
    {
        $route = $request->getRoute();
        $routeName = $route->getName();
        $routeArguments = $route->getArguments();
        $routeArguments = is_array($routeArguments) ? $routeArguments : [];
        $basenameRoute = $this->baseRouteName;

        if (strpos($routeName, $basenameRoute) !== false && strpos($routeName, $basenameRoute) == 0) {

            $simpleName = str_replace($basenameRoute, '', $routeName);
            $routeURL = ($this->routeNameValidator)($simpleName, $routeArguments);
            $allowed = mb_strlen($routeURL) > 0;
            if (!$allowed) {
                throw403($request, []);
            }

        }

        $response = $handler->handle($request);
        return $response;
    }

}
