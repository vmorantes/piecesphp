<?php

/**
 * GeoQueryException.php
 */

namespace PiecesPHP\Geo;

/**
 * GeoQueryException - Un valor de la petición que el geovisor no acepta. Su mensaje ya va traducido y la ruta lo
 * devuelve con un 400: nunca se recorta en silencio.
 *
 * @package     PiecesPHP\Geo
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class GeoQueryException extends \InvalidArgumentException
{
}
