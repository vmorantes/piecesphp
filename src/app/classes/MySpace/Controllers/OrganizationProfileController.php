<?php

/**
 * OrganizationProfileController.php
 */

namespace MySpace\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use MySpace\MySpaceLang;
use MySpace\MySpaceRoutes;
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
 * @package     MySpace\Controllers
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
    const LANG_GROUP = MySpaceLang::LANG_GROUP;

    public function __construct()
    {
        parent::__construct();

        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());

        $this->setInstanceViewDir(__DIR__ . '/../Views/profile-organization');

        add_global_asset(MySpaceRoutes::staticRoute('globals-vars.css'), 'css');
        add_global_asset(MySpaceRoutes::staticRoute(self::BASE_CSS_DIR . '/profile-organization.css'), 'css');

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
        try {
            $administrator = $organizationMapper->administrator;
            $administrator = $administrator !== null && $administrator->id !== null ? $administrator->id : -1;
            $adminUser = new UserDataPackage($administrator);
        } catch (\Exception) {}

        if ($organizationMapper->id !== null && $adminUser !== null) {

            remove_imported_asset('locations');
            import_locations([], false, true);
            set_custom_assets([
                MySpaceRoutes::staticRoute(self::BASE_JS_DIR . '/profile-organization.js'),
            ], 'js');

            $title = __(self::LANG_GROUP, 'Perfil de organización');
            $description = '';
            set_title($title . (mb_strlen($description) > 0 ? " - {$description}" : ''));

            $data = [];
            $data['langGroup'] = self::LANG_GROUP;
            $data['currentUser'] = getLoggedFrameworkUser();
            $data['organizationMapper'] = $organizationMapper;
            $data['adminUser'] = $adminUser;
            $data['title'] = $title;
            $data['description'] = $description;
            $data['breadcrumbs'] = get_breadcrumbs([
                __(self::LANG_GROUP, 'Inicio') => [
                    'url' => get_route('admin'),
                ],
                $title,
            ]);

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
}
