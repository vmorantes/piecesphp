<?php
/**
 * StringManipulate.php
 */

namespace PiecesPHP\Core;

/**
 * StringManipulate - Operaciones sobre strings
 *
 * Operaciones i/o con strings
 *
 * @category Helper
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class StringManipulate
{
    /**
     * Genera una contraseña
     *
     * @param int $length Longitud de la contraseña
     * @return array ['password' => string, 'encrypt' => string]
     */
    public static function generatePass(int $length = 5)
    {
        $new_pass = "";
        $chars = "-_$*.ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890";
        $len_chars = strlen($chars);
        $len_pass = $length;

        for ($i = 1; $i <= $len_pass; $i++) {
            $random_pos = rand(0, $len_chars - 1);
            $random_char = mb_substr($chars, $random_pos, 1);
            $new_pass .= $random_char;
        }
        $new_pass_encrypt = password_hash($new_pass, PASSWORD_DEFAULT);
        return [
            'password' => $new_pass,
            'encrypt' => $new_pass_encrypt,
        ];
    }
    /**
     * Base64 seguro para url
     *
     * @param string $input El string para codificar
     * @return string El base64
     */
    public static function urlSafeB64Encode(string $input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    /**
     * String obtenido de un base64 seguro para url
     *
     * @param string $input El base64 para decodificar
     * @return string El string
     */
    public static function urlSafeB64Decode(string $input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * Codifica un objeto PHP en un string JSON.
     *
     * @link http://php.net/manual/en/function.json-last-error.php json-last-error()
     * @param object|array $input Objeto o array para codificar
     * @return string|int
     *  Representación en string JSON de un objeto|array PHP o json-last-error().
     */
    public static function jsonEncode($input)
    {
        $json = json_encode($input);
        if (function_exists('json_last_error') && $errno = json_last_error()) {
            return $errno;
        }
        return $json;
    }
    /**
     * Convierte un string JSON y en un objeto PHP.
     *
     * @link http://php.net/manual/en/function.json-last-error.php json-last-error()
     * @param string $input JSON
     * @return object|int
     *  Representación de un JSON como objeto PHP o json-last-error().
     */
    public static function jsonDecode(string $input)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '>=') && !(defined('JSON_C_VERSION') && PHP_INT_SIZE > 4)) {
            /** In PHP >=5.4.0, json_decode() accepts an options parameter, that allows you
             * to specify that large ints (like Steam Transaction IDs) should be treated as
             * strings, rather than the PHP default behaviour of converting them to floats.
             */
            $obj = json_decode($input, false, 512, JSON_BIGINT_AS_STRING);
        } else {
            /** Not all servers will support that, however, so for older versions we must
             * manually detect large ints in the JSON string and quote them (thus converting
             *them to strings) before decoding, hence the preg_replace() call.
             */
            $max_int_length = strlen((string) PHP_INT_MAX) - 1;
            $json_without_bigints = preg_replace('/:\s*(-?\d{' . $max_int_length . ',})/', ': "$1"', $input);
            $obj = json_decode($json_without_bigints);
        }
        if (function_exists('json_last_error') && $errno = json_last_error()) {
            return $errno;
        }

        return $obj;
    }
}
