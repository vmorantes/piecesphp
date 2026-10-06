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
     * @var array<int,array<string,mixed>>
     */
    protected static $nodes = [];

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
