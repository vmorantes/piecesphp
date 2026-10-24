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
        //Sin ruta resuelta no hay con qué decidir el acceso: se niega, nunca se deja pasar.
        if ($route === null) {
            return throw403($request);
        }
        $routeName = $route->getName();
        $routeArguments = $route->getArguments();
        $routeArguments = is_array($routeArguments) ? $routeArguments : [];
        $basenameRoute = $this->baseRouteName;

        if (str_contains($routeName, $basenameRoute) && str_starts_with($routeName, $basenameRoute)) {

            //Sin usuario, lo que pide sesión se niega antes del validador, que con routeName() concedería (D6).
            if (getLoggedFrameworkUser() === null) {
                $routeInfo = get_route_info((string) $routeName);
                if (is_array($routeInfo) && ($routeInfo['require_login'] ?? false) === true) {
                    return throw403($request);
                }
            }

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
