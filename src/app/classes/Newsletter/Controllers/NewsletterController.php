<?php

/**
 * NewsletterController.php
 */

namespace Newsletter\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use Newsletter\Mappers\NewsletterSuscriberMapper;
use Newsletter\NewsletterLang;
use Newsletter\NewsletterRoutes;
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

/**
 * NewsletterController.
 *
 * @package     Newsletter\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 */
class NewsletterController extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $URLDirectory = 'newsletter';
    /**
     * @var string
     */
    protected static $baseRouteName = 'newsletter-admin';
    /**
     * @var string
     */
    protected static $title = 'Suscriptor';
    /**
     * @var string
     */
    protected static $pluralTitle = 'Suscriptores';

    /**
     * @var HelperController
     */
    protected $helpController = null;

    const BASE_VIEW_DIR = 'newsletter';
    const BASE_JS_DIR = 'js/newsletter';
    const BASE_CSS_DIR = 'css';
    const LANG_GROUP = NewsletterLang::LANG_GROUP;

    public function __construct()
    {
        parent::__construct();

        self::$title = __(self::LANG_GROUP, self::$title);
        self::$pluralTitle = __(self::LANG_GROUP, self::$pluralTitle);

        $this->model = (new NewsletterSuscriberMapper())->getModel();
        set_title(self::$title);

        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());

        $this->setInstanceViewDir(__DIR__ . '/../Views/');

        add_global_asset(NewsletterRoutes::staticRoute('globals-vars.css'), 'css');
        add_global_asset(NewsletterRoutes::staticRoute(self::BASE_CSS_DIR . '/newsletter.css'), 'css');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function addForm(Request $request, Response $response)
    {

        set_custom_assets([
            NewsletterRoutes::staticRoute(self::BASE_JS_DIR . '/forms.js'),
        ], 'js');

        $action = self::routeName('actions-add');
        $backLink = self::routeName('list');

        $title = __(self::LANG_GROUP, 'Agregar suscriptor');
        $description = '';

        set_title($title . (mb_strlen($description) > 0 ? " - {$description}" : ''));

        $data = [];
        $data['action'] = $action;
        $data['langGroup'] = self::LANG_GROUP;
        $data['title'] = $title;
        $data['description'] = $description;
        $data['breadcrumbs'] = get_breadcrumbs([
            __(self::LANG_GROUP, 'Inicio') => [
                'url' => get_route('admin'),
            ],
            __(self::LANG_GROUP, 'Suscriptores') => [
                'url' => $backLink,
            ],
            $title,
        ]);

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

        $element = new NewsletterSuscriberMapper($id);

        if ($element->id !== null && NewsletterSuscriberMapper::existsByID($element->id)) {

            set_custom_assets([
                NewsletterRoutes::staticRoute(self::BASE_JS_DIR . '/delete-config.js'),
                NewsletterRoutes::staticRoute(self::BASE_JS_DIR . '/forms.js'),
            ], 'js');

            $action = self::routeName('actions-edit');
            $backLink = self::routeName('list');

            $title = __(self::LANG_GROUP, 'Editar suscriptor');
            $description = '';

            set_title($title . (mb_strlen($description) > 0 ? " - {$description}" : ''));

            $data = [];
            $data['action'] = $action;
            $data['element'] = $element;
            $data['deleteRoute'] = self::routeName('actions-delete', ['id' => $element->id]);
            $data['allowDelete'] = self::allowedRoute('actions-delete', ['id' => $element->id]);
            $data['langGroup'] = self::LANG_GROUP;
            $data['title'] = $title;
            $data['description'] = $description;
            $data['breadcrumbs'] = get_breadcrumbs([
                __(self::LANG_GROUP, 'Inicio') => [
                    'url' => get_route('admin'),
                ],
                __(self::LANG_GROUP, 'Suscriptores') => [
                    'url' => $backLink,
                ],
                $title,
            ]);

            $this->helpController->render('panel/layout/header');
            self::view('forms/edit', $data, true, false);
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

        $addLink = self::routeName('forms-add');
        $processTableLink = self::routeName('datatables');

        $title = __(self::LANG_GROUP, 'Suscriptores');
        $description = __(self::LANG_GROUP, 'Listado');

        set_title($title . (mb_strlen($description) > 0 ? " - {$description}" : ''));

        $data = [];
        $data['processTableLink'] = $processTableLink;
        $data['langGroup'] = self::LANG_GROUP;
        $data['addLink'] = $addLink;
        $data['hasPermissionsAdd'] = strlen($addLink) > 0;
        $data['title'] = $title;
        $data['description'] = $description;
        $data['breadcrumbs'] = get_breadcrumbs([
            __(self::LANG_GROUP, 'Inicio') => [
                'url' => get_route('admin'),
            ],
            $title,
        ]);

        set_custom_assets([
            NewsletterRoutes::staticRoute(self::BASE_JS_DIR . '/delete-config.js'),
            NewsletterRoutes::staticRoute(self::BASE_JS_DIR . '/list.js'),
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
                'name',
                NewsletterSuscriberMapper::UNSPECIFIED_VALUE,
                function ($value) {
                    return is_string($value) && strlen(trim($value)) > 0;
                },
                true,
                function ($value) {
                    return clean_string($value);
                }
            ),
            new Parameter(
                'email',
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
                'acceptUpdates',
                false,
                function ($value) {
                    $isNumber = ctype_digit($value) || is_int($value);
                    $value = $isNumber ? $value == NewsletterSuscriberMapper::ACCEPT_UPDATES_YES : false;
                    return is_bool($value);
                },
                true,
                function ($value) {
                    return !is_bool($value) ? ((int) $value) === NewsletterSuscriberMapper::ACCEPT_UPDATES_YES : $value;
                }
            ),
        ]);

        //Obtención de datos
        $inputData = $request->getParsedBody();

        //Asignación de datos para procesar
        $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

        //──── Estructura de respuesta ───────────────────────────────────────────────────────────

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Suscriptor'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación

        //Valores iniciales de la respuesta
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);

        //Mensajes de respuesta
        $notExistsMessage = __(self::LANG_GROUP, 'El suscriptor que intenta modificar no existe.');
        $successCreateMessage = __(self::LANG_GROUP, 'Suscriptor creado.');
        $successEditMessage = __(self::LANG_GROUP, 'Datos guardados.');
        $unknowErrorMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido.');
        $unknowErrorWithValuesMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido al procesar los valores ingresados.');

        //──── Acciones ──────────────────────────────────────────────────────────────────────────
        try {

            //Intenta validar, si todo sale bien el código continúa
            $expectedParameters->validate();

            //Información del formulario
            /**
             * @var int $id
             * @var string $name
             * @var string $email
             * @var bool $acceptUpdates
             */
            $id = $expectedParameters->getValue('id');
            $name = $expectedParameters->getValue('name');
            $email = $expectedParameters->getValue('email');
            $acceptUpdates = $expectedParameters->getValue('acceptUpdates');

            //Se define si es edición o creación
            $isEdit = $id !== -1;

            try {

                if (!$isEdit) {
                    //Nuevo

                    $mapper = new NewsletterSuscriberMapper();

                    $mapper->name = $name;
                    $mapper->email = $email;
                    $mapper->acceptUpdates = $acceptUpdates ? NewsletterSuscriberMapper::ACCEPT_UPDATES_YES : NewsletterSuscriberMapper::ACCEPT_UPDATES_NO;

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

                    $mapper = new NewsletterSuscriberMapper((int) $id);
                    $exists = !is_null($mapper->id);

                    if ($exists) {

                        $mapper->name = $name;
                        $mapper->email = $email;
                        $mapper->acceptUpdates = $acceptUpdates ? NewsletterSuscriberMapper::ACCEPT_UPDATES_YES : NewsletterSuscriberMapper::ACCEPT_UPDATES_NO;

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
     * @return Response
     */
    public function addSuscriber(Request $request, Response $response)
    {

        //──── Entrada ───────────────────────────────────────────────────────────────────────────

        //Definición de validaciones y procesamiento
        $expectedParameters = new Parameters([
            new Parameter(
                'email',
                null,
                function ($value) {
                    return is_string($value) && strlen(trim($value)) > 0;
                },
                false,
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

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Añadir al boletín'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación

        //Valores iniciales de la respuesta
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);

        //Mensajes de respuesta
        $successCreateMessage = __(self::LANG_GROUP, 'Ha sido añadido satisfactoriamente.');
        $unknowErrorMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido.');
        $unknowErrorWithValuesMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido al procesar los valores ingresados.');

        //──── Acciones ──────────────────────────────────────────────────────────────────────────
        try {

            //Intenta validar, si todo sale bien el código continúa
            $expectedParameters->validate();

            //Información del formulario
            /**
             * @var string $email
             */
            $email = $expectedParameters->getValue('email');

            try {

                $mapper = new NewsletterSuscriberMapper();
                $mapper->email = $email;
                $mapper->acceptUpdates = NewsletterSuscriberMapper::ACCEPT_UPDATES_YES;

                $saved = $mapper->save();
                $resultOperation->setSuccessOnSingleOperation($saved);

                if ($saved) {

                    $mapper->id = $mapper->getInsertIDOnSave();

                    $resultOperation
                        ->setMessage($successCreateMessage)
                        ->setValue('redirect', false);

                } else {
                    $resultOperation->setMessage($unknowErrorMessage);
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

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Eliminar suscriptor'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación

        //Valores iniciales de la respuesta
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);
        $resultOperation->setValue('received', $inputData);

        //Mensajes de respuesta
        $notExistsMessage = __(self::LANG_GROUP, 'El suscriptor que intenta eliminar no existe.');
        $successMessage = __(self::LANG_GROUP, 'Suscriptor eliminado.');
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

                $exists = NewsletterSuscriberMapper::existsByID($id);

                if ($exists) {

                    //Dirección de redirección en caso de creación
                    $redirectURLOn = self::routeName('list');

                    $table = NewsletterSuscriberMapper::TABLE;

                    $transactionSQLDeleteQueries = [
                        [
                            'query' => "DELETE FROM {$table} WHERE id = :ID",
                            'aliasConfig' => [
                                ':ID' => $id,
                            ],
                        ],
                    ];

                    $pdo = NewsletterSuscriberMapper::model()::getDb(Config::app_db('default')['db']);

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
        ]);

        $expectedParameters->setInputValues($request->getQueryParams());
        $expectedParameters->validate();

        /**
         * @var int $page
         * @var int $perPage
         */
        $page = $expectedParameters->getValue('page');
        $perPage = $expectedParameters->getValue('per_page');

        $result = self::_all($page, $perPage);
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
        $table = NewsletterSuscriberMapper::TABLE;
        $where = [];

        if (!empty($where)) {
            $whereString = trim(implode(' ', $where));
        }

        $selectFields = NewsletterSuscriberMapper::fieldsToSelect();

        $columnsOrder = [
            'idPadding',
            'name',
            'email',
            'acceptUpdatesDisplay',
        ];

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);

        $result = DataTablesHelper::process([

            'where_string' => $whereString,
            'select_fields' => $selectFields,
            'columns_order' => $columnsOrder,
            'mapper' => new NewsletterSuscriberMapper(),
            'request' => $request,
            'on_set_data' => function ($e) {

                $mapper = NewsletterSuscriberMapper::objectToMapper($e);

                $buttons = [];
                $hasEdit = self::allowedRoute('forms-edit', ['id' => $e->id]);
                $hasDelete = self::allowedRoute('actions-delete', ['id' => $e->id]);

                if ($hasEdit) {
                    $editLink = self::routeName('forms-edit', ['id' => $e->id]);
                    $editText = __(self::LANG_GROUP, 'Editar');
                    $editButton = "<a href='{$editLink}' class='ui button brand-color'>{$editText}</a>";
                    $buttons[] = $editButton;
                }
                if ($hasDelete) {
                    $deleteLink = self::routeName('actions-delete', ['id' => $mapper->id]);
                    $deleteText = __(self::LANG_GROUP, 'Eliminar');
                    $deleteButton = "<a data-route='{$deleteLink}' class='ui button brand-color alt2' delete-newsletter-suscriber-button>{$deleteText}</a>";
                    $buttons[] = $deleteButton;
                }

                $buttons = implode('', $buttons);
                $columns = [];

                $columns[] = $e->idPadding;
                $columns[] = $mapper->nameDisplay();
                $columns[] = $mapper->emailDisplay();
                $columns[] = $e->acceptUpdatesDisplay;
                $columns[] = $buttons;
                return $columns;
            },

        ]);

        return $response->withJson($result->getValues());
    }

    /**
     * @param int $page =1
     * @param int $perPage =10
     * @return PaginationResult
     */
    public static function _all(int $page = null, int $perPage = null)
    {
        $page = $page === null ? 1 : $page;
        $perPage = $perPage === null ? 10 : $perPage;
        $table = NewsletterSuscriberMapper::TABLE;
        $fields = NewsletterSuscriberMapper::fieldsToSelect();

        $whereString = null;
        $where = [];

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

        $sqlSelect .= " ORDER BY " . implode(', ', NewsletterSuscriberMapper::ORDER_BY_PREFERENCE);

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

            $value = self::routeName('forms-edit', ['id' => $elementID]);

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
        return (new NewsletterController)->render(self::BASE_VIEW_DIR . '/' . trim($name, '/'), $data, $mode, $format);
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

            if ($name == 'SAMPLE') { //do something
            }

        }

        return $allow;
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
                "{$startRoute}/forms/edit/{id}[/]",
                $classname . ':editForm',
                self::$baseRouteName . '-forms-edit',
                'GET',
                true,
                null,
                $edition
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
                "{$startRoute}/add[/]",
                $classname . ':addSuscriber',
                self::$baseRouteName . '-add',
                'POST'
            ),
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

        return $group;
    }
}
