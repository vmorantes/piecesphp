<?php

/**
 * FileManagerController.php
 */

namespace FileManager\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use elFinder;
use elFinderConnector;
use FileManager\FileManagerLang;
use FileManager\FileManagerRoutes;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;

/**
 * FileManagerController.
 *
 * @package     FileManager\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 */
class FileManagerController extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $URLDirectory = 'filemanager';
    /**
     * @var string
     */
    protected static $baseRouteName = 'filemanager-admin';
    /**
     * @var string
     */
    protected static $title = 'Gestor de archivos';

    /**
     * @var HelperController
     */
    protected $helpController = null;

    const BASE_VIEW_DIR = '';
    const BASE_JS_DIR = 'js';
    const LANG_GROUP = FileManagerLang::LANG_GROUP;

    public function __construct()
    {
        parent::__construct();

        self::$title = __(self::LANG_GROUP, self::$title);

        $this->model = null;
        set_title(self::$title);

        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());
        $this->setInstanceViewDir(__DIR__ . '/../Views/');

    }

    /**
     * @return void
     */
    public function fileManager()
    {

        $backLink = get_route('admin');
        $configurationRoute = self::routeName('filemanager-configuration');

        $title = self::$title;

        $data = [];
        $data['langGroup'] = self::LANG_GROUP;
        $data['backLink'] = $backLink;
        $data['configurationRoute'] = $configurationRoute;
        $data['title'] = $title;

        import_elfinder();
        import_jqueryui();

        set_custom_assets([
            FileManagerRoutes::staticRoute(self::BASE_JS_DIR . '/file-manager.js'),
        ], 'js');

        $this->helpController->render('panel/layout/header');
        self::view('file-manager', $data);
        $this->helpController->render('panel/layout/footer');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function fileManagerConfiguration(Request $request, Response $response)
    {

        $dirs = [
            [
                'alias' => 'Archivos',
                'relativePath' => 'files',
            ],
            [
                'alias' => 'Imágenes (Editor de texto)',
                'relativePath' => 'images',
                'trashHash' => 't2_Lw',
                'uploadDeny' => array('all'),
                'uploadAllow' => array('image'),
                'permissions' => 0777,
            ],
            [
                'alias' => 'Cargas',
                'relativePath' => 'statics/uploads',
                'withBasePath' => false,
                'permissions' => 0777,
            ],
            [
                'alias' => 'Temporales',
                'relativePath' => 'tmp',
                'withBasePath' => false,
                'permissions' => 0777,
            ],
        ];
        $trashes = [
            [
                'id' => '1',
                'relativePath' => '.trash',
            ],
            [
                'id' => '2',
                'relativePath' => '.trash-images',
                'alias' => 'Papelera' . ' (Imágenes - Editor de texto)',
                'uploadDeny' => array('all'),
                'uploadAllow' => array('image'),
            ],
        ];

        $opts = array(
            'roots' => self::structureOptions($dirs, $trashes),
        );

        $connector = new elFinderConnector(new elFinder($opts));
        $connector->run();

        return $response;

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function fileManagerConfigurationRichEditor(Request $request, Response $response)
    {

        $dirs = [
            [
                'alias' => 'Imágenes',
                'relativePath' => 'images',
                'trashHash' => 't2_Lw',
                'uploadDeny' => array('all'),
                'uploadAllow' => array('image'),
            ],
        ];
        $trashes = [
            [
                'id' => '2',
                'relativePath' => '.trash-images',
                'alias' => 'Papelera' . ' (Imágenes)',
                'uploadDeny' => array('all'),
                'uploadAllow' => array('image'),
            ],
        ];

        $opts = array(
            'roots' => self::structureOptions($dirs, $trashes),
        );

        $connector = new elFinderConnector(new elFinder($opts));
        $connector->run();

        return $response;

    }

    /**
     * @param array $dirs
     * @param array $trashes
     * @param string $base
     * @return array
     */
    public static function structureOptions(array $dirs, array $trashes, string $base = 'statics/filemanager')
    {

        //https://github.com/Studio-42/elFinder/wiki/Connector-configuration-options

        $basePath = basepath($base);

        $createOrder = [
            $basePath,
        ];
        $permissionsOrder = [];

        $accessControlDefault = self::class . '::accessElFinderHandler';

        $options = [];

        foreach ($dirs as $i) {

            $expectedOptions = [
                'driver' => 'LocalFileSystem',
                'trashHash' => 't1_Lw',
                'winHashFix' => DIRECTORY_SEPARATOR !== '/',
                'uploadDeny' => array(),
                'uploadAllow' => array('all'),
                'uploadOrder' => [
                    'deny',
                    'allow',
                ],
                'accessControl' => $accessControlDefault,
            ];

            foreach ($expectedOptions as $expectedOption => $defaultOptionValue) {
                if (!isset($i[$expectedOption])) {
                    if ($defaultOptionValue !== 'NO_VALUE') {
                        $i[$expectedOption] = $defaultOptionValue;
                    }
                }
            }

            $withBasePath = isset($i['withBasePath']) ? $i['withBasePath'] === true : true;
            $permissions = isset($i['permissions']) ? $i['permissions'] : null;

            $i['path'] = $withBasePath ? basepath("{$base}/{$i['relativePath']}") : basepath("{$i['relativePath']}");
            $i['URL'] = $withBasePath ? baseurl("{$base}/{$i['relativePath']}") : baseurl("{$i['relativePath']}");

            $createOrder[] = $i['path'];
            if ($permissions !== null) {
                $permissionsOrder[] = [
                    'path' => $i['path'],
                    'permissions' => $permissions,
                ];
            }

            $options[] = $i;

        }

        foreach ($trashes as $i) {

            $expectedOptions = [
                'driver' => 'Trash',
                'winHashFix' => DIRECTORY_SEPARATOR !== '/',
                'uploadDeny' => array(),
                'uploadAllow' => array('all'),
                'uploadOrder' => [
                    'deny',
                    'allow',
                ],
                'accessControl' => $accessControlDefault,
            ];

            foreach ($expectedOptions as $expectedOption => $defaultOptionValue) {
                if (!isset($i[$expectedOption])) {
                    if ($defaultOptionValue !== 'NO_VALUE') {
                        $i[$expectedOption] = $defaultOptionValue;
                    }
                }
            }

            $withBasePath = isset($i['withBasePath']) ? $i['withBasePath'] === true : true;
            $permissions = isset($i['permissions']) ? $i['permissions'] : null;

            $i['path'] = $withBasePath ? basepath("{$base}/{$i['relativePath']}") : basepath("{$i['relativePath']}");
            $i['tmbURL'] = $withBasePath ? baseurl("{$base}/{$i['relativePath']}/.tmb") : baseurl("{$i['relativePath']}/.tmb");

            $createOrder[] = $i['path'];
            if ($permissions !== null) {
                $permissionsOrder[] = [
                    'path' => $i['path'],
                    'permissions' => $permissions,
                ];
            }

            $options[] = $i;

        }

        $removeKeys = [
            'relativePath',
            'withBasePath',
            'permissions',
        ];
        foreach ($options as $k => $i) {
            foreach ($i as $ik => $j) {
                if (in_array($ik, $removeKeys)) {
                    unset($options[$k][$ik]);
                }
            }
        }
        foreach ($createOrder as $iPath) {
            if (!file_exists($iPath)) {
                mkdir($iPath, 0777);
            }
        }

        foreach ($permissionsOrder as $iPathData) {
            $iPath = $iPathData['path'];
            $iToPermissionsOctal = $iPathData['permissions'];
            $iToPermissionsDecimalRepresentation = decoct($iToPermissionsOctal & 0777);
            $iCurrentPermissions = fileperms($iPath);
            $iCurrentPermissionsDecimalRepresentation = decoct($iCurrentPermissions & 0777);

            if ($iCurrentPermissionsDecimalRepresentation != $iToPermissionsDecimalRepresentation) {
                @chmod($iPath, $iToPermissionsDecimalRepresentation);
            }
        }

        return $options;

    }

    /**
     * Simple function to demonstrate how to control file access using "accessControl" callback.
     * This method will disable accessing files/folders starting from '.' (dot)
     *
     * @param  string    $attr    attribute name (read|write|locked|hidden)
     * @param  string    $path    absolute file path
     * @param  string    $data    value of volume option `accessControlData`
     * @param  object    $volume  elFinder volume driver object
     * @param  bool|null $isDir   path is directory (true: directory, false: file, null: unknown)
     * @param  string    $relpath file path relative to volume root directory started with directory separator
     * @return bool|null
     **/
    public static function accessElFinderHandler($attr, $path, $data, $volume, $isDir, $relpath)
    {
        $basename = basename($path);
        return $basename[0] === '.' // if file/folder begins with '.' (dot)
         && strlen($relpath) !== 1// but with out volume root
         ? !($attr == 'read' || $attr == 'write') // set read+write to false, other (locked+hidden) set to true
         : null; // else elFinder decide it itself
    }

    /**
     * @param string $name
     * @param array $data
     * @param bool $mode
     * @param bool $format
     * @return void|string
     */
    public static function view(string $name, array $data = [], bool $mode = true, bool $format = true)
    {
        $name = trim(self::BASE_VIEW_DIR . '/' . trim($name, '/'), '/');
        return (new FileManagerController)->render($name, $data, $mode, $format);
    }

    /**
     * @param string $name
     * @param array $params
     * @return bool
     */
    public static function allowedRoute(string $name, array $params = [])
    {

        $route = self::routeName($name, $params, true);
        $allow = strlen($route) > 0;

        if ($allow) {

            if ($name == 'SAMPLE') { //do something
            }

        }

        return $allow;
    }

    /**
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

        $name = !is_null($name) ? self::$baseRouteName . $name : self::$baseRouteName;

        $allowed = false;
        $current_user = getLoggedFrameworkUser();

        if ($current_user !== null) {
            $allowed = Roles::hasPermissions($name, $current_user->type);
        } else {
            $allowed = true;
        }

        if ($allowed) {
            $routeResult = get_route(
                $name,
                $params,
                $silentOnNotExists
            );
            return is_string($routeResult) ? $routeResult : '';
        } else {
            return '';
        }
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
        $all_roles = array_keys(UsersModel::TYPES_USERS);

        $permisos_estados_gestion = [
            UsersModel::TYPE_USER_ROOT,
        ];

        $routes = [

            //──── GET ───────────────────────────────────────────────────────────────────────────────
            //HTML
            new Route( //Vista del listado
                "{$startRoute}[/]",
                $classname . ':fileManager',
                self::$baseRouteName . '-filemanager',
                'GET',
                true,
                null,
                $permisos_estados_gestion
            ),
            //JSON
            new Route(
                "{$startRoute}/configuration[/]",
                $classname . ':fileManagerConfiguration',
                self::$baseRouteName . '-filemanager-configuration',
                'GET|POST',
                true,
                null,
                $permisos_estados_gestion
            ),
            new Route(
                "{$startRoute}/rich-editor/configuration[/]",
                $classname . ':fileManagerConfigurationRichEditor',
                self::$baseRouteName . '-filemanager-configuration-rich-editor',
                'GET|POST',
                true,
                null,
                $all_roles
            ),

        ];

        $group->register($routes);

        return $group;
    }
}
