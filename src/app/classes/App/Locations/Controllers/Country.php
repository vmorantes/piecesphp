<?php

/**
 * Country.php
 */

namespace App\Locations\Controllers;

use App\Controller\AdminPanelController;
use App\Locations\LocationsLang;
use App\Locations\Mappers\CountryMapper;
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

    use ControllerRoutingTrait;

    /**
     * @var string
     */
    protected static $baseRouteName = 'locations-countries';

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
                'url' => $back_link,
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
    public function countries(Request $request, Response $response)
    {
        $region = $request->getQueryParam('region', null);
        //`IN (...)` NO pasa por marcador —ver T152—, así que se valida el DOMINIO. Lista
        //vacía = NO se añade el criterio: `IN ()` no compila.
        $ids = $request->getQueryParam('ids', []);
        $ids = is_array($ids) ? array_values(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)) : [];
        $ids = count($ids) > 0 ? implode(',', $ids) : null;

        //`region` son NOMBRES: `intval` no aplica y se descarta lo que no case. El patrón vive
        //en `regionNameOrNull()`, que comparten los dos sitios que comparan por nombre.
        if ($region !== null && is_string($region) && mb_strlen(trim($region)) > 0) {
            $nombresRegion = [];
            foreach (explode(',', $region) as $nombre) {
                $nombre = self::regionNameOrNull($nombre);
                if ($nombre !== null) {
                    $nombresRegion[] = "UPPER('{$nombre}')";
                }
            }
            //`NONE` es de la casa y entra siempre: sin él, todo descartado dejaría `IN ()`.
            $nombresRegion[] = "UPPER('NONE')";
            $region = implode(',', $nombresRegion);
        } else {
            $region = null;
        }

        $query = $this->model->select();

        $where = [];
        $whereString = null;

        if (!is_null($region)) {
            $operator = !empty($where) ? ' AND ' : '';
            $critery = "{$operator} ( UPPER(region) IN ({$region}) )";
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
            $value->name = is_string($value->name) ? __(LocationsLang::LANG_GROUP_NAMES, $value->name) : $value->name;
            $value->region = is_string($value->region) ? __(LocationsLang::LANG_GROUP_NAMES, $value->region) : $value->region;
            $result[$key] = $value;
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
        return $this->countries($request, $response);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function countriesDataTables(Request $request, Response $response)
    {

        //POR MARCADOR, con `where_segment`: el valor viaja como DATO y ya no hace falta el
        //patrón, así que un nombre con apóstrofo vuelve a poder buscarse. Ver T156.
        $region = $request->getQueryParam('region', null);
        $regionSegment = null;
        if ($region !== null) {
            $regionSegment = new WhereSegment([
                new WhereItem(
                    'UPPER(region)',
                    WhereItem::EQUAL_OPERATOR,
                    is_string($region) ? trim($region) : '',
                    '',
                    'UPPER(' . WhereItem::REPLACEMENT_VALUE_ON_RIGHT_WRAP_FUNCTION . ')'
                ),
            ]);
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
                    $e->code ?? '-',
                    stripslashes($e->name),
                    $e->region ?? '-',
                    __(LOCATIONS_LANG_GROUP, CountryMapper::STATUS[$e->active]),
                    $editButton,
                ];

            },
            'where_segment' => $regionSegment,
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

        $table = CountryMapper::PREFIX_TABLE . CountryMapper::TABLE;

        //`where(string)` CONCATENA —`"WHERE ({$where})"`— y `clean_string()` no escapa comillas.
        //Por marcador: `WhereSegment` arma `getReplacementValues()` y se prepara. Ver T150.
        $whereSegment = new WhereSegment([
            WhereItem::like(
                "UPPER({$table}.name)",
                $query . '%',
                '',
                'UPPER(' . WhereItem::REPLACEMENT_VALUE_ON_RIGHT_WRAP_FUNCTION . ')'
            ),
        ]);

        if ($whereSegment->countCriteria() > 0) {

            $model = CountryMapper::model();
            $model->select()->where($whereSegment);
            $model->execute(false, 1, 15);
            $queryResult = $model->result();

            foreach ($queryResult as $row) {
                $row->name = is_string($row->name) ? __(LocationsLang::LANG_GROUP_NAMES, $row->name) : $row->name;
                $result[] = [
                    'id' => $row->id,
                    'title' => $row->name,
                ];
            }

        }

        return $response->withJson($result);
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

    /**
     * Valida UN nombre de región contra el patrón, y devuelve `null` si no casa.
     *
     * `region` es la única columna de este módulo que se compara por NOMBRE y no por entero, y
     * se compara desde DOS sitios: `countries()` con `IN (...)` y `countriesDataTables()` con
     * `=`. Ninguno de los dos pasa por marcador, así que lo que cierra el agujero es esto.
     *
     * El patrón es conservador porque **el conjunto real no se puede saber sin la base**:
     * `structure.sql:156` declara `region text DEFAULT NULL`, sin ENUM ni tabla de regiones.
     * Un nombre con apóstrofo o con punto queda descartado, y eso se sabe. Ver T152 y T155.
     *
     * @param string $nombre
     * @return string|null
     */
    protected static function regionNameOrNull(string $nombre): ?string
    {
        $nombre = trim($nombre);
        return preg_match('/^[\p{L}\p{N} \-]{1,60}$/u', $nombre) === 1 ? $nombre : null;
    }
}
