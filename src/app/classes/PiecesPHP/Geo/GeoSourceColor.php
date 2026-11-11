<?php

/**
 * GeoSourceColor.php
 */

namespace PiecesPHP\Geo;

/**
 * GeoSourceColor.
 *
 * Opcional para una fuente del geovisor: el color con el que pinta sus puntos y su fila de la leyenda cuando nadie define
 * su variable CSS (`colorVariable()`), que sigue mandando. Así una fuente nueva tiene color sin tocar el framework.
 *
 * @package     PiecesPHP\Geo
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
interface GeoSourceColor
{
    /**
     * Un color CSS: `#rrggbb` (o `#rgb`, con o sin alfa), `var(--un-token)`, una función de color (`rgb()`, `hsl()`,
     * `oklch()`, `color-mix()`…) o un nombre de color. Lo que no tenga esa forma se ignora.
     *
     * @return string
     */
    public function colorValue(): string;
}
