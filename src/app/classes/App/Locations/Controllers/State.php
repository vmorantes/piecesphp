<?php

/**
 * State.php
 */

namespace App\Locations\Controllers;

use App\Controller\AdminPanelController;
use App\Locations\Mappers\StateMapper;
use PiecesPHP\Core\HTML\HtmlElement;
use PiecesPHP\Core\Pagination\PageQuery;
use PiecesPHP\Core\Pagination\PaginationResult;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Core\Utilities\ReturnTypes\Operation;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use Slim\Exception\NotFoundException;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

/**
 * State.
 *
 * Controlador de estados
 *
 * @package     App\Locations\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class State extends AdminPanelController
{

    /**
     * $prefixParentEntity
     *
     * @var string
     */
    protected static $prefixParentEntity = 'locations';
    /**
     * $prefixEntity
     *
     * @var string
     */
    protected static $prefixEntity = 'states';
    /**
     * $prefixSingularEntity
     *
     * @var string
     */
    protected static $prefixSingularEntity = 'state';
    /**
     * $title
     *
     * @var string
     */
    protected static $title = 'Departamento';
    /**
     * $pluralTitle
     *
     * @var string
     */
    protected static $pluralTitle = 'Departamentos';

    /**
     * __construct
     *
     * @return static
     */
    public function __construct()
    {

        self::$title = __(LOCATIONS_LANG_GROUP, self::$title);
        self::$pluralTitle = __(LOCATIONS_LANG_GROUP, self::$pluralTitle);

        parent::__construct(false); //No cargar ningún modelo automáticamente.
        $this->model = (new StateMapper())->getModel();
        set_title(self::$pluralTitle);
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

        $action = self::routeName('actions-add');
        $status_options = array_map(function ($i) {return __(LOCATIONS_LANG_GROUP, $i);}, StateMapper::STATUS);
        $status_options = array_to_html_options($status_options, StateMapper::ACTIVE);
        $back_link = self::routeName('list');

        $data = [];
        $data['action'] = $action;
        $data['status_options'] = $status_options;
        $data['back_link'] = $back_link;
        $data['title'] = self::$title;

        $this->render('panel/layout/header');
        $this->render('panel/' . self::$prefixParentEntity . '/' . self::$prefixSingularEntity . '/add-form', $data);
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

        $element = new StateMapper($id);

        if (!is_null($element->id)) {

            $action = self::routeName('actions-edit');
            $status_options = array_map(function ($i) {return __(LOCATIONS_LANG_GROUP, $i);}, StateMapper::STATUS);
            $status_options = array_to_html_options($status_options, $element->active);
            $back_link = self::routeName('list');

            $data = [];
            $data['action'] = $action;
            $data['status_options'] = $status_options;
            $data['element'] = $element;
            $data['back_link'] = $back_link;
            $data['title'] = self::$title;

            $this->render('panel/layout/header');
            $this->render('panel/' . self::$prefixParentEntity . '/' . self::$prefixSingularEntity . '/edit-form', $data);
            $this->render('panel/layout/footer');

        } else {
            throw new NotFoundException($request, $response);
        }
    }

    /**
     * list
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return void
     */
    function list(Request $request, Response $response, array $args) {

        $process_table = self::routeName('datatables');
        $back_link = self::routeName();
        $add_link = self::routeName('forms-add');

        $data = [];
        $data['process_table'] = $process_table;
        $data['back_link'] = $back_link;
        $data['add_link'] = $add_link;
        $data['has_add_link_permissions'] = mb_strlen($add_link) > 0;
        $data['title'] = self::$pluralTitle;

        $this->render('panel/layout/header');
        $this->render('panel/' . self::$prefixParentEntity . '/' . self::$prefixSingularEntity . '/list', $data);
        $this->render('panel/layout/footer');
    }

    /**
     * states
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function states(Request $request, Response $response, array $args)
    {
        $country = $request->getQueryParam('country', null);

        if ($country != null) {
            if (ctype_digit($country)) {
                $country = (int) $country;
            } else {
                $country = -1;
            }
        }

        $valid = $country !== -1;

        if ($valid) {

            $query = $this->model->select();

            if (!is_null($country)) {
                $query->where([
                    'country' => $country,
                ]);
            }

            $query->execute();

            $result = $query->result();

            foreach ($result as $key => $value) {
                $value->name = htmlentities(stripslashes($value->name));
                $result[$key] = $value;
            }

            return $response->withJson($result);

        } else {
            throw new NotFoundException($request, $response);
        }
    }

    /**
     * statesDataTables
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function statesDataTables(Request $request, Response $response, array $args)
    {
        $country = $request->getQueryParam('country', null);

        if ($country != null) {
            if (ctype_digit($country)) {
                $country = (int) $country;
            } else {
                $country = -1;
            }
        }

        $valid = $country !== -1;

        if ($request->isXhr() && $valid) {

            $columns_order = [
                'id',
                'code',
                'name',
                'country',
                'active',
            ];

            $result = DataTablesHelper::process([
                'columns_order' => $columns_order,
                'mapper' => new StateMapper(),
                'request' => $request,
                'on_set_data' => function ($e) {

                    $buttonEdit = new HtmlElement('a', __(LOCATIONS_LANG_GROUP, 'Editar'));
                    $buttonEdit->setAttribute('class', "ui button green");
                    $buttonEdit->setAttribute('href', self::routeName('forms-edit', [
                        'id' => $e->id,
                    ]));

                    if ($buttonEdit->getAttributes(false)->offsetExists('href')) {
                        $href = $buttonEdit->getAttributes(false)->offsetGet('href');
                        if (mb_strlen(trim($href->getValue())) < 1) {
                            $buttonEdit = __(LOCATIONS_LANG_GROUP, 'Sin acciones');
                        }
                    }

                    $mapper = new StateMapper($e->id);

                    return [
                        $e->id,
                        !is_null($e->code) ? $e->code : '-',
                        stripslashes($e->name),
                        $mapper->country->name,
                        __(LOCATIONS_LANG_GROUP, StateMapper::STATUS[$e->active]),
                        (string) $buttonEdit,
                    ];

                },
                'where_string' => is_null($country) ? null : "country = $country",
            ]);

            return $response->withJson($result->getValues());

        } else {
            throw new NotFoundException($request, $response);
        }
    }

    /**
     * action
     *
     * Creación/Edición de estados
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function action(Request $request, Response $response, array $args)
    {

        $id = $request->getParsedBodyParam('id', -1);
        $country = $request->getParsedBodyParam('country', null);
        $name = $request->getParsedBodyParam('name', null);
        $code = $request->getParsedBodyParam('code', null);
        $active = $request->getParsedBodyParam('active', null);
        $is_edit = $id !== -1;

        $valid_params = !in_array(null, [
            !is_null($country) && ctype_digit($country) ? (int) $country : null,
            $name,
            $active,
        ]);

        $operation_name = $is_edit ? __(LOCATIONS_LANG_GROUP, 'Modificar departamento') : __(LOCATIONS_LANG_GROUP, 'Crear departamento');

        $result = new ResultOperations([
            new Operation($operation_name),
        ], $operation_name);

        $result->setValue('redirect', false);

        $error_parameters_message = __(LOCATIONS_LANG_GROUP, 'Los parámetros recibidos son erróneos.');
        $not_exists_message = __(LOCATIONS_LANG_GROUP, 'El departamento que intenta modificar no existe');
        $success_create_message = __(LOCATIONS_LANG_GROUP, 'Departamento creado.');
        $success_edit_message = __(LOCATIONS_LANG_GROUP, 'Datos guardados.');
        $unknow_error_message = __(LOCATIONS_LANG_GROUP, 'Ha ocurrido un error desconocido.');
        $is_duplicate_message_name = __(LOCATIONS_LANG_GROUP, 'Ya existe un departamento con ese nombre.');
        $is_duplicate_message_code = __(LOCATIONS_LANG_GROUP, 'Ya existe un departamento con ese código.');

        $redirect_url_on_create = self::routeName('list');

        if ($valid_params) {

            $name = clean_string($name);
            $code = is_string($code) ? clean_string($code) : '';
            $code = mb_strlen($code) > 0 ? $code : null;

            $is_duplicate_name = StateMapper::isDuplicateName($name, $country, $id);
            $is_duplicate_code = StateMapper::isDuplicateCode($code, $country, $id);

            if (!$is_duplicate_name && !$is_duplicate_code) {

                if (!$is_edit) {

                    $mapper = new StateMapper();

                    try {

                        $mapper->country = $country;
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

                    $mapper = new StateMapper((int) $id);
                    $exists = !is_null($mapper->id);

                    if ($exists) {

                        try {

                            $mapper->country = $country;
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
     * @param array $args
     * @return Response
     */
    public function all(Request $request, Response $response, array $args)
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
                'country',
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
         * @var int $country
         * @var int[] $ignore
         */;
        $page = $expectedParameters->getValue('page');
        $perPage = $expectedParameters->getValue('per_page');
        $country = $expectedParameters->getValue('country');
        $ignore = $expectedParameters->getValue('ignore');

        $result = self::_all($page, $perPage, $country, $ignore);

        return $response->withJson($result);
    }

    /**
     * @param int $page
     * @param int $perPage
     * @param int $country
     * @param int[] $ignore
     * @return PaginationResult
     */
    public static function _all(int $page = 1, int $perPage = 10, int $country = null, array $ignore = [])
    {
        $table = StateMapper::PREFIX_TABLE . StateMapper::TABLE;
        $fields = [
            "{$table}.*",
        ];

        $whereString = null;
        $where = [
            $country !== null ? "{$table}.country = {$country}" : '',
        ];

        $where = array_filter($where, function ($i) {return mb_strlen($i) > 0;});

        if (count($ignore) > 0) {
            $ignore = implode(', ', $ignore);
            $where[] = (count($where) > 0 ? ' AND ' : '') . "{$table}.id NOT IN ($ignore)";
        }

        if (count($where) > 0) {
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
     * routeName
     *
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
}
