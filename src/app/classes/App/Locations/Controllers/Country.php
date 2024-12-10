<?php

/**
 * Country.php
 */

namespace App\Locations\Controllers;

use App\Controller\AdminPanelController;
use App\Locations\Mappers\CountryMapper;
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
 * Country.
 *
 * Controlador de estados
 *
 * @package     App\Locations\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class Country extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $prefixParentEntity = 'locations';
    /**
     * @var string
     */
    protected static $prefixEntity = 'countries';
    /**
     * @var string
     */
    protected static $prefixSingularEntity = 'country';
    /**
     * @var string
     */
    protected static $title = 'País';
    /**
     * @var string
     */
    protected static $pluralTitle = 'Países';

    /**
     * @return static
     */
    public function __construct()
    {

        self::$title = __(LOCATIONS_LANG_GROUP, self::$title);
        self::$pluralTitle = __(LOCATIONS_LANG_GROUP, self::$pluralTitle);

        parent::__construct();
        $this->model = (new CountryMapper())->getModel();

        set_title(self::$pluralTitle);

    }

    /**
     * @return void
     */
    public function addForm()
    {

        $action = self::routeName('actions-add');
        $status_options = array_map(function ($i) {return __(LOCATIONS_LANG_GROUP, $i);}, CountryMapper::STATUS);
        $status_options = array_to_html_options($status_options, CountryMapper::ACTIVE);
        $back_link = self::routeName('list');
        $regionsOptions = array_to_html_options(CountryMapper::allRegionsForSelect());

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
            __(LOCATIONS_LANG_GROUP, 'Países') => [
                'url' => $back_link,
            ],
            self::$title,
        ]);
        $data['regionsOptions'] = $regionsOptions;

        $this->render('panel/layout/header');
        $this->render('panel/' . self::$prefixParentEntity . '/' . self::$prefixSingularEntity . '/add-form', $data);
        $this->render('panel/layout/footer');
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

        $element = new CountryMapper($id);

        if (!is_null($element->id)) {

            $action = self::routeName('actions-edit');
            $status_options = array_map(function ($i) {return __(LOCATIONS_LANG_GROUP, $i);}, CountryMapper::STATUS);
            $status_options = array_to_html_options($status_options, $element->active);
            $regionsOptions = array_to_html_options(CountryMapper::allRegionsForSelect(), $element->region);
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
                __(LOCATIONS_LANG_GROUP, 'Países') => [
                    'url' => $back_link,
                ],
                self::$title,
            ]);
            $data['regionsOptions'] = $regionsOptions;

            $this->render('panel/layout/header');
            $this->render('panel/' . self::$prefixParentEntity . '/' . self::$prefixSingularEntity . '/edit-form', $data);
            $this->render('panel/layout/footer');

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
                'url' => $back_link,
            ],
            self::$pluralTitle,
        ]);

        $this->render('panel/layout/header');
        $this->render('panel/' . self::$prefixParentEntity . '/' . self::$prefixSingularEntity . '/list', $data);
        $this->render('panel/layout/footer');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function countries(Request $request, Response $response)
    {
        $region = $request->getQueryParam('region', null);
        $ids = $request->getQueryParam('ids', []);
        $ids = is_array($ids) && !empty($ids) ? implode(',', $ids) : null;

        if ($region !== null) {
            if (is_string($region) && mb_strlen(trim($region))) {
                $region = trim($region);
            } else {
                $region = "";
            }
        }

        $query = $this->model->select();

        $where = [];
        $whereString = null;

        if (!is_null($region)) {
            $operator = !empty($where) ? ' AND ' : '';
            $critery = "{$operator} (UPPER(region) = UPPER('{$region}'))";
            $where[] = $critery;
        }

        if (!is_null($ids)) {
            $operator = !empty($where) ? ' AND ' : '';
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
    public function countriesDataTables(Request $request, Response $response)
    {

        $region = $request->getQueryParam('region', null);
        if ($region !== null) {
            if (is_string($region) && mb_strlen(trim($region))) {
                $region = trim($region);
            } else {
                $region = "";
            }
        }

        $columns_order = [
            'id',
            'code',
            'name',
            'region',
            'active',
        ];

        $result = DataTablesHelper::process([
            'columns_order' => $columns_order,
            'mapper' => new CountryMapper(),
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
                    !is_null($e->code) ? $e->code : '-',
                    stripslashes($e->name),
                    !is_null($e->region) ? $e->region : '-',
                    __(LOCATIONS_LANG_GROUP, CountryMapper::STATUS[$e->active]),
                    $editButton,
                ];

            },
            'where_string' => is_null($region) ? null : "UPPER(region) = UPPER('{$region}')",
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
        $code = $request->getParsedBodyParam('code', null);
        $name = $request->getParsedBodyParam('name', null);
        $region = $request->getParsedBodyParam('region', null);
        $active = $request->getParsedBodyParam('active', null);
        $is_edit = $id !== -1;

        $valid_params = !in_array(null, [
            $name,
            $active,
        ]);

        $operation_name = $is_edit ? __(LOCATIONS_LANG_GROUP, 'Modificar país') : __(LOCATIONS_LANG_GROUP, 'Crear país');

        $result = new ResultOperations([
            new Operation($operation_name),
        ], $operation_name);

        $result->setValue('redirect', false);

        $error_parameters_message = __(LOCATIONS_LANG_GROUP, 'Los parámetros recibidos son erróneos.');
        $not_exists_message = __(LOCATIONS_LANG_GROUP, 'El país que intenta modificar no existe');
        $success_create_message = __(LOCATIONS_LANG_GROUP, 'País creado.');
        $success_edit_message = __(LOCATIONS_LANG_GROUP, 'Datos guardados.');
        $unknow_error_message = __(LOCATIONS_LANG_GROUP, 'Ha ocurrido un error desconocido.');
        $is_duplicate_message_name = __(LOCATIONS_LANG_GROUP, 'Ya existe un país con ese nombre.');
        $is_duplicate_message_code = __(LOCATIONS_LANG_GROUP, 'Ya existe un país con ese código.');

        $redirect_url_on_create = self::routeName('list');

        if ($valid_params) {

            $name = clean_string($name);
            $code = is_string($code) ? clean_string($code) : '';
            $code = mb_strlen($code) > 0 ? $code : null;
            $region = is_string($region) && mb_strlen(trim($region)) > 0 ? clean_string($region) : null;
            $is_duplicate_name = CountryMapper::isDuplicateName($name, $id);
            $is_duplicate_code = CountryMapper::isDuplicateCode($code, $id);

            if (!$is_duplicate_name && !$is_duplicate_code) {

                if (!$is_edit) {

                    $mapper = new CountryMapper();

                    try {

                        $mapper->code = $code;
                        $mapper->name = $name;
                        $mapper->region = $region;
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

                    $mapper = new CountryMapper((int) $id);
                    $exists = !is_null($mapper->id);

                    if ($exists) {

                        try {

                            $mapper->code = $code;
                            $mapper->name = $name;
                            $mapper->region = $region;
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

        $table = CountryMapper::PREFIX_TABLE . CountryMapper::TABLE;
        $beforeOperator = !empty($where) ? $and : '';
        $critery = "UPPER({$table}.name) LIKE UPPER('{$query}%')";
        $where[] = "{$beforeOperator} ({$critery})";

        if (!empty($where)) {
            $whereString = implode(' ', $where);
        }

        if (is_string($whereString)) {

            $model = CountryMapper::model();
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
