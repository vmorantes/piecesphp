<?php

/**
 * ArticleControllerPublic.php
 */

namespace PiecesPHP\BuiltIn\Article\Controllers;

use App\Model\AvatarModel;
use PiecesPHP\BuiltIn\Article\Controllers\ArticleController;
use PiecesPHP\BuiltIn\Article\Mappers\ArticleMapper;
use PiecesPHP\Core\BaseController;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Utilities\Helpers\MetaTags;
use PiecesPHP\Core\Utilities\OsTicket\OsTicketAPI;
use Slim\Exception\NotFoundException;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

/**
 * ArticleControllerPublic.
 *
 * @package     PiecesPHP\BuiltIn\Article\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class ArticleControllerPublic extends BaseController
{

    /**
     * $prefixNameRoutes
     *
     * @var string
     */
    private static $prefixNameRoutes = 'built-in-articles-public';

    /**
     * $startSegmentRoutes
     *
     * @var string
     */
    private static $startSegmentRoutes = '';

    const CSS_FOLDER = 'statics/css/built-in/article';
    const JS_FOLDER = 'statics/js/built-in/article';
    const VIEWS_FOLDER = 'pages/built-in/article';

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

        /* JQuery */
        import_jquery();
        /* Semantic */
        import_semantic();
        /* NProgress */
        import_nprogress();
        /* Librerías de la aplicación */
        import_app_libraries();

        add_global_asset(base_url('statics/css/global.css'), 'css');
        add_global_asset(base_url(self::JS_FOLDER . '/BuiltInArticle.js'), 'js');
    }

    public function listView(Request $req, Response $res, array $params)
    {
        set_custom_assets([
            base_url(self::JS_FOLDER . '/list.js'),
        ], 'js');

        set_title("Listado");

        $this->render('layout/header');
        $this->render('layout/menu');

        $this->render(self::VIEWS_FOLDER . '/list', [
            'ajaxURL' => ArticleController::routeName('ajax-all'),
            'singleURL' => self::routeName('single', ['friendly_name' => '{{friendly_name}}']),
        ]);

        $this->render('layout/footer');

        return $res;
    }

    public function single(Request $req, Response $res, array $params)
    {
        $friendly_name = $req->getAttribute('friendly_name');

        $article = ArticleMapper::getByFriendlyURL($friendly_name, true);

        if ($article !== null) {

            set_title($article->title);
            MetaTags::setDescription($article->content);
            MetaTags::setImage(base_url($article->meta->imageMain));

            $reference_date = !is_null($article->start_date) ? $article->start_date : $article->created;
            $day_created = $reference_date->format('d');
            $month_created = num_month_to_text($reference_date->format('d-m-Y'));
            $year_created = $reference_date->format('Y');

            $date_created = "{$day_created} de {$month_created}, {$year_created}";

            $relateds = ArticleMapper::allByCategory($article->category->id, true, $article->id, true, 1, 3);

            $this->render('layout/header');
            $this->render('layout/menu');
            $this->render(self::VIEWS_FOLDER . '/single', [
                'article' => $article,
                'date_created' => $date_created,
                'relateds' => $relateds,
            ]);
            $this->render('layout/footer');

        } else {
            throw new NotFoundException($req, $res);
        }

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
        $startRoute = strlen(self::$startSegmentRoutes) > 0 ? $startRoute : '';

        //Otras rutas
        $namePrefix = self::$prefixNameRoutes;
        $startRoute .= self::$startSegmentRoutes;

        //──── GET ─────────────────────────────────────────────────────────────────────────

        //Generales
        $group->register([
            new Route(
                "{$startRoute}[/]",
                self::class . ":listView",
                "{$namePrefix}-list",
                'GET'
            ),
            new Route(
                "{$startRoute}/{friendly_name}[/]",
                self::class . ":single",
                "{$namePrefix}-single",
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
