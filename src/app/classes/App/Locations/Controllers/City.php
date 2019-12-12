<?php

/**
 * City.php
 */

namespace App\Locations\Controllers;

use App\Controller\AdminPanelController;
use App\Locations\Mappers\CityMapper;
use PiecesPHP\Core\HTML\HtmlElement;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Core\Utilities\ReturnTypes\Operation;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
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
    protected static $prefixEntity = 'cities';
    /**
     * $prefixSingularEntity
     *
     * @var string
     */
    protected static $prefixSingularEntity = 'city';
    /**
     * $title
     *
     * @var string
     */
    protected static $title = 'Ciudad';
    /**
     * $pluralTitle
     *
     * @var string
     */
    protected static $pluralTitle = 'Ciudades';

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
        $this->model = (new CityMapper())->getModel();
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
        $status_options = array_map(function ($i) {return __('locationBackend', $i);}, CityMapper::STATUS);
        $status_options = array_to_html_options($status_options, CityMapper::ACTIVE);
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

        $element = new CityMapper($id);

        if (!is_null($element->id)) {

            $action = self::routeName('actions-edit');
            $status_options = array_map(function ($i) {return __('locationBackend', $i);}, CityMapper::STATUS);
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
     * cities
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function cities(Request $request, Response $response, array $args)
    {
        $state = $request->getQueryParam('state', null);

        if ($state != null) {
            if (ctype_digit($state)) {
                $state = (int) $state;
            } else {
                $state = -1;
            }
        }

        $valid = $state !== -1;

        if ($request->isXhr() && $valid) {

            $query = $this->model->select();

            if (!is_null($state)) {
                $query->where([
                    'state' => $state,
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
     * citiesDataTables
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function citiesDataTables(Request $request, Response $response, array $args)
    {
        $state = $request->getQueryParam('state', null);

        if ($state != null) {
            if (ctype_digit($state)) {
                $state = (int) $state;
            } else {
                $state = -1;
            }
        }

        $valid = $state !== -1;

        if ($request->isXhr() && $valid) {

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

                    $e_mapper = new CityMapper($e->id);

                    return [
                        $e->id,
                        !is_null($e->code) ? $e->code : '-',
                        stripslashes($e->name),
                        $e_mapper->state->country->name,
                        $e_mapper->state->name,
                        __('locationBackend', CityMapper::STATUS[$e->active]),
                        (string) $buttonEdit,
                    ];

                },
                'where' => is_null($state) ? null : "state = $state",
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

        $operation_name = $is_edit ? __('locationBackend', 'Modificar ciudad') : __('locationBackend', 'Crear ciudad');

        $result = new ResultOperations([
            new Operation($operation_name),
        ], $operation_name);

        $result->setValue('redirect', false);

        $error_parameters_message = __('locationBackend', 'Los parámetros recibidos son erróneos.');
        $not_exists_message = __('locationBackend', 'La ciudad que intenta modificar no existe');
        $success_create_message = __('locationBackend', 'Ciudad creada.');
        $success_edit_message = __('locationBackend', 'Datos guardados.');
        $unknow_error_message = __('locationBackend', 'Ha ocurrido un error desconocido.');
        $is_duplicate_message_name = __('locationBackend', 'Ya existe una ciudad con ese nombre.');
        $is_duplicate_message_code = __('locationBackend', 'Ya existe una ciudad con ese código.');

        $redirect_url_on_create = self::routeName('list');

        if ($valid_params) {

            $name = clean_string($name);
            $code = !is_null($code) ? clean_string($code) : '';
            $code = strlen($code) > 0 ? $code : null;

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
