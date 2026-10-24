<?php

/**
 * OrganizationProfileController.php
 */

namespace Organizations\Controllers;

use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Organizations\OrganizationsLang;
use Organizations\OrganizationsRoutes;
use Directory\Controllers\DirectoryController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * OrganizationProfileController.
 *
 * @package     Organizations\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class OrganizationProfileController extends AdminPanelController
{

    use ControllerRoutingTrait;

    /**
     * @var string
     */
    protected static $URLDirectory = 'profile-organization';
    /**
     * @var string
     */
    protected static $baseRouteName = 'profile-organization-admin';

    /**
     * @var HelperController
     */
    protected $helpController = null;

    const BASE_JS_DIR = 'js';
    const BASE_CSS_DIR = 'css';
    const LANG_GROUP = OrganizationsLang::LANG_GROUP;

    public function __construct()
    {
        parent::__construct();

        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());

        $this->setInstanceViewDir(__DIR__ . '/../Views/profile-organization');

        add_global_asset(OrganizationsRoutes::staticRoute('globals-vars.css'), 'css');
        add_global_asset(OrganizationsRoutes::staticRoute(self::BASE_CSS_DIR . '/profile-organization.css'), 'css');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return void
     */
    public function profileView(Request $request, Response $response, array $args = [])
    {

        $organizationID = $args['organizationID'] ?? null;
        $organizationID = Validator::isInteger($organizationID) ? (int) $organizationID : null;
        $adminUser = null;
        $organizationMapper = new OrganizationMapper($organizationID);
        //El encargado es opcional: el perfil es de la organización y se pinta sin él (#1433 H2).
        $administrator = $organizationMapper->administrator;
        $administrator = $administrator instanceof UsersModel && $administrator->id !== null ? $administrator->id : (is_int($administrator) ? $administrator : null);
        if ($administrator !== null) {
            try {
                $adminUser = new UserDataPackage($administrator);
            } catch (\Exception) {}
        }

        //Una organización oculta (P136) es, para los demás, como una que no existe: solo la ven su encargado y root.
        $viewer = getLoggedFrameworkUser();
        $visible = $organizationMapper->id !== null && ((int) ($organizationMapper->publicVisible ?? 1) !== 0
            || ($viewer !== null && ((int) $viewer->type === UsersModel::TYPE_USER_ROOT || ($adminUser !== null && (int) $adminUser->id === (int) $viewer->id))));

        if ($visible) {

            remove_imported_asset('locations');
            import_locations([], false, true);
            set_custom_assets([
                OrganizationsRoutes::staticRoute(self::BASE_JS_DIR . '/profile-organization.js'),
            ], 'js');

            $title = __(self::LANG_GROUP, 'Perfil de organización');
            $description = __(self::LANG_GROUP, 'La ficha pública de una organización del Directorio.');
            $name = (string) $organizationMapper->currentLangData('name');
            set_title($title . ' - ' . $name);

            $data = [];
            $data['langGroup'] = self::LANG_GROUP;
            $data['currentUser'] = getLoggedFrameworkUserOrFail();
            $data['organizationMapper'] = $organizationMapper;
            $data['adminUser'] = $adminUser;
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
            $breadcrumbs[] = $name;
            $data['breadcrumbs'] = $breadcrumbs;

            if ($organizationMapper->profileIsComplete()) {
                $this->helpController->render('panel/layout/header');
                $this->render('profile', $data);
                $this->helpController->render('panel/layout/footer');
            } else {
                $this->helpController->render('panel/layout/header');
                $this->render('profile-not-completed', $data);
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
                "{$startRoute}/{organizationID}[/]",
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
