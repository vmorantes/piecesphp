<?php

/**
 * MySpaceController.php
 */

namespace MySpace\Controllers;

use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\UserSystem\ORM\UsersModel;
use EventsLog\Mappers\LogsMapper;
use MySpace\MySpaceLang;
use MySpace\MySpaceRoutes;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\UserSystemFeaturesLang;

/**
 * MySpaceController.
 *
 * @package     MySpace\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2022
 */
class MySpaceController extends AdminPanelController
{

    use ControllerRoutingTrait;

    /**
     * @var string
     */
    protected static $URLDirectory = 'my-space';
    /**
     * @var string
     */
    protected static $baseRouteName = 'my-space-admin';

    /**
     * @var HelperController
     */
    protected $helpController = null;

    const BASE_JS_DIR = 'js';
    const BASE_CSS_DIR = 'css';
    const LANG_GROUP = MySpaceLang::LANG_GROUP;

    public function __construct()
    {
        parent::__construct();

        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());

        $this->setInstanceViewDir(__DIR__ . '/../Views/');

        add_global_asset(MySpaceRoutes::staticRoute('globals-vars.css'), 'css');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function userSecurity(Request $request, Response $response)
    {

        set_title(__(AdminPanelController::ADMIN_LANG_GROUP, 'Opciones de seguridad'));

        set_custom_assets([
            MySpaceRoutes::staticRoute(self::BASE_JS_DIR . '/user-security.js'),
        ], 'js');

        set_custom_assets([
            MySpaceRoutes::staticRoute(self::BASE_CSS_DIR . '/user-security.css'),
        ], 'css');

        import_apexcharts();
        import_qrcodejs();

        $currentUser = getLoggedFrameworkUserOrFail();

        $title = __(MySpaceLang::LANG_GROUP, 'Seguridad de usuario');
        $data = [];
        $data['langGroup'] = MySpaceLang::LANG_GROUP;
        $data['title'] = $title;
        $data['description'] = __(MySpaceLang::LANG_GROUP, 'La verificación en dos pasos de tu cuenta y los dispositivos donde has iniciado sesión.');
        $data['helpController'] = $this->helpController;
        $data['breadcrumbs'] = [
            __(MySpaceLang::LANG_GROUP, 'Inicio') => [
                'url' => AdminPanelController::routeName(''),
            ],
            $title,
        ];

        //Sin `gradient-base`: el fondo degradado morado no es el del panel, y sobre él las migas no se leían.
        $this->helpController->render('panel/layout/header');
        $this->render('user-security', $data);
        $this->helpController->render('panel/layout/footer');
        return $response;
    }

    /**
     * Cierra TODAS las sesiones del usuario, incluida la de esta petición (ADR 0026).
     *
     * El sujeto sale de la sesión, JAMÁS del cuerpo. No emite token nuevo: un token robado no puede echar al dueño y
     * quedarse con una sesión fresca. Durante una suplantación no se puede usar: cerraría las sesiones del suplantado.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function revokeMySessions(Request $request, Response $response)
    {
        $langGroup = UserSystemFeaturesLang::LANG_GROUP;
        $currentUser = getLoggedFrameworkUserOrFail();

        if (self::isImpersonating((int) $currentUser->id)) {
            return $response->withStatus(403)->withJson([
                'success' => false,
                'name' => __($langGroup, 'Cerrar sesiones'),
                'message' => __($langGroup, 'No se pueden cerrar las sesiones de un usuario mientras se actúa como él.'),
            ]);
        }

        $revoked = (new UsersModel())->revokeSessions((int) $currentUser->id);

        if (!$revoked) {
            return $response->withStatus(500)->withJson([
                'success' => false,
                'name' => __($langGroup, 'Cerrar sesiones'),
                'message' => __($langGroup, 'No se pudieron cerrar las sesiones.'),
            ]);
        }

        LogsMapper::addLog(LogsMapper::MSG_REVOKE_OWN_SESSIONS, [
            '%username%' => $currentUser->username,
        ], 'id', (string) $currentUser->id, UsersModel::TABLE);

        self::forgetSessionCookie();

        return $response->withJson([
            'success' => true,
            'name' => __($langGroup, 'Cerrar sesiones'),
            'message' => __($langGroup, 'Se cerraron todas sus sesiones. Tendrá que volver a ingresar.'),
            'values' => [
                'redirect' => true,
                'redirect_to' => UsersController::routeName('form-login'),
            ],
        ]);
    }

    /**
     * Root actuando como otro usuario: `index.php` guarda el id original de root y deja en la sesión al suplantado.
     *
     * @param int $currentUserID
     * @return bool
     */
    public static function isImpersonating(int $currentUserID): bool
    {
        $rootOriginalID = get_config(ROOT_ORIGINAL_ID_CONFIG_NAME);
        return Validator::isInteger($rootOriginalID) && (int) $rootOriginalID !== $currentUserID;
    }

