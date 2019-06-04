<?php

/**
 * Country.php
 */

namespace App\Locations\Controllers;

use App\Controller\AdminPanelController;
use App\Locations\Mappers\CountryMapper;
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
     * __construct
     *
     * @return static
     */
    public function __construct()
    {
        parent::__construct(false); //No cargar ningún modelo automáticamente.
        $this->model = (new CountryMapper())->getModel();
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
            return $response->withJson($this->model->result());
        } else {
            throw new NotFoundException($request, $response);
        }
    }
}
