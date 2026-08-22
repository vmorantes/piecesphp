<?php

/**
 * LocalizationSystemController.php
 */

namespace PiecesPHP\LocalizationSystem\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use MySpace\MySpaceLang;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;

/**
 * LocalizationSystemController.
 *
 * @package     PiecesPHP\LocalizationSystem\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class LocalizationSystemController extends AdminPanelController
{

    use ControllerRoutingTrait;

    /**
     * @var string
     */
    protected static $URLDirectory = 'localization-system-features';
    /**
     * @var string
     */
    protected static $baseRouteName = 'localization-system-features';

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
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function getLangMessagesByGroup(Request $request, Response $response)
    {

        $langGroups = $request->getQueryParam('group', []);
        $langGroups = is_array($langGroups) ? $langGroups : (
            is_string($langGroups) ? [$langGroups] : [uniqid()]
        );
        $langGroups = array_map(fn($e) => is_string($e) && mb_strlen($e) == 0 ? uniqid() : $e, $langGroups);
        $langGroups = !empty($langGroups) ? $langGroups : [uniqid()];
        $allTranslations = get_config('pcsphp_system_translations');
        $translationsByGroups = [];

        foreach ($langGroups as $langGroup) {
            $translations = array_map(function ($data) use ($langGroup) {
                foreach ($data as $group => $groupData) {
                    if ($group !== $langGroup) {
                        unset($data[$group]);
                    }
                }
                return $data;
            }, $allTranslations);
            $translations = array_filter($translations, function ($data) use ($langGroup) {
                return isset($data[$langGroup]);
            });
            if (!array_key_exists($langGroup, $translationsByGroups)) {
                $translationsByGroups[$langGroup] = [];
            }
            foreach ($translations as $lang => $langData) {
                $translationsByGroups[$langGroup][$lang] = array_key_exists($langGroup, $langData) ? $langData[$langGroup] : [];
            }
        }
        $headersAndStatus = generateCachingHeadersAndStatus($request, new \DateTime(date('Y-m-d 00:00:00')), json_encode($translationsByGroups, \JSON_THROW_ON_ERROR));
        foreach ($headersAndStatus['headers'] as $header => $value) {
            $response = $response->withHeader($header, $value);
        }
        $response = $response->withStatus($headersAndStatus['status']);

        if ($headersAndStatus['status'] !== 304) {
            $response = $response->withJson($translationsByGroups);
        }

        return $response;
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
            new Route(
                "{$startRoute}/get-lang-messages-by-group[/]",
                $classname . ':getLangMessagesByGroup',
                self::$baseRouteName . '-get-lang-messages-by-group',
                'GET',
                false
            ),

            //──── POST ───────────────────────────────────────────────────────────────────────────────

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
