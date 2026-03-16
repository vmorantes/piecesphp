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

    /**
     * Obtiene los valores humanos de las características
     * @param array<FeaturesTypes> $ignoreValues Valores a ignorar
     * @return array Valores humanos
     */
    public static function humanValues(array $ignoreValues = []): array
    {
        $values = [
            self::APPLICATION_CALLS->value => __(GeoJsonManagerLang::LANG_GROUP, 'Contenidos'),
            self::PROFILES->value => __(GeoJsonManagerLang::LANG_GROUP, 'Perfiles'),
        ];

        foreach ($ignoreValues as $value) {
            unset($values[$value->value]);
        }

        return $values;
    }

    /**
     * Un array listo para ser usado en array_to_html_options
     * @param string $defaultLabel
     * @param string $defaultValue
     * @param array<FeaturesTypes> $ignoreValues Valores a ignorar
     * @return array
     */
    public static function valuesForSelect(string $defaultLabel = '', string $defaultValue = '', array $ignoreValues = [])
    {
        $defaultLabel = strlen($defaultLabel) > 0 ? $defaultLabel : __(GeoJsonManagerLang::LANG_GROUP, 'Seleccione un elemento');
        $options = [];
        $options[$defaultValue] = $defaultLabel;
        $elements = self::humanValues($ignoreValues);

        foreach ($elements as $key => $value) {
            $options[$key] = $value;
        }

        return $options;
    }
}
