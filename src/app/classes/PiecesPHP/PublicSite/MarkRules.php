<?php

/**
 * MarkRules.php
 */

namespace PiecesPHP\PublicSite;

/**
 * MarkRules - Las reglas de las marcas, leídas de kit/pcs/rules.json: la misma fuente que el comprobador del navegador
 * (CHECK.html), la importación y el compilador. Una marca que no está aquí la conoce solo el registro del proyecto.
 *
 * @package     PiecesPHP\PublicSite
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class MarkRules
{

    const RULES_FILE = __DIR__ . '/kit/pcs/rules.json';

    const CONTENT_EMPTY = 'empty';
    const CONTENT_TEXT = 'text';
    const CONTENT_INLINE = 'inline';
    const CONTENT_TEMPLATE = 'template';
    const CONTENT_ANY = 'any';

    /** Qué escribir cuando un valor no casa con su «pattern»; el mismo sentido que PATTERN_FIX de check.js. */
    const PATTERN_HINTS = [
        'name' => 'solo minúsculas, números y guiones (por ejemplo «main-menu»)',
        'fragment' => 'el id de la sección, sin «#»: letras, números, «-», «_», «:» o «.», empezando por una letra (por ejemplo «equipo»)',
        'classList' => 'uno o más nombres de clase separados por espacios (por ejemplo «w--current»)',
        'sizes' => 'medidas como «(max-width: 600px) 100vw, 50vw», sin comillas ni < >',
    ];

    /**
     * @var array<string,mixed>|null
     */
    private static $rules = null;

    /**
     * @return array<string,mixed>
     * @throws \RuntimeException Si el archivo falta o no es el JSON esperado: sin reglas no se compila ni se importa nada
     */
    public static function rules(): array
    {
        if (self::$rules === null) {
            $raw = is_file(self::RULES_FILE) ? file_get_contents(self::RULES_FILE) : false;
            $rules = is_string($raw) ? json_decode($raw, true) : null;
            if (!is_array($rules) || !is_int($rules['formatVersion'] ?? null) || !is_array($rules['marks'] ?? null) || !is_string($rules['namePattern'] ?? null) || !is_string($rules['fieldPattern'] ?? null)) {
                throw new \RuntimeException('Las reglas de las marcas (' . self::RULES_FILE . ') faltan o no tienen la forma esperada.');
            }
            self::$rules = $rules;
        }
        return self::$rules;
    }

    /**
     * La huella del archivo de reglas: entra en la de cada vista compilada.
     *
     * @return string
     */
    public static function fingerprint(): string
    {
        return (string) sha1(json_encode(self::rules()) ?: '');
    }

    /**
     * Los servicios de fuera que un sitio puede nombrar con una dirección absoluta (rules.json, knownHosts): fuentes, vídeo
     * y mapas. La misma lista que lee CHECK.html.
     *
     * @return string[] Hosts en minúsculas
     */
    public static function knownHosts(): array
    {
        $hosts = [];
        foreach ((array) (self::rules()['knownHosts'] ?? []) as $group) {
            foreach (is_array($group) ? $group : [] as $host) {
                $hosts[] = strtolower((string) $host);
            }
        }
        return $hosts;
    }

    /**
     * @return int
     */
    public static function formatVersion(): int
    {
        return (int) self::rules()['formatVersion'];
    }

    /**
     * La expresión, sin delimitadores, de un nombre de sitio, página, parte o recurso.
     *
     * @return string
     */
    public static function namePattern(): string
    {
        return (string) self::rules()['namePattern'];
    }

    /**
     * La expresión, sin delimitadores, de un {{campo}}.
     *
     * @return string
     */
    public static function fieldPattern(): string
    {
        return (string) self::rules()['fieldPattern'];
    }

    /**
     * @return string[]
     */
    public static function markNames(): array
    {
        return array_values(array_filter(array_keys(self::rules()['marks']), 'is_string'));
    }

    /**
     * @param string $mark
     * @return bool
     */
    public static function isKnown(string $mark): bool
    {
        return array_key_exists($mark, self::rules()['marks']);
    }

    /**
     * El tipo de contenido de una marca de las reglas; las del proyecto, `any`.
     *
     * @param string $mark
     * @return string
     */
    public static function content(string $mark): string
    {
        $content = self::rules()['marks'][$mark]['content'] ?? self::CONTENT_ANY;
        return is_string($content) ? $content : self::CONTENT_ANY;
    }

    /**
     * Lo que falla en los atributos de una marca de las reglas, en español; vacío si está bien o si no es de las reglas.
     *
     * @param string $mark
     * @param array<string,string> $attributes
     * @return string[]
     */
    public static function attributeProblems(string $mark, array $attributes): array
    {
        $definition = self::rules()['marks'][$mark]['attributes'] ?? null;
        if (!is_array($definition)) {
            return [];
        }
        $problems = [];
        foreach ($definition as $name => $rule) {
            $rule = is_array($rule) ? $rule : [];
            $value = $attributes[$name] ?? null;
            if ($value === null || trim($value) === '') {
                if (($rule['required'] ?? false) === true) {
                    $problems[] = "a <{$mark}> le falta el atributo «{$name}»";
                }
                continue;
            }
            $problems = array_merge($problems, self::valueProblems($mark, (string) $name, $rule, $value));
        }
        foreach (array_keys($attributes) as $name) {
            if (!array_key_exists($name, $definition) && !self::isGlobalAttribute((string) $name)) {
                $problems[] = '<' . self::printable($mark) . '> no admite el atributo «' . self::printable((string) $name) . '» (admite: ' . implode(', ', array_merge(array_keys($definition), self::globalAttributes())) . ')';
            }
        }
        return $problems;
    }

    /**
     * Si un valor casa con un «pattern» de las reglas: la clave «<pattern>Pattern» de rules.json o, si no existe, el
     * propio texto como expresión (como check.js y pcs.js).
     *
     * @param string $pattern
     * @param string $value
     * @return bool
     */
    public static function matchesPattern(string $pattern, string $value): bool
    {
        $source = self::rules()[$pattern . 'Pattern'] ?? $pattern;
        return is_string($source) && preg_match('~' . str_replace('~', '\\~', $source) . '~D', $value) === 1;
    }

    /**
     * Si un atributo opcional de una marca trae un valor que sus reglas aceptan; vacío o ausente, false. Lo usan los
     * pintores: la vista compilada no se vuelve a validar.
     *
     * @param string $mark
     * @param string $name
     * @param string|null $value
     * @return bool
     */
    public static function acceptsValue(string $mark, string $name, ?string $value): bool
    {
        $rule = self::rules()['marks'][$mark]['attributes'][$name] ?? null;
        return is_array($rule) && $value !== null && trim($value) !== '' && self::valueProblems($mark, $name, $rule, $value) === [];
    }

    /**
     * Lo que falla en un valor no vacío según su regla: «pattern», «values» e «integer».
     *
     * @param string $mark
     * @param string $name
     * @param array<mixed> $rule
     * @param string $value
     * @return string[]
     */
    private static function valueProblems(string $mark, string $name, array $rule, string $value): array
    {
        $problems = [];
        $raw = $value;
        $value = self::printable($value);
        if (is_string($rule['pattern'] ?? null) && !self::matchesPattern($rule['pattern'], $raw)) {
            $problems[] = "el atributo «{$name}» de <{$mark}> vale «{$value}»: " . (self::PATTERN_HINTS[$rule['pattern']] ?? 'no tiene la forma que piden las reglas');
        }
        if (is_array($rule['values'] ?? null) && !in_array($raw, $rule['values'], true)) {
            $problems[] = "el atributo «{$name}» de <{$mark}> vale «{$value}»: tiene que ser uno de " . implode(', ', $rule['values']);
        }
        if (is_array($rule['integer'] ?? null)) {
            $min = (int) ($rule['integer']['min'] ?? \PHP_INT_MIN);
            $max = (int) ($rule['integer']['max'] ?? \PHP_INT_MAX);
            if (preg_match('/^\d+$/D', $raw) !== 1 || (int) $raw < $min || (int) $raw > $max) {
                $problems[] = "el atributo «{$name}» de <{$mark}> vale «{$value}»: tiene que ser un número entero entre {$min} y {$max}";
            }
        }
        return $problems;
    }

    /**
     * Un texto del diseñador listo para un mensaje: sin C0, DEL ni C1 (U+0080-U+009F), que en una terminal son órdenes.
     *
     * @param string $text
     * @return string
     */
    public static function printable(string $text): string
    {
        return (string) preg_replace('/[\x00-\x1F\x7F]|\xC2[\x80-\x9F]/', '', $text);
    }

    /**
     * Cuántos elementos pinta un pcs-list sin count: «default» de su atributo count, el mismo que usa la vista previa.
     *
     * @return int
     * @throws \RuntimeException Si falta o no es un entero dentro de sus límites
     */
    public static function defaultListCount(): int
    {
        $rule = self::rules()['marks']['pcs-list']['attributes']['count'] ?? null;
        $default = is_array($rule) ? ($rule['default'] ?? null) : null;
        $limits = is_array($rule) && is_array($rule['integer'] ?? null) ? $rule['integer'] : [];
        if (!is_int($default) || $default < (int) ($limits['min'] ?? 1) || $default > (int) ($limits['max'] ?? \PHP_INT_MAX)) {
            throw new \RuntimeException('Las reglas de las marcas (' . self::RULES_FILE . ') no dan un «default» entero válido al count de pcs-list.');
        }
        return $default;
    }

    /**
     * Los atributos que vale cualquier marca de las reglas (`globalAttributes`; «data-*» vale por prefijo).
     *
     * @return string[]
     */
    public static function globalAttributes(): array
    {
        $global = self::rules()['globalAttributes'] ?? [];
        return is_array($global) ? array_values(array_filter($global, 'is_string')) : [];
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isGlobalAttribute(string $name): bool
    {
        foreach (self::globalAttributes() as $allowed) {
            if (str_ends_with($allowed, '*') ? str_starts_with($name, substr($allowed, 0, -1)) : $name === $allowed) {
                return true;
            }
        }
        return false;
    }

    /**
     * Solo para las pruebas: vuelve a leer el archivo.
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$rules = null;
    }
}
