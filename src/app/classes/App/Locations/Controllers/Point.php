<?php

/**
 * Point.php
 */

namespace App\Locations\Controllers;

use App\Controller\AdminPanelController;
use App\Locations\Mappers\PointMapper;
use PiecesPHP\Core\HTML\HtmlElement;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Core\Utilities\ReturnTypes\Operation;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use Slim\Exception\NotFoundException;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

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
    protected static $prefixEntity = 'points';
    /**
     * $prefixSingularEntity
     *
     * @var string
     */
    protected static $prefixSingularEntity = 'point';
    /**
     * $title
     *
     * @var string
     */
    protected static $title = 'Localidad';
    /**
     * $pluralTitle
     *
     * @var string
     */
    protected static $pluralTitle = 'Localidades';

    /**
     * __construct
     *
     * @return static
     */
    public function __construct()
    {

        self::$title = __('locationBackend', self::$title);
        self::$pluralTitle = __('locationBackend', self::$pluralTitle);

        parent::__construct(false); //No cargar ningún modelo automáticamente.
        $this->model = (new PointMapper())->getModel();
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
        $status_options = array_map(function ($i) {return __('locationBackend', $i);}, PointMapper::STATUS);
        $status_options = array_to_html_options($status_options, PointMapper::ACTIVE);
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

        $element = new PointMapper($id);

        if (!is_null($element->id)) {

            $action = self::routeName('actions-edit');
            $status_options = array_map(function ($i) {return __('locationBackend', $i);}, PointMapper::STATUS);
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
        $data['has_add_link_permissions'] = strlen($add_link) > 0;
        $data['title'] = self::$pluralTitle;

        $this->render('panel/layout/header');
        $this->render('panel/' . self::$prefixParentEntity . '/' . self::$prefixSingularEntity . '/list', $data);
        $this->render('panel/layout/footer');
    }

    /**
     * points
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function points(Request $request, Response $response, array $args)
    {
        $city = $request->getQueryParam('city', null);

        if ($city != null) {
            if (ctype_digit($city)) {
                $city = (int) $city;
            } else {
                $city = -1;
            }
        }

        $valid = $city !== -1;

        if ($request->isXhr() && $valid) {

            $query = $this->model->select();

            if (!is_null($city)) {
                $query->where([
                    'city' => $city,
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
     * pointsDataTables
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function pointsDataTables(Request $request, Response $response, array $args)
    {
        $city = $request->getQueryParam('city', null);

        if ($city != null) {
            if (ctype_digit($city)) {
                $city = (int) $city;
            } else {
                $city = -1;
            }
        }

        $valid = $city !== -1;

        if ($request->isXhr() && $valid) {

            $columns_order = [
                'id',
                'name',
                DataTablesHelper::INGNORE,
                'city',
                'address',
                ['longitude', 'latitude'],
                'active',
            ];

            $result = DataTablesHelper::process([
                'columns_order' => $columns_order,
                'mapper' => new PointMapper(),
                'request' => $request,
                'on_set_data' => function ($e) {

                    $buttonEdit = new HtmlElement('a', __('locationBackend', 'Editar'));
                    $buttonEdit->setAttribute('class', "ui button green");
                    $buttonEdit->setAttribute('href', self::routeName('forms-edit', [
                        'id' => $e->id,
                    ]));

                    if ($buttonEdit->getAttributes(false)->offsetExists('href')) {
                        $href = $buttonEdit->getAttributes(false)->offsetGet('href');
                        if (strlen(trim($href->getValue())) < 1) {
                            $buttonEdit = __('locationBackend', 'Sin acciones');
                        }
                    }

                    $e_mapper = new PointMapper($e->id);

                    return [
                        $e->id,
                        stripslashes($e->name),
                        $e_mapper->city->state->country->name,
                        $e_mapper->city->state->name,
                        $e_mapper->city->name,
                        $e_mapper->address,
                        "$e->longitude, $e->latitude",
                        __('locationBackend', PointMapper::STATUS[$e->active]),
                        (string) $buttonEdit,
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
        $city = $request->getParsedBodyParam('city', null);
        $address = $request->getParsedBodyParam('address', null);
        $name = $request->getParsedBodyParam('name', null);
        $longitude = $request->getParsedBodyParam('longitude', null);
        $latitude = $request->getParsedBodyParam('latitude', null);
        $active = $request->getParsedBodyParam('active', null);
        $is_edit = $id !== -1;

        $valid_params = !in_array(null, [
            !is_null($city) && ctype_digit($city) ? (int) $city : null,
            $address,
            $name,
            $longitude,
            $latitude,
            $active,
        ]);

        $operation_name = $is_edit ? __('locationBackend', 'Modificar localidad') : __('locationBackend', 'Crear localidad');

        $result = new ResultOperations([
            new Operation($operation_name),
        ], $operation_name);

        $result->setValue('redirect', false);

        $error_parameters_message = __('locationBackend', 'Los parámetros recibidos son erróneos.');
        $not_exists_message = __('locationBackend', 'La localidad que intenta modificar no existe');
        $success_create_message = __('locationBackend', 'Localidad creada.');
        $success_edit_message = __('locationBackend', 'Datos guardados.');
        $unknow_error_message = __('locationBackend', 'Ha ocurrido un error desconocido.');
        $is_duplicate_message = __('locationBackend', 'Ya existe una localidad con ese nombre.');

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
            $name = strlen($name) > 0 ? "-{$name}" : '';
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
