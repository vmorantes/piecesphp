<?php

/**
 * ImporterController.php
 */

namespace App\Controller;

use App\Model\UsersModel;
use Importers\ImporterUsers;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Importer\Collections\ImporterCollection;
use PiecesPHP\Core\Importer\Importer;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use Slim\Exception\NotFoundException;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

/**
 * ImporterController.
 *
 * @package     App\Controller
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class ImporterController extends AdminPanelController
{

    /**
     * $importers
     *
     * @var ImporterCollection
     */
    protected $importers = null;

    /**
     * $views
     *
     * @var array
     */
    protected $views = [];

    /**
     * $texts
     *
     * @var array
     */
    protected $texts = [];

    /**
     * $defaultView
     *
     * @var string
     */
    protected $defaultView = 'panel/pages/importador/generic';

    public function __construct()
    {
        $this->importers = new StringArray([
            'users' => ImporterUsers::class,
        ]);

        $this->views = [
            'users' => 'panel/pages/importador/generic',
        ];

        $this->texts = [
            'users' => __('importerModule', 'TEXTO_IMPORTACION_USUARIOS'),
        ];

        parent::__construct();

        import_izitoast();
    }

    /**
     * importForm
     *
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return Response
     * @throws NotFoundException
     */
    public function importForm(Request $req, Response $res, array $args)
    {
        $type = $args['type'];

        if ($this->existsImporter($type) && $this->isValidImporter($type)) {

            set_custom_assets(
                [
                    base_url(ADMIN_AREA_PATH_JS . '/importer-form.js'),
                ]
                , 'js'
            );

            $data = [];

            $data['title'] = $this->getImporter($type, [])->getTitle();

            $data['text'] = $this->getImporter($type, [])->getDescription();

            if (strlen($data['text']) == 0) {
                $data['text'] = $this->getText($type);
            }

            $data['action'] = get_route('importer-action', ['type' => $type]);

            $data['template'] = get_route('importer-template', ['type' => $type]);

            $this->render('panel/layout/header');
            $this->render($this->getView($type), $data);
            $this->render('panel/layout/footer');

            return $res;

        } else {
            throw new NotFoundException($req, $res);
        }
    }

    /**
     * import
     *
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return Response
     */
    public function import(Request $req, Response $res, array $args)
    {
        $data = $this->processFile($_FILES);
        $type = $args['type'];

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
                    $json_response['message'] = __('importerModule', 'El importador no es válido.');
                }

            } else {
                $json_response['message'] = __('importerModule', 'No existe el importador solicitado.');
            }

        } else {
            $json_response['message'] = __('importerModule', 'Ha ocurrido un error.');
        }

        return $res->withJson($json_response);
    }

    /**
     * templates
     *
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return Response
     * @throws NotFoundException
     */
    public function templates(Request $req, Response $res, array $args)
    {
        $type = $args['type'];

        if ($this->existsImporter($type)) {

            if ($this->isValidImporter($type)) {

                $importer = $this->getImporter($type, []);
                $schema = $importer->getSchema();
                $template = $schema->template();
                $name = __('importerModule', 'plantilla');

                $template->save('php://output');

                return $res->withHeader('Content-Type', 'application/force-download')
                    ->withHeader('Content-Description', 'File Transfer')
                    ->withHeader('Content-Transfer-Encoding', 'binary')
                    ->withHeader('Content-Disposition', 'attachment; filename="' . $name . '.xlsx"');

            }

        } else {
            throw new NotFoundException($req, $res);
        }

    }

    /**
     * getImporter
     *
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
     * getView
     *
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
     * getText
     *
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
     * existsImporter
     *
     * @param string $type
     * @return bool
     */
    public function existsImporter(string $type)
    {
        return isset($this->importers[$type]) && is_string($this->importers[$type]);
    }

    /**
     * isValidImporter
     *
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

                if ($index_row == 1) {

                    $titles[$index_cell] = trim($cell->getValue());

                } else {

                    if (!isset($titles[$index_cell])) {
                        continue;
                    }

                    $column_name = $titles[$index_cell];
                    if (!isset($data[$index_row])) {
                        $data[$index_row] = [];
                    }

                    $data[$index_row][$column_name] = trim($cell->getValue());

                }

            }

        }
        return $data;
    }

    /**
     * processFile
     *
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
        $classname = ImporterController::class;
        $routes = [];
        $all_roles = array_keys(UsersModel::TYPES_USERS);
        $edition_permissions = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN,
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

        $group->active(IMPORTS_MODULE_ENABLED);
        $group->register($routes);

        return $group;
    }
}
