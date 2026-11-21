<?php

/**
 * MarkRegistry.php
 */

namespace PiecesPHP\PublicSite;

use PiecesPHP\Core\Utilities\Helpers\MetaTags;
use PiecesPHP\Core\Utilities\Helpers\StructuredData;
use PiecesPHP\Settings\Controllers\SettingsController;

/**
 * MarkRegistry - Qué pinta cada marca en el proyecto. Las siete del ADR 0084 vienen de fábrica; un proyecto añade una
 * suya, o sustituye o quita una, desde el routes() de su módulo. La vista compilada solo llama a render().
 *
 * Un pintor recibe los atributos de la marca, su contenido ya pintado (pcs-text: su texto; pcs-list: su <template>) y el
 * contexto, y devuelve HTML que él mismo escapa.
 *
 * @package     PiecesPHP\PublicSite
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class MarkRegistry
{

    /** Un nombre de atributo que se puede pintar; los on* se quitan aparte. */
    const ATTRIBUTE_NAME_PATTERN = '/^[a-zA-Z_:][-a-zA-Z0-9_:.]*$/D';

    /** Los de <pcs-image> que pasan a la <img>, además de class y alt; cada valor, validado con rules.json. */
    const IMAGE_ATTRIBUTES = ['width', 'height', 'loading', 'decoding', 'sizes'];

    /**
     * @var array<string,callable(array<string,string>,string,MarkContext):string>
     */
    private static $marks = [];

    /**
     * @var bool
     */
    private static $defaultsLoaded = false;

    /**
     * @param string $name El nombre del elemento, con el prefijo «pcs-»
     * @param callable(array<string,string>,string,MarkContext):string $renderer
     * @return void
     * @throws \InvalidArgumentException Si el nombre no empieza por «pcs-» o ya existe (usa replace())
     */
    public static function register(string $name, callable $renderer): void
    {
        self::loadDefaults();
        if (preg_match('/^pcs-[a-z0-9]+(-[a-z0-9]+)*$/D', $name) !== 1) {
            throw new \InvalidArgumentException("El nombre de la marca «{$name}» no es válido: «pcs-» seguido de minúsculas, números y guiones.");
        }
        if (array_key_exists($name, self::$marks)) {
            throw new \InvalidArgumentException("La marca «{$name}» ya está registrada: usa replace() para cambiarla.");
        }
        self::$marks[$name] = $renderer;
    }

    /**
     * @param string $name
     * @param callable(array<string,string>,string,MarkContext):string $renderer
     * @return void
     * @throws \InvalidArgumentException Si no existe
     */
    public static function replace(string $name, callable $renderer): void
    {
        self::loadDefaults();
        if (!array_key_exists($name, self::$marks)) {
            throw new \InvalidArgumentException("No hay ninguna marca «{$name}» que sustituir.");
        }
        self::$marks[$name] = $renderer;
    }

    /**
     * @param string $name
     * @return bool Si existía
     */
    public static function remove(string $name): bool
    {
        self::loadDefaults();
        $existed = array_key_exists($name, self::$marks);
        unset(self::$marks[$name]);
        return $existed;
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function has(string $name): bool
    {
        self::loadDefaults();
        return array_key_exists($name, self::$marks);
    }

    /**
     * @return string[] Ordenados
     */
    public static function names(): array
    {
        self::loadDefaults();
        $names = array_keys(self::$marks);
        sort($names);
        return $names;
    }

    /**
     * Pinta una marca. Si no existe o su pintor lanza, vacía y una entrada en el registro de errores: nunca tumba la página.
     *
     * @param string $name
     * @param array<string,string> $attributes
     * @param string $content
     * @param MarkContext $context
     * @return string
     */
    public static function render(string $name, array $attributes, string $content, MarkContext $context): string
    {
        self::loadDefaults();
        if (!array_key_exists($name, self::$marks)) {
            self::report($context, "la marca <{$name}> no está registrada: se pinta vacía");
            return '';
        }
        try {
            $html = (self::$marks[$name])($attributes, $content, $context);
            return is_string($html) ? $html : '';
        } catch (\Throwable $e) {
            log_exception($e);
            return '';
        }
    }

    /**
     * Anota en el registro de errores un problema de una marca del sitio.
     *
     * @param MarkContext $context
     * @param string $problem
     * @return void
     */
    public static function report(MarkContext $context, string $problem): void
    {
        log_exception(new \RuntimeException(MarkRules::printable("Sitio público «{$context->site}», página «{$context->page}»: {$problem}.")));
    }

    /**
     * Para las pruebas: cambia el registro entero y devuelve el anterior. Con null, vuelve a los de fábrica.
     *
     * @param array<string,callable(array<string,string>,string,MarkContext):string>|null $restore
     * @return array<string,callable(array<string,string>,string,MarkContext):string>
     */
    public static function swapForTesting(?array $restore = null): array
    {
        self::loadDefaults();
        $previous = self::$marks;
        if ($restore === null) {
            self::$marks = [];
            self::$defaultsLoaded = false;
            self::loadDefaults();
        } else {
            self::$marks = $restore;
        }
        return $previous;
    }

    /**
     * Los atributos de la marca que pasan tal cual al elemento que pinta, escapados; sin los que la marca consume.
     *
     * @param array<string,string> $attributes
     * @param string[] $consumed
     * @return string Con espacio inicial si hay alguno
     */
    public static function passThroughAttributes(array $attributes, array $consumed): string
    {
        $html = '';
        foreach ($attributes as $name => $value) {
            if (in_array($name, $consumed, true) || !self::isSafeAttributeName((string) $name)) {
                continue;
            }
            $html .= ' ' . $name . '="' . escape_html($value) . '"';
        }
        return $html;
    }

    /**
     * Si un nombre de atributo se puede pintar: con forma de nombre y nunca un manejador de eventos (on*).
     *
     * @param string $name
     * @return bool
     */
    public static function isSafeAttributeName(string $name): bool
    {
        return preg_match(self::ATTRIBUTE_NAME_PATTERN, $name) === 1 && stripos($name, 'on') !== 0;
    }

    /**
     * Una URL que se puede pintar, por lista blanca: http(s)://, mailto:, tel:, /, ./, ../, #, ? o sin «:» antes de «/»;
     * si no, vacía. Antes, lo que el navegador quita: los C0 y espacios del principio, y tabuladores y saltos de todo.
     *
     * @param string $value
     * @return string Sin escapar
     */
    public static function safeUrl(string $value): string
    {
        $clean = (string) preg_replace('/[\t\r\n]/', '', ltrim($value, "\x00..\x20"));
        if (preg_match('#^(https?://|mailto:|tel:|/|\./|\.\./|\#|\?)#i', $clean) === 1) {
            return $clean;
        }
        $colon = strpos($clean, ':');
        $slash = strpos($clean, '/');
        return $colon === false || ($slash !== false && $slash < $colon) ? $clean : '';
    }

    /**
     * Un valor de un campo de recurso listo para el HTML: escapado y, si es url o image o va en un atributo de URL
     * ($asUrl), pasado antes por safeUrl().
     *
     * @param string $value
     * @param string $type
     * @param bool $asUrl
     * @return string
     */
    public static function fieldValue(string $value, string $type, bool $asUrl = false): string
    {
        if ($asUrl || in_array($type, [ResourceContract::TYPE_URL, ResourceContract::TYPE_IMAGE], true)) {
            $value = self::safeUrl($value);
        }
        return escape_html($value);
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

        self::$marks['pcs-part'] = fn (array $attributes, string $content, MarkContext $context): string => $context->part($attributes['name'] ?? '');

        self::$marks['pcs-link'] = function (array $attributes, string $content, MarkContext $context): string {
            $page = $attributes['page'] ?? '';
            $url = PublicSite::pageURL($page);
            if ($url === '') {
                self::report($context, "<pcs-link page=\"{$page}\"> apunta a una página que no existe");
            }
            $anchor = $attributes['anchor'] ?? null;
            if ($url !== '' && MarkRules::acceptsValue('pcs-link', 'anchor', $anchor)) {
                $url .= '#' . $anchor;
            }
            $consumed = ['page', 'href', 'anchor', 'current-class'];
            $current = '';
            //La página que se pinta, también desde una parte: el contexto es el de la página que la pide.
            if ($page === $context->page) {
                $currentClass = $attributes['current-class'] ?? null;
                $classes = (string) ($attributes['class'] ?? '') . ' ' . (MarkRules::acceptsValue('pcs-link', 'current-class', $currentClass) ? $currentClass : '');
                $classes = array_values(array_unique(preg_split('/\s+/', trim($classes), -1, \PREG_SPLIT_NO_EMPTY) ?: []));
                $current = ($classes !== [] ? ' class="' . escape_html(implode(' ', $classes)) . '"' : '') . ' aria-current="page"';
                $consumed = array_merge($consumed, ['class', 'aria-current']);
            }
            return '<a href="' . escape_html($url) . '"' . $current . self::passThroughAttributes($attributes, $consumed) . '>' . $content . '</a>';
        };

        self::$marks['pcs-meta'] = fn (array $attributes, string $content, MarkContext $context): string => MetaTags::getMetaTagsGeneric() . MetaTags::getMetaTagsOpenGraph()
            . (($attributes['structured-data'] ?? '') === 'off' ? '' : StructuredData::get());

        self::$marks['pcs-image'] = function (array $attributes, string $content, MarkContext $context): string {
            $brand = $attributes['brand'] ?? '';
            $configName = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $brand))));
            if (!array_key_exists($configName, SettingsController::BRAND_IMAGES_DARK_MODE)) {
                self::report($context, "<pcs-image brand=\"{$brand}\"> no es una imagen de marca (" . implode(', ', self::brandNames()) . ')');
                return '';
            }
            $extra = '';
            foreach (self::IMAGE_ATTRIBUTES as $name) {
                if (MarkRules::acceptsValue('pcs-image', $name, $attributes[$name] ?? null)) {
                    $extra .= ' ' . $name . '="' . escape_html((string) $attributes[$name]) . '"';
                }
            }
            //brandImageTag() pinta una o dos <img> (la clara y la oscura): las dos llevan los mismos atributos.
            return str_replace('<img ', '<img' . $extra . ' ', SettingsController::brandImageTag($configName, $attributes['class'] ?? '', $attributes['alt'] ?? ''));
        };

        self::$marks['pcs-text'] = function (array $attributes, string $content, MarkContext $context): string {
            $key = $attributes['key'] ?? '';
            $translated = __($context->langGroup(), $key);
            return escape_html($translated !== $key ? $translated : $content);
        };

        self::$marks['pcs-list'] = function (array $attributes, string $content, MarkContext $context): string {
            $contract = ResourceRegistry::get($attributes['resource'] ?? '');
            if ($contract === null) {
                self::report($context, '<pcs-list resource="' . ($attributes['resource'] ?? '') . '"> pide un recurso que no está registrado');
                return '';
            }
            $count = isset($attributes['count']) && ctype_digit($attributes['count']) ? max(1, min(100, (int) $attributes['count'])) : MarkRules::defaultListCount();
            $html = '';
            foreach ($contract->rows($count, $context) as $row) {
                //«{{@campo}}» lo escribe el compilador en los atributos de URL: va por safeUrl() sea cual sea el tipo.
                $html .= (string) preg_replace_callback('/\{\{\s*+(@?)(' . trim(MarkRules::fieldPattern(), '^$') . ')\s*+\}\}/', function (array $match) use ($row, $contract): string {
                    $type = $contract->fields[$match[2]]['type'] ?? null;
                    return $type !== null ? self::fieldValue($row[$match[2]] ?? '', $type, $match[1] === '@') : '';
                }, $content);
            }
            return $html;
        };

        self::$marks['pcs-form'] = function (array $attributes, string $content, MarkContext $context): string {
            $action = FormActionRegistry::resolve($attributes['action'] ?? '');
            if ($action === null) {
                self::report($context, '<pcs-form action="' . ($attributes['action'] ?? '') . '"> pide una acción que no está registrada');
                return '';
            }
            $extra = '';
            foreach ($action['attributes'] as $name => $value) {
                $extra .= ' ' . escape_html($name) . ($value !== '' ? '="' . escape_html($value) . '"' : '');
            }
            return '<form method="POST" action="' . escape_html($action['url']) . '"' . $extra . self::passThroughAttributes($attributes, array_merge(['action', 'method'], array_keys($action['attributes'])))
                . '><input type="hidden" name="from" value="' . escape_html(get_current_url()) . '">' . $content . '</form>';
        };
    }

    /**
     * Los valores de brand de <pcs-image>: las imágenes de marca, en minúsculas con guiones.
     *
     * @return string[]
     */
    public static function brandNames(): array
    {
        return array_map(fn (string $key): string => mb_strtolower((string) preg_replace('/([a-z])([A-Z])/', '$1-$2', $key)), array_keys(SettingsController::BRAND_IMAGES_DARK_MODE));
    }
}
