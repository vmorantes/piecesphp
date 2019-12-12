<?php

/**
 * Country.php
 */

namespace App\Locations\Controllers;

use App\Controller\AdminPanelController;
use App\Locations\Mappers\CountryMapper;
use PiecesPHP\Core\HTML\HtmlElement;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Core\Utilities\ReturnTypes\Operation;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use Slim\Exception\NotFoundException;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

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
    protected static $prefixEntity = 'countries';
    /**
     * $prefixSingularEntity
     *
     * @var string
     */
    protected static $prefixSingularEntity = 'country';
    /**
     * $title
     *
     * @var string
     */
    protected static $title = 'País';
    /**
     * $pluralTitle
     *
     * @var string
     */
    protected static $pluralTitle = 'Países';

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
        $this->model = (new CountryMapper())->getModel();

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
        $status_options = array_map(function ($i) {return __('locationBackend', $i);}, CountryMapper::STATUS);
        $status_options = array_to_html_options($status_options, CountryMapper::ACTIVE);
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

        $element = new CountryMapper($id);

        if (!is_null($element->id)) {

            $action = self::routeName('actions-edit');
            $status_options = array_map(function ($i) {return __('locationBackend', $i);}, CountryMapper::STATUS);
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
     * countries
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function countries(Request $request, Response $response, array $args)
    {
        if ($request->isXhr()) {
            $this->model->select()->execute();
            $result = $this->model->result();

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
     * countriesDataTables
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function countriesDataTables(Request $request, Response $response, array $args)
    {

        $columns_order = [
            'id',
            'code',
            'name',
            'active',
        ];

        $result = DataTablesHelper::process([
            'columns_order' => $columns_order,
            'mapper' => new CountryMapper(),
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

                return [
                    $e->id,
                    !is_null($e->code) ? $e->code : '-',
                    stripslashes($e->name),
                    __('locationBackend', CountryMapper::STATUS[$e->active]),
                    (string) $buttonEdit,
                ];

            },
        ]);

        return $response->withJson($result->getValues());

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
        $code = $request->getParsedBodyParam('code', null);
        $name = $request->getParsedBodyParam('name', null);
        $active = $request->getParsedBodyParam('active', null);
        $is_edit = $id !== -1;

        $valid_params = !in_array(null, [
            $name,
            $active,
        ]);

        $operation_name = $is_edit ? __('locationBackend', 'Modificar país') : __('locationBackend', 'Crear país');

        $result = new ResultOperations([
            new Operation($operation_name),
        ], $operation_name);

        $result->setValue('redirect', false);

        $error_parameters_message = __('locationBackend', 'Los parámetros recibidos son erróneos.');
        $not_exists_message = __('locationBackend', 'El país que intenta modificar no existe');
        $success_create_message = __('locationBackend', 'País creado.');
        $success_edit_message = __('locationBackend', 'Datos guardados.');
        $unknow_error_message = __('locationBackend', 'Ha ocurrido un error desconocido.');
        $is_duplicate_message_name = __('locationBackend', 'Ya existe un país con ese nombre.');
        $is_duplicate_message_code = __('locationBackend', 'Ya existe un país con ese código.');

        $redirect_url_on_create = self::routeName('list');

        if ($valid_params) {

            $name = clean_string($name);
            $code = is_string($code) ? clean_string($code) : '';
            $code = strlen($code) > 0 ? $code : null;
            $is_duplicate_name = CountryMapper::isDuplicateName($name, $id);
            $is_duplicate_code = CountryMapper::isDuplicateCode($code, $id);

            if (!$is_duplicate_name && !$is_duplicate_code) {

                if (!$is_edit) {

                    $mapper = new CountryMapper();

                    try {

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

                    $mapper = new CountryMapper((int) $id);
                    $exists = !is_null($mapper->id);

                    if ($exists) {

                        try {

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
