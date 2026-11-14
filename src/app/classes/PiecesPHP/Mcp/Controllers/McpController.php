<?php

/**
 * McpController.php
 */

namespace PiecesPHP\Mcp\Controllers;

use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\Mcp\McpProtocol;
use PiecesPHP\Core\Mcp\PanelServer;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Mcp\McpLang;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * McpController - La ruta del servidor MCP del panel (ADR 0075): MCP por HTTP con la sesión del usuario.
 *
 * Solo POST con un mensaje JSON-RPC 2.0 (MCP 2025-06-18 no admite lotes). Un Origin de otro dominio no pasa: la cookie
 * de sesión viaja en las peticiones de otro sitio que la política SameSite deja pasar, y esto responde datos del usuario.
 *
 * @package     PiecesPHP\Mcp\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class McpController extends AdminPanelController
{
    use ControllerRoutingTrait;

    const LANG_GROUP = McpLang::LANG_GROUP;

    /**
     * El cuerpo más grande que se lee, en bytes (64 KiB): un mensaje MCP es pequeño.
     */
    const MAX_BODY_BYTES = 65536;

    /**
     * @var string
     */
    protected static $URLDirectory = 'mcp';

    /**
     * @var string
     */
    protected static $baseRouteName = 'mcp';

    /**
     * POST: un mensaje JSON-RPC 2.0, respondido por PanelServer con el usuario de la sesión.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function server(Request $request, Response $response): Response
    {
        $origin = $request->getHeaderLine('Origin');
        $fetchSite = $request->getHeaderLine('Sec-Fetch-Site');
        //«Origin: null» lo manda un origen opaco (un iframe con sandbox, un archivo local), y requestIsSameDomain() lo
        //dejaría pasar por el Referer. Sec-Fetch-Site lo pone el navegador y la página no lo puede falsear.
        if (mb_strtolower(trim($origin)) === 'null' || ($fetchSite !== '' && mb_strtolower(trim($fetchSite)) !== 'same-origin')) {
            return $response->withStatus(403);
        }
        if ($origin !== '' && !requestIsSameDomain()) {
            return $response->withStatus(403);
        }
        $contentType = mb_strtolower(trim(explode(';', $request->getHeaderLine('Content-Type'))[0]));
        if ($contentType !== 'application/json') {
            return $response->withStatus(415);
        }
        $raw = (string) $request->getBody();
        if (strlen($raw) > self::MAX_BODY_BYTES) {
            return $response->withStatus(413);
        }
        $message = json_decode($raw, true);
        if (json_last_error() !== \JSON_ERROR_NONE) {
            return $response->withJson(McpProtocol::parseError(McpProtocol::text('El cuerpo no es JSON válido.')));
        }
        //Decodificado a array, «{}» y «[]» dan lo mismo: el lote se reconoce por su primer carácter.
        if (str_starts_with(ltrim($raw), '[')) {
            return $response->withJson(McpProtocol::error(null, McpProtocol::INVALID_REQUEST, McpProtocol::text('Este servidor no admite lotes: un mensaje por petición.')));
        }
        if (!is_array($message)) {
            return $response->withJson(McpProtocol::error(null, McpProtocol::INVALID_REQUEST, McpProtocol::text('Petición JSON-RPC 2.0 no válida.')));
        }
        $answer = PanelServer::handle($message, getLoggedFrameworkUserOrFail());
        if ($answer === null) {
            return $response->withStatus(202);
        }
        return $response->withJson($answer);
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        $startRoute = (last_char($group->getGroupSegment()) == '/' ? '' : '/') . self::$URLDirectory;
        $allRoles = array_keys(UsersModel::TYPES_USERS);
        $group->register([
            new Route("{$startRoute}[/]", self::class . ':server', self::$baseRouteName . '-panel-server', 'POST', true, null, $allRoles),
        ]);
        return $group;
    }
}
