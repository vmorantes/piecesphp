<?php

/**
 * BrandController.php
 */

namespace PiecesPHP\BuiltIn\Shop\Brand\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use PiecesPHP\BuiltIn\Shop\Brand\Mappers\BrandMapper;
use PiecesPHP\BuiltIn\Shop\EntryPointController;
use PiecesPHP\Core\Forms\FileUpload;
use PiecesPHP\Core\Forms\FileValidator;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParamaterException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\ParsedValueException;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use Slim\Exception\NotFoundException;
use Slim\Http\Request as Request;
use Slim\Http\Response as Response;

/**
 * BrandController.
 *
 * @package     PiecesPHP\BuiltIn\Shop\Brand\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2020
 */
class BrandController extends AdminPanelController
{

    /**
     * $URLDirectory
     *
     * @var string
     */
    protected static $URLDirectory = 'shop/private/brands';

    /**
     * $baseRouteName
     *
     * @var string
     */
    protected static $baseRouteName = 'built-in-shop-private-brands';

    /**
     * $title
     *
     * @var string
     */
    protected static $title = 'Marca';
    /**
     * $pluralTitle
     *
     * @var string
     */
    protected static $pluralTitle = 'Marcas';

    /**
     * $uploadDir
     *
     * @var string
     */
    protected $uploadDir = '';
    /**
     * $uploadDirURL
     *
     * @var string
     */
    protected $uploadDirURL = '';

    const BASE_VIEW_DIR = 'built-in/shop/brands';
    const BASE_JS_DIR = 'statics/js/built-in/shop/brands';
    const UPLOAD_DIR = 'general/shop/brands';
    const LANG_GROUP = 'bi-shop-brands';

    /**
     * __construct
     *
     * @return static
     */
    public function __construct()
    {
        parent::__construct(false); //No cargar ningún modelo automáticamente.

        self::$title = __(self::LANG_GROUP, self::$title);
        self::$pluralTitle = __(self::LANG_GROUP, self::$pluralTitle);

        $this->model = (new BrandMapper())->getModel();
        set_title(self::$title);

        $baseURL = base_url();
        $pcsUploadDir = get_config('upload_dir');
        $pcsUploadDirURL = get_config('upload_dir_url');

        $this->uploadDir = append_to_url($pcsUploadDir, self::UPLOAD_DIR);
        $this->uploadDirURL = str_replace($baseURL, '', append_to_url($pcsUploadDirURL, self::UPLOAD_DIR));
    }

    /**
     * addForm
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return void
     */
    public function addForm(Request $request, Response $response, array $args)
    {

        set_custom_assets([
            self::BASE_JS_DIR . '/private/forms.js',
        ], 'js');

        import_cropper();

        $action = self::routeName('actions-add');
        $backLink = self::routeName('list');

        $data = [];
        $data['action'] = $action;
        $data['langGroup'] = self::LANG_GROUP;
        $data['backLink'] = $backLink;
        $data['title'] = self::$title;

        $this->render('panel/layout/header');
        self::view('private/forms/add', $data);
        $this->render('panel/layout/footer');

    }

    /**
     * editForm
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return void
     */
    public function editForm(Request $request, Response $response, array $args)
    {

        $id = $request->getAttribute('id', null);
        $id = !is_null($id) && ctype_digit($id) ? (int) $id : null;

        $element = new BrandMapper($id);

        if (!is_null($element->id)) {

            set_custom_assets([
                self::BASE_JS_DIR . '/private/forms.js',
            ], 'js');

            import_cropper();

            $action = self::routeName('actions-edit');
            $backLink = self::routeName('list');

            $data = [];
            $data['action'] = $action;
            $data['element'] = $element;
            $data['langGroup'] = self::LANG_GROUP;
            $data['backLink'] = $backLink;
            $data['title'] = self::$title;

            $this->render('panel/layout/header');
            self::view('private/forms/edit', $data);
            $this->render('panel/layout/footer');

        } else {
            throw new NotFoundException($request, $response);
        }

    }

