<?php

/**
 * ImporterController.php
 */
namespace Importers\Controller;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use Importers\Managers\ImporterUsers;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Importer\Collections\ImporterCollection;
use PiecesPHP\Core\Importer\Importer;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\Core\ServerStatics;
use PiecesPHP\CSSVariables;
use PiecesPHP\LangInjector;
use Slim\Exception\HttpForbiddenException;
use \PiecesPHP\Core\Routing\RequestRoute as Request;
use \PiecesPHP\Core\Routing\ResponseRoute as Response;

/**
 * ImporterController.
 *
 * @package     Importers\Controller
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class ImporterController extends AdminPanelController
{

    /**
     * @var ImporterCollection
     */
    protected $importers = null;

    /**
     * @var HelperController
     */
    protected $helpController = null;

    /**
     * @var array
     */
    protected $views = [];

    /**
     * @var string[]
     */
    protected $texts = [];

    /**
     * @var array<string,string[]>
     */
    protected $js = [];

    /**
     * @var array<string,string[]>
     */
    protected $css = [];

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var string[]
     */
    protected $titles = [];

    /**
     * @var array<string,int[]>
     */
    protected $customPermissions = [];

    /**
     * @var string
     */
    protected $defaultView = 'generic';

    const LANG_GROUP = 'importerModule';
    const ENABLE = IMPORTS_MODULE_ENABLED;

    public function __construct()
    {
        $this->importers = new StringArray([
            'users' => ImporterUsers::class,
        ]);
        $this->views = [];
        $this->texts = [];
        $this->js = [];
        $this->css = [];
        $this->data = [];
        $this->customPermissions = [];

        foreach ($this->importers as $importerKey => $importerClassname) {
            $staticMethodsToCall = [
                //Método que se llamará => Propiedad que rellenará
                'title' => "titles",
                'view' => "views",
                'text' => "texts",
                'js' => "js",
                'css' => "css",
                'data' => "data",
            ];
            $staticMethodsToCallQty = count($staticMethodsToCall);
            $staticMethodsCalledCount = 0;
            foreach ($staticMethodsToCall as $staticMethodName => $propertyToAffect) {
                if (method_exists($importerClassname, $staticMethodName)) {
                    $this->$propertyToAffect[$importerKey] = call_user_func("{$importerClassname}::{$staticMethodName}");
                    $staticMethodsCalledCount++;
                }
            }
            //Si no existen todos los métodos no se agrega
            if ($staticMethodsCalledCount != $staticMethodsToCallQty) {
                unset($this->importers[$importerKey]);
            } else {
                //Variables disponibles por defecto
                $defaultVars = [
                    //Descripción
                    'description' => __(self::LANG_GROUP, 'Importador'),
                    //Breadcrumbs
                    'breadcrumbs' => get_breadcrumbs([
                        __(self::LANG_GROUP, 'Inicio') => [
                            'url' => get_route('admin'),
                        ],
                        $this->titles[$importerKey],
                    ]),
                ];
                foreach ($defaultVars as $defaultVarName => $defaultVarValue) {
                    if (!array_key_exists($defaultVarName, $this->data[$importerKey])) {
                        $this->data[$importerKey][$defaultVarName] = $defaultVarValue;
                    }
                }

                //Verificar si se importan los estilos base (por defecto sí)
                $importDefaultStyles = true;
                if (method_exists($importerClassname, 'importDefaultStyles')) {
                    $importDefaultStyles = call_user_func("{$importerClassname}::importDefaultStyles");
                }
                if ($importDefaultStyles) {
                    $importerCSS = $this->css[$importerKey];
                    array_unshift($importerCSS, self::staticRoute('css/importers.css'));
                    $this->css[$importerKey] = $importerCSS;
                }

                //Verificar si tiene roles personalizados para sus permisos
                $this->customPermissions[$importerKey] = [];
                if (method_exists($importerClassname, 'overwritePermissions')) {
                    $overwritePermissions = call_user_func("{$importerClassname}::overwritePermissions");
                    if (!empty($overwritePermissions)) {
                        $this->customPermissions[$importerKey] = $overwritePermissions;
                    }
                }
            }
        }

        parent::__construct();

        $this->helpController = new HelperController(getLoggedFrameworkUser(), $this->getGlobalVariables());
        $this->setInstanceViewDir(__DIR__ . '/../Views/');

        import_izitoast();
    }

    /**
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return Response
     * @throws NotFoundException
     */
    public function importForm(Request $req, Response $res, array $args)
    {
        $type = $args['type'];
        $this->verifyCustomPermissions($req, $type);

        if ($this->existsImporter($type) && $this->isValidImporter($type)) {

            $js = $this->getJs($type);
            $css = $this->getCss($type);

            if (!empty($js)) {
                set_custom_assets($js, 'js');
            }

            if (!empty($css)) {
                set_custom_assets($css, 'css');
            }

            $data = $this->getData($type);

            $data['title'] = $this->getImporter($type, [])->getTitle();

            $data['text'] = $this->getImporter($type, [])->getDescription();

            if (mb_strlen($data['text']) == 0) {
                $data['text'] = $this->getText($type);
            }

            $data['action'] = get_route('importer-action', ['type' => $type]);

            $data['template'] = get_route('importer-template', ['type' => $type]);

            $this->helpController->render('panel/layout/header');
            $this->render($this->getView($type), $data);
            $this->helpController->render('panel/layout/footer');

            return $res;

        } else {
            throw new NotFoundException($req, $res);
        }
    }

    /**
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return Response
     */
    public function import(Request $req, Response $res, array $args)
    {
        $data = $this->processFile($_FILES);
        $type = $args['type'];
        $this->verifyCustomPermissions($req, $type);

        $json_response = [
            'success' => false,
            'message' => '',
            'messages' => null,
            'total' => null,
            'inserted' => null,
        ];

        if ($data !== null) {

            if ($this->existsImporter($type)) {

                if ($this->isValidImporter($type)) {

                    $importador = $this->getImporter($type, $data);
                    $importador->import();

                    $json_response['messages'] = $importador->getResponses();
                    $json_response['total'] = $importador->getTotalProcessed();
                    $json_response['inserted'] = $importador->getTotalImported();
                    $json_response['success'] = true;

                } else {
                    $json_response['message'] = __(self::LANG_GROUP, 'El importador no es válido.');
                }

            } else {
                $json_response['message'] = __(self::LANG_GROUP, 'No existe el importador solicitado.');
            }

        } else {
            $json_response['message'] = __(self::LANG_GROUP, 'Ha ocurrido un error.');
        }

        return $res->withJson($json_response);
    }

    /**
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return Response
     * @throws NotFoundException
     */
    public function templates(Request $req, Response $res, array $args)
    {
        $type = $args['type'];
        $this->verifyCustomPermissions($req, $type);

        if ($this->existsImporter($type)) {

            if ($this->isValidImporter($type)) {

                $importer = $this->getImporter($type, []);
                $importerClassname = get_class($importer);
                $schema = $importer->getSchema();
                $template = $schema->template();
                $name = method_exists($importerClassname, 'templateTitle') ? call_user_func("{$importerClassname}::templateTitle") : __(self::LANG_GROUP, 'plantilla');

                ob_start();
                $template->save('php://output');
                $fileData = ob_get_contents();
                ob_end_clean();

                return $res->write($fileData)
                    ->withHeader('Content-Type', 'application/force-download')
                    ->withHeader('Content-Description', 'File Transfer')
                    ->withHeader('Content-Transfer-Encoding', 'binary')
                    ->withHeader('Content-Disposition', 'attachment; filename="' . $name . '.xlsx"');

            }

        } else {
            throw new NotFoundException($req, $res);
        }

    }

    /**
     * @param Request $req
     * @param string $type
     * @throws HttpForbiddenException
     * @return void
     */
    public function verifyCustomPermissions(Request $req, string $type)
    {
        if ($this->existsImporter($type) && $this->isValidImporter($type)) {
            $customPermissions = $this->customPermissions[$type];
            if (!empty($customPermissions)) {
                $currentUser = getLoggedFrameworkUser();
                if (!in_array($currentUser->type, $customPermissions)) {
                    throw403($req, [
                        'url' => get_route('admin'),
                        'showReportButton' => false,
                    ]);
                }
            }
        }
    }

    /**
     * @param string $type
     * @param array $data
     * @return Importer
     */
    public function getImporter(string $type, array $data)
    {
        $importer = null;
        if ($this->existsImporter($type)) {
            $importName = $this->importers[$type];
            $importer = new $importName($data);
        }
        return $importer;
    }

    /**
     * @param string $type
     * @return string
     */
    public function getView(string $type)
    {
        $view = $this->defaultView;

        if (array_key_exists($type, $this->views)) {
            $view = $this->views[$type];
        }

        return $view;
    }

    /**
     * @param string $type
     * @return string
     */
    public function getText(string $type)
    {
        $text = '';

        if (array_key_exists($type, $this->texts)) {
            $text = $this->texts[$type];
        }

        return $text;
    }

    /**
     * @param string $type
     * @return string[]
     */
    public function getJs(string $type)
    {
        $js = [];

        if (array_key_exists($type, $this->js)) {
            $js = $this->js[$type];
        }

        return $js;
    }

    /**
     * @param string $type
     * @return string[]
     */
    public function getCss(string $type)
    {
        $css = [];

        if (array_key_exists($type, $this->css)) {
            $css = $this->css[$type];
        }

        return $css;
    }

    /**
     * @param string $type
     * @return array
     */
    public function getData(string $type)
    {
        $data = [];

        if (array_key_exists($type, $this->data)) {
            $data = $this->data[$type];
        }

        return $data;
    }

    /**
     * @param string $type
     * @return string
     */
    public function getTitle(string $type)
    {
        $title = "";

        if (array_key_exists($type, $this->titles)) {
            $title = $this->titles[$type];
        }

        return $title;
    }

    /**
     * @param string $type
     * @return bool
     */
    public function existsImporter(string $type)
    {
        return isset($this->importers[$type]) && is_string($this->importers[$type]);
    }

    /**
     * @param string $type
     * @return bool
     */
    public function isValidImporter(string $type)
    {
        if ($this->existsImporter($type)) {
            $importName = $this->importers[$type];
            return $importName == Importer::class || is_subclass_of($importName, Importer::class);
        } else {
            return false;
        }
    }

    public function readFile($file, $sheet = 0)
    {
        $libro = IOFactory::load($file);
        $sheets_names = $libro->getSheetNames();
        $sheet = $libro->getSheet($sheet);
        $row_iterator = $sheet->getRowIterator();
        $data = [];
        $titles = [];

        foreach ($row_iterator as $index_row => $row) {

            $cell_iterator = $row->getCellIterator();
            $cell_iterator->setIterateOnlyExistingCells(true);

            foreach ($cell_iterator as $index_cell => $cell) {

                $cellValue = $cell->getValue();
                $cellValue = !is_null($cellValue) ? $cellValue : 'NULL';
                $cellValue = is_scalar($cellValue) ? (string) $cellValue : 'NULL';

                if ($index_row == 1) {

                    $titles[$index_cell] = $cellValue;

                } else {

                    if (!isset($titles[$index_cell])) {
                        continue;
                    }

                    $column_name = $titles[$index_cell];
                    if (!isset($data[$index_row])) {
                        $data[$index_row] = [];
                    }

                    $data[$index_row][$column_name] = $cellValue;

                }

            }

        }
        return $data;
    }

    /**
     * @param array $files
     * @return array|null
     */
    private function processFile(array $files)
    {

        $data = null;

        if (isset($files['archivo'])) {

            $archivo = $files['archivo'];

            if ($archivo['error'] == \UPLOAD_ERR_OK) {

                $data = $this->readFile($archivo['tmp_name']);

            }
        }

        return $data;
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {

        self::staticResolver($group);
        $injector = new LangInjector(__DIR__ . '/../lang', Config::get_allowed_langs());
        $injector->injectGroup(self::LANG_GROUP);

        $groupSegmentURL = $group->getGroupSegment();
        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = $lastIsBar ? '' : '/';
        $classname = ImporterController::class;
        $routes = [];
        /**
         * @var array<string>
         */
        $all_roles = array_keys(UsersModel::TYPES_USERS);
        $edition_permissions = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN_GRAL,
        ];

        //──── GET ─────────────────────────────────────────────────────────────────────────
        //Importación de usuarios
        $routes[] = new Route(
            "{$startRoute}importer/{type}/form[/]",
            $classname . ':importForm',
            'importer-form',
            'GET',
            true,
            null,
            $edition_permissions
        );
        $routes[] = new Route(
            "{$startRoute}importer/{type}/do[/]",
            $classname . ':import',
            'importer-action',
            'POST',
            true,
            null,
            $edition_permissions
        );
        $routes[] = new Route(
            "{$startRoute}importer/{type}/template[/]",
            $classname . ':templates',
            'importer-template',
            'GET',
            true,
            null,
            $edition_permissions
        );

        $group->active(self::ENABLE);
        $group->register($routes);

        return $group;
    }

    /**
     * @param string $segment
     * @return string
     */
    public static function staticRoute(string $segment = '')
    {
        return get_router()->getContainer()->get('staticRouteModulesResolver')(self::class, $segment, __DIR__ . '/../Statics', self::ENABLE);
    }

    /**
     * @param RouteGroup $group
     * @return void
     */
    protected static function staticResolver(RouteGroup $group)
    {

        /**
         * @param Request $request
         * @param Response $response
         * @param array $args
         * @return Response
         */
        $callableHandler = function (Request $request, Response $response, array $args) {
            $server = new ServerStatics();
            return $server->compileScssServe($request, $response, $args, __DIR__ . '/../Statics', [], self::staticRoute());
        };

        /**
         * @param Request $request
         * @param Response $response
         * @return Response
         */
        $cssGlobalVariables = function (Request $request, Response $response) {
            $css = CSSVariables::instance('global');
            return $css->toResponse($request, $response, false);
        };

        $routeStatics = [
            new Route('system-importers/statics/globals-vars.css', $cssGlobalVariables, ImporterController::class . '-global-vars'),
            new Route('system-importers/statics/[{params:.*}]', $callableHandler, ImporterController::class),
        ];
        $group->register($routeStatics);

    }

}