    /**
     * Caduca la cookie de sesión en las dos formas en que la pone el cliente: la del host y la del dominio.
     *
     * @return void
     */
    private static function forgetSessionCookie(): void
    {
        $name = SessionToken::tokenName();
        $host = isset($_SERVER['HTTP_HOST']) && is_string($_SERVER['HTTP_HOST']) ? explode(':', $_SERVER['HTTP_HOST'])[0] : '';
        setcookie($name, '', ['expires' => 1, 'path' => '/']);
        if ($host !== '') {
            setcookie($name, '', ['expires' => 1, 'path' => '/', 'domain' => $host]);
        }
    }

    /**
     * @inheritDoc
     */
    public function render(string $name = "index", array $data = [], bool $mode = true, bool $format = false)
    {
        return parent::render(trim($name, '/'), $data, $mode, $format);
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        $routes = [];

        $groupSegmentURL = $group->getGroupSegment();

        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = ($lastIsBar ? '' : '/') . self::$URLDirectory;

        $classname = self::class;

        /**
         * @var array<string>
         */
        $allRoles = array_keys(UsersModel::TYPES_USERS);

        $routes = [

            //──── GET ───────────────────────────────────────────────────────────────────────────────
            //HTML
            new Route(
                "{$startRoute}/user-security[/]",
                $classname . ':userSecurity',
                self::$baseRouteName . '-user-security',
                'GET',
                true,
                null,
                $allRoles
            ),

            //──── POST ──────────────────────────────────────────────────────────────────────────────
            new Route(
                "{$startRoute}/revoke-my-sessions[/]",
                $classname . ':revokeMySessions',
                self::$baseRouteName . '-revoke-my-sessions',
                'POST',
                true,
                null,
                $allRoles
            ),

        ];

        $group->register($routes);

        $group->addMiddleware(function (\PiecesPHP\Core\Routing\RequestRoute $request, $handler) {
            return (new DefaultAccessControlModules(self::$baseRouteName . '-', function (string $name, array $params) {
                return self::routeName($name, $params);
            }))->getResponse($request, $handler);
        });

        return $group;
    }

    /**
     * Verificar si una ruta es permitida y determinar pasos para permitirla o no
     *
     * PUNTO DE VARIACIÓN DEL MÓDULO. Aquí, y en ningún otro sitio, van las reglas de negocio
     * que oculten una ruta que los roles SÍ permiten. Está vacío a propósito: es la plantilla,
     * y su presencia dice dónde se escribe la regla el día que aparezca.
     *
     * Devolver `false` ESTRECHA lo que ya concedieron los roles; nunca ensancha. `routeName()`
     * llama a este método SIEMPRE, y `allowedRoute()` no hace más que preguntarle a
     * `routeName()` si devolvió cadena.
     *
     * @param string $name
     * @param string $route
     * @param array $params
     * @return bool
     */
    protected static function _allowedRoute(string $name, string $route, array $params = [])
    {
        return true;
    }
}
