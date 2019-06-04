<?php

/**
 * Validator.php
 */
namespace PiecesPHP\Core\Validation;

use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Validator
 *
 * @category    Validation
 * @package     PiecesPHP\Core\Validation
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class Validator
{
    const T_INTEGER = 'T_INTEGER';
    const T_DOUBLE = 'T_DOUBLE';
    const T_STRING = 'T_STRING';
    const T_ARRAY = 'T_ARRAY';
    const T_DATE = 'T_DATE';
    const T_EMAIL = 'T_EMAIL';

    /**
     * validate
     *
     * @param string $type
     * @param mixed $value
     * @param bool $excel
     * @return bool
     */
    public static function validate(string $type, $value, string $format = 'Y-m-d', bool $excel = false)
    {
        switch ($type) {
            case self::T_INTEGER:
                return self::isInteger($value);
                break;
            case self::T_DOUBLE:
                return self::isDouble($value);
                break;
            case self::T_STRING:
                return self::isString($value);
                break;
            case self::T_ARRAY:
                return self::isArrray($value);
                break;
            case self::T_DATE:
                return self::isDate($value, $format, $excel);
                break;
            case self::T_EMAIL:
                return self::isEmail($value);
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * isInteger
     *
     * @param mixed $value
     * @return bool
     */
    public static function isInteger($value)
    {
        return is_scalar($value) && (ctype_digit((string) $value) || is_int($value));
    }

    /**
     * isDouble
     *
     * @param mixed $value
     * @return bool
     */
    public static function isDouble($value)
    {
        return is_scalar($value) && is_numeric($value);
    }

    /**
     * isString
     *
     * @param mixed $value
     * @return bool
     */
    public static function isString($value)
    {
        return is_string($value);
    }

    /**
     * isArrray
     *
     * @param mixed $value
     * @return bool
     */
    public static function isArrray($value)
    {
        return is_array($value);
    }

    /**
     * isEmail
     *
     * @param mixed $value
     * @return bool
     */
    public static function isEmail($value)
    {
        if (is_string($value)) {

            $domain_email = explode('@', $value);
            $domain_email = isset($domain_email[1]) ? $domain_email[1] : '';
            return strlen($domain_email)>0 && checkdnsrr($domain_email, 'MX');

        } else {

            return false;

        }

    }

    /**
     * isDate
     *
     * @param mixed $value
     * @param string $format
     * @param bool $excel
     * @return bool
     */
    public static function isDate($value, string $format = 'Y-m-d', bool $excel = false)
    {
        if (!($value instanceof \DateTime)) {

            if (is_string($value)) {

                $date = \DateTime::createFromFormat($format, $value);
                return $date !== false;

            } else if (is_int($value) || is_float($value)) {

                if ($excel) {
                    try {
                        $data = ExcelDate::excelToDateTimeObject($value);
                        return true;
                    } catch (\Exception $e) {
                        return false;
                    }
                } else {
                    return false;
                }

            } else {
                return false;
            }

        } else {
            return true;
        }

    }
}
