<?php

/**
 * StructuredData.php
 */
namespace PiecesPHP\Core\Utilities\Helpers;

use PiecesPHP\Core\Config;

/**
 * StructuredData
 *
 * Los datos estructurados de la página en JSON-LD (ADR 0036): un solo `<script type="application/ld+json">` con
 * `@graph`. Por omisión, `WebSite` y `Organization`; cada página o módulo añade lo suyo con add().
 *
 * No se marca nada que la página no enseñe: es la condición de Google para no penalizar.
 *
 * @category    Helpers
 * @package     PiecesPHP\Core\Utilities\Helpers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class StructuredData
{

    /**
     * `JSON_HEX_TAG` y `JSON_HEX_AMP`: un texto con `</script>` no puede cerrar la etiqueta.
     */
    const JSON_FLAGS = \JSON_HEX_TAG | \JSON_HEX_AMP | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR;

    /**
     * Los subtipos de schema.org `Organization` que admite extendOrganization(): lista cerrada.
     */
    const ORGANIZATION_TYPES = [
        'Organization', 'Airline', 'Consortium', 'Cooperative', 'Corporation', 'EducationalOrganization', 'FundingScheme',
        'GovernmentOrganization', 'LibrarySystem', 'LocalBusiness', 'MedicalOrganization', 'NGO', 'NewsMediaOrganization',
        'OnlineBusiness', 'PerformingGroup', 'PoliticalParty', 'Project', 'ResearchOrganization', 'SearchRescueOrganization',
        'SportsOrganization', 'WorkersUnion',
    ];

    /**
     * @var array<int,array<string,mixed>>
     */
    protected static $nodes = [];

    /**
     * Lo que el proyecto añade al nodo de la organización (extendOrganization()).
     *
     * @var array<string,mixed>
     */
    protected static array $organization = [];

    /**
     * Lo que extendOrganization() rechazó, sin aplicarlo.
     *
     * @var string[]
     */
    protected static array $errors = [];

    /**
     * @param array<string,mixed> $node Un nodo de schema.org, con su `@type`.
     * @return void
     */
    public static function add(array $node)
    {
        self::$nodes[] = $node;
    }

    /**
     * Retira los nodos añadidos; los de por omisión se vuelven a calcular en get().
     *
     * @return void
     */
    public static function clear()
    {
        self::$nodes = [];
    }

    /**
     * Amplía el nodo de la organización (src/app/config/extensions/structured-data.php): el subtipo con `@type`, las
     * redes con `sameAs` y cualquier otra propiedad de schema.org. Varias llamadas se acumulan: la última gana por clave
     * y `sameAs` se une sin repetir. Lo rechazado va a errors() y no se aplica; el `@id` no se toca nunca, porque
     * `WebSite.publisher` apunta a él.
     *
     * @param array<array-key,mixed> $properties Las claves que no son texto se rechazan a errors()
     * @return void
     */
    public static function extendOrganization(array $properties): void
    {
        foreach ($properties as $key => $value) {
            if (!is_string($key)) {
                self::$errors[] = 'la clave ' . var_export($key, true) . ' no es un nombre de propiedad: las claves tienen que ser texto';
                continue;
            }
            if ($key === '@id' || $key === '@context') {
                self::$errors[] = "{$key} no se puede cambiar: la organización conserva el suyo";
                continue;
            }
            if (str_starts_with($key, '@') && $key !== '@type') {
                self::$errors[] = "{$key} no se admite: de las claves con @ solo vale @type";
                continue;
            }
            if ($key === '@type' && !in_array($value, self::ORGANIZATION_TYPES, true)) {
                self::$errors[] = '@type ' . var_export($value, true) . ' no es un subtipo admitido de Organization (StructuredData::ORGANIZATION_TYPES)';
                continue;
            }
            if ($key === 'sameAs') {
                $invalid = is_array($value) && array_is_list($value) ? array_filter($value, fn($url) => !self::isAbsoluteHttpURL($url)) : [$value];
                if ($invalid !== []) {
                    self::$errors[] = 'sameAs tiene que ser una lista de URL absolutas http(s); no lo es: ' . implode(', ', array_map(fn($url) => var_export($url, true), $invalid));
                    continue;
                }
                $value = array_values(array_unique(array_merge(self::$organization['sameAs'] ?? [], $value)));
            }
            if (($key === 'logo' || $key === 'image') && is_string($value) && $value !== '' && !str_contains($value, '://')) {
                $value = baseurl(ltrim($value, '/'));
            }
            self::$organization[$key] = $value;
        }
    }

    /**
     * Lo que extendOrganization() rechazó. En local tumba la petición (final-configurations.php) y verify-integrity lo da
     * por fallo; en producción solo deja de aplicarse.
     *
     * @return string[]
     */
    public static function errors(): array
    {
        return self::$errors;
    }

    /**
     * @param mixed $url
     * @return bool
     */
    private static function isAbsoluteHttpURL($url): bool
    {
        if (!is_string($url) || filter_var($url, \FILTER_VALIDATE_URL) === false) {
            return false;
        }
        return in_array(mb_strtolower((string) parse_url($url, \PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    /**
     * Los nodos por omisión de toda página pública: el sitio y la organización.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function defaultNodes(): array
    {
        $base = baseurl();
        $name = get_config('title_app');
        $owner = get_config('owner');
        $logo = get_config('logo');
        $organization = [
            '@type' => 'Organization',
            '@id' => $base . '#organization',
            'name' => is_string($owner) && $owner !== '' ? $owner : (is_string($name) ? $name : ''),
            'url' => $base,
        ];
        if (is_string($logo) && $logo !== '') {
            $organization['logo'] = str_contains($logo, '://') ? $logo : baseurl(ltrim($logo, '/'));
        }
        //Aquí y no en extendOrganization(): config/extensions/ carga antes que las opciones de la base (H401).
        $image = get_config('open_graph_image');
        if (is_string($image) && $image !== '') {
            $organization['image'] = str_contains($image, '://') ? $image : baseurl(ltrim($image, '/'));
        }
        //Después de lo de omisión: lo que fije extendOrganization() gana.
        $organization = array_merge($organization, self::$organization);
        return [
            [
                '@type' => 'WebSite',
                '@id' => $base . '#website',
                'name' => is_string($name) ? $name : '',
                'url' => $base,
                'inLanguage' => Config::get_lang(),
                'publisher' => ['@id' => $base . '#organization'],
            ],
            $organization,
        ];
    }

    /**
     * El `<script>` con todos los nodos. Si la codificación falla no imprime nada y lo registra: una cabecera pública
     * no se cae por un dato.
     *
     * @return string
     */
    public static function get(): string
    {
        try {
            $json = json_encode([
                '@context' => 'https://schema.org',
                '@graph' => array_merge(self::defaultNodes(), self::$nodes),
            ], self::JSON_FLAGS);
        } catch (\JsonException $e) {
            log_exception($e);
            return '';
        }
        return "\r\n\t<script type=\"application/ld+json\">{$json}</script>\r\n";
    }
}
