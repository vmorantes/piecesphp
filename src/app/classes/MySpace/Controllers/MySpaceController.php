<?php

/**
 * MySpaceController.php
 */

namespace MySpace\Controllers;

use EventsLog\Mappers\LogsMapper;
use MySpace\MySpaceLang;
use MySpace\MySpaceRoutes;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;
use PiecesPHP\UserSystem\Authentication\Impersonation;
use PiecesPHP\UserSystem\Authentication\SessionCookie;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserDataPackage;
use PiecesPHP\UserSystem\UserSystemFeaturesLang;
use SystemApprovals\SystemApprovalsRoutes;
use SystemApprovals\Util\Packages\UsersApprovalHandler;
use SystemApprovals\Util\SystemApprovalManager;

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
     * El manual de usuario (ADR 0072): el índice abre el primer capítulo visible; con {slug}, ese capítulo.
     *
     * Un capítulo que no está en el índice o que el usuario no puede ver da 404: no revela que existe.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function manual(Request $request, Response $response)
    {
        $currentUser = getLoggedFrameworkUserOrFail();
        if (get_config('manual_for_pending') === false && self::isPendingAccount($currentUser)) {
            return throw403($request);
        }
        $chapters = self::manualChapters();
        $slug = $request->getAttribute('slug', null);
        $chapter = null;
        foreach ($chapters as $candidate) {
            if (!is_string($slug) || $candidate['slug'] === $slug) {
                $chapter = $candidate;
                break;
            }
        }
        if ($chapter === null) {
            throw new NotFoundException($request, $response);
        }

        $title = __(MySpaceLang::LANG_GROUP, 'Manual');
        set_title($chapter['titulo'] . ' - ' . $title);
        $data = [];
        $data['langGroup'] = MySpaceLang::LANG_GROUP;
        $data['title'] = $chapter['titulo'];
        $data['helpController'] = $this->helpController;
        $data['chapters'] = $chapters;
        $data['current'] = $chapter['slug'];
        $data['content'] = (string) $this->_render('manual/' . $chapter['idioma'] . '/' . $chapter['slug'] . '.php', [], false);
        $data['breadcrumbs'] = [
            __(MySpaceLang::LANG_GROUP, 'Inicio') => [
                'url' => AdminPanelController::routeName(''),
            ],
            $title => [
                'url' => self::routeName('manual'),
            ],
            $chapter['titulo'],
        ];

        $this->helpController->render('panel/layout/header');
        $this->render('manual', $data);
        $this->helpController->render('panel/layout/footer');
        return $response;
    }

    /**
     * Los capítulos del manual que ve el usuario actual, en su idioma o, si el capítulo falta en él, en español.
     *
     * Lo ve quien tiene permiso sobre ALGUNA de las rutas de `ve`, si el capítulo la trae, o de `rutas` si no; o todos
     * si dice `todos`. `ve` separa lo administrativo de lo que puede mucha gente en un mismo capítulo (JDY). Con
     * `manual_for_pending` en false, una cuenta sin aprobar no ve ninguno.
     *
     * @param array<mixed>|null $index El índice que filtrar; sin él, el generado por bin/manual. Solo para la suite
     *        core/manual-visibility, que prueba la regla con uno sintético.
     * @return array<int,array{slug:string,titulo:string,rutas:string|string[],ve:string[],idioma:string,origen:string,fecha:string}>
     */
    public static function manualChapters(?array $index = null): array
    {
        $currentUser = getLoggedFrameworkUser();
        if ($currentUser === null || (get_config('manual_for_pending') === false && self::isPendingAccount($currentUser))) {
            return [];
        }
        if ($index === null) {
            $indexFile = __DIR__ . '/../Views/manual/index.php';
            $index = is_file($indexFile) ? require $indexFile : [];
            $index = is_array($index) ? $index : [];
        }
        $lang = Config::get_lang();
        $bySlug = [];
        foreach ($index as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $slug = $entry['slug'] ?? null;
            $chapterLang = $entry['idioma'] ?? null;
            $title = $entry['titulo'] ?? null;
            $origin = $entry['origen'] ?? null;
            $date = $entry['fecha'] ?? null;
            $routes = $entry['rutas'] ?? null;
            if (!is_string($slug) || !is_string($chapterLang) || !is_string($title) || !is_string($origin) || !is_string($date)) {
                continue;
            }
            $routes = $routes === 'todos' ? 'todos' : array_values(array_filter(is_array($routes) ? $routes : [], 'is_string'));
            $seenBy = $entry['ve'] ?? [];
            $seenBy = array_values(array_filter(is_array($seenBy) ? $seenBy : [], 'is_string'));
            //El del idioma actual gana; el español, solo si el capítulo falta en el idioma actual.
            if ($chapterLang === $lang || ($chapterLang === 'es' && !isset($bySlug[$slug]))) {
                $bySlug[$slug] = ['slug' => $slug, 'titulo' => $title, 'rutas' => $routes, 've' => $seenBy, 'idioma' => $chapterLang, 'origen' => $origin, 'fecha' => $date];
            }
        }
        ksort($bySlug);
        $visible = [];
        foreach ($bySlug as $entry) {
            $routes = $entry['rutas'];
            $allowed = $routes === 'todos';
            //Sin `ve`, deciden todas las que explica.
            $deciding = $entry['ve'] !== [] ? $entry['ve'] : (is_array($routes) ? $routes : []);
            foreach ($deciding as $routeName) {
                if (Roles::hasPermissions($routeName, (int) $currentUser->type)) {
                    $allowed = true;
                    break;
                }
            }
            if ($allowed) {
                $visible[] = $entry;
            }
        }
        return $visible;
    }

    /**
     * Si el usuario actual ve una sección del manual marcada con `<!-- ve: … -->` (H182): la regla del capítulo, permiso
     * sobre alguna de sus rutas. Una ruta no registrada (un módulo apagado) da false, también a root. La llaman las vistas
     * que genera bin/manual, con las rutas ya validadas.
     *
     * @param string[] $routes
     * @return bool
     */
    public static function manualSectionVisible(array $routes): bool
    {
        $currentUser = getLoggedFrameworkUser();
        if ($currentUser === null) {
            return false;
        }
        foreach ($routes as $routeName) {
            if (is_string($routeName) && Roles::hasPermissions($routeName, (int) $currentUser->type)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Si la cuenta está sin aprobar: la misma comprobación que el recorte de SystemApprovalsMiddleware.
     *
     * @param UserDataPackage $user
     * @return bool
     */
    public static function isPendingAccount(UserDataPackage $user): bool
    {
        if (!SystemApprovalsRoutes::ENABLE || (int) $user->type === UsersModel::TYPE_USER_ROOT) {
            return false;
        }
        $approved = SystemApprovalManager::getInstance()->isApproved(UsersModel::class, $user->id);
        return !($approved ? $approved : UsersApprovalHandler::isAutoApproval($user->getMapper()));
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

        SessionCookie::forget();

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
        //$currentUserID se queda por compatibilidad: la regla es una sola y la conoce Impersonation (H34).
        return Impersonation::isImpersonating();
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

            //El manual de usuario (ADR 0072): para todos; quién ve cada capítulo lo decide manualChapters().
            new Route(
                "{$startRoute}/manual[/]",
                $classname . ':manual',
                self::$baseRouteName . '-manual',
                'GET',
                true,
                null,
                $allRoles
            ),
            new Route(
                "{$startRoute}/manual/{slug}[/]",
                $classname . ':manual',
                self::$baseRouteName . '-manual-page',
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
