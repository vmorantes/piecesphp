<?php

/**
 * Region.php
 */

namespace App\Locations\Controllers;

use App\Controller\AdminPanelController;
use App\Locations\Mappers\CountryMapper;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use \PiecesPHP\Core\Routing\RequestRoute as Request;
use \PiecesPHP\Core\Routing\ResponseRoute as Response;

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
            $value->name = $value->name;
            $result[$key] = $value;
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
                'title' => $row->name,
            ];
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
