<?php

/**
 * PublicAreaController.php
 */

namespace App\Controller;

use App\Model\AvatarModel;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Utilities\OsTicket\OsTicketAPI;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

/**
 * PublicAreaController.
 *
 * Controlador del área pública
 *
 * @package     App\Controller
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class PublicAreaController extends \PiecesPHP\Core\BaseController
{

    /**
     * $prefixNameRoutes
     *
     * @var string
     */
    private static $prefixNameRoutes = 'public';

    /**
     * $startSegmentRoutes
     *
     * @var string
     */
    private static $startSegmentRoutes = '';

    /**
     * $automaticImports
     *
     * @var bool
     */
    private static $automaticImports = true;

    /**
     * $user
     *
     * Usuario logueado
     *
     * @var \stdClass
     */
    protected $user = null;

    /**
     * __construct
     *
     * @return static
     */
    public function __construct()
    {
        parent::__construct(false); //No cargar ningún modelo automáticamente

        $this->init();

        if (self::$automaticImports === true) {

            /* JQuery */
            import_jquery();
            /* Semantic */
            import_semantic();
            /* NProgress */
            import_nprogress();
            /* Librerías de la aplicación */
			import_app_libraries();
			
			add_global_asset(base_url('statics/css/global.css'), 'css');
			
        }
    }

    /**
     * indexView
     *
     * Vista principal
     *
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return void
     */
    public function indexView(Request $req, Response $res, array $args)
    {

		import_quilljs();
		
        $this->render('layout/header');
        $this->render('pages/sample-public');
        $this->render('layout/footer');

        return $res;
    }

    /**
     * routeName
     *
     * @param string $name
     * @param array $params
     * @param bool $silentOnNotExists
     * @return string
     */
    public static function routeName(string $name = null, array $params = [], bool $silentOnNotExists = false)
    {
        if (!is_null($name)) {
            $name = trim($name);
            $name = strlen($name) > 0 ? "-{$name}" : '';
        }

        $name = !is_null($name) ? self::$prefixNameRoutes . $name : self::$prefixNameRoutes;

        $allowed = false;
        $current_user = get_config('current_user');

        if ($current_user != false) {
            $allowed = Roles::hasPermissions($name, (int) $current_user->type);
        } else {
            $allowed = true;
        }

        if ($allowed) {
            return get_route(
                $name,
                $params,
                $silentOnNotExists
            );
        } else {
            return '';
        }
    }

    /**
     * routes
     *
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {

        $groupSegmentURL = $group->getGroupSegment();

        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = $lastIsBar ? '' : '/';

        //Otras rutas
        $namePrefix = self::$prefixNameRoutes;
        $startRoute .= self::$startSegmentRoutes;

        //──── GET ─────────────────────────────────────────────────────────────────────────

        //Generales
        $group->register([
            new Route(
                "{$startRoute}[/]",
                self::class . ":indexView",
                "{$namePrefix}-index",
                'GET'
            ),
        ]);

        //──── POST ─────────────────────────────────────────────────────────────────────────

        return $group;
    }

    /**
     * init
     *
     * @return void
     */
    protected function init()
    {
        $api_url = get_config('osTicketAPI');
        $api_key = get_config('osTicketAPIKey');

        OsTicketAPI::setBaseURL($api_url);
        OsTicketAPI::setBaseAPIKey($api_key);

        $view_data = [];
        $this->user = get_config('current_user');

        if ($this->user instanceof \stdClass) {
            $view_data['user'] = $this->user;
            $this->user->avatar = AvatarModel::getAvatar($this->user->id);
            $this->user->hasAvatar = !is_null($this->user->avatar);
            $this->user->id = BaseHashEncryption::encrypt(base64_encode($this->user->id), self::class);
            unset($this->user->password);
        }

        $this->setVariables($view_data);

    }
}
