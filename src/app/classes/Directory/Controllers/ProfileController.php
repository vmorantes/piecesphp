<?php

/**
 * ProfileController.php
 */

namespace Directory\Controllers;

use Directory\DirectoryLang;
use Directory\DirectoryRoutes;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * ProfileController.
 *
 * @package     Directory\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class ProfileController extends AdminPanelController
{

    use ControllerRoutingTrait;

    /**
     * @var string
     */
    protected static $URLDirectory = 'profile';
    /**
     * @var string
     */
    protected static $baseRouteName = 'profile-admin';

    /**
     * @var HelperController
     */
    protected $helpController = null;

    const BASE_JS_DIR = 'js';
    const BASE_CSS_DIR = 'css';
    const LANG_GROUP = DirectoryLang::LANG_GROUP;

    public function __construct()
    {
        parent::__construct();

        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());

        $this->setInstanceViewDir(__DIR__ . '/../Views/profile');

        add_global_asset(DirectoryRoutes::staticRoute('globals-vars.css'), 'css');
        add_global_asset(DirectoryRoutes::staticRoute(self::BASE_CSS_DIR . '/profile.css'), 'css');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return void
     */
    public function profileView(Request $request, Response $response, array $args = [])
    {

        $userID = $args['userID'] ?? null;
        $userID = Validator::isInteger($userID) ? (int) $userID : null;
        $userOfProfile = null;
        try {
            $userOfProfile = new UserDataPackage($userID);
        } catch (\Exception) {}

        //Un usuario Eliminado deja de existir en la práctica (PO, 2026-10-16): su perfil no existe para nadie, root incluido.
        if ($userOfProfile !== null && (int) $userOfProfile->status === UsersModel::STATUS_USER_DELETED) {
            $userOfProfile = null;
        }

        //Un perfil oculto (P136) es, para los demás, como uno que no existe: solo lo ven su dueño y root.
        $viewer = getLoggedFrameworkUser();
        if ($userOfProfile !== null && (int) ($userOfProfile->profile->publicVisible ?? 1) === 0
            && ($viewer === null || ((int) $viewer->id !== (int) $userOfProfile->id && (int) $viewer->type !== UsersModel::TYPE_USER_ROOT))) {
            $userOfProfile = null;
        }

        if ($userOfProfile !== null) {

            remove_imported_asset('locations');
            import_locations([], false, true);
            set_custom_assets([
                DirectoryRoutes::staticRoute(self::BASE_JS_DIR . '/profile.js'),
            ], 'js');

            $title = __(self::LANG_GROUP, 'Perfil');
            $description = __(self::LANG_GROUP, 'La ficha pública de una persona del Directorio.');
            $fullName = $userOfProfile->getMapper()->getFullName();
            set_title($title . ' - ' . $fullName);

            $data = [];
            $data['langGroup'] = self::LANG_GROUP;
            $data['currentUser'] = getLoggedFrameworkUserOrFail();
            $data['userOfProfile'] = $userOfProfile;
            $data['title'] = $title;
            $data['description'] = $description;
            $data['helpController'] = $this->helpController;
            $breadcrumbs = [
                __(self::LANG_GROUP, 'Inicio') => [
                    'url' => \PiecesPHP\AdminPanel\Controllers\AdminPanelController::routeName(''),
                ],
            ];
            if (DirectoryController::allowedRoute('list')) {
                $breadcrumbs[__(self::LANG_GROUP, 'Directorio')] = ['url' => DirectoryController::routeName('list')];
            }
            $breadcrumbs[] = $fullName;
            $data['breadcrumbs'] = $breadcrumbs;

            if ($userOfProfile->profile->isComplete()) {
                $this->helpController->render('panel/layout/header');
                if (in_array($userOfProfile->type, UsersModel::TYPES_USER_SHOULD_HAVE_PROFILE)) {
                    $this->render('profile', $data);
                }
                $this->helpController->render('panel/layout/footer');
            } else {
                $this->helpController->render('panel/layout/header');
                if (in_array($userOfProfile->type, UsersModel::TYPES_USER_SHOULD_HAVE_PROFILE)) {
                    $this->render('profile-not-completed', $data);
                }
                $this->helpController->render('panel/layout/footer');
            }

        } else {
            throw new NotFoundException($request, $response);
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

        //Rutas
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
                "{$startRoute}/{userID}[/]",
                $classname . ':profileView',
                self::$baseRouteName . '-profile',
                'GET',
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
