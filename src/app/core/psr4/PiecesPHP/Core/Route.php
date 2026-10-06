<?php

/**
 * Route.php
 */
namespace PiecesPHP\Core;

use PiecesPHP\Core\Routing\RouteAdapter;

/**
 * Route
 *
 * Su SEXTO argumento, el alias, está IGNORADO desde el bloque CX: no se retira porque desplazaría
 * `rolesAllowed` en las 221 declaraciones posicionales que hay, y en las de cualquier clon.
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class Route extends RouteAdapter
{}
