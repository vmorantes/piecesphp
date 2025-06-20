<?php
/**
 * FeaturesTypes.php
 */

namespace GeoJSONManager\Enums;

use GeoJSONManager\GeoJsonManagerLang;
use PiecesPHP\GeoJson\Geometry\Polygon;

/**
 * FeaturesTypes.
 *
 * @package     GeoJSONManager\Enums
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
enum FeaturesTypes: String {
    case APPLICATION_CALLS = 'APPLICATION_CALLS';
    case PROFILES = 'PROFILES';

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    public static function humanValues(): array
    {
        return [
            self::APPLICATION_CALLS->value => __(GeoJsonManagerLang::LANG_GROUP, 'Contenidos'),
            self::PROFILES->value => __(GeoJsonManagerLang::LANG_GROUP, 'Perfiles'),
        ];
    }

    /**
     * Un array listo para ser usado en array_to_html_options
     * @param string $defaultLabel
     * @param string $defaultValue
     * @param bool $encryptValue
     * @param bool $ignoreInitial
     * @return array
     */
    public static function valuesForSelect(string $defaultLabel = '', string $defaultValue = '')
    {
        $defaultLabel = strlen($defaultLabel) > 0 ? $defaultLabel : __(GeoJsonManagerLang::LANG_GROUP, 'Seleccione un elemento');
        $options = [];
        $options[$defaultValue] = $defaultLabel;
        $elements = self::humanValues();

        foreach ($elements as $key => $value) {
            $options[$key] = $value;
        }

        return $options;
    }
}
