<?php

/**
 * NewsController.php
 */

namespace News\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use News\Exceptions\DuplicateException;
use News\Exceptions\SafeException;
use News\Mappers\NewsCategoryMapper;
use News\Mappers\NewsMapper;
use News\NewsLang;
use News\NewsRoutes;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Pagination\PageQuery;
use PiecesPHP\Core\Pagination\PaginationResult;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParamaterException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\ParsedValueException;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;

/**
 * NewsController.
 *
 * @package     News\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2022
 */
class NewsController extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $URLDirectory = 'news';
    /**
     * @var string
     */
    protected static $baseRouteName = 'news-admin';
    /**
     * @var string
     */
    protected static $title = 'Noticia';
    /**
     * @var string
     */
    protected static $pluralTitle = 'Noticias';

    /**
     * @var HelperController
     */
    protected $helpController = null;

    const BASE_VIEW_DIR = 'news';
    const BASE_JS_DIR = 'js/news';
    const BASE_CSS_DIR = 'css';
    const LANG_GROUP = NewsLang::LANG_GROUP;

    const RESPONSE_SOURCE_STATIC_CACHE = 'STATIC_CACHE';
    const RESPONSE_SOURCE_NORMAL_RESULT = 'NORMAL_RESULT';
    const ENABLE_CACHE = true;

    public function __construct()
    {
        parent::__construct();

        self::$title = __(self::LANG_GROUP, self::$title);
        self::$pluralTitle = __(self::LANG_GROUP, self::$pluralTitle);

        $this->model = (new NewsMapper())->getModel();
        set_title(self::$title);

        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());

        $this->setInstanceViewDir(__DIR__ . '/../Views/');

        add_global_asset(NewsRoutes::staticRoute('globals-vars.css'), 'css');
        add_global_asset(NewsRoutes::staticRoute(self::BASE_CSS_DIR . '/news.css'), 'css');

        NewsCategoryMapper::uncategorizedCategory();

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function addForm(Request $request, Response $response)
    {

        set_custom_assets([
            NewsRoutes::staticRoute(self::BASE_JS_DIR . '/forms.js'),
        ], 'js');

        import_default_rich_editor();

        $action = self::routeName('actions-add');
        $backLink = self::routeName('list');
        $allCategories = array_to_html_options(NewsCategoryMapper::allForSelect(), null);
        $ignoreUserTypes = [
            UsersModel::TYPE_USER_ROOT,
        ];
        $userTypesForSelect = array_filter(UsersModel::typesUserForSelect(), function ($key) use ($ignoreUserTypes) {
            return !in_array($key, $ignoreUserTypes);
        }, \ARRAY_FILTER_USE_KEY);
        $allUsersTypes = array_to_html_options($userTypesForSelect, null, true);

        $data = [];
        $data['action'] = $action;
        $data['langGroup'] = self::LANG_GROUP;
        $data['backLink'] = $backLink;
        $data['title'] = __(self::LANG_GROUP, 'Gestión de noticias');
        $data['allCategories'] = $allCategories;
        $data['allUsersTypes'] = $allUsersTypes;

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

        $element = new NewsMapper($id);

        if ($element->id !== null && NewsMapper::existsByID($element->id)) {

            set_custom_assets([
                NewsRoutes::staticRoute(self::BASE_JS_DIR . '/delete-config.js'),
                NewsRoutes::staticRoute(self::BASE_JS_DIR . '/forms.js'),
            ], 'js');

            import_default_rich_editor();

            $action = self::routeName('actions-edit');
            $backLink = self::routeName('list');
            $manyLangs = count($allowedLangs) > 1;
            $allCategories = array_to_html_options(NewsCategoryMapper::allForSelect(), $element->category->id);
            $ignoreUserTypes = [
                UsersModel::TYPE_USER_ROOT,
            ];
            $userTypesForSelect = array_filter(UsersModel::typesUserForSelect(), function ($key) use ($ignoreUserTypes) {
                return !in_array($key, $ignoreUserTypes);
            }, \ARRAY_FILTER_USE_KEY);
            $allUsersTypes = array_to_html_options($userTypesForSelect, $element->profilesTarget, true);
            $allowedLangs = array_to_html_options(self::allowedLangsForSelect($lang, $element->id), $lang);

            $data = [];
            $data['action'] = $action;
            $data['element'] = $element;
            $data['deleteRoute'] = self::routeName('actions-delete', ['id' => $element->id]);
            $data['allowDelete'] = self::allowedRoute('actions-delete', ['id' => $element->id]);
            $data['langGroup'] = self::LANG_GROUP;
            $data['backLink'] = $backLink;
            $data['title'] = __(self::LANG_GROUP, 'Gestión de noticias');
            $data['allCategories'] = $allCategories;
            $data['allUsersTypes'] = $allUsersTypes;
            $data['allowedLangs'] = $allowedLangs;
            $data['manyLangs'] = $manyLangs;
            $data['lang'] = $lang;

            $this->helpController->render('panel/layout/header');
            self::view('forms/edit', $data, true, false);
            $this->helpController->render('panel/layout/footer');

            return $response;

        } else {
            throw new NotFoundException($request, $response);
        }

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function listView(Request $request, Response $response)
    {

        $backLink = get_route('admin');
        $addLink = self::routeName('forms-add');
        $listCategoriesLink = NewsCategoryController::routeName('list');
        $processTableLink = self::routeName('datatables');

        $title = self::$pluralTitle;

        $data = [];
        $data['processTableLink'] = $processTableLink;
        $data['langGroup'] = self::LANG_GROUP;
        $data['backLink'] = $backLink;
        $data['addLink'] = $addLink;
        $data['hasPermissionsAdd'] = strlen($addLink) > 0;
        $data['listCategoriesLink'] = $listCategoriesLink;
        $data['hasPermissionsListCategories'] = strlen($listCategoriesLink) > 0;
        $data['title'] = $title;

        set_custom_assets([
            NewsRoutes::staticRoute(self::BASE_JS_DIR . '/delete-config.js'),
            NewsRoutes::staticRoute(self::BASE_JS_DIR . '/list.js'),
        ], 'js');

        $this->helpController->render('panel/layout/header');
        self::view('list', $data);
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
                'profilesTarget',
                null,
                function ($value) {
                    return is_array($value) && !empty($value);
                },
                false,
                function ($value) {
                    $parse = [];
                    if (is_array($value)) {
                        foreach ($value as $k => $i) {
                            if (Validator::isInteger($i)) {
                                $parse[] = (int) $i;
                            }
                        }
                    }
                    return $parse;
                }
            ),
            new Parameter(
                'category',
                null,
                function ($value) {
                    return ctype_digit($value) || is_int($value) || $value == NewsCategoryMapper::UNCATEGORIZED_ID;
                },
                false,
                function ($value) {
                    return (int) $value;
                }
            ),
            new Parameter(
                'newsTitle',
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
                'content',
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
                'startDate',
                null,
                function ($value) {
                    return $value === null || Validator::isDate($value, 'd-m-Y h:i A');
                },
                true,
                function ($value) {
                    return $value === null ? $value : \DateTime::createFromFormat('d-m-Y h:i A', $value);
                }
            ),
            new Parameter(
                'endDate',
                null,
                function ($value) {
                    return $value === null || Validator::isDate($value, 'd-m-Y h:i A');
                },
                true,
                function ($value) {
                    return $value === null ? $value : \DateTime::createFromFormat('d-m-Y h:i A', $value);
                }
            ),
        ]);

        //Obtención de datos
        $inputData = $request->getParsedBody();

        //Asignación de datos para procesar
        $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

        //──── Estructura de respuesta ───────────────────────────────────────────────────────────

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Noticia'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación

        //Valores iniciales de la respuesta
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);

        //Mensajes de respuesta
        $notExistsMessage = __(self::LANG_GROUP, 'La noticia que intenta modificar no existe.');
        $successCreateMessage = __(self::LANG_GROUP, 'Noticia creada.');
        $successEditMessage = __(self::LANG_GROUP, 'Datos guardados.');
        $unknowErrorMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido.');
        $unknowErrorWithValuesMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido al procesar los valores ingresados.');
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
             * @var int[] $profilesTarget
             * @var int $category
             * @var string $content
             * @var string $newsTitle
             * @var \DateTime|null $startDate
             * @var \DateTime|null $endDate
             */
            $id = $expectedParameters->getValue('id');
            $lang = $expectedParameters->getValue('lang');
            $profilesTarget = $expectedParameters->getValue('profilesTarget');
            $category = $expectedParameters->getValue('category');
            $newsTitle = $expectedParameters->getValue('newsTitle');
            $content = $expectedParameters->getValue('content');
            $startDate = $expectedParameters->getValue('startDate');
            $endDate = $expectedParameters->getValue('endDate');

            //Se define si es edición o creación
            $isEdit = $id !== -1;

            try {

                $allowedLangs = Config::get_allowed_langs();

                if (!NewsCategoryMapper::existsByID($category)) {
                    throw new SafeException($categoryNotExistsMessage);
                }

                if ($isEdit) {
                    if (!in_array($lang, $allowedLangs)) {
                        throw new SafeException(vsprintf($notAllowedLangMessage, [$lang]));
                    }
                } else {
                    $lang = get_config('default_lang');
                }

                if (!$isEdit) {
                    //Nuevo

                    $mapper = new NewsMapper();

                    $mapper->setLangData($lang, 'profilesTarget', $profilesTarget);
                    $mapper->setLangData($lang, 'category', $category);
                    $mapper->setLangData($lang, 'newsTitle', $newsTitle);
                    $mapper->setLangData($lang, 'content', $content);
                    $mapper->setLangData($lang, 'startDate', $startDate);
                    $mapper->setLangData($lang, 'endDate', $endDate);
                    $mapper->setLangData($lang, 'folder', str_replace('.', '', uniqid()));

                    $saved = $mapper->save();
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
                    //Existente

                    $mapper = new NewsMapper((int) $id);
                    $exists = !is_null($mapper->id);

                    if ($exists) {

                        $mapper->setLangData($lang, 'profilesTarget', $profilesTarget);
                        $mapper->setLangData($lang, 'category', $category);
                        $mapper->setLangData($lang, 'newsTitle', $newsTitle);
                        $mapper->setLangData($lang, 'content', $content);
                        $mapper->setLangData($lang, 'startDate', $startDate);
                        $mapper->setLangData($lang, 'endDate', $endDate);

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

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Eliminar noticia'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación

        //Valores iniciales de la respuesta
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);
        $resultOperation->setValue('received', $inputData);

        //Mensajes de respuesta
        $notExistsMessage = __(self::LANG_GROUP, 'La noticia que intenta eliminar no existe.');
        $successMessage = __(self::LANG_GROUP, 'Noticia eliminada.');
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

                $exists = NewsMapper::existsByID($id);

                if ($exists) {

                    //Dirección de redirección en caso de creación
                    $redirectURLOn = self::routeName('list');

                    $table = NewsMapper::TABLE;
                    $inactive = NewsMapper::INACTIVE;

                    $transactionSQLDeleteQueries = [
                        [
                            'query' => "UPDATE {$table} SET {$table}.status = {$inactive} WHERE id = :ID",
                            'aliasConfig' => [
                                ':ID' => $id,
                            ],
                        ],
                    ];

                    $pdo = NewsMapper::model()::getDb(Config::app_db('default')['db']);

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
                    return (ctype_digit($value) || is_int($value)) || $value == NewsCategoryMapper::UNCATEGORIZED_ID;
                },
                true,
                function ($value) {
                    return (int) $value;
                }
            ),
            new Parameter(
                'status',
                null,
                function ($value) {
                    return ctype_digit($value) || is_int($value) || (is_string($value) && mb_strtoupper($value) == 'ANY');
                },
                true,
                function ($value) {
                    return (is_string($value) && mb_strtoupper($value) == 'ANY') ? 'ANY' : (int) $value;
                }
            ),
        ]);

        $expectedParameters->setInputValues($request->getQueryParams());
        $expectedParameters->validate();

        /**
         * @var int $page
         * @var int $perPage
         * @var int $category
         * @var int $status
         */
        $page = $expectedParameters->getValue('page');
        $perPage = $expectedParameters->getValue('per_page');
        $category = $expectedParameters->getValue('category');
        $status = $expectedParameters->getValue('status');

        $ignoreStatus = $status === 'ANY';
        $status = $status === 'ANY' ? null : $status;

        $sourceData = self::RESPONSE_SOURCE_NORMAL_RESULT;
        $result = self::_all($page, $perPage, $category, $status, $ignoreStatus);
        $response = $response->withJson($result);

        $response = $response->withHeader('PCSPHP-Response-Source', $sourceData);

        return $response;

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function dataTables(Request $request, Response $response)
    {

        $whereString = null;
        $table = NewsMapper::TABLE;
        $inactive = NewsMapper::INACTIVE;

        $where = [
            "{$table}.status != {$inactive}",
        ];

        if (!empty($where)) {
            $whereString = trim(implode(' ', $where));
        }

        $selectFields = NewsMapper::fieldsToSelect();

        $columnsOrder = [
            'idPadding',
            'newsTitle',
            'categoryName',
            'startDateFormat',
            'endDateFormat',
            'activeText',
        ];

        $customOrder = [
            'idPadding' => 'DESC',
            'createdAt' => 'DESC',
            'updatedAt' => 'DESC',
            'newsTitle' => 'ASC',
            'categoryName' => 'ASC',
        ];

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);

        $result = DataTablesHelper::process([

            'where_string' => $whereString,
            'select_fields' => $selectFields,
            'columns_order' => $columnsOrder,
            'custom_order' => $customOrder,
            'mapper' => new NewsMapper(),
            'request' => $request,
            'on_set_data' => function ($e) {

                $mapper = NewsMapper::objectToMapper($e);

                $buttons = [];
                $hasEdit = self::allowedRoute('forms-edit', ['id' => $e->id]);
                $hasDelete = self::allowedRoute('actions-delete', ['id' => $e->id]);

                if ($hasEdit) {
                    $editLink = self::routeName('forms-edit', ['id' => $e->id]);
                    $editText = __(self::LANG_GROUP, 'Editar');
                    $editIcon = "<i class='icon edit'></i>";
                    $editButton = "<a title='{$editText}' href='{$editLink}' class='ui button brand-color icon'>{$editIcon}</a>";
                    $buttons[] = $editButton;
                }

                if ($hasDelete) {
                    $deleteLink = self::routeName('actions-delete', ['id' => $mapper->id]);
                    $deleteText = __(self::LANG_GROUP, 'Eliminar');
                    $deleteIcon = "<i class='icon trash'></i>";
                    $deleteButton = "<a title='{$deleteText}' data-route='{$deleteLink}' class='ui button brand-color alt2 icon' delete-news-button>{$deleteIcon}</a>";
                    $buttons[] = $deleteButton;
                }

                $buttons = implode('', $buttons);
                $columns = [];

                $tagColor = NewsMapper::STATUSES_COLORS[$e->activeStatus];
                $tag = "<span class='ui {$tagColor} tag label'>{$e->activeText}</span>";
                $newsTitle = mb_strlen($e->newsTitle) <= 54 ? $e->newsTitle : mb_substr($e->newsTitle, 0, 51) . '...';

                $columns[] = $e->idPadding;
                $columns[] = $newsTitle;
                $columns[] = $e->categoryName;
                $columns[] = $e->startDateFormat;
                $columns[] = $e->endDateFormat;
                $columns[] = $tag;
                $columns[] = $buttons;
                return $columns;
            },

        ]);

        return $response->withJson($result->getValues());
    }

    /**
     * @param int $page =1
     * @param int $perPage =10
     * @param int $category =null
     * @param int $status =NewsMapper::ACTIVE
     * @param bool $ignoreStatus =false
     * @param bool $ignoreDateLimit =false
     * @return PaginationResult
     */
    public static function _all(
        int $page = null,
        int $perPage = null,
        int $category = null,
        int $status = null,
        bool $ignoreStatus = false,
        bool $ignoreDateLimit = false
    ) {
        $page = $page === null ? 1 : $page;
        $perPage = $perPage === null ? 10 : $perPage;
        $status = $status === null ? NewsMapper::ACTIVE : $status;

        $table = NewsMapper::TABLE;
        $fields = NewsMapper::fieldsToSelect();
        $jsonExtractExists = NewsMapper::jsonExtractExistsMySQL();

        $whereString = null;
        $where = [];
        $and = 'AND';

        $currentUser = getLoggedFrameworkUser();
        $currentTypeUser = $currentUser !== null ? $currentUser->type : -1;

        if (!in_array($currentTypeUser, NewsMapper::CAN_VIEW_TARGET_ALL)) {
            $beforeOperator = !empty($where) ? $and : '';
            $critery = "JSON_CONTAINS({$table}.profilesTarget, {$currentTypeUser}, '$')";
            $where[] = "{$beforeOperator} ({$critery})";
        }

        if ($category !== null) {

            $beforeOperator = !empty($where) ? $and : '';
            $critery = "{$table}.category = {$category}";
            $where[] = "{$beforeOperator} ({$critery})";

        }

        if (!$ignoreStatus) {

            $beforeOperator = !empty($where) ? $and : '';
            $critery = "{$table}.status = {$status}";
            $where[] = "{$beforeOperator} ({$critery})";

        }

        $now = \DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:00'));
        $now = $now->getTimestamp();
        $unixNowDate = "FROM_UNIXTIME({$now})";
        $startDateSQL = "{$table}.startDate";

        if (!$ignoreDateLimit) {

            $beforeOperator = !empty($where) ? $and : '';
            $critery = "{$startDateSQL} <= {$unixNowDate} OR {$table}.startDate IS NULL";
            $where[] = "{$beforeOperator} ({$critery})";

            $beforeOperator = !empty($where) ? $and : '';
            $critery = "DATE_ADD({$table}.endDate, INTERVAL 15 DAY) > {$unixNowDate} OR {$table}.endDate IS NULL";
            $where[] = "{$beforeOperator} ({$critery})";

        }

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

        $sqlSelect .= " ORDER BY " . implode(', ', NewsMapper::ORDER_BY_PREFERENCE);

        $pageQuery = new PageQuery($sqlSelect, $sqlCount, $page, $perPage, 'total');

        $parser = function ($element) {
            $element = NewsMapper::objectToMapper($element);
            $element = mb_convert_encoding(self::view('public/util/item', [
                'element' => $element,
                'langGroup' => self::LANG_GROUP,
            ], false, false), 'UTF-8');
            return $element;
        };
        $each = !$jsonExtractExists ? function ($element) {
            $mapper = NewsMapper::objectToMapper($element);
            $element = NewsMapper::translateEntityObject($element);
            return $element;
        } : function ($element) {
            $mapper = new NewsMapper($element->id);
            $elementData = $mapper->humanReadable();
            $elementData['profilesTarget'] = (array) $mapper->profilesTarget;
            $elementData['createdBy'] = $mapper->createdBy->id;
            $elementData['modifiedBy'] = $mapper->modifiedBy !== null ? $mapper->modifiedBy->id : null;
            $elementData['category']['iconImage'] = baseurl($elementData['category']['iconImage']);
            unset($elementData['category']['meta']);
            unset($elementData['category']['META:langData']);
            unset($elementData['meta']);
            unset($elementData['META:langData']);
            return $elementData;
        };

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
        return (new NewsController)->render(self::BASE_VIEW_DIR . '/' . trim($name, '/'), $data, $mode, $format);
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

        $getParam = function ($paramName) use ($params) {
            $_POST = isset($_POST) && is_array($_POST) ? $_POST : [];
            $_GET = isset($_GET) && is_array($_GET) ? $_GET : [];
            $paramValue = isset($params[$paramName]) ? $params[$paramName] : null;
            $paramValue = $paramValue !== null ? $paramValue : (isset($_GET[$paramName]) ? $_GET[$paramName] : null);
            $paramValue = $paramValue !== null ? $paramValue : (isset($_POST[$paramName]) ? $_POST[$paramName] : null);
            return $paramValue;
        };

        $allow = strlen($route) > 0;

        if ($allow) {

            $currentUser = getLoggedFrameworkUser();

            if ($currentUser !== null) {

                $currentUserType = $currentUser->type;
                $currentUserID = $currentUser->id;

                if ($name == 'actions-delete') {

                    $allow = false;
                    $id = ($getParam)('id');
                    $news = NewsMapper::getBy($id, 'id');

                    if ($news !== null) {

                        $createdByID = (int) $news->createdBy;
                        $allow = $createdByID == $currentUserID;

                        if (in_array($currentUserType, NewsMapper::CAN_DELETE_ALL)) {
                            $allow = true;
                        }

                    }

                }

            }

        }

        return $allow;
    }

    public static function pathFrontNewsAdapter()
    {
        return NewsRoutes::staticRoute('js/NewsAdapter.js');
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
        $queries = $allRoles;
        $list = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN,
            UsersModel::TYPE_USER_GENERAL,
        ];
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
                "{$startRoute}/forms/add[/]",
                $classname . ':addForm',
                self::$baseRouteName . '-forms-add',
                'GET',
                true,
                null,
                $creation
            ),
            new Route( //Formulario de editar
                "{$startRoute}/forms/edit/{id}/{lang}[/]",
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

        $group->addMiddleware(function (\PiecesPHP\Core\Routing\RequestRoute $request, $handler) {
            return (new DefaultAccessControlModules(self::$baseRouteName . '-', function (string $name, array $params) {
                return self::routeName($name, $params);
            }))->getResponse($request, $handler);
        });

        return $group;
    }
}
