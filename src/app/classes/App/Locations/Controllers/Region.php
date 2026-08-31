<?php

/**
 * Region.php
 */

namespace App\Locations\Controllers;

use App\Controller\AdminPanelController;
use App\Locations\LocationsLang;
use App\Locations\Mappers\CountryMapper;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use \PiecesPHP\Core\Routing\RequestRoute as Request;
use \PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;

/**
 * Region.
 *
 * Controlador de regiones
 *
 * @package     App\Locations\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class Region extends AdminPanelController
{

    use ControllerRoutingTrait;

    /**
     * @var string
     */
    protected static $baseRouteName = 'locations-regions';

    /**
     * @var string
     */
    protected static $prefixParentEntity = 'locations';
    /**
     * @var string
     */
    protected static $prefixEntity = 'regions';
    /**
     * @var string
     */
    protected static $prefixSingularEntity = 'region';

    /**
     * @return static
     */
    public function __construct()
    {

        parent::__construct();
        $this->model = (new CountryMapper())->getModel();

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function regions(Request $request, Response $response)
    {
        $model = $this->model;
        $prepared = $model->prepare("SELECT region AS name FROM locations_countries GROUP BY region ORDER BY region ASC");
        $prepared->execute();
        $regionsRecords = $prepared->fetchAll(\PDO::FETCH_OBJ);

        $result = $regionsRecords;
        $result = is_array($result) ? $result : [];

        foreach ($result as $key => $value) {
            $value->name = is_string($value->name) ? __(LocationsLang::LANG_GROUP_NAMES, $value->name) : $value->name;
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
        return $this->regions($request, $response);
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

        $model = $this->model;
        $prepared = $model->prepare("SELECT region AS name FROM locations_countries WHERE UPPER(region) LIKE UPPER('{$query}%') GROUP BY region ORDER BY region ASC");
        $prepared->execute();
        $regionsRecords = $prepared->fetchAll(\PDO::FETCH_OBJ);

        foreach ($regionsRecords as $row) {
            $result[] = [
                'id' => $row->name,
                'title' => is_string($row->name) ? __(LocationsLang::LANG_GROUP_NAMES, $row->name) : $row->name,
            ];
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
}
