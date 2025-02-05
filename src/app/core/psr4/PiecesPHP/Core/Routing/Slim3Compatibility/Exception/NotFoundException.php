<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace PiecesPHP\Core\Routing\Slim3Compatibility\Exception;

use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpNotFoundException;
use Throwable;

class NotFoundException extends HttpNotFoundException
{
    /**
     * @var ServerRequestInterface
     */
    protected ServerRequestInterface $request;

    /**
     * @var ResponseRoute
     */
    protected $response;

    /**
     * @param RequestRoute $request
     * @param ResponseRoute $response
     */
    public function __construct(RequestRoute $request, ?ResponseRoute $response = null, ?string $message = null, ?Throwable $previous = null)
    {
        $route = $request->getRoute();
        $extraDataKey = 'information404';
        $extraData = $request->getAttribute($extraDataKey, []);
        $extraData = is_array($extraData) ? $extraData : [];
        if ($route !== null) {
            $routeName = $route->getName();
            $routeInformation = get_route_info($routeName);
            $requireLogin = $routeInformation['require_login'];
            //Definir el botón de volver en la ruta administrativa si no hay una url definida y la ruta requiere login
            $adminRoute = get_route('admin');
            if ($requireLogin && !array_key_exists('url', $extraData)) {
                $extraData['url'] = $adminRoute;
            }
        }
        $request = $request->withAttribute($extraDataKey, $extraData);
        parent::__construct($request, $message, $previous);
        $this->response = $response;
    }

    /**
     * @return ResponseRoute
     */
    public function getResponse()
    {
        return $this->response;
    }
}
