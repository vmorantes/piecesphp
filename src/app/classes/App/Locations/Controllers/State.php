<?php

/**
 * State.php
 */

namespace App\Locations\Controllers;

use App\Controller\AdminPanelController;
use App\Locations\LocationsLang;
use App\Locations\Mappers\StateMapper;
use PiecesPHP\Core\Pagination\PageQuery;
use PiecesPHP\Core\Pagination\PaginationResult;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
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
use PiecesPHP\Core\Routing\ControllerRoutingTrait;

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

    use ControllerRoutingTrait;

    /**
     * @var string
     */
    protected static $baseRouteName = 'locations-states';

    /**
     * @var string
     */
    protected static $prefixParentEntity = 'locations';
    /**
     * @var string
     */
    protected static $prefixEntity = 'states';
    /**
     * @var string
     */
    protected static $prefixSingularEntity = 'state';
    /**
     * @var string
     */
    protected static $title = 'Departamento';
    /**
     * @var string
     */
    protected static $pluralTitle = 'Departamentos';

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
        $this->model = (new StateMapper())->getModel();
        set_title(self::$pluralTitle);
    }

    /**
     * @return void
     */
    public function addForm()
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
        $data['breadcrumbs'] = get_breadcrumbs([
            __(ADMIN_MENU_LANG_GROUP, 'Inicio') => [
                'url' => get_route('admin'),
            ],
            __(ADMIN_MENU_LANG_GROUP, 'Ubicaciones') => [
                'url' => get_route('locations', [], true),
            ],
            __(LOCATIONS_LANG_GROUP, 'Departamentos') => [
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
            $data['breadcrumbs'] = get_breadcrumbs([
                __(ADMIN_MENU_LANG_GROUP, 'Inicio') => [
                    'url' => get_route('admin'),
                ],
                __(ADMIN_MENU_LANG_GROUP, 'Ubicaciones') => [
                    'url' => get_route('locations', [], true),
                ],
                __(LOCATIONS_LANG_GROUP, 'Departamentos') => [
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
        $back_link = Locations::routeName();
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
    public function states(Request $request, Response $response)
    {
        $country = $request->getQueryParam('country', null);
        //`IN (...)` NO pasa por marcador —ver T152—, así que se valida el DOMINIO. Lista
        //vacía = NO se añade el criterio: `IN ()` no compila.
        $ids = $request->getQueryParam('ids', []);
        $ids = is_array($ids) ? array_values(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)) : [];
        $ids = count($ids) > 0 ? implode(',', $ids) : null;

        if ($country !== null) {
            if (Validator::isInteger($country)) {
                $country = (int) $country;
            } else {
                $country = -1;
            }
        }

        $query = $this->model->select();

        $where = [];
        $whereString = null;

        if (!is_null($country)) {
            $operator = !empty($where) ? ' AND ' : '';
            $critery = "{$operator} (country = {$country})";
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
    public function statesDataTables(Request $request, Response $response)
    {
        $country = $request->getQueryParam('country', null);
        if ($country !== null) {
            $country = Validator::isInteger($country) ? (int) $country : -1;
        }

        $select_fields = StateMapper::fieldsToSelect();

        $columns_order = [
            'id',
            'code',
            'name',
            'countryName',
            'active',
        ];

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);
        $result = DataTablesHelper::process([
            'select_fields' => $select_fields,
            'columns_order' => $columns_order,
            'mapper' => new StateMapper(),
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
                    $e->code ?? '-',
                    stripslashes($e->name),
                    $e->countryName,
                    __(LOCATIONS_LANG_GROUP, StateMapper::STATUS[$e->active]),
                    $editButton,
                ];

            },
            'where_string' => is_null($country) ? null : "country = $country",
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
        $country = $request->getParsedBodyParam('country', null);
        $name = $request->getParsedBodyParam('name', null);
        $code = $request->getParsedBodyParam('code', null);
        $active = $request->getParsedBodyParam('active', null);
        $is_edit = $id !== -1;

        $valid_params = !in_array(null, [
            Validator::isInteger($country) ? (int) $country : null,
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

        $table = StateMapper::PREFIX_TABLE . StateMapper::TABLE;

        //`where(string)` CONCATENA —`"WHERE ({$where})"`— y `clean_string()` no escapa comillas.
        //Por marcador: `WhereSegment` arma `getReplacementValues()` y se prepara. Ver T151.
        $whereSegment = new WhereSegment([
            WhereItem::like(
                "UPPER({$table}.name)",
                $query . '%',
                '',
                'UPPER(' . WhereItem::REPLACEMENT_VALUE_ON_RIGHT_WRAP_FUNCTION . ')'
            ),
        ]);

        if ($whereSegment->countCriteria() > 0) {
            $model = StateMapper::model();
            $model->select()->where($whereSegment);
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
                'country',
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
         * @var int $country
         * @var int[] $ignore
         */
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
    public static function _all(int $page = 1, int $perPage = 10, ?int $country = null, array $ignore = [])
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
     * Verificar si una ruta es permitida y determinar pasos para permitirla o no
     *
     * PUNTO DE VARIACIÓN DEL MÓDULO. Aquí, y en ningún otro sitio, van las reglas de negocio
     * que oculten una ruta que los roles SÍ permiten. Está vacío a propósito: es la plantilla,
     * y su presencia dice dónde se escribe la regla el día que aparezca.
     *
     * Devolver `false` ESTRECHA lo que ya concedieron los roles; nunca ensancha. `routeName()`
     * llama a este método SIEMPRE, y `allowedRoute()` no hace más que preguntarle a
     * `routeName()` si devolvió cadena.
     *
     * @param string $name
     * @param string $route
     * @param array $params
     * @return bool
     */
    protected static function _allowedRoute(string $name, string $route, array $params = [])
    {
        return true;
    }
}
