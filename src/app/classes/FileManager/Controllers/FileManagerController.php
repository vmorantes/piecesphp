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
use Slim\Http\Request as Request;
use Slim\Http\Response as Response;

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
        parent::__construct(false); //No cargar ningún modelo automáticamente.

        self::$title = __(self::LANG_GROUP, self::$title);

        $this->model = null;
        set_title(self::$title);

        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());
        $this->setInstanceViewDir(__DIR__ . '/../Views/');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return void
     */
    public function fileManager(Request $request, Response $response, array $args)
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
     * @param array $args
     * @return void
     */
    public function fileManagerConfiguration(Request $request, Response $response, array $args)
    {

        // https://github.com/Studio-42/elFinder/wiki/Connector-configuration-options

        $base = 'statics/filemanager';
        $basePath = basepath($base);

        $pathDir = basepath("{$base}/files");
        $pathTrashDir = basepath("{$base}/.trash");

        $pathImagesDir = basepath("{$base}/images");
        $pathImagesTrashDir = basepath("{$base}/.trash-images");

        $pathUploadsDir = basepath("statics/uploads");

        $pathTmpDir = basepath("tmp");

        if (!file_exists($basePath)) {
            mkdir($basePath, 0777);
        }
        if (!file_exists($pathDir)) {
            mkdir($pathDir, 0777);
        }
        if (!file_exists($pathTrashDir)) {
            mkdir($pathTrashDir, 0777);
        }
        if (!file_exists($pathImagesDir)) {
            mkdir($pathImagesDir, 0777);
        }
        if (!file_exists($pathImagesTrashDir)) {
            mkdir($pathImagesTrashDir, 0777);
        }
        if (!file_exists($pathUploadsDir)) {
            mkdir($pathUploadsDir, 0777);
        }
        if (decoct(fileperms($pathUploadsDir) & 0777) != 777) {
            @chmod($pathUploadsDir, 0777);
        }
        if (!file_exists($pathTmpDir)) {
            mkdir($pathTmpDir, 0777);
        }
        if (decoct(fileperms($pathTmpDir) & 0777) != 777) {
            @chmod($pathTmpDir, 0777);
        }

        $pathURL = baseurl("{$base}/files");
        $pathTrashTmbURL = baseurl("{$base}/.trash/.tmb");

        $pathImagesURL = baseurl("{$base}/images");
        $pathImagesTrashTmbURL = baseurl("{$base}/.trash-images/.tmb");

        $pathUploadsURL = baseurl("statics/uploads");
        $pathTmpUploadsURL = baseurl("tmp");

        $uploadDeny = array();
        $allowedMimeUploads = array('image');
        $uploadOrder = array('deny', 'allow');
        $accessControl = self::class . '::accessElFinderHandler';

        $opts = array(
            'roots' => array(
                // Items volume
                array(
                    'alias' => 'Archivos',
                    'driver' => 'LocalFileSystem',
                    'path' => $pathDir,
                    'URL' => $pathURL,
                    'trashHash' => 't1_Lw',
                    'winHashFix' => DIRECTORY_SEPARATOR !== '/',
                    'uploadDeny' => $uploadDeny,
                    'uploadAllow' => $allowedMimeUploads,
                    'uploadOrder' => $uploadOrder,
                    'accessControl' => $accessControl,
                ),
                array(
                    'alias' => 'Imágenes (Editor de texto)',
                    'driver' => 'LocalFileSystem',
                    'path' => $pathImagesDir,
                    'URL' => $pathImagesURL,
                    'trashHash' => 't2_Lw',
                    'winHashFix' => DIRECTORY_SEPARATOR !== '/',
                    'uploadDeny' => array('all'),
                    'uploadAllow' => array('image'),
                    'uploadOrder' => $uploadOrder,
                    'accessControl' => $accessControl,
                ),
                array(
                    'alias' => 'Cargas',
                    'driver' => 'LocalFileSystem',
                    'path' => $pathUploadsDir,
                    'URL' => $pathUploadsURL,
                    'trashHash' => 't1_Lw',
                    'winHashFix' => DIRECTORY_SEPARATOR !== '/',
                    'uploadDeny' => $uploadDeny,
                    'uploadAllow' => $allowedMimeUploads,
                    'uploadOrder' => $uploadOrder,
                    'accessControl' => $accessControl,
                ),
                array(
                    'alias' => 'Temporales',
                    'driver' => 'LocalFileSystem',
                    'path' => $pathTmpDir,
                    'URL' => $pathTmpUploadsURL,
                    'trashHash' => 't1_Lw',
                    'winHashFix' => DIRECTORY_SEPARATOR !== '/',
                    'uploadDeny' => $uploadDeny,
                    'uploadAllow' => $allowedMimeUploads,
                    'uploadOrder' => $uploadOrder,
                    'accessControl' => $accessControl,
                ),
                // Trash volume
                array(
                    'id' => '1',
                    'driver' => 'Trash',
                    'path' => $pathTrashDir,
                    'tmbURL' => $pathTrashTmbURL,
                    'winHashFix' => DIRECTORY_SEPARATOR !== '/',
                    'uploadDeny' => $uploadDeny,
                    'uploadAllow' => $allowedMimeUploads,
                    'uploadOrder' => $uploadOrder,
                    'accessControl' => $accessControl,
                ),
                array(
                    'id' => '2',
                    'alias' => 'Papelera' . ' (Imágenes)',
                    'driver' => 'Trash',
                    'path' => $pathImagesTrashDir,
                    'tmbURL' => $pathImagesTrashTmbURL,
                    'winHashFix' => DIRECTORY_SEPARATOR !== '/',
                    'uploadDeny' => array('all'),
                    'uploadAllow' => array('image'),
                    'uploadOrder' => $uploadOrder,
                    'accessControl' => $accessControl,
                ),
            ),
        );

        $connector = new elFinderConnector(new elFinder($opts));
        $connector->run();

        return $response;

    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return void
     */
    public function fileManagerConfigurationRichEditor(Request $request, Response $response, array $args)
    {

        // https://github.com/Studio-42/elFinder/wiki/Connector-configuration-options

        $base = 'statics/filemanager';
        $basePath = basepath($base);

        $pathDir = basepath("{$base}/images");
        $pathTrashDir = basepath("{$base}/.trash-images");

        if (!file_exists($basePath)) {
            mkdir($basePath, 0777);
        }
        if (!file_exists($pathDir)) {
            mkdir($pathDir, 0777);
        }
        if (!file_exists($pathTrashDir)) {
            mkdir($pathTrashDir, 0777);
        }

        $pathURL = baseurl("{$base}/images");
        $pathTrashTmbURL = baseurl("{$base}/.trash-images/.tmb");

        $uploadDeny = array('all');
        $allowedMimeUploads = array('image');
        $uploadOrder = array('deny', 'allow');
        $accessControl = self::class . '::accessElFinderHandler';

        $opts = array(
            'roots' => array(
                // Items volume
                array(
                    'alias' => 'Imágenes',
                    'driver' => 'LocalFileSystem',
                    'path' => $pathDir,
                    'URL' => $pathURL,
                    'trashHash' => 't1_Lw',
                    'winHashFix' => DIRECTORY_SEPARATOR !== '/',
                    'uploadDeny' => $uploadDeny,
                    'uploadAllow' => $allowedMimeUploads,
                    'uploadOrder' => $uploadOrder,
                    'accessControl' => $accessControl,
                ),
                // Trash volume
                array(
                    'id' => '1',
                    'driver' => 'Trash',
                    'path' => $pathTrashDir,
                    'tmbURL' => $pathTrashTmbURL,
                    'winHashFix' => DIRECTORY_SEPARATOR !== '/',
                    'uploadDeny' => $uploadDeny,
                    'uploadAllow' => $allowedMimeUploads,
                    'uploadOrder' => $uploadOrder,
                    'accessControl' => $accessControl,
                ),
            ),
        );

        $connector = new elFinderConnector(new elFinder($opts));
        $connector->run();

        return $response;

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
        return (new static )->render($name, $data, $mode, $format);
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
