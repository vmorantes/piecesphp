<?php

/**
 * PersonsController.php
 */

namespace Persons\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use PDOException;
use Persons\Exceptions\DuplicateException;
use Persons\Exceptions\SafeException;
use Persons\Mappers\PersonsMapper;
use Persons\PersonsLang;
use Persons\PersonsRoutes;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Forms\FileUpload;
use PiecesPHP\Core\Forms\FileValidator;
use PiecesPHP\Core\Pagination\PageQuery;
use PiecesPHP\Core\Pagination\PaginationResult;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoutePiecesPHP as Request;
use PiecesPHP\Core\Routing\ResponseRoutePiecesPHP as Response;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParamaterException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\ParsedValueException;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;

/**
 * PersonsController.
 *
 * @package     Persons\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2022
 */
class PersonsController extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $URLDirectory = 'persons';
    /**
     * @var string
     */
    protected static $baseRouteName = 'persons-admin';
    /**
     * @var string
     */
    protected static $title = 'Persona';
    /**
     * @var string
     */
    protected static $pluralTitle = 'Registro de personas';

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

    const BASE_VIEW_DIR = 'persons';
    const BASE_JS_DIR = 'js/persons';
    const BASE_CSS_DIR = 'css/persons';
    const UPLOAD_DIR = 'persons';
    const UPLOAD_DIR_TMP = 'persons/tmp';
    const LANG_GROUP = PersonsLang::LANG_GROUP;

    public function __construct()
    {
        parent::__construct();

        self::$title = __(self::LANG_GROUP, self::$title);
        self::$pluralTitle = __(self::LANG_GROUP, self::$pluralTitle);

        $this->model = (new PersonsMapper())->getModel();
        set_title(self::$pluralTitle);

        $baseURL = base_url();
        $pcsUploadDir = get_config('upload_dir');
        $pcsUploadDirURL = get_config('upload_dir_url');

        $this->uploadDir = append_to_path_system($pcsUploadDir, self::UPLOAD_DIR);
        $this->uploadTmpDir = append_to_path_system($pcsUploadDir, self::UPLOAD_DIR_TMP);
        $this->uploadDirURL = str_replace($baseURL, '', append_to_url($pcsUploadDirURL, self::UPLOAD_DIR));
        $this->uploadDirTmpURL = str_replace($baseURL, '', append_to_url($pcsUploadDirURL, self::UPLOAD_DIR_TMP));

        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());

        $this->setInstanceViewDir(__DIR__ . '/../Views/');

        add_global_asset(PersonsRoutes::staticRoute('globals-vars.css'), 'css');
        add_global_asset(PersonsRoutes::staticRoute(self::BASE_CSS_DIR . '/persons-style.css'), 'css');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function listView(Request $request, Response $response)
    {

        $backLink = get_route('admin');

        $processTableLink = self::routeName('datatables');

        $action = self::routeName('actions-add');

        $title = __(self::LANG_GROUP, 'Gestión de') . ' ' . self::$pluralTitle;

        $optionsDocumentTypes = array_to_html_options(PersonsMapper::idDocumentTypesForSelect(), null);

        $data = [];
        $data['langGroup'] = self::LANG_GROUP;
        $data['processTableLink'] = $processTableLink;
        $data['backLink'] = $backLink;
        $data['title'] = self::$pluralTitle;
        $data['subtitle'] = $title;
        $data['formVariables'] = [
            'action' => $action,
            'langGroup' => self::LANG_GROUP,
            'standalone' => false,
            'optionsDocumentTypes' => $optionsDocumentTypes,
            'submitButtonText' => __(self::LANG_GROUP, 'Guardar'),
        ];

        set_custom_assets([
            PersonsRoutes::staticRoute(self::BASE_JS_DIR . '/utils.js'),
            PersonsRoutes::staticRoute(self::BASE_JS_DIR . '/delete-config.js'),
            PersonsRoutes::staticRoute(self::BASE_JS_DIR . '/list.js'),
        ], 'js');

        $this->helpController->render('panel/layout/header');
        self::view('list', $data);
        $this->helpController->render('panel/layout/footer');

        return $response;

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function addForm(Request $request, Response $response)
    {

        set_custom_assets([
            PersonsRoutes::staticRoute(self::BASE_JS_DIR . '/utils.js'),
            PersonsRoutes::staticRoute(self::BASE_JS_DIR . '/delete-config.js'),
            PersonsRoutes::staticRoute(self::BASE_JS_DIR . '/add-form.js'),
        ], 'js');

        $action = self::routeName('actions-add');
        $backLink = self::routeName('list');
        $standalone = true;

        $optionsDocumentTypes = array_to_html_options(PersonsMapper::idDocumentTypesForSelect(), null);

        $data = [];
        $data['action'] = $action;
        $data['langGroup'] = self::LANG_GROUP;
        $data['backLink'] = $backLink;
        $data['title'] = self::$title;
        $data['standalone'] = $standalone;
        $data['optionsDocumentTypes'] = $optionsDocumentTypes;

        $this->helpController->render('panel/layout/header');
        self::view('forms/add', $data);
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

        $element = new PersonsMapper($id);

        if ($element->id !== null && PersonsMapper::existsByID($element->id)) {

            set_custom_assets([
                PersonsRoutes::staticRoute(self::BASE_JS_DIR . '/utils.js'),
                PersonsRoutes::staticRoute(self::BASE_JS_DIR . '/delete-config.js'),
                PersonsRoutes::staticRoute(self::BASE_JS_DIR . '/edit-form.js'),
            ], 'js');

            $action = self::routeName('actions-edit');
            $backLink = self::routeName('list');
            $manyLangs = !empty($allowedLangs) && !empty($element->getTranslatableProperties());
            $allowedLangs = array_to_html_options(self::allowedLangsForSelect($lang, $element->id), $lang);

            $optionsDocumentTypes = array_to_html_options(PersonsMapper::idDocumentTypesForSelect(), $element->documentType);

            $data = [];
            $data['action'] = $action;
            $data['element'] = $element;
            $data['deleteRoute'] = self::routeName('actions-delete', ['id' => $element->id]);
            $data['allowDelete'] = self::allowedRoute('actions-delete', ['id' => $element->id]);
            $data['langGroup'] = self::LANG_GROUP;
            $data['backLink'] = $backLink;
            $data['title'] = self::$title;
            $data['allowedLangs'] = $allowedLangs;
            $data['manyLangs'] = $manyLangs;
            $data['lang'] = $lang;
            $data['optionsDocumentTypes'] = $optionsDocumentTypes;

            $this->helpController->render('panel/layout/header');
            self::view('forms/edit', $data, true, false);
            $this->helpController->render('panel/layout/footer');

            return $response;

        } else {
            throw new NotFoundException($request, $response);
        }

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
                'documentType',
                null,
                function ($value) {
                    return is_string($value) && mb_strlen($value) > 0;
                },
                false,
                function ($value) {
                    return trim(clean_string($value));
                }
            ),
            new Parameter(
                'documentNumber',
                null,
                function ($value) {
                    return is_string($value) && mb_strlen($value) > 0;
                },
                false,
                function ($value) {
                    return trim(clean_string($value));
                }
            ),
            new Parameter(
                'personName1',
                null,
                function ($value) {
                    return is_string($value) && mb_strlen($value) > 0;
                },
                false,
                function ($value) {
                    return trim(clean_string($value));
                }
            ),
            new Parameter(
                'personName2',
                '',
                function ($value) {
                    return is_string($value) && mb_strlen($value) > 0;
                },
                true,
                function ($value) {
                    return trim(clean_string($value));
                }
            ),
            new Parameter(
                'personLastName1',
                null,
                function ($value) {
                    return is_string($value) && mb_strlen($value) > 0;
                },
                false,
                function ($value) {
                    return trim(clean_string($value));
                }
            ),
            new Parameter(
                'personLastName2',
                '',
                function ($value) {
                    return is_string($value) && mb_strlen($value) > 0;
                },
                true,
                function ($value) {
                    return trim(clean_string($value));
                }
            ),
        ]);

        //Obtención de datos
        $inputData = $request->getParsedBody();

        //Asignación de datos para procesar
        $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

        //──── Estructura de respuesta ───────────────────────────────────────────────────────────

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Persona'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación

        //Valores iniciales de la respuesta
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);

        //Mensajes de respuesta
        $notExistsMessage = __(self::LANG_GROUP, 'La persona que intenta modificar no existe.');
        $successCreateMessage = __(self::LANG_GROUP, 'Persona creada.');
        $successEditMessage = __(self::LANG_GROUP, 'Datos guardados.');
        $unknowErrorMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido.');
        $unknowErrorWithValuesMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido al procesar los valores ingresados.');
        $notAllowedLangMessage = __(self::LANG_GROUP, 'El idioma "%s" no está permitido.');

        //──── Acciones ──────────────────────────────────────────────────────────────────────────
        try {

            //Intenta validar, si todo sale bien el código continúa
            $expectedParameters->validate();

            //Información del formulario
            /**
             * @var string $lang
             * @var int $id
             * @var string $documentType
             * @var string $documentNumber
             * @var string $personName1
             * @var string $personName2
             * @var string $personLastName1
             * @var string $personLastName2
             */
            $lang = $expectedParameters->getValue('lang');
            $id = $expectedParameters->getValue('id');
            $documentType = $expectedParameters->getValue('documentType');
            $documentNumber = $expectedParameters->getValue('documentNumber');
            $personName1 = $expectedParameters->getValue('personName1');
            $personName2 = $expectedParameters->getValue('personName2');
            $personLastName1 = $expectedParameters->getValue('personLastName1');
            $personLastName2 = $expectedParameters->getValue('personLastName2');

            //Se define si es edición o creación
            $isEdit = $id !== -1;

            try {

                $allowedLangs = Config::get_allowed_langs();

                if ($isEdit) {
                    if (!in_array($lang, $allowedLangs)) {
                        throw new \Exception(vsprintf($notAllowedLangMessage, [$lang]));
                    }
                } else {
                    $lang = get_config('default_lang');
                }

                if (!$isEdit) {
                    //Nuevo

                    $mapper = new PersonsMapper();

                    $mapper->setLangData($lang, 'documentType', $documentType);
                    $mapper->setLangData($lang, 'documentNumber', $documentNumber);
                    $mapper->setLangData($lang, 'personName1', $personName1);
                    $mapper->setLangData($lang, 'personName2', $personName2);
                    $mapper->setLangData($lang, 'personLastName1', $personLastName1);
                    $mapper->setLangData($lang, 'personLastName2', $personLastName2);
                    $mapper->folder = str_replace('.', '', uniqid());

                    $saved = $mapper->save();

                    $resultOperation->setSuccessOnSingleOperation($saved);

                    if ($saved) {

                        $mapper->id = $mapper->getInsertIDOnSave();

                        $resultOperation
                            ->setMessage($successCreateMessage)
                            ->setValue('redirect', true)
                            ->setValue('redirect_to', self::routeName('list'));

                    } else {
                        $resultOperation->setMessage($unknowErrorMessage);
                    }

                } else {
                    //Existente

                    $mapper = new PersonsMapper((int) $id);
                    $exists = !is_null($mapper->id);

                    if ($exists) {

                        $mapper->setLangData($lang, 'documentType', $documentType);
                        $mapper->setLangData($lang, 'documentNumber', $documentNumber);
                        $mapper->setLangData($lang, 'personName1', $personName1);
                        $mapper->setLangData($lang, 'personName2', $personName2);
                        $mapper->setLangData($lang, 'personLastName1', $personLastName1);
                        $mapper->setLangData($lang, 'personLastName2', $personLastName2);

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

                        $resultOperation->setMessage($notExistsMessage);

                    }

                }

            } catch (SafeException | DuplicateException $e) {

                $resultOperation->setMessage($e->getMessage());

            } catch (\Exception $e) {

                $resultOperation->setMessage($e->getMessage());
                log_exception($e);

            }

        } catch (SafeException $e) {

            $resultOperation->setMessage($e->getMessage());

        } catch (ParsedValueException $e) {

            $resultOperation->setMessage($unknowErrorWithValuesMessage);
            log_exception($e);

        } catch (MissingRequiredParamaterException | InvalidParameterValueException | \Exception $e) {

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

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Eliminar persona'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación

        //Valores iniciales de la respuesta
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);
        $resultOperation->setValue('received', $inputData);

        //Mensajes de respuesta
        $notExistsMessage = __(self::LANG_GROUP, 'La persona que intenta eliminar no existe.');
        $successMessage = __(self::LANG_GROUP, 'Persona eliminada.');
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

                $element = new PersonsMapper($id);
                $exists = $element->id !== null;

                if ($exists) {

                    $table = PersonsMapper::TABLE;
                    $inactiveStatus = PersonsMapper::STATUS_INACTIVE;

                    $transactionSQLDeleteQueries = [
                        [
                            'query' => "UPDATE {$table} SET {$table}.status = {$inactiveStatus} WHERE {$table}.id = :ID",
                            'aliasConfig' => [
                                ':ID' => $id,
                            ],
                        ],
                    ];

                    $pdo = PersonsMapper::model()::getDb(Config::app_db('default')['db']);

                    try {

                        $pdo->beginTransaction();

                        foreach ($transactionSQLDeleteQueries as $sqlQueryConfig) {

                            $query = $sqlQueryConfig['query'];
                            $aliasConfig = $sqlQueryConfig['aliasConfig'];

                            $preparedStatement = $pdo->prepare($query);
                            $preparedStatement->execute($aliasConfig);

                        }

                        $pdo->commit();

                        $resultOperation->setSuccessOnSingleOperation(true);

                        //Dirección de redirección
                        $redirectURLOn = self::routeName('list');

                        $resultOperation
                            ->setMessage($successMessage)
                            ->setValue('redirect', true)
                            ->setValue('redirect_to', $redirectURLOn);

                    } catch (\Exception $e) {
                        if ($e instanceof PDOException) {
                            $pdo->rollBack();
                            $resultOperation->setValue('transactionError', $e->getMessage());
                        }
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
                'id',
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
         * @var int $id
         */
        $page = $expectedParameters->getValue('page');
        $perPage = $expectedParameters->getValue('per_page');
        $id = $expectedParameters->getValue('id');

        $result = self::_all($page, $perPage, $id);

        return $response->withJson($result);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function dataTables(Request $request, Response $response)
    {
        $status = PersonsMapper::STATUS_ACTIVE;

        $table = PersonsMapper::TABLE;

        $whereString = null;
        $where = [];

        if ($status !== null) {

            $beforeOperator = !empty($where) ? 'AND' : '';
            $field = "{$table}.status";
            $critery = "{$field} = {$status}";
            $where[] = "{$beforeOperator} ({$critery})";

        }

        if (!empty($where)) {
            $whereString = trim(implode(' ', $where));
        }

        $selectFields = PersonsMapper::fieldsToSelect();

        $columnsOrder = [
            'documentShortAndNumber',
            'fullname',
        ];
        $customOrder = [
            'idPadding' => 'DESC',
        ];

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);

        $result = DataTablesHelper::process([
            'where_string' => $whereString,
            'select_fields' => $selectFields,
            'columns_order' => $columnsOrder,
            'custom_order' => $customOrder,
            'mapper' => new PersonsMapper(),
            'request' => $request,
            'on_set_data' => function ($e) {

                $mapper = PersonsMapper::objectToMapper($e);

                $buttons = [];
                $hasEdit = self::allowedRoute('forms-edit', ['id' => $e->id]);
                $hasDelete = self::allowedRoute('actions-delete', ['id' => $e->id]);

                if ($hasEdit) {
                    $editLink = self::routeName('forms-edit', ['id' => $e->id]);
                    $editText = __(self::LANG_GROUP, 'Editar');
                    $editButton = "<a href='{$editLink}' class='ui button brand-color icon labeled'><i class='edit outline icon'></i> {$editText}</a>";
                    $buttons[] = $editButton;
                }
                if ($hasDelete) {
                    $deleteLink = self::routeName('actions-delete', ['id' => $mapper->id]);
                    $deleteText = __(self::LANG_GROUP, 'Eliminar');
                    $deleteButton = "<a data-route='{$deleteLink}' class='ui button brand-color alt2 icon labeled' delete-persons-button><i class='trash icon'></i> {$deleteText}</a>";
                    $buttons[] = $deleteButton;
                }

                $buttons = implode('', $buttons);
                $columns = [];

                $columns[] = $e->documentShortAndNumber;
                $columns[] = $e->fullname;
                $columns[] = $buttons;
                return $columns;
            },
        ]);

        return $response->withJson($result->getValues());
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function searchDropdown(Request $request, Response $response)
    {

        $search = $request->getQueryParam('search', null);
        $search = is_string($search) && mb_strlen(trim($search)) > 0 ? trim($search) : null;

        $results = new \stdClass;
        $results->success = true;
        $results->results = [];

        $model = PersonsMapper::model();
        $model->select(PersonsMapper::fieldsToSelect());
        $model->orderBy('fullname ASC');

        if ($search !== null) {

            $search = mb_strtolower($search);
            $having = [
                "LOWER(fullname) LIKE LOWER('{$search}%')",
            ];
            $having = trim(implode(' ', $having));

            $model->having($having);

            $model->execute();

        } else {
            $model->execute(false, 1, 15);
        }

        $resultsQuery = $model->result();
        $resultsQuery = is_array($resultsQuery) ? $resultsQuery : [];

        foreach ($resultsQuery as $element) {
            $results->results[] = [
                'value' => $element->id,
                'name' => $element->fullname,
            ];
        }

        return $response->withJson($results);
    }

    /**
     * @param int $page =1
     * @param int $perPage =10
     * @param int $id =NULL
     * @return PaginationResult
     */
    public static function _all(
        int $page = null,
        int $perPage = null,
        int $id = null
    ) {
        $page = $page === null ? 1 : $page;
        $perPage = $perPage === null ? 10 : $perPage;

        $table = PersonsMapper::TABLE;
        $fields = PersonsMapper::fieldsToSelect();
        $jsonExtractExists = PersonsMapper::jsonExtractExistsMySQL();

        $whereString = null;
        $where = [];
        $and = 'AND';

        //Verificación de idioma
        $defaultLang = Config::get_default_lang();
        $currentLang = Config::get_lang();

        if ($currentLang != $defaultLang) {

            if ($jsonExtractExists) {
                $beforeOperator = !empty($where) ? $and : '';
                $critery = "JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$.langData.{$currentLang}')) IS NOT NULL";
                $where[] = "{$beforeOperator} ({$critery})";
            } else {
                $beforeOperator = !empty($where) ? $and : '';
                $critery = "POSITION('\"{$currentLang}\":{' IN meta) != 0 || POSITION(\"'{$currentLang}':{\" IN meta) != 0";
                $where[] = "{$beforeOperator} ({$critery})";
            }

        }

        if ($id != null) {
            $beforeOperator = !empty($where) ? $and : '';
            $critery = "{$table}.id = {$id}";
            $where[] = "{$beforeOperator} ({$critery})";
        }

        if (!empty($where)) {
            $whereString = implode(' ', $where);
        }

        $fields = implode(', ', $fields);
        $sqlSelect = "SELECT {$fields} FROM {$table}";
        $sqlCount = "SELECT COUNT({$table}.id) AS total FROM {$table}";

        if ($whereString !== null) {
            $sqlSelect .= " WHERE {$whereString}";
            $sqlCount .= " WHERE {$whereString}";
        }

        $sqlSelect .= " ORDER BY " . implode(', ', PersonsMapper::ORDER_BY_PREFERENCE);

        $pageQuery = new PageQuery($sqlSelect, $sqlCount, $page, $perPage, 'total');

        $parser = null;
        $each = null;

        $pagination = $pageQuery->getPagination($parser, $each);

        return $pagination;
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
        return (new PersonsController)->render(self::BASE_VIEW_DIR . '/' . trim($name, '/'), $data, $mode, $format);
    }

    /**
     * Verificar si una ruta es permitida
     *
     * @param string $name
     * @param array $params
     * @return bool
     */
    public static function allowedRoute(string $name, array $params = [])
    {
        $route = self::routeName($name, $params, true);
        $allow = strlen($route) > 0;
        return $allow;
    }

    /**
     * Verificar si una ruta es permitida y determinar pasos para permitirla o no
     *
     * @param string $name
     * @param string $route
     * @param array $params
     * @return bool
     */
    private static function _allowedRoute(string $name, string $route, array $params = [])
    {

        $allow = strlen($route) > 0;

        if ($allow) {

            $currentUser = getLoggedFrameworkUser();

            if ($currentUser !== null) {

                $currentUserType = $currentUser->type;
                $currentUserID = $currentUser->id;
                $canViewAll = in_array($currentUserType, PersonsMapper::CAN_VIEW_ALL);
                $candAddAll = in_array($currentUserType, PersonsMapper::CAN_ADD_ALL);

                if ($name == 'actions-delete') {

                    $allow = false;
                    $id = $params['id'];
                    $element = PersonsMapper::getBy($id, 'id');

                    if ($element !== null) {

                        $createdByID = (int) $element->createdBy;
                        $allow = $createdByID == $currentUserID;

                        if (in_array($currentUserType, PersonsMapper::CAN_DELETE_ALL)) {
                            $allow = true;
                        }

                    }

                } elseif ($name == 'forms-edit' || $name == 'actions-edit') {

                    $id = isset($params['id']) ? $params['id'] : null;
                    $id = $id !== null ? $id : (isset($_GET['id']) ? $_GET['id'] : null);
                    $id = $id !== null ? $id : (isset($_POST['id']) ? $_POST['id'] : null);

                    if ($id !== null) {

                        $element = PersonsMapper::getBy($id, 'id');

                        if ($element !== null) {

                            $allow = false;

                            $createdByID = (int) $element->createdBy;
                            $allow = $createdByID == $currentUserID;

                            if (in_array($currentUserType, PersonsMapper::CAN_EDIT_ALL)) {
                                $allow = true;
                            }

                        }

                    }

                } elseif ($name == 'forms-add' || $name == 'actions-add') {

                    $allow = false;

                    if (!$candAddAll) {
                        $allow = true;
                    } else {
                        $allow = true;
                    }

                }

                $checkNames = [
                    'list',
                ];

                if (in_array($name, $checkNames)) {

                    $allow = false;

                    if (!$canViewAll) {
                        $allow = true;
                    } else {
                        $allow = true;
                    }

                }

            }

        }

        return $allow;
    }

    /**
     * @param string $nameOnFiles
     * @param string $folder
     * @param string $currentRoute
     * @param array $allowedTypes
     * @param bool $setNameByInput
     * @param string $name
     * @return string
     * @throws \Exception
     */
    protected static function handlerUpload(string $nameOnFiles, string $folder, string $currentRoute = null, array $allowedTypes = null, bool $setNameByInput = true, string $name = null)
    {
        if ($allowedTypes === null) {
            $allowedTypes = [
                FileValidator::TYPE_ALL_IMAGES,
            ];
        }
        $handler = new FileUpload($nameOnFiles, $allowedTypes);
        $valid = false;
        $relativeURL = '';

        $name = $name !== null ? $name : 'file_' . uniqid();
        $oldFile = null;

        if ($handler->hasInput()) {

            try {

                $valid = $handler->validate();

                $instance = new PersonsController;
                $uploadDirPath = $instance->uploadDir;
                $uploadDirRelativeURL = $instance->uploadDirURL;

                if ($setNameByInput && $valid) {

                    $name = $_FILES[$nameOnFiles]['name'];
                    $lastPointIndex = mb_strrpos($name, '.');

                    if ($lastPointIndex !== false) {
                        $name = mb_substr($name, 0, $lastPointIndex);
                    }

                }

                if (!is_null($currentRoute)) {
                    //Si ya existe
                    $oldFile = append_to_url(basepath(), $currentRoute);
                    $oldFile = file_exists($oldFile) ? $oldFile : null;

                    if (mb_strlen(trim($folder)) < 1) {
                        //Si folder está vacío
                        $folder = str_replace($uploadDirRelativeURL, '', $currentRoute);
                        $folder = str_replace(basename($currentRoute), '', $folder);
                        $folder = trim($folder, '/');
                    }

                }

                $uploadDirPath = append_to_path_system($uploadDirPath, $folder);
                $uploadDirRelativeURL = append_to_url($uploadDirRelativeURL, $folder);

                if ($valid) {

                    $locations = $handler->moveTo($uploadDirPath, $name, null, false, true);

                    if (!empty($locations)) {

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
     * Obtener URL de una ruta
     *
     * @param string $name
     * @param array $params
     * @param bool $silentOnNotExists
     * @return string
     */
    public static function routeName(string $name = null, array $params = [], bool $silentOnNotExists = false)
    {

        $simpleName = !is_null($name) ? $name : '';

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

        $route = '';

        if ($allowed) {
            $route = get_route(
                $name,
                $params,
                $silentOnNotExists
            );
            $route = !is_string($route) ? '' : $route;
        }

        $allow = self::_allowedRoute($simpleName, $route, $params);

        return $allow ? $route : '';
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

        //Permisos
        $list = $allRoles;
        $filterView = $allRoles;
        $creation = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN,
            UsersModel::TYPE_USER_GENERAL,
        ];
        $edition = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN,
            UsersModel::TYPE_USER_GENERAL,
        ];
        $deletion = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN,
            UsersModel::TYPE_USER_GENERAL,
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
                $list
            ),
            new Route( //Formulario de crear
                "{$startRoute}/forms/agregar[/]",
                $classname . ':addForm',
                self::$baseRouteName . '-forms-add',
                'GET',
                true,
                null,
                $creation
            ),
            new Route( //Formulario de editar
                "{$startRoute}/forms/editar/{id}/{lang}[/]",
                $classname . ':editForm',
                self::$baseRouteName . '-forms-edit',
                'GET',
                true,
                null,
                $edition,
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
                $list
            ),
            //Search Dropdown
            new Route(
                "{$startRoute}/search-dropdown[/]",
                $classname . ':searchDropdown',
                self::$baseRouteName . '-search-dropdown',
                'GET',
            ),

            //──── POST ──────────────────────────────────────────────────────────────────────────────
            new Route( //Acción de crear
                "{$startRoute}/action/add[/]",
                $classname . ':action',
                self::$baseRouteName . '-actions-add',
                'POST',
                true,
                null,
                $creation
            ),
            new Route( //Acción de editar
                "{$startRoute}/action/edit[/]",
                $classname . ':action',
                self::$baseRouteName . '-actions-edit',
                'POST',
                true,
                null,
                $edition
            ),
            new Route( //Acción de eliminar
                "{$startRoute}/action/delete/{id}[/]",
                $classname . ':toDelete',
                self::$baseRouteName . '-actions-delete',
                'POST',
                true,
                null,
                $deletion
            ),

        ];

        $group->register($routes);

        $group->addMiddleware(function (\PiecesPHP\Core\Routing\RequestRoutePiecesPHP $request, $handler) {

            $response = $handler->handle($request);

            $route = $request->getRoute();
            $routeName = $route->getName();
            $routeArguments = $route->getArguments();
            $routeArguments = is_array($routeArguments) ? $routeArguments : [];
            $basenameRoute = self::$baseRouteName . '-';

            if (strpos($routeName, $basenameRoute) !== false) {

                $simpleName = str_replace($basenameRoute, '', $routeName);
                $routeURL = self::routeName($simpleName, $routeArguments);
                $allowed = mb_strlen($routeURL) > 0;

                if (!$allowed) {
                    return throw403($request, $response);
                }

            }
            return $response;
        });

        return $group;
    }
}
