<?php

/**
 * Point.php
 */

namespace App\Locations\Controllers;

use App\Controller\AdminPanelController;
use App\Locations\LocationsLang;
use App\Locations\Mappers\PointMapper;
use PiecesPHP\Core\Pagination\PageQuery;
use PiecesPHP\Core\Pagination\PaginationResult;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Core\Utilities\ReturnTypes\Operation;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use PiecesPHP\Core\Validation\Validator;
use \PiecesPHP\Core\Routing\RequestRoute as Request;
use \PiecesPHP\Core\Routing\ResponseRoute as Response;

/**
 * Point.
 *
 * Controlador de puntos
 *
 * @package     App\Locations\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class Point extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $prefixParentEntity = 'locations';
    /**
     * @var string
     */
    protected static $prefixEntity = 'points';
    /**
     * @var string
     */
    protected static $prefixSingularEntity = 'point';
    /**
     * @var string
     */
    protected static $title = 'Localidad';
    /**
     * @var string
     */
    protected static $pluralTitle = 'Localidades';

    protected HelperController $helperController;

    /**
     * @return static
     */
    public function __construct()
    {
        $this->helperController = new HelperController();
        self::$title = __(LOCATIONS_LANG_GROUP, self::$title);
        self::$pluralTitle = __(LOCATIONS_LANG_GROUP, self::$pluralTitle);

        parent::__construct();
        $this->model = (new PointMapper())->getModel();
        set_title(self::$pluralTitle);
    }

    /**
     * @return void
     */
    public function addForm()
    {
        $action = self::routeName('actions-add');
        $status_options = array_map(function ($i) {return __(LOCATIONS_LANG_GROUP, $i);}, PointMapper::STATUS);
        $status_options = array_to_html_options($status_options, PointMapper::ACTIVE);
        $back_link = self::routeName('list');

        $data = [];
        $data['action'] = $action;
        $data['status_options'] = $status_options;
        $data['back_link'] = $back_link;
        $data['title'] = self::$title;
        $data['breadcrumbs'] = get_breadcrumbs([
            __(ADMIN_MENU_LANG_GROUP, 'Inicio') => [
                'url' => get_route('admin'),
            ],
            __(ADMIN_MENU_LANG_GROUP, 'Ubicaciones') => [
                'url' => get_route('locations', [], true),
            ],
            __(LOCATIONS_LANG_GROUP, 'Localidades') => [
                'url' => $back_link,
            ],
            self::$title,
        ]);

        $this->helperController->render('panel/layout/header');
        $this->helperController->localRender(self::$prefixParentEntity . '/' . self::$prefixSingularEntity . '/add-form', $data);
        $this->helperController->render('panel/layout/footer');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function editForm(Request $request, Response $response)
    {
        $id = $request->getAttribute('id', null);
        $id = Validator::isInteger($id) ? (int) $id : null;

        $element = new PointMapper($id);

        if (!is_null($element->id)) {
            $action = self::routeName('actions-edit');
            $status_options = array_map(function ($i) {return __(LOCATIONS_LANG_GROUP, $i);}, PointMapper::STATUS);
            $status_options = array_to_html_options($status_options, $element->active);
            $back_link = self::routeName('list');

            $data = [];
            $data['action'] = $action;
            $data['status_options'] = $status_options;
            $data['element'] = $element;
            $data['back_link'] = $back_link;
            $data['title'] = self::$title;
            $data['breadcrumbs'] = get_breadcrumbs([
                __(ADMIN_MENU_LANG_GROUP, 'Inicio') => [
                    'url' => get_route('admin'),
                ],
                __(ADMIN_MENU_LANG_GROUP, 'Ubicaciones') => [
                    'url' => get_route('locations', [], true),
                ],
                __(LOCATIONS_LANG_GROUP, 'Localidades') => [
                    'url' => $back_link,
                ],
                self::$title,
            ]);

            $this->helperController->render('panel/layout/header');
            $this->helperController->localRender(self::$prefixParentEntity . '/' . self::$prefixSingularEntity . '/edit-form', $data);
            $this->helperController->render('panel/layout/footer');
        } else {
            throw new NotFoundException($request, $response);
        }
    }

    /**
     * @return void
     */
    public function list()
    {
        $process_table = self::routeName('datatables');
        $back_link = self::routeName();
        $add_link = self::routeName('forms-add');

        $data = [];
        $data['process_table'] = $process_table;
        $data['back_link'] = $back_link;
        $data['add_link'] = $add_link;
        $data['has_add_link_permissions'] = mb_strlen($add_link) > 0;
        $data['title'] = self::$pluralTitle;
        $data['breadcrumbs'] = get_breadcrumbs([
            __(ADMIN_MENU_LANG_GROUP, 'Inicio') => [
                'url' => get_route('admin'),
            ],
            __(ADMIN_MENU_LANG_GROUP, 'Ubicaciones') => [
                'url' => get_route('locations', [], true),
            ],
            self::$pluralTitle,
        ]);

        $this->helperController->render('panel/layout/header');
        $this->helperController->localRender(self::$prefixParentEntity . '/' . self::$prefixSingularEntity . '/list', $data);
        $this->helperController->render('panel/layout/footer');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function points(Request $request, Response $response)
    {
        $city = $request->getQueryParam('city', null);

        if ($city !== null) {
            if (Validator::isInteger($city)) {
                $city = (int) $city;
            } else {
                $city = -1;
            }
        }

        $query = $this->model->select();

        if (!is_null($city)) {
            $query->where([
                'city' => $city,
            ]);
        }

        $query->execute();

        $result = $query->result();
        $result = is_array($result) ? $result : [];

        foreach ($result as $key => $value) {
            $value->name = __(LocationsLang::LANG_GROUP_NAMES, $value->name);
            $result[$key] = $value;
        }

        return $response->withJson($result);

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function pointsDataTables(Request $request, Response $response)
    {
        $city = $request->getQueryParam('city', null);

        if ($city !== null) {
            if (Validator::isInteger($city)) {
                $city = (int) $city;
            } else {
                $city = -1;
            }
        }

        if ($request->isXhr()) {

            $select_fields = PointMapper::fieldsToSelect();

            $columns_order = [
                'id',
                'name',
                'countryName',
                'stateName',
                'cityName',
                'address',
                'coordinates',
                'active',
            ];

            DataTablesHelper::setTablePrefixOnOrder(false);
            DataTablesHelper::setTablePrefixOnSearch(false);

            $result = DataTablesHelper::process([
                'select_fields' => $select_fields,
                'columns_order' => $columns_order,
                'mapper' => new PointMapper(),
                'request' => $request,
                'on_set_data' => function ($e) {

                    $editButton = __(LOCATIONS_LANG_GROUP, 'Sin acciones');
                    $editLink = self::routeName('forms-edit', [
                        'id' => $e->id,
                    ]);

                    if (mb_strlen($editLink) > 0) {
                        $editText = __(LOCATIONS_LANG_GROUP, 'Editar');
                        $editButton = "<a class='ui button green' href='{$editLink}'>{$editText}</a>";
                    }

                    return [
                        $e->id,
                        stripslashes($e->name),
                        $e->countryName,
                        $e->stateName,
                        $e->cityName,
                        $e->address,
                        $e->coordinates,
                        __(LOCATIONS_LANG_GROUP, PointMapper::STATUS[$e->active]),
                        $editButton,
                    ];
                },
                'where' => is_null($city) ? null : "city = $city",
            ]);

            $result = $result->getValues();
            return $response->withJson($result);
        } else {
            throw new NotFoundException($request, $response);
        }
    }

    /**
     * Creación/Edición de estados
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function action(Request $request, Response $response)
    {

        $id = $request->getParsedBodyParam('id', -1);
        $city = $request->getParsedBodyParam('city', null);
        $address = $request->getParsedBodyParam('address', null);
        $name = $request->getParsedBodyParam('name', null);
        $longitude = $request->getParsedBodyParam('longitude', null);
        $latitude = $request->getParsedBodyParam('latitude', null);
        $active = $request->getParsedBodyParam('active', null);
        $is_edit = $id !== -1;

        $valid_params = !in_array(null, [
            Validator::isInteger($city) ? (int) $city : null,
            $address,
            $name,
            $longitude,
            $latitude,
            $active,
        ]);

        $operation_name = $is_edit ? __(LOCATIONS_LANG_GROUP, 'Modificar localidad') : __(LOCATIONS_LANG_GROUP, 'Crear localidad');

        $result = new ResultOperations([
            new Operation($operation_name),
        ], $operation_name);

        $result->setValue('redirect', false);

        $error_parameters_message = __(LOCATIONS_LANG_GROUP, 'Los parámetros recibidos son erróneos.');
        $not_exists_message = __(LOCATIONS_LANG_GROUP, 'La localidad que intenta modificar no existe');
        $success_create_message = __(LOCATIONS_LANG_GROUP, 'Localidad creada.');
        $success_edit_message = __(LOCATIONS_LANG_GROUP, 'Datos guardados.');
        $unknow_error_message = __(LOCATIONS_LANG_GROUP, 'Ha ocurrido un error desconocido.');
        $is_duplicate_message = __(LOCATIONS_LANG_GROUP, 'Ya existe una localidad con ese nombre.');

        $redirect_url_on_create = self::routeName('list');

        if ($valid_params) {

            $name = trim($name);
            $address = trim($address);
            $is_duplicate = PointMapper::isDuplicate($name, $city, $id);

            if (!$is_duplicate) {

                if (!$is_edit) {

                    $mapper = new PointMapper();

                    try {

                        $mapper->city = $city;
                        $mapper->address = $address;
                        $mapper->name = $name;
                        $mapper->longitude = $longitude;
                        $mapper->latitude = $latitude;
                        $mapper->active = $active;
                        $saved = $mapper->save();

                        if ($saved) {

                            $result->setMessage($success_create_message)
                                ->operation($operation_name)
                                ->setSuccess(true);

                            $result->setValue('redirect', true);
                            $result->setValue('redirect_to', $redirect_url_on_create);
                        } else {
                            $result->setMessage($unknow_error_message);
                        }
                    } catch (\Exception $e) {
                        $result->setMessage($e->getMessage());
                        log_exception($e);
                    }
                } else {

                    $mapper = new PointMapper((int) $id);
                    $exists = !is_null($mapper->id);

                    if ($exists) {

                        try {

                            $mapper->city = $city;
                            $mapper->address = $address;
                            $mapper->name = $name;
                            $mapper->longitude = $longitude;
                            $mapper->latitude = $latitude;
                            $mapper->active = $active;
                            $updated = $mapper->update();

                            if ($updated) {
                                $result->setMessage($success_edit_message)
                                    ->operation($operation_name)
                                    ->setSuccess(true);
                            } else {
                                $result->setMessage($unknow_error_message);
                            }
                        } catch (\Exception $e) {
                            $result->setMessage($e->getMessage());
                            log_exception($e);
                        }
                    } else {
                        $result->setMessage($not_exists_message);
                    }
                }
            } else {

                $result->setMessage($is_duplicate_message);
            }
        } else {
            $result->setMessage($error_parameters_message);
        }

        return $response->withJson($result);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function search(Request $request, Response $response)
    {

        $expectedParameters = new Parameters([
            new Parameter(
                'query',
                uniqid(),
                function ($value) {
                    return is_string($value);
                },
                true,
                function ($value) {
                    return clean_string(trim($value));
                }
            ),
        ]);

        $expectedParameters->setInputValues($request->getQueryParams());
        $expectedParameters->validate();

        /**
         * @var string $query
         */
        $query = $expectedParameters->getValue('query');

        $result = [];

        $where = [];
        $whereString = null;

        $and = 'AND';

        $table = PointMapper::PREFIX_TABLE . PointMapper::TABLE;
        $beforeOperator = !empty($where) ? $and : '';
        $critery = "UPPER({$table}.name) LIKE UPPER('{$query}%')";
        $where[] = "{$beforeOperator} ({$critery})";

        if (!empty($where)) {
            $whereString = implode(' ', $where);
        }

        if (is_string($whereString)) {

            $model = PointMapper::model();
            $model->select()->where($whereString);
            $model->execute(false, 1, 15);
            $queryResult = $model->result();

            foreach ($queryResult as $row) {
                $result[] = [
                    'id' => $row->id,
                    'title' => __(LocationsLang::LANG_GROUP_NAMES, $row->name),
                ];
            }

        }

        return $response->withJson($result);
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
                    return Validator::isInteger($value);
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
                    return Validator::isInteger($value);
                },
                true,
                function ($value) {
                    return (int) $value;
                }
            ),
            new Parameter(
                'city',
                null,
                function ($value) {
                    return Validator::isInteger($value) || $value == -1;
                },
                true,
                function ($value) {
                    return (int) $value;
                }
            ),
            new Parameter(
                'ignore',
                [],
                function ($value) {
                    return is_array($value) || Validator::isInteger($value);
                },
                true,
                function ($value) {

                    if (is_scalar($value)) {
                        $value = [$value];
                    }

                    $value = is_array($value) ? $value : [];

                    $value = array_filter($value, function ($i) {

                        return Validator::isInteger($i);

                    });
                    $value = array_map(function ($i) {

                        return (int) $i;

                    }, $value);

                    return $value;

                }
            ),
        ]);

        $expectedParameters->setInputValues($request->getQueryParams());
        $expectedParameters->validate();

        /**
         * @var int $id
         * @var int $perPage
         * @var int $city
         * @var int[] $ignore
         */
        $page = $expectedParameters->getValue('page');
        $perPage = $expectedParameters->getValue('per_page');
        $city = $expectedParameters->getValue('city');
        $ignore = $expectedParameters->getValue('ignore');

        $result = self::_all($page, $perPage, $city, $ignore);

        return $response->withJson($result);
    }

    /**
     * @param int $page
     * @param int $perPage
     * @param int $city
     * @param int[] $ignore
     * @return PaginationResult
     */
    public static function _all(int $page = 1, int $perPage = 10, int $city = null, array $ignore = [])
    {
        $table = PointMapper::PREFIX_TABLE . PointMapper::TABLE;
        $fields = [
            "{$table}.*",
        ];

        $whereString = null;
        $where = [
        ];

        if ($city !== null) {
            $where[] = (!empty($where) ? ' AND ' : '') . "{$table}.city = ($city)";
        }

        if (!empty($ignore)) {
            $ignore = implode(', ', $ignore);
            $where[] = (!empty($where) ? ' AND ' : '') . "{$table}.id NOT IN ($ignore)";
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

        $pageQuery = new PageQuery($sqlSelect, $sqlCount, $page, $perPage, 'total');

        $pagination = $pageQuery->getPagination(function ($e) {
            $e->name = __(LocationsLang::LANG_GROUP_NAMES, $e->name);
            return $e;
        });

        return $pagination;
    }

    /**
     * @param string $name
     * @param array $params
     * @param bool $silentOnNotExists
     * @return string
     */
    protected static function routeName(string $name = null, array $params = [], bool $silentOnNotExists = false)
    {
        if (!is_null($name)) {
            $name = trim($name);
            $name = mb_strlen($name) > 0 ? "-{$name}" : '';
        }

        $name = !is_null($name) ? self::$prefixParentEntity . '-' . self::$prefixEntity . $name : self::$prefixParentEntity;

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
}
