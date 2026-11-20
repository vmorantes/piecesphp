<?php

/**
 * FormActionRegistry.php
 */

namespace PiecesPHP\PublicSite;

use App\Controller\ContactFormsController;

/**
 * FormActionRegistry - Las acciones de <pcs-form action="…">: adónde envía, con qué atributos y qué JavaScript la
 * protege. El framework registra `contact` (el formulario de contacto, con su reCAPTCHA) si la zona pública lo tiene.
 *
 * @package     PiecesPHP\PublicSite
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class FormActionRegistry
{

    /**
     * @var array<string,callable():array{url:string,attributes:array<string,string>,js:string[]}>
     */
    private static $actions = [];

    /**
     * @var bool
     */
    private static $defaultsLoaded = false;

    /**
     * @param string $name
     * @param callable():array{url:string,attributes:array<string,string>,js:string[]} $definition Se llama al pintar
     * @return void
     * @throws \InvalidArgumentException Si ya existe: usa replace()
     */
    public static function register(string $name, callable $definition): void
    {
        self::loadDefaults();
        if (array_key_exists($name, self::$actions)) {
            throw new \InvalidArgumentException("La acción de formulario «{$name}» ya está registrada: usa replace() para cambiarla.");
        }
        self::$actions[$name] = $definition;
    }

    /**
     * @param string $name
     * @param callable():array{url:string,attributes:array<string,string>,js:string[]} $definition
     * @return void
     * @throws \InvalidArgumentException Si no existe
     */
    public static function replace(string $name, callable $definition): void
    {
        self::loadDefaults();
        if (!array_key_exists($name, self::$actions)) {
            throw new \InvalidArgumentException("No hay ninguna acción de formulario «{$name}» que sustituir.");
        }
        self::$actions[$name] = $definition;
    }

    /**
     * @param string $name
     * @return bool Si existía
     */
    public static function remove(string $name): bool
    {
        self::loadDefaults();
        $existed = array_key_exists($name, self::$actions);
        unset(self::$actions[$name]);
        return $existed;
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function has(string $name): bool
    {
        self::loadDefaults();
        return array_key_exists($name, self::$actions);
    }

    /**
     * @return string[]
     */
    public static function names(): array
    {
        self::loadDefaults();
        $names = array_keys(self::$actions);
        sort($names);
        return $names;
    }

    /**
     * La definición de una acción, o null si no existe o no devuelve la forma esperada (eso queda registrado).
     *
     * @param string $name
     * @return array{url:string,attributes:array<string,string>,js:string[]}|null
     */
    public static function resolve(string $name): ?array
    {
        self::loadDefaults();
        if (!array_key_exists($name, self::$actions)) {
            return null;
        }
        try {
            $definition = (self::$actions[$name])();
        } catch (\Throwable $e) {
            log_exception($e);
            return null;
        }
        if (!is_array($definition) || !is_string($definition['url'] ?? null) || $definition['url'] === '') {
            log_exception(new \UnexpectedValueException("La acción de formulario «{$name}» no devolvió una URL."));
            return null;
        }
        $attributes = is_array($definition['attributes'] ?? null) ? array_filter($definition['attributes'], fn ($value, $key) => is_string($key) && is_string($value) && MarkRegistry::isSafeAttributeName($key), \ARRAY_FILTER_USE_BOTH) : [];
        $js = is_array($definition['js'] ?? null) ? array_values(array_filter($definition['js'], 'is_string')) : [];
        return ['url' => $definition['url'], 'attributes' => $attributes, 'js' => $js];
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
        if (defined('PUBLIC_AREA_CONTACT_FORMS') && PUBLIC_AREA_CONTACT_FORMS) {
            self::$actions['contact'] = fn (): array => [
                'url' => (string) ContactFormsController::routeName('general', [], true),
                'attributes' => ['contact-form' => ''],
                'js' => ['statics/js/contact-form.js'],
            ];
        }
    }
}
