<?php

/**
 * City.php
 */

namespace App\Locations\Controllers;

use App\Controller\AdminPanelController;
use App\Locations\Mappers\CityMapper;
use PiecesPHP\Core\Pagination\PageQuery;
use PiecesPHP\Core\Pagination\PaginationResult;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Core\Utilities\ReturnTypes\Operation;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use PiecesPHP\Core\Validation\Validator;
use Slim\Exception\NotFoundException;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

/**
 * City.
 *
 * Controlador de ciudades
 *
 * @package     App\Locations\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class City extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $prefixParentEntity = 'locations';
    /**
     * @var string
     */
    protected static $prefixEntity = 'cities';
    /**
     * @var string
     */
    protected static $prefixSingularEntity = 'city';
    /**
     * @var string
     */
    protected static $title = 'Ciudad';
    /**
     * @var string
     */
    protected static $pluralTitle = 'Ciudades';

    const DEFAULT_HEADER_LAYOUT = 'panel/layout/header';
    const DEFAULT_FOOTER_LAYOUT = 'panel/layout/footer';

    /**
     * @return static
     */
    public function __construct()
    {

        self::$title = __(LOCATIONS_LANG_GROUP, self::$title);
        self::$pluralTitle = __(LOCATIONS_LANG_GROUP, self::$pluralTitle);

        parent::__construct();
        $this->model = (new CityMapper())->getModel();
        set_title(self::$pluralTitle);
    }

    /**
     * @return void
     */
    public function addForm()
    {

        $action = self::routeName('actions-add');
        $status_options = array_map(function ($i) {return __(LOCATIONS_LANG_GROUP, $i);}, CityMapper::STATUS);
        $status_options = array_to_html_options($status_options, CityMapper::ACTIVE);
        $back_link = self::routeName('list');

        $data = [];
        $data['action'] = $action;
        $data['status_options'] = $status_options;
        $data['back_link'] = $back_link;
        $data['title'] = self::$title;

        $this->render(self::DEFAULT_HEADER_LAYOUT);
        $this->render('panel/' . self::$prefixParentEntity . '/' . self::$prefixSingularEntity . '/add-form', $data);
        $this->render(self::DEFAULT_FOOTER_LAYOUT);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function editForm(Request $request, Response $response)
    {

        $id = $request->getAttribute('id', null);
        $id = !is_null($id) && ctype_digit($id) ? (int) $id : null;

        $element = new CityMapper($id);

        if (!is_null($element->id)) {

            $action = self::routeName('actions-edit');
            $status_options = array_map(function ($i) {return __(LOCATIONS_LANG_GROUP, $i);}, CityMapper::STATUS);
            $status_options = array_to_html_options($status_options, $element->active);
            $back_link = self::routeName('list');

            $data = [];
            $data['action'] = $action;
            $data['status_options'] = $status_options;
            $data['element'] = $element;
            $data['back_link'] = $back_link;
            $data['title'] = self::$title;

            $this->render(self::DEFAULT_HEADER_LAYOUT);
            $this->render('panel/' . self::$prefixParentEntity . '/' . self::$prefixSingularEntity . '/edit-form', $data);
            $this->render(self::DEFAULT_FOOTER_LAYOUT);

        } else {
            throw new NotFoundException($request, $response);
        }
    }

    /**
     * @return void
     */
    function list() {

        $process_table = self::routeName('datatables');
        $back_link = self::routeName();
        $add_link = self::routeName('forms-add');

        $data = [];
        $data['process_table'] = $process_table;
        $data['back_link'] = $back_link;
        $data['add_link'] = $add_link;
        $data['has_add_link_permissions'] = mb_strlen($add_link) > 0;
        $data['title'] = self::$pluralTitle;

        $this->render(self::DEFAULT_HEADER_LAYOUT);
        $this->render('panel/' . self::$prefixParentEntity . '/' . self::$prefixSingularEntity . '/list', $data);
        $this->render(self::DEFAULT_FOOTER_LAYOUT);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function cities(Request $request, Response $response)
    {
        $state = $request->getQueryParam('state', null);
        $ids = $request->getQueryParam('ids', []);
        $ids = is_array($ids) && !empty($ids) ? implode(',', $ids) : null;

        if ($state !== null) {
            if (ctype_digit($state)) {
                $state = (int) $state;
            } else {
                $state = -1;
            }
        }

        $query = $this->model->select();

        $where = [];
        $whereString = null;
        $and = ' AND ';

        if (!is_null($state)) {
            $operator = !empty($where) ? $and : '';
            $critery = "{$operator} (state = {$state})";
            $where[] = $critery;
        }

        if (!is_null($ids)) {
            $operator = !empty($where) ? $and : '';
            $critery = "{$operator} (id IN ({$ids}))";
            $where[] = $critery;
        }

        if (!empty($where)) {
            $whereString = implode(' ', $where);
            $query->where($whereString);
        }

        $query->execute();

        $result = $query->result();
        $result = is_array($result) ? $result : [];

        foreach ($result as $key => $value) {
            $value->name = htmlentities(stripslashes($value->name));
            $result[$key] = $value;
        }

        return $response->withJson($result);

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function citiesDataTables(Request $request, Response $response)
    {
        $state = $request->getQueryParam('state', null);
        if ($state !== null) {
            $state = Validator::isInteger($state) ? (int) $state : -1;
        }

        $columns_order = [
            'id',
            'code',
            'name',
            'state',
            'active',
        ];

        $result = DataTablesHelper::process([
            'columns_order' => $columns_order,
            'mapper' => new CityMapper(),
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

                $e_mapper = new CityMapper($e->id);

                return [
                    $e->id,
                    !is_null($e->code) ? $e->code : '-',
                    stripslashes($e->name),
                    $e_mapper->state->country->name,
                    $e_mapper->state->name,
                    __(LOCATIONS_LANG_GROUP, CityMapper::STATUS[$e->active]),
                    $editButton,
                ];

            },
            'where' => is_null($state) ? null : "state = $state",
        ]);

        return $response->withJson($result->getValues());

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
        $state = $request->getParsedBodyParam('state', null);
        $name = $request->getParsedBodyParam('name', null);
        $code = $request->getParsedBodyParam('code', null);
        $active = $request->getParsedBodyParam('active', null);
        $is_edit = $id !== -1;

        $valid_params = !in_array(null, [
            !is_null($state) && ctype_digit($state) ? (int) $state : null,
            $name,
            $active,
        ]);

        $operation_name = $is_edit ? __(LOCATIONS_LANG_GROUP, 'Modificar ciudad') : __(LOCATIONS_LANG_GROUP, 'Crear ciudad');

        $result = new ResultOperations([
            new Operation($operation_name),
        ], $operation_name);

        $result->setValue('redirect', false);

        $error_parameters_message = __(LOCATIONS_LANG_GROUP, 'Los parámetros recibidos son erróneos.');
        $not_exists_message = __(LOCATIONS_LANG_GROUP, 'La ciudad que intenta modificar no existe');
        $success_create_message = __(LOCATIONS_LANG_GROUP, 'Ciudad creada.');
        $success_edit_message = __(LOCATIONS_LANG_GROUP, 'Datos guardados.');
        $unknow_error_message = __(LOCATIONS_LANG_GROUP, 'Ha ocurrido un error desconocido.');
        $is_duplicate_message_name = __(LOCATIONS_LANG_GROUP, 'Ya existe una ciudad con ese nombre.');
        $is_duplicate_message_code = __(LOCATIONS_LANG_GROUP, 'Ya existe una ciudad con ese código.');

        $redirect_url_on_create = self::routeName('list');

        if ($valid_params) {

            $name = clean_string($name);
            $code = !is_null($code) ? clean_string($code) : '';
            $code = mb_strlen($code) > 0 ? $code : null;

            $is_duplicate_name = CityMapper::isDuplicateName($name, $state, $id);
            $is_duplicate_code = CityMapper::isDuplicateCode($code, $state, $id);

            if (!$is_duplicate_name && !$is_duplicate_code) {

                if (!$is_edit) {

                    $mapper = new CityMapper();

                    try {

                        $mapper->state = $state;
                        $mapper->code = $code;
                        $mapper->name = $name;
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

                    $mapper = new CityMapper((int) $id);
                    $exists = !is_null($mapper->id);

                    if ($exists) {

                        try {

                            $mapper->state = $state;
                            $mapper->code = $code;
                            $mapper->name = $name;
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

                if ($is_duplicate_name) {

                    $result->setMessage($is_duplicate_message_name);

                } elseif ($is_duplicate_code) {

                    $result->setMessage($is_duplicate_message_code);

                }

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
                '-1',
                function ($value) {
                    return is_string($value);
                },
                false,
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

        $table = CityMapper::PREFIX_TABLE . CityMapper::TABLE;
        $beforeOperator = !empty($where) ? $and : '';
        $critery = "UPPER({$table}.name) LIKE UPPER('{$query}%')";
        $where[] = "{$beforeOperator} ({$critery})";

        if (!empty($where)) {
            $whereString = implode(' ', $where);
        }

        if (is_string($whereString)) {

            $model = CityMapper::model();
            $model->select()->where($whereString);
            $model->execute(false, 1, 15);
            $queryResult = $model->result();

            foreach ($queryResult as $row) {
                $result[] = [
                    'id' => $row->id,
                    'title' => $row->name,
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
                'state',
                null,
                function ($value) {
                    return ctype_digit($value) || is_int($value) || $value == -1;
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
                    return is_array($value) || ctype_digit($value) || is_int($value);
                },
                true,
                function ($value) {

                    if (is_scalar($value)) {
                        $value = [$value];
                    }

                    $value = is_array($value) ? $value : [];

                    $value = array_filter($value, function ($i) {

                        return (is_string($i) && ctype_digit($i)) || is_int($i);

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
         * @var int $state
         * @var int[] $ignore
         */
        $page = $expectedParameters->getValue('page');
        $perPage = $expectedParameters->getValue('per_page');
        $state = $expectedParameters->getValue('state');
        $ignore = $expectedParameters->getValue('ignore');

        $result = self::_all($page, $perPage, $state, $ignore);

        return $response->withJson($result);
    }

    /**
     * @param int $page
     * @param int $perPage
     * @param int $state
     * @param int[] $ignore
     * @return PaginationResult
     */
    public static function _all(int $page = 1, int $perPage = 10, int $state = null, array $ignore = [])
    {
        $table = CityMapper::PREFIX_TABLE . CityMapper::TABLE;
        $fields = [
            "{$table}.*",
        ];

        $whereString = null;
        $where = [];
        $and = ' AND ';

        if ($state !== null) {
            $where[] = (!empty($where) ? $and : '') . "{$table}.state = {$state}";
        }

        if (!empty($ignore)) {
            $ignore = implode(', ', $ignore);
            $where[] = (!empty($where) ? $and : '') . "{$table}.id NOT IN ($ignore)";
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

        $pagination = $pageQuery->getPagination();

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
