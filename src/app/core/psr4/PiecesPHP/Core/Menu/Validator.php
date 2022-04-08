<?php

/**
 * Validator.php
 */
namespace PiecesPHP\Core\Menu;

use PiecesPHP\Core\Validation\Validator as ValidatorMain;

/**
 * Validator
 *
 * @package     PiecesPHP\Core\Menu
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2022
 */
class Validator
{
    const RULE_IS_STRING = 'is_string';
    const RULE_IS_BOOL = 'bool';
    const RULE_IS_ARRAY = 'is_array';
    const RULE_IS_INTEGER = 'integer';
    const RULES = [
        self::RULE_IS_STRING,
        self::RULE_IS_BOOL,
        self::RULE_IS_ARRAY,
        self::RULE_IS_INTEGER,
    ];

    /**
     * @param string $rule
     * @param mixed $value
     * @return bool
     */
    public static function validate(string $rule, $value)
    {
        $validators = [
            self::RULE_IS_STRING => function ($value) {
                return is_scalar($value);
            },
            self::RULE_IS_BOOL => function ($value) {
                return is_bool($value);
            },
            self::RULE_IS_ARRAY => function ($value) {
                return ValidatorMain::isArray($value);
            },
            self::RULE_IS_INTEGER => function ($value) {
                return ValidatorMain::isInteger($value);
            },
        ];

        $isValid = false;
        if (is_scalar($rule) && in_array($rule, self::RULES) && array_key_exists($rule, $validators)) {
            $isValid = ($validators[$rule])($value);
        }
        return $isValid;
    }

    /**
     * @param string $rule Disponibles RULE_IS_STRING|RULE_IS_INTEGER
     * @param mixed $value
     * @return mixed
     */
    public static function parse(string $rule, $value)
    {
        $parsers = [
            self::RULE_IS_STRING => function ($value) {
                return is_scalar($value) ? (string) $value : $value;
            },
            self::RULE_IS_INTEGER => function ($value) {
                return ValidatorMain::isInteger($value) ? (int) $value : $value;
            },
        ];

        $parsed = $value;
        if (is_scalar($rule) && in_array($rule, self::RULES) && array_key_exists($rule, $parsers)) {
            $parsed = ($parsers[$rule])($value);
        }
        return $parsed;
    }

}