    /**
     * listView
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return void
     */
    public function listView(Request $request, Response $response, array $args)
    {

        $backLink = EntryPointController::routeName('options');
        $addLink = self::routeName('forms-add');

        $processTableLink = self::routeName('datatables');

        $title = self::$pluralTitle;

        $data = [];
        $data['processTableLink'] = $processTableLink;
        $data['langGroup'] = self::LANG_GROUP;
        $data['backLink'] = $backLink;
        $data['addLink'] = $addLink;
        $data['hasPermissionsAdd'] = strlen($addLink) > 0;
        $data['title'] = $title;

        set_custom_assets([
            self::BASE_JS_DIR . '/private/list.js',
        ], 'js');

        $this->render('panel/layout/header');
        self::view('private/list', $data);
        $this->render('panel/layout/footer');

    }

    /**
     * action
     *
     * Creación/Edición
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function action(Request $request, Response $response, array $args)
    {

        //──── Entrada ───────────────────────────────────────────────────────────────────────────

        //Definición de validaciones y procesamiento
        $expectedParameters = new Parameters([
            new Parameter(
                'id',
                -1,
                function ($value) {
                    return ctype_digit($value) || is_int($value);
                },
                true,
                function ($value) {
                    return (int) $value;
                }
            ),
            new Parameter(
                'name',
                null,
                function ($value) {
                    return is_string($value) && strlen(trim($value)) > 0;
                },
                false,
                function ($value) {
                    return clean_string($value);
                }
            ),
            new Parameter(
                'description',
                '',
                function ($value) {
                    return is_string($value);
                },
                true,
                function ($value) {
                    return clean_string($value);
                }
            ),
        ]);

        //Obtención de datos
        $inputData = $request->getParsedBody();

        //Asignación de datos para procesar
        $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

        //──── Estructura de respuesta ───────────────────────────────────────────────────────────

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Marca'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación

        //Valores iniciales de la respuesta
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);

        //Mensajes de respuesta
        $notExistsMessage = __(self::LANG_GROUP, 'La marca que intenta modificar no existe.');
        $successCreateMessage = __(self::LANG_GROUP, 'Marca creada.');
        $successEditMessage = __(self::LANG_GROUP, 'Datos guardados.');
        $unknowErrorMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido.');
        $unknowErrorWithValuesMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido al procesar los valores ingresados.');
        $isDuplicateMessage = __(self::LANG_GROUP, 'Ya existe una marca con ese nombre.');
        $noImageMessage = __(self::LANG_GROUP, 'No ha sido subida ninguna imagen.');

        //──── Acciones ──────────────────────────────────────────────────────────────────────────
        try {

            //Intenta validar, si todo sale bien el código continúa
            $expectedParameters->validate();

            //Información del formulario
            /**
             * @var int $id
             * @var string $name
             * @var string $description
             */;
            $id = $expectedParameters->getValue('id');
            $name = $expectedParameters->getValue('name');
            $description = $expectedParameters->getValue('description');

            //Se define si es edición o creación
            $isEdit = $id !== -1;

            //Dirección de redirección en cadso de creación
            $redirectURLOnCreate = self::routeName('list');

            //Verificar si está duplicado
            $isDuplicate = BrandMapper::isDuplicate($name, $id);

