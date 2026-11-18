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
use PiecesPHP\Mcp\Assistant;
use PiecesPHP\Mcp\McpLang;
use PiecesPHP\UserSystem\UserTypes;

/**
 * McpController - Las rutas del servidor MCP del panel y del asistente (ADR 0075), con la sesión del usuario.
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
        $body = self::guardedBody($request);
        if (is_int($body)) {
            return $response->withStatus($body);
        }
        if (!$body['valid']) {
            return $response->withJson(McpProtocol::parseError(McpProtocol::text('El cuerpo no es JSON válido.')));
        }
        if ($body['batch']) {
            return $response->withJson(McpProtocol::error(null, McpProtocol::INVALID_REQUEST, McpProtocol::text('Este servidor no admite lotes: un mensaje por petición.')));
        }
        if (!is_array($body['decoded'])) {
            return $response->withJson(McpProtocol::error(null, McpProtocol::INVALID_REQUEST, McpProtocol::text('Petición JSON-RPC 2.0 no válida.')));
        }
        $answer = PanelServer::handle($body['decoded'], getLoggedFrameworkUserOrFail());
        if ($answer === null) {
            return $response->withStatus(202);
        }
        return $response->withJson($answer);
    }

    /**
     * POST: una pregunta al asistente del panel, {question: string, history?: list}. Responde lo que da Assistant::reply().
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function ask(Request $request, Response $response): Response
    {
        $body = self::guardedBody($request);
        if (is_int($body)) {
            return $response->withStatus($body);
        }
        $decoded = $body['valid'] && !$body['batch'] && is_array($body['decoded']) ? $body['decoded'] : null;
        if ($decoded === null || !is_string($decoded['question'] ?? null)) {
            return $response->withJson(['ok' => false, 'error' => McpProtocol::text('El cuerpo tiene que ser un objeto JSON con la pregunta en «question».')], 400);
        }
        $history = is_array($decoded['history'] ?? null) ? $decoded['history'] : [];
        //La respuesta del proveedor puede traer bytes que no son UTF-8: se sustituyen en vez de fallar al codificar.
        return $response->withJson(Assistant::reply(getLoggedFrameworkUserOrFail(), $decoded['question'], $history), null, \JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * Las defensas comunes de las dos rutas: el mismo origen (también por Sec-Fetch-Site y sin «Origin: null»), JSON y
     * como mucho MAX_BODY_BYTES. Devuelve el código HTTP con que rechazar, o el cuerpo decodificado una sola vez.
     *
     * @param Request $request
     * @return int|array{valid:bool,batch:bool,decoded:mixed}
     */
    private static function guardedBody(Request $request): int|array
    {
        $origin = $request->getHeaderLine('Origin');
        $fetchSite = $request->getHeaderLine('Sec-Fetch-Site');
        //«Origin: null» lo manda un origen opaco (un iframe con sandbox, un archivo local), y requestIsSameDomain() lo
        //dejaría pasar por el Referer. Sec-Fetch-Site lo pone el navegador y la página no lo puede falsear.
        if (mb_strtolower(trim($origin)) === 'null' || ($fetchSite !== '' && mb_strtolower(trim($fetchSite)) !== 'same-origin')) {
            return 403;
        }
        if ($origin !== '' && !requestIsSameDomain()) {
            return 403;
        }
        $contentType = mb_strtolower(trim(explode(';', $request->getHeaderLine('Content-Type'))[0]));
        if ($contentType !== 'application/json') {
            return 415;
        }
        $raw = (string) $request->getBody();
        if (strlen($raw) > self::MAX_BODY_BYTES) {
            return 413;
        }
        $decoded = json_decode($raw, true);
        //Decodificado a array, «{}» y «[]» dan lo mismo: el lote se reconoce por su primer carácter.
        return ['valid' => json_last_error() === \JSON_ERROR_NONE, 'batch' => str_starts_with(ltrim($raw), '['), 'decoded' => $decoded];
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        $startRoute = (last_char($group->getGroupSegment()) == '/' ? '' : '/') . self::$URLDirectory;
        $allRoles = UserTypes::codes();
        $group->register([
            new Route("{$startRoute}[/]", self::class . ':server', self::$baseRouteName . '-panel-server', 'POST', true, null, $allRoles),
            new Route((last_char($group->getGroupSegment()) == '/' ? '' : '/') . 'assistant[/]', self::class . ':ask', self::$baseRouteName . '-assistant-ask', 'POST', true, null, $allRoles),
        ]);
        return $group;
    }
}
