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
            if (($rule['pattern'] ?? null) === 'name' && preg_match('/' . self::namePattern() . '/D', $value) !== 1) {
                $problems[] = "el atributo «{$name}» de <{$mark}> vale «{$value}»: solo minúsculas, números y guiones (por ejemplo «main-menu»)";
            }
            if (is_array($rule['values'] ?? null) && !in_array($value, $rule['values'], true)) {
                $problems[] = "el atributo «{$name}» de <{$mark}> vale «{$value}»: tiene que ser uno de " . implode(', ', $rule['values']);
            }
            if (is_array($rule['integer'] ?? null)) {
                $min = (int) ($rule['integer']['min'] ?? \PHP_INT_MIN);
                $max = (int) ($rule['integer']['max'] ?? \PHP_INT_MAX);
                if (preg_match('/^\d+$/D', $value) !== 1 || (int) $value < $min || (int) $value > $max) {
                    $problems[] = "el atributo «{$name}» de <{$mark}> vale «{$value}»: tiene que ser un número entero entre {$min} y {$max}";
                }
            }
        }
        foreach (array_keys($attributes) as $name) {
            if (!array_key_exists($name, $definition) && !self::isGlobalAttribute((string) $name)) {
                $problems[] = "<{$mark}> no admite el atributo «{$name}» (admite: " . implode(', ', array_merge(array_keys($definition), self::globalAttributes())) . ')';
            }
        }
        return $problems;
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