            if (!$isDuplicate) {

                try {

                    if (!$isEdit) {
                        //Nuevo

                        $mapper = new BrandMapper();

                        $folder = (new \DateTime)->format('Y/m/d/') . str_replace('.', '', uniqid());
                        $image = self::handlerUploadImage('image', $folder);

                        $mapper->name = $name;
                        $mapper->description = $description;
                        $mapper->image = $image;

                        if (strlen($image) > 0) {

                            $saved = $mapper->save();

                            $resultOperation->setSuccessOnSingleOperation($saved);

                            if ($saved) {

                                $resultOperation
                                    ->setMessage($successCreateMessage)
                                    ->setValue('redirect', true)
                                    ->setValue('redirect_to', $redirectURLOnCreate);

                            } else {
                                $resultOperation->setMessage($unknowErrorMessage);
                            }

                        } else {
                            $resultOperation->setMessage($noImageMessage);
                        }

                    } else {
                        //Existente

                        $mapper = new BrandMapper((int) $id);
                        $exists = !is_null($mapper->id);

                        if ($exists) {

                            $image = self::handlerUploadImage('image', '', $mapper->image);

                            $mapper->name = $name;
                            $mapper->description = $description;
                            $mapper->image = strlen($image) > 0 ? $image : $mapper->image;

                            $updated = $mapper->update();

                            $resultOperation->setSuccessOnSingleOperation($updated);

                            if ($updated) {

                                $resultOperation
                                    ->setMessage($successEditMessage)
                                    ->setValue('redirect', true)
                                    ->setValue('redirect_to', $redirectURLOnCreate);

                            } else {

                                $resultOperation->setMessage($unknowErrorMessage);

                            }

                        } else {

                            $resultOperation->setMessage($notExistsMessage);

                        }

                    }

                } catch (\Exception $e) {

                    $resultOperation->setMessage($e->getMessage());
                    log_exception($e);

                }

            } else {

                $resultOperation->setMessage($isDuplicateMessage);
            }

        } catch (MissingRequiredParamaterException $e) {

            $resultOperation->setMessage($e->getMessage());
            log_exception($e);

        } catch (ParsedValueException $e) {

            $resultOperation->setMessage($unknowErrorWithValuesMessage);
            log_exception($e);

        } catch (InvalidParameterValueException $e) {

            $resultOperation->setMessage($e->getMessage());
            log_exception($e);

        }

        return $response->withJson($resultOperation);
    }

    /**
     * all
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function all(Request $request, Response $response, array $args)
    {
        $query = $this->model->select();
        $query->execute();
        $result = $query->result();
        return $response->withJson($result);
    }

    /**
     * dataTables
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function dataTables(Request $request, Response $response, array $args)
    {

        $whereString = null;

        $table = BrandMapper::TABLE;

        $selectFields = [
            "{$table}.id",
            "{$table}.name",
            "{$table}.description",
            "{$table}.image",
            "{$table}.meta",
        ];

        $columnsOrder = [
            'name',
            'description',
        ];

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);

        $result = DataTablesHelper::process([

            'where_string' => $whereString,
            'select_fields' => $selectFields,
            'columns_order' => $columnsOrder,
            'mapper' => new BrandMapper(),
            'request' => $request,
            'on_set_data' => function ($e) {
                return [
                    '',
                    '',
                ];
            },

        ]);

        $rawData = $result->getValue('rawData');

        foreach ($rawData as $index => $element) {

            $mapper = new BrandMapper($element->id);

            $rawData[$index] = self::view(
                'private/util/list-card',
                [
                    'mapper' => $mapper,
                    'editLink' => self::routeName('forms-edit', [
                        'id' => $mapper->id,
                    ]),
                    'langGroup' => self::LANG_GROUP,
                ],
                false
            );

        }

        $result->setValue('rawData', $rawData);

        return $response->withJson($result->getValues());
    }

    /**
     * view
     *
     * @param string $name
     * @param array $data
     * @param bool $mode
     * @param bool $format
     * @return void|string
     */
    public static function view(string $name, array $data = [], bool $mode = true, bool $format = true)
    {
        return (new static )->render(self::BASE_VIEW_DIR . '/' . trim($name, '/'), $data, $mode, $format);
    }

    /**
     * handlerUploadImage
     *
     * @param string $nameOnFiles
     * @param string $folder
     * @param string $currentRoute
     * @param bool $setNameByInput
     * @return string
     * @throws \Exception
     */
    protected static function handlerUploadImage(string $nameOnFiles, string $folder, string $currentRoute = null, bool $setNameByInput = true)
    {
        $handler = new FileUpload($nameOnFiles, [
            FileValidator::TYPE_ALL_IMAGES,
        ]);
        $valid = false;
        $relativeURL = '';

        $name = 'file_' . uniqid();
        $oldFile = null;

        if ($handler->hasInput()) {

            try {

                $valid = $handler->validate();

                $uploadDirPath = (new static )->uploadDir;
                $uploadDirRelativeURL = (new static )->uploadDirURL;

                if ($setNameByInput && $valid) {

                    $name = $_FILES[$nameOnFiles]['name'];
                    $lastPointIndex = strrpos($name, '.');

                    if ($lastPointIndex !== false) {
                        $name = substr($name, 0, $lastPointIndex);
                    }

                }

                if (!is_null($currentRoute)) {
                    //Si ya existe
                    $oldFile = append_to_url(basepath(), $currentRoute);
                    $oldFile = file_exists($oldFile) ? $oldFile : null;

                    if (strlen(trim($folder)) < 1) {
                        //Si folder está vacío
                        $folder = str_replace($uploadDirRelativeURL, '', $currentRoute);
                        $folder = str_replace(basename($currentRoute), '', $folder);
                        $folder = trim($folder, '/');
                    }

                }

                $uploadDirPath = append_to_url($uploadDirPath, $folder);
                $uploadDirRelativeURL = append_to_url($uploadDirRelativeURL, $folder);

                if ($valid) {

                    $locations = $handler->moveTo($uploadDirPath, $name, null, false, true);

                    if (count($locations) > 0) {

                        $url = $locations[0];
                        $nameCurrent = basename($url);
                        $relativeURL = trim(append_to_url($uploadDirRelativeURL, $nameCurrent), '/');

                        //Eliminar archivo anterior
                        if (!is_null($oldFile)) {

                            if (basename($oldFile) != $nameCurrent) {
                                unlink($oldFile);
                            }

                        }

                        //Se elimina cualquier otro archivo
                        foreach ($locations as $file) {
                            if ($url != $file) {
                                if (is_string($file) && file_exists($file)) {
                                    unlink($file);
                                }
                            }
                        }

                    }

                } else {
                    throw new \Exception(implode('<br>', $handler->getErrorMessages()));
                }

            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }

        }

        return $relativeURL;
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
     * routes
     *
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
            UsersModel::TYPE_USER_ADMIN,
        ];

        $routes = [

            //──── GET ───────────────────────────────────────────────────────────────────────────────

            //HTML
            new Route( //Vista del listado
                "{$startRoute}/list[/]",
                $classname . ':listView',
                self::$baseRouteName . '-list',
                'GET',
                true,
                null,
                $permisos_estados_gestion
            ),
            new Route( //Formulario de crear
                "{$startRoute}/forms/add[/]",
                $classname . ':addForm',
                self::$baseRouteName . '-forms-add',
                'GET',
                true,
                null,
                $permisos_estados_gestion
            ),
            new Route( //Formulario de editar
                "{$startRoute}/forms/edit/{id}[/]",
                $classname . ':editForm',
                self::$baseRouteName . '-forms-edit',
                'GET',
                true,
                null,
                $permisos_estados_gestion
            ),

            //JSON
            new Route( //JSON con todos los elementos
                "{$startRoute}/all[/]",
                $classname . ':all',
                self::$baseRouteName . '-ajax-all',
                'GET'
            ),
            new Route( //Datos para datatables
                "{$startRoute}/datatables[/]",
                $classname . ':dataTables',
                self::$baseRouteName . '-datatables',
                'GET',
                true,
                null,
                $permisos_estados_gestion
            ),

            //──── POST ──────────────────────────────────────────────────────────────────────────────

            new Route( //Acción de crear
                "{$startRoute}/action/add[/]",
                $classname . ':action',
                self::$baseRouteName . '-actions-add',
                'POST',
                true,
                null,
                $all_roles
            ),
            new Route( //Acción de editar
                "{$startRoute}/action/edit[/]",
                $classname . ':action',
                self::$baseRouteName . '-actions-edit',
                'POST',
                true,
                null,
                $permisos_estados_gestion
            ),

        ];

        $group->register($routes);

        return $group;
    }
}
