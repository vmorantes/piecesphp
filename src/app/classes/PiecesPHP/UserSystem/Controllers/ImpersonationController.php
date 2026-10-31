<?php

/**
 * ImpersonationController.php
 */

namespace PiecesPHP\UserSystem\Controllers;

use EventsLog\LogsRoutes;
use EventsLog\Mappers\LogsMapper;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\BaseToken;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\UserSystem\Authentication\Impersonation;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserSystemFeaturesLang;

/**
 * ImpersonationController - Empezar y terminar de actuar como otro usuario (P204 a). Solo desde el panel, por POST: la
 * cookie de la suplantación la escribe y la borra el servidor; index.php solo la lee (Impersonation).
 *
 * @package     PiecesPHP\UserSystem\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class ImpersonationController extends AdminPanelController
{
    use ControllerRoutingTrait;

    const LANG_GROUP = UserSystemFeaturesLang::LANG_GROUP;

    /**
     * El contexto de FlashMessages del aviso de la franja: por qué terminó una suplantación que no terminó root.
     */
    const NOTICE_CONTEXT = 'pcsphp_impersonation_notice';

    /**
     * @var string
     */
    protected static $URLDirectory = 'impersonation';

    /**
     * @var string
     */
    protected static $baseRouteName = 'user-impersonation';

    /**
     * Root empieza a actuar como otro usuario.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function start(Request $request, Response $response)
    {
        $actor = getLoggedFrameworkUserOrFail();
        if (self::isImpersonating()) {
            return self::refused($response, __(self::LANG_GROUP, 'Ya está actuando como otro usuario.'));
        }
        $sessionID = self::currentSessionID();
        if ($sessionID === null) {
            return self::refused($response, __(self::LANG_GROUP, 'No se puede actuar como otro usuario desde esta sesión.'));
        }
        $targetID = $request->getParsedBodyParam('userID');
        $refusal = Impersonation::refusalReason($targetID);
        if ($refusal !== null) {
            return self::refused($response, $refusal);
        }
        $targetID = (int) $targetID;
        $startedAt = time();
        setcookie(CONNECT_AS_ANOTHER_USER_ID_COOKIE_NAME, Impersonation::cookieValue($sessionID, (int) $actor->id, $targetID, $startedAt), self::cookieOptions($startedAt + Impersonation::lifetime()));
        if (LogsRoutes::ENABLE) {
            //Queda la huella de quién empezó a actuar como quién: lo que el suplantado «hace» después lleva al actor real.
            LogsMapper::addLog(LogsMapper::MSG_IMPERSONATION_START, [
                'username' => (string) $actor->username,
                'target' => (string) (UsersModel::getBy($targetID, 'id')->username ?? $targetID),
            ], 'id', (string) $targetID, UsersModel::TABLE);
        }
        return $response->withJson([
            'success' => true,
            'name' => __(self::LANG_GROUP, 'Actuar como otro usuario'),
            'message' => '',
            'values' => [
                'redirect' => AdminPanelController::routeName(''),
            ],
        ]);
    }

    /**
     * Root deja de actuar como otro usuario. Mientras suplanta, el usuario actual es el suplantado: la ruta admite todos
     * los roles y el método se comprueba a sí mismo.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function end(Request $request, Response $response)
    {
        if (!self::isImpersonating()) {
            return self::refused($response, __(self::LANG_GROUP, 'No está actuando como otro usuario.'));
        }
        $rootID = (int) get_config(ROOT_ORIGINAL_ID_CONFIG_NAME);
        self::clearCookie();
        if (LogsRoutes::ENABLE) {
            LogsMapper::addLog(LogsMapper::MSG_IMPERSONATION_END, [
                'username' => (string) (UsersModel::getBy($rootID, 'id')->username ?? $rootID),
            ], 'id', (string) $rootID, UsersModel::TABLE);
        }
        return $response->withJson([
            'success' => true,
            'name' => __(self::LANG_GROUP, 'Actuar como otro usuario'),
            'message' => '',
            'values' => [
                'redirect' => AdminPanelController::routeName(''),
            ],
        ]);
    }

    /**
     * Si root está actuando como otro en esta petición (lo fija index.php).
     *
     * @return bool
     */
    public static function isImpersonating(): bool
    {
        $rootOriginalID = get_config(ROOT_ORIGINAL_ID_CONFIG_NAME);
        $asUserID = get_config(ROOT_ID_AS_CONNECT_CONFIG_NAME);
        return Validator::isInteger($rootOriginalID) && Validator::isInteger($asUserID) && (int) $rootOriginalID !== (int) $asUserID;
    }

    /**
     * Borra la cookie de la suplantación, con las mismas opciones con las que se escribe.
     *
     * @return void
     */
    public static function clearCookie(): void
    {
        setcookie(CONNECT_AS_ANOTHER_USER_ID_COOKIE_NAME, '', self::cookieOptions(1));
        unset($_COOKIE[CONNECT_AS_ANOTHER_USER_ID_COOKIE_NAME]);
    }

    /**
     * Deja el aviso de la franja para la próxima página del panel.
     *
     * @param string $message Ya traducido
     * @return void
     */
    public static function setNotice(string $message): void
    {
        set_flash_message('message', $message, self::NOTICE_CONTEXT);
    }

    /**
     * Saca el aviso de la franja: se enseña una vez.
     *
     * @return string|null
     */
    public static function pullNotice(): ?string
    {
        $message = get_flash_messages(self::NOTICE_CONTEXT)['message'] ?? null;
        return is_string($message) && $message !== '' ? $message : null;
    }

    /**
     * El `sid` del token de esta petición; el del terminal no lo lleva.
     *
     * @return string|null
     */
    protected static function currentSessionID(): ?string
    {
        $JWT = SessionToken::getJWTReceived();
        $data = is_string($JWT) && $JWT !== '' ? BaseToken::getData($JWT) : null;
        $sessionID = $data instanceof \stdClass ? ($data->sid ?? null) : null;
        return is_string($sessionID) && $sessionID !== '' ? $sessionID : null;
    }

    /**
     * Las de la configuración, siempre HttpOnly: el navegador no la lee ni la escribe.
     *
     * @param int $expires
     * @return array{expires:int,path:string,domain:string,secure:bool,httponly:bool,samesite:'Lax'|'Strict'|'None'}
     */
    protected static function cookieOptions(int $expires): array
    {
        $cookiesConfig = get_config('cookies');
        $options = cookie_options_by_config(is_array($cookiesConfig) ? $cookiesConfig : []);
        $options['httponly'] = true;
        $options['expires'] = $expires;
        return $options;
    }

    /**
     * @param Response $response
     * @param string $message
     * @return Response
     */
    protected static function refused(Response $response, string $message): Response
    {
        return $response->withStatus(400)->withJson([
            'success' => false,
            'name' => __(self::LANG_GROUP, 'Actuar como otro usuario'),
            'message' => $message,
        ]);
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
            new Route("{$startRoute}/start[/]", self::class . ':start', self::$baseRouteName . '-start', 'POST', true, null, [UsersModel::TYPE_USER_ROOT]),
            new Route("{$startRoute}/end[/]", self::class . ':end', self::$baseRouteName . '-end', 'POST', true, null, $allRoles),
        ]);

        return $group;
    }
}
