<?php
/**
 * GeometryPackage.php
 */

namespace GeoJSONManager\Util;

use PiecesPHP\GeoJson\Geometry\GeometryInterface;
use PiecesPHP\GeoJson\Geometry\MultiLineString;
use PiecesPHP\GeoJson\Geometry\MultiPolygon;
use PiecesPHP\GeoJson\Geometry\Point;
use PiecesPHP\GeoJson\Geometry\Polygon;

/**
 * GeometryPackage.
 *
 * @package     GeoJSONManager\Util
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class GeometryPackage
{
    public readonly string $type;
    public readonly GeometryInterface $geometry;

    /**
     * @param MultiLineString|MultiPolygon|Point|Polygon $geometry
     */
    public function __construct(MultiLineString | MultiPolygon | Point | Polygon $geometry)
    {
        $this->type = $geometry::TYPE;
        $this->geometry = $geometry;
    }

}
