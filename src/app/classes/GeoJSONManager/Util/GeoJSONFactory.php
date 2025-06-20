<?php
/**
 * GeoJSONFactory.php
 */

namespace GeoJSONManager\Util;

use PiecesPHP\GeoJson\Feature;
use PiecesPHP\GeoJson\GeoJson;
use PiecesPHP\GeoJson\Geometry\MultiLineString;
use PiecesPHP\GeoJson\Geometry\MultiPolygon;
use PiecesPHP\GeoJson\Geometry\Polygon;

/**
 * GeoJSONFactory.
 *
 * @package     GeoJSONManager\Util
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class GeoJSONFactory
{

    /**
     * @param FeaturesCollection $features
     * @return GeoJson
     */
    public static function getGeoJsonFromGeometries(FeaturesCollection $featuresCollection)
    {
        $geoJson = new GeoJson([]);
        $features = [];
        /**
         * @var Feature $geometryPackage
         */
        foreach ($featuresCollection->getArrayCopy() as $feature) {
            $features[] = $feature;

        }
        $geoJson->features($features);
        return $geoJson;
    }

    /**
     * @param GeometryPackage $geometryPackage
     * @param array $properties
     * @return Feature
     */
    public static function getFeatureFromGeometry(GeometryPackage $geometryPackage, array $properties = [])
    {
        $type = $geometryPackage->type;
        $coordinates = $geometryPackage->geometry->coordinates();
        if ($type == MultiPolygon::TYPE || $type == MultiLineString::TYPE) {
            $coordinates = is_array($coordinates) && isset($coordinates[0]) ? $coordinates[0] : [];
            $coordinates = is_array($coordinates) && isset($coordinates[0]) ? $coordinates[0] : [];
        } elseif ($type == Polygon::TYPE) {
            $coordinates = is_array($coordinates) && isset($coordinates[0]) ? $coordinates[0] : [];
        }
        $feature = new Feature($coordinates, $type, $properties);
        return $feature;
    }

}
