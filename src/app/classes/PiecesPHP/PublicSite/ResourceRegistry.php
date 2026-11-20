<?php

/**
 * ResourceRegistry.php
 */

namespace PiecesPHP\PublicSite;

use Publications\Controllers\PublicationsController;
use Publications\PublicationsRoutes;

/**
 * ResourceRegistry - Los recursos que puede pedir <pcs-list>. El framework registra `publications` (las visibles al
 * público, si el módulo está encendido); un proyecto añade, quita o sustituye desde el routes() de su módulo.
 *
 * @package     PiecesPHP\PublicSite
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class ResourceRegistry
{

    /**
     * @var array<string,ResourceContract>
     */
    private static $resources = [];

    /**
     * @var bool
     */
    private static $defaultsLoaded = false;

    /**
     * @param ResourceContract $contract
     * @return void
     * @throws \InvalidArgumentException Si ya hay un recurso con ese nombre: usa replace()
     */
    public static function register(ResourceContract $contract): void
    {
        self::loadDefaults();
        if (array_key_exists($contract->name, self::$resources)) {
            throw new \InvalidArgumentException("El recurso «{$contract->name}» ya está registrado: usa replace() para cambiarlo.");
        }
        self::$resources[$contract->name] = $contract;
    }

    /**
     * @param ResourceContract $contract
     * @return void
     * @throws \InvalidArgumentException Si no hay ningún recurso con ese nombre
     */
    public static function replace(ResourceContract $contract): void
    {
        self::loadDefaults();
        if (!array_key_exists($contract->name, self::$resources)) {
            throw new \InvalidArgumentException("No hay ningún recurso «{$contract->name}» que sustituir.");
        }
        self::$resources[$contract->name] = $contract;
    }

    /**
     * @param string $name
     * @return bool Si existía
     */
    public static function remove(string $name): bool
    {
        self::loadDefaults();
        $existed = array_key_exists($name, self::$resources);
        unset(self::$resources[$name]);
        return $existed;
    }

    /**
     * @param string $name
     * @return ResourceContract|null
     */
    public static function get(string $name): ?ResourceContract
    {
        self::loadDefaults();
        return self::$resources[$name] ?? null;
    }

    /**
     * @return array<string,ResourceContract>
     */
    public static function all(): array
    {
        self::loadDefaults();
        ksort(self::$resources, \SORT_STRING);
        return self::$resources;
    }

    /**
     * Para las pruebas: cambia el registro entero y devuelve el anterior. Con null, vuelve a los de fábrica.
     *
     * @param array<string,ResourceContract>|null $restore
     * @return array<string,ResourceContract>
     */
    public static function swapForTesting(?array $restore = null): array
    {
        self::loadDefaults();
        $previous = self::$resources;
        if ($restore === null) {
            self::$resources = [];
            self::$defaultsLoaded = false;
            self::loadDefaults();
        } else {
            self::$resources = $restore;
        }
        return $previous;
    }

    /**
     * @return void
     */
    private static function loadDefaults(): void
    {
        if (self::$defaultsLoaded) {
            return;
        }
        self::$defaultsLoaded = true;
        if (class_exists(PublicationsRoutes::class) && PublicationsRoutes::ENABLE) {
            self::$resources['publications'] = self::publications();
        }
    }

    /**
     * Las publicaciones que ve el público, en el idioma actual: el listado público de siempre (_all), que ya filtra
     * estado, fechas, aprobación e idioma.
     *
     * @return ResourceContract
     */
    private static function publications(): ResourceContract
    {
        return new ResourceContract('publications', [
            'title' => ['type' => ResourceContract::TYPE_TEXT, 'example' => 'Título de la publicación'],
            'excerpt' => ['type' => ResourceContract::TYPE_TEXT, 'example' => 'Un resumen corto de la publicación, de unas dos líneas.'],
            'url' => ['type' => ResourceContract::TYPE_URL, 'example' => '#'],
            'image' => ['type' => ResourceContract::TYPE_IMAGE, 'example' => 'https://placehold.co/600x400'],
            'date' => ['type' => ResourceContract::TYPE_DATE, 'example' => '20/11/2026'],
            'category' => ['type' => ResourceContract::TYPE_TEXT, 'example' => 'Noticias'],
        ], function (int $count): array {
            $rows = [];
            foreach (PublicationsController::_all(1, max(1, $count))->elements() as $element) {
                if (!$element instanceof \stdClass) {
                    continue;
                }
                $image = (string) ($element->thumbImage ?? $element->mainImage ?? '');
                $rows[] = [
                    'title' => (string) ($element->title ?? ''),
                    'excerpt' => (string) ($element->excerptAlt ?? ''),
                    'url' => (string) ($element->link ?? ''),
                    'image' => $image !== '' && !str_contains($image, '://') ? baseurl(ltrim($image, '/')) : $image,
                    'date' => (string) ($element->publicDateFormat ?? ''),
                    'category' => (string) ($element->categoryName ?? ''),
                ];
            }
            return $rows;
        });
    }
}
