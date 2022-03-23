<?php

/**
 * PresentationsController.php
 */

namespace App\Presentations\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use App\Presentations\Mappers\PresentationCategoryMapper;
use App\Presentations\Mappers\PresentationMapper;
use App\Presentations\PresentationsLang;
use App\Presentations\PresentationsRoutes;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Forms\FileUpload;
use PiecesPHP\Core\Forms\FileValidator;
use PiecesPHP\Core\Pagination\PageQuery;
use PiecesPHP\Core\Pagination\PaginationResult;
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
 * PresentationsController.
 *
 * @package     App\Presentations\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2020
 */
class PresentationsController extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $URLDirectory = 'presentations';
    /**
     * @var string
     */
    protected static $baseRouteName = 'app-presentations-admin';
    /**
     * @var string
     */
    protected static $title = 'Presentación';
    /**
     * @var string
     */
    protected static $pluralTitle = 'Presentaciones';

    /**
     * @var string
     */
    protected $uploadDir = '';
    /**
     * @var string
     */
    protected $uploadTmpDir = '';
    /**
     * @var string
     */
    protected $uploadDirURL = '';
    /**
     * @var string
     */
    protected $uploadDirTmpURL = '';
    /**
     * @var HelperController
     */
    protected $helpController = null;

    const BASE_VIEW_DIR = 'presentations';
    const BASE_JS_DIR = 'js/presentations';
    const BASE_CSS_DIR = 'css/presentations';
    const UPLOAD_DIR = 'presentations';
    const UPLOAD_DIR_TMP = 'presentations/tmp';
    const LANG_GROUP = PresentationsLang::LANG_GROUP;

    public function __construct()
    {
        parent::__construct();

        self::$title = __(self::LANG_GROUP, self::$title);
        self::$pluralTitle = __(self::LANG_GROUP, self::$pluralTitle);

        $this->model = (new PresentationMapper())->getModel();
        set_title(self::$title);

        $baseURL = base_url();
        $pcsUploadDir = get_config('upload_dir');
        $pcsUploadDirURL = get_config('upload_dir_url');

        $this->uploadDir = append_to_url($pcsUploadDir, self::UPLOAD_DIR);
        $this->uploadTmpDir = append_to_url($pcsUploadDir, self::UPLOAD_DIR_TMP);
        $this->uploadDirURL = str_replace($baseURL, '', append_to_url($pcsUploadDirURL, self::UPLOAD_DIR));
        $this->uploadDirTmpURL = str_replace($baseURL, '', append_to_url($pcsUploadDirURL, self::UPLOAD_DIR_TMP));

        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());

        $this->setInstanceViewDir(__DIR__ . '/../Views/');

        add_global_asset(PresentationsRoutes::staticRoute('globals-vars.css'), 'css');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function addForm(Request $request, Response $response)
    {

        set_custom_assets([
            PresentationsRoutes::staticRoute(self::BASE_JS_DIR . '/private/forms.js'),
        ], 'js');

        import_cropper();

        $action = self::routeName('actions-add');
        $backLink = self::routeName('list');
        $allCategories = array_to_html_options(PresentationCategoryMapper::allForSelect(), null);

        $data = [];
        $data['action'] = $action;
        $data['langGroup'] = self::LANG_GROUP;
        $data['backLink'] = $backLink;
        $data['title'] = self::$title;
        $data['allCategories'] = $allCategories;

        $this->helpController->render('panel/layout/header');
        self::view('private/forms/add', $data);
        $this->helpController->render('panel/layout/footer');

        return $response;

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function editForm(Request $request, Response $response)
    {

        $id = $request->getAttribute('id', null);
        $id = !is_null($id) && ctype_digit($id) ? (int) $id : null;

        $lang = $request->getAttribute('lang', null);
        $lang = is_string($lang) ? $lang : null;

        $allowedLangs = Config::get_allowed_langs();

        if ($lang === null || !in_array($lang, $allowedLangs)) {
            throw new NotFoundException($request, $response);
        }

        $element = new PresentationMapper($id);

        if ($element->id !== null && PresentationMapper::existsByID($element->id)) {

            set_custom_assets([
                PresentationsRoutes::staticRoute(self::BASE_JS_DIR . '/private/delete-config.js'),
                PresentationsRoutes::staticRoute(self::BASE_JS_DIR . '/private/forms.js'),
            ], 'js');

            import_cropper();

            $action = self::routeName('actions-edit');
            $backLink = self::routeName('list');
            $allCategories = array_to_html_options(PresentationCategoryMapper::allForSelect(), $element->category->id);
            $allowedLangs = array_to_html_options(self::allowedLangsForSelect($lang, $element->id), $lang);

            $data = [];
            $data['action'] = $action;
            $data['element'] = $element;
            $data['deleteRoute'] = self::routeName('actions-delete', ['id' => $element->id]);
            $data['allowDelete'] = self::allowedRoute('actions-delete', ['id' => $element->id]);
            $data['langGroup'] = self::LANG_GROUP;
            $data['backLink'] = $backLink;
            $data['title'] = self::$title;
            $data['allCategories'] = $allCategories;
            $data['allowedLangs'] = $allowedLangs;
            $data['lang'] = $lang;

            $this->helpController->render('panel/layout/header');
            self::view('private/forms/edit', $data, true, false);
            $this->helpController->render('panel/layout/footer');

            return $response;

        } else {
            throw new NotFoundException($request, $response);
        }

    }

    /**
     * @return void
     */
    public function listView()
    {

        $backLink = get_route('admin');
        $addLink = self::routeName('forms-add');
        $addCategoryLink = PresentationsCategoryController::routeName('forms-add');
        $listCategoriesLink = PresentationsCategoryController::routeName('list');
        $processTableLink = self::routeName('datatables');

        $title = self::$pluralTitle;

        $data = [];
        $data['processTableLink'] = $processTableLink;
        $data['langGroup'] = self::LANG_GROUP;
        $data['backLink'] = $backLink;
        $data['addLink'] = $addLink;
        $data['hasPermissionsAdd'] = strlen($addLink) > 0;
        $data['addCategoryLink'] = $addCategoryLink;
        $data['hasPermissionsAddCategory'] = strlen($addCategoryLink) > 0;
        $data['listCategoriesLink'] = $listCategoriesLink;
        $data['hasPermissionsListCategories'] = strlen($listCategoriesLink) > 0;
        $data['title'] = $title;

        set_custom_assets([
            PresentationsRoutes::staticRoute(self::BASE_JS_DIR . '/private/delete-config.js'),
            PresentationsRoutes::staticRoute(self::BASE_JS_DIR . '/private/list.js'),
        ], 'js');

        $this->helpController->render('panel/layout/header');
        self::view('private/list', $data);
        $this->helpController->render('panel/layout/footer');

    }

    /**
     * Creación/Edición
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function action(Request $request, Response $response)
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
                'lang',
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
                'order',
                0,
                function ($value) {
                    return ctype_digit($value) || is_int($value);
                },
                false,
                function ($value) {
                    return (int) $value;
                }
            ),
            new Parameter(
                'category',
                null,
                function ($value) {
                    return ctype_digit($value) || is_int($value);
                },
                false,
                function ($value) {
                    return (int) $value;
                }
            ),
            new Parameter(
                'images_to_delete',
                [],
                function ($value) {
                    return is_array($value);
                },
                true,
                function ($value) {
                    return $value;
                }
            ),
        ]);

        //Obtención de datos
        $inputData = $request->getParsedBody();

        //Asignación de datos para procesar
        $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

        //──── Estructura de respuesta ───────────────────────────────────────────────────────────

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Presentación'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación

        //Valores iniciales de la respuesta
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);

        //Mensajes de respuesta
        $notExistsMessage = __(self::LANG_GROUP, 'La presentación que intenta modificar no existe.');
        $successCreateMessage = __(self::LANG_GROUP, 'Presentación creada.');
        $successEditMessage = __(self::LANG_GROUP, 'Datos guardados.');
        $unknowErrorMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido.');
        $unknowErrorWithValuesMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido al procesar los valores ingresados.');
        $noImageMessage = __(self::LANG_GROUP, 'No ha sido subida ninguna imagen.');
        $categoryNotExistsMessage = __(self::LANG_GROUP, 'No hay existe la categoría.');
        $notAllowedLangMessage = __(self::LANG_GROUP, 'El idioma "%s" no está permitido.');

        //──── Acciones ──────────────────────────────────────────────────────────────────────────
        try {

            //Intenta validar, si todo sale bien el código continúa
            $expectedParameters->validate();

            //Información del formulario
            /**
             * @var int $id
             * @var string $lang
             * @var string $name
             * @var int $order
             * @var int $category
             * @var string[] $imagesToDelete
             */
            $id = $expectedParameters->getValue('id');
            $lang = $expectedParameters->getValue('lang');
            $name = $expectedParameters->getValue('name');
            $order = $expectedParameters->getValue('order');
            $category = $expectedParameters->getValue('category');
            $imagesToDelete = $expectedParameters->getValue('images_to_delete');

            //Se define si es edición o creación
            $isEdit = $id !== -1;

            try {

                $allowedLangs = Config::get_allowed_langs();

                if (!PresentationCategoryMapper::existsByID($category)) {
                    throw new \Exception($categoryNotExistsMessage);
                }

                if ($isEdit) {
                    if (!in_array($lang, $allowedLangs)) {
                        throw new \Exception(vsprintf($notAllowedLangMessage, [$lang]));
                    }
                } else {
                    $lang = get_config('default_lang');
                }

                if (!$isEdit) {
                    //Nuevo

                    $mapper = new PresentationMapper();

                    $mapper->setLangData($lang, 'name', $name);
                    $mapper->setLangData($lang, 'order', $order);
                    $mapper->setLangData($lang, 'category', $category);

                    $folder = str_replace('.', '', uniqid());
                    $imagesToAdd = self::handlerUploadImages('images_to_add', $folder, $imagesToDelete, 1, null, Config::get_default_lang());
                    $mapper->setLangData($lang, 'images', array_values((array) $imagesToAdd));

                    if (!empty($imagesToAdd)) {

                        $saved = $mapper->save();

                        $mapper->id = $mapper->getInsertIDOnSave();

                        $resultOperation->setSuccessOnSingleOperation($saved);

                        if ($saved) {

                            $resultOperation
                                ->setMessage($successCreateMessage)
                                ->setValue('redirect', true)
                                ->setValue('redirect_to', self::routeName('forms-edit', [
                                    'id' => $mapper->id,
                                ]));

                        } else {
                            $resultOperation->setMessage($unknowErrorMessage);
                        }

                    } else {
                        $resultOperation->setMessage($noImageMessage);
                    }

                } else {
                    //Existente

                    $mapper = new PresentationMapper((int) $id);
                    $exists = !is_null($mapper->id);

                    if ($exists) {

                        $mapper->setLangData($lang, 'name', $name);
                        $mapper->setLangData($lang, 'order', $order);
                        $mapper->setLangData($lang, 'category', $category);

                        $currentImages = array_values((array) $mapper->getLangData($lang, 'images', false, []));

                        //Obtener directorio de imágenes
                        $uploadDirRelativeURL = $this->uploadDirURL;
                        /**
                         * @var string
                         */
                        $directoryReferencePath = !empty($currentImages) ? $currentImages[0] : array_values((array) $mapper->images)[0];
                        $folder = str_replace($uploadDirRelativeURL, '', $directoryReferencePath);
                        $folder = str_replace(basename($directoryReferencePath), '', $folder);
                        $folder = trim($folder, '/');
                        //Obtener directorio de imágenes FIN

                        $imagesToAdd = self::handlerUploadImages('images_to_add', $folder, $imagesToDelete, 1, count($currentImages), $lang);

                        foreach ($imagesToDelete as $imageToDelete) {
                            $indexToDelete = array_search($imageToDelete, $currentImages);
                            if ($indexToDelete !== false) {
                                unset($currentImages[$indexToDelete]);
                            }
                        }

                        foreach ($imagesToAdd as $imageToAdd) {
                            $currentImages[] = $imageToAdd;
                        }

                        $mapper->setLangData($lang, 'images', array_values((array) $currentImages));

                        if (!empty($currentImages)) {

                            $updated = $mapper->update();

                            $resultOperation->setSuccessOnSingleOperation($updated);

                            if ($updated) {

                                $resultOperation
                                    ->setMessage($successEditMessage)
                                    ->setValue('redirect', true)
                                    ->setValue('redirect_to', self::routeName('list'));

                            } else {

                                $resultOperation->setMessage($unknowErrorMessage);

                            }

                        } else {
                            $resultOperation->setMessage($noImageMessage);
                        }

                    } else {

                        $resultOperation->setMessage($notExistsMessage);

                    }

                }

            } catch (\Exception $e) {

                $resultOperation->setMessage($e->getMessage());
                log_exception($e);

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

        } catch (\Exception $e) {

            $resultOperation->setMessage($e->getMessage());
            log_exception($e);

        }

        return $response->withJson($resultOperation);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function toDelete(Request $request, Response $response, array $args)
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
        ]);

        //Obtención de datos
        $inputData = $args;

        //Asignación de datos para procesar
        $expectedParameters->setInputValues($inputData);

        //──── Estructura de respuesta ───────────────────────────────────────────────────────────

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Eliminar presentación'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación

        //Valores iniciales de la respuesta
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);
        $resultOperation->setValue('received', $inputData);

        //Mensajes de respuesta
        $notExistsMessage = __(self::LANG_GROUP, 'La presentación que intenta eliminar no existe.');
        $successMessage = __(self::LANG_GROUP, 'Presentación eliminada.');
        $unknowErrorMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido.');
        $unknowErrorWithValuesMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido al procesar los valores ingresados.');

        //──── Acciones ──────────────────────────────────────────────────────────────────────────
        try {

            //Intenta validar, si todo sale bien el código continúa
            $expectedParameters->validate();

            //Información del formulario

            /**
             * @var int $id
             */
            $id = $expectedParameters->getValue('id');

            try {

                $exists = PresentationMapper::existsByID($id);

                if ($exists) {

                    //Dirección de redirección en caso de creación
                    $redirectURLOn = self::routeName('list');

                    $table = PresentationMapper::TABLE;

                    $transactionSQLDeleteQueries = [
                        [
                            'query' => "DELETE FROM {$table} WHERE id = :ID",
                            'aliasConfig' => [
                                ':ID' => $id,
                            ],
                        ],
                    ];

                    $pdo = PresentationMapper::model()::getDb(Config::app_db('default')['db']);

                    try {

                        $mapper = PresentationMapper::getBy($id, 'id', true);

                        $pdo->beginTransaction();

                        foreach ($transactionSQLDeleteQueries as $sqlQueryConfig) {

                            $query = $sqlQueryConfig['query'];
                            $aliasConfig = $sqlQueryConfig['aliasConfig'];

                            $preparedStatement = $pdo->prepare($query);
                            $preparedStatement->execute($aliasConfig);

                        }

                        $pdo->commit();

                        if ($mapper instanceof PresentationMapper) {
                            self::deletePresentationImages($mapper);
                        }

                        $resultOperation->setSuccessOnSingleOperation(true);

                        $resultOperation
                            ->setMessage($successMessage)
                            ->setValue('redirect', true)
                            ->setValue('redirect_to', $redirectURLOn);

                    } catch (\Exception $e) {
                        $pdo->rollBack();
                        $resultOperation->setValue('transactionError', $e->getMessage());
                        $resultOperation->setMessage($unknowErrorMessage);
                        log_exception($e);
                    }

                } else {
                    $resultOperation->setMessage($notExistsMessage);
                }

            } catch (\Exception $e) {

                $resultOperation->setMessage($e->getMessage());
                log_exception($e);

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
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function all(Request $request, Response $response)
    {

        $expectedParameters = new Parameters([
            new Parameter(
                'page',
                1,
                function ($value) {
                    return ctype_digit($value) || is_int($value);
                },
                true,
                function ($value) {
                    return (int) $value;
                }
            ),
            new Parameter(
                'per_page',
                10,
                function ($value) {
                    return ctype_digit($value) || is_int($value);
                },
                true,
                function ($value) {
                    return (int) $value;
                }
            ),
            new Parameter(
                'category',
                null,
                function ($value) {
                    return ctype_digit($value) || is_int($value);
                },
                true,
                function ($value) {
                    return (int) $value;
                }
            ),
        ]);

        $expectedParameters->setInputValues($request->getQueryParams());
        $expectedParameters->validate();

        /**
         * @var int $page
         * @var int $perPage
         * @var int $category
         */
        $page = $expectedParameters->getValue('page');
        $perPage = $expectedParameters->getValue('per_page');
        $category = $expectedParameters->getValue('category');

        $result = self::_all($page, $perPage, $category);

        return $response->withJson($result);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function dataTables(Request $request, Response $response)
    {

        $whereString = null;

        $selectFields = PresentationMapper::fieldsToSelect();

        $table = PresentationMapper::TABLE;
        $tableCategory = PresentationCategoryMapper::TABLE;
        $selectFields[] = "(SELECT {$tableCategory}.name FROM {$tableCategory} WHERE {$tableCategory}.id = {$table}.category) AS categoryName";

        $columnsOrder = [
            'name',
            'categoryName',
        ];

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);

        $result = DataTablesHelper::process([

            'where_string' => $whereString,
            'select_fields' => $selectFields,
            'columns_order' => $columnsOrder,
            'mapper' => new PresentationMapper(),
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

            $mapper = new PresentationMapper($element->id);

            $rawData[$index] = self::view(
                'private/util/list-card',
                [
                    'mapper' => $mapper,
                    'editLink' => self::routeName('forms-edit', [
                        'id' => $mapper->id,
                    ]),
                    'hasEdit' => self::allowedRoute('forms-edit', [
                        'id' => $mapper->id,
                    ]),
                    'deleteRoute' => self::routeName('actions-delete', ['id' => $mapper->id]),
                    'hasDelete' => self::allowedRoute('actions-delete', ['id' => $mapper->id]),
                    'langGroup' => self::LANG_GROUP,
                ],
                false
            );

        }

        $result->setValue('rawData', $rawData);

        return $response->withJson($result->getValues());
    }

    /**
     * @param int $page
     * @param int $perPage
     * @param int $category
     * @return PaginationResult
     */
    public static function _all(int $page = 1, int $perPage = 10, int $category = null)
    {
        $table = PresentationMapper::TABLE;
        $fields = PresentationMapper::fieldsToSelect();
        $jsonExtractExists = PresentationMapper::jsonExtractExistsMySQL();

        $whereString = null;
        $where = [];

        if ($category !== null) {
            $where[] = "category = {$category}";
        }

        if (!empty($where)) {
            $whereString = implode('', $where);
        }

        $fields = implode(', ', $fields);
        $sqlSelect = "SELECT {$fields} FROM {$table}";
        $sqlCount = "SELECT COUNT({$table}.id) AS total FROM {$table}";

        if ($whereString !== null) {
            $sqlSelect .= " WHERE {$whereString}";
            $sqlCount .= " WHERE {$whereString}";
        }

        $sqlSelect .= " ORDER BY " . implode(', ', PresentationMapper::ORDER_BY_PREFERENCE);

        $pageQuery = new PageQuery($sqlSelect, $sqlCount, $page, $perPage, 'total');

        $parser = null;
        $each = !$jsonExtractExists ? function ($element) {
            $element = PresentationMapper::translateEntityObject($element);
            return $element;
        } : null;

        $pagination = $pageQuery->getPagination($parser, $each);

        return $pagination;
    }

    /**
     * Elimina todas las imágenes de una presentación
     * @param PresentationMapper $mapper
     * @return void
     */
    public static function deletePresentationImages(PresentationMapper $mapper)
    {

        $lang_data = $mapper->lang_data;
        $currentImages = array_values((array) $mapper->images);

        if ($lang_data instanceof \stdClass) {

            $properties = get_object_vars($lang_data);

            if (is_array($properties)) {

                foreach ($properties as $value) {

                    $value = (array) $value;
                    $imagesLang = isset($value['images']) ? (array) $value['images'] : [];

                    foreach ($imagesLang as $imageLang) {
                        $currentImages[] = $imageLang;
                    }

                }

            }

        }

        $imagesToDelete = [];

        foreach ($currentImages as $image) {
            $imagesToDelete[] = basepath($image);
        }

        foreach ($imagesToDelete as $imageToDelete) {

            if (file_exists($imageToDelete)) {
                unlink($imageToDelete);
            }

        }

    }

    /**
     * @param string $currentLang
     * @param int $elementID
     * @return array
     */
    public static function allowedLangsForSelect(string $currentLang, int $elementID)
    {

        $allowedLangsForSelect = [];

        $allowedLangs = Config::get_allowed_langs();

        $allowedLangs = array_filter($allowedLangs, function ($l) use ($currentLang) {
            return $l != $currentLang;
        });

        array_unshift($allowedLangs, $currentLang);

        foreach ($allowedLangs as $i) {

            $value = self::routeName('forms-edit', ['id' => $elementID, 'lang' => $i]);

            $allowedLangsForSelect[$value] = __('lang', $i);

        }

        return $allowedLangsForSelect;

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
        return (new PresentationsController)->render(self::BASE_VIEW_DIR . '/' . trim($name, '/'), $data, $mode, $format);
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
     * @param string $nameOnFiles
     * @param string $folder
     * @param string[] $imagesToDelete
     * @param int $minImages
     * @param int $currentTotal
     * @param string $namePrefrix
     * @return array
     * @throws \Exception
     */
    protected static function handlerUploadImages(string $nameOnFiles, string $folder, array $imagesToDelete = [], int $minImages = 1, int $currentTotal = null, string $namePrefrix = 'file')
    {
        $handler = new FileUpload($nameOnFiles, [
            FileValidator::TYPE_ALL_IMAGES,
        ], null, true);

        $name = $namePrefrix . '_' . str_replace('.', '', uniqid());
        $relativeURLs = [];

        $instance = new PresentationsController;
        $uploadDirPath = $instance->uploadDir;
        $uploadDirRelativeURL = $instance->uploadDirURL;

        $uploadDirPath = append_to_url($uploadDirPath, $folder);
        $uploadDirRelativeURL = append_to_url($uploadDirRelativeURL, $folder);

        $deleteImages = function (array $images, string $uploadDirRelativeURL) {
            foreach ($images as $imageToDelete) {
                if (mb_strpos($imageToDelete, $uploadDirRelativeURL) !== false && file_exists($imageToDelete)) {
                    unlink(basepath($imageToDelete));
                }
            }
        };

        try {

            $locations = $handler->moveTo($uploadDirPath, $name, null, false, true);

            foreach ($locations as $url) {

                if (mb_strlen($url) > 0) {
                    $nameCurrent = basename($url);
                    $relativeURLs[] = trim(append_to_url($uploadDirRelativeURL, $nameCurrent), '/');
                }

            }

            if ($currentTotal !== null) {

                $readyToDelete = true;
                $uploadCount = count($relativeURLs);
                $toDeleteCount = count($imagesToDelete);

                if ($toDeleteCount === $currentTotal) {
                    if ($uploadCount < $minImages) {
                        $readyToDelete = false;
                    }
                }

                if ($readyToDelete) {
                    ($deleteImages)($imagesToDelete, (new PresentationsController)->uploadDirURL);
                }

            } else {
                ($deleteImages)($imagesToDelete, (new PresentationsController)->uploadDirURL);
            }

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $relativeURLs;
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

        if ($current_user !== false) {
            $allowed = Roles::hasPermissions($name, (int) $current_user->type);
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

        $permisos_listado = $all_roles;

        $permisos_estados_gestion = [
            UsersModel::TYPE_USER_ROOT,
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
                $permisos_listado
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
                "{$startRoute}/forms/edit/{id}/{lang}[/]",
                $classname . ':editForm',
                self::$baseRouteName . '-forms-edit',
                'GET',
                true,
                null,
                $permisos_estados_gestion,
                [
                    'lang' => Config::get_default_lang(),
                ]
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
                $permisos_listado
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
            new Route( //Acción de eliminar
                "{$startRoute}/action/delete/{id}[/]",
                $classname . ':toDelete',
                self::$baseRouteName . '-actions-delete',
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
