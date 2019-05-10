<?php
/**
 * BaseHashEncryption.php
 */
namespace PiecesPHP\Core;

/**
 * BaseHashEncryption - Hashing y encriptado
 *
 * Encriptación, codificación y hashing de strings
 *
 * @category Criptografía
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class BaseHashEncryption
{
    /**
     * Encripta un string y lo devuelve en base64 seguro para url
     *
     * @param string $string El string
     * @param string $key La llave
     * @return string El string base64 seguro para url del string encriptado
     * @use self::getSecretKey
     * @use self::urlSafeB64Encode
     */
    public static function encrypt(string $string, string $key = null)
    {
        $key = is_null($key) ? self::getSecretKey() : $key;
        $result = '';
        for ($i = 0; $i < strlen($string); $i++) {
            $char = substr($string, $i, 1);
            $keychar = substr($key, ($i % strlen($key)) - 1, 1);
            $char = chr(ord($char) + ord($keychar));
            $result .= $char;
        }
        return self::urlSafeB64Encode($result);
    }

    /**
     * Desencripta un string
     *
     * @param string    $encrypt_string     El base64 seguro para url del string encriptado
     * @param string    $key    La llave
     * @return string   El string desencriptado
     * @use self::getSecretKey
     * @use self::urlSafeB64Decode
     */
    public static function decrypt(string $encrypt_string, string $key = null)
    {
        $key = is_null($key) ? self::getSecretKey() : $key;
        $result = '';
        $string = self::urlSafeB64Decode($encrypt_string);
        for ($i = 0; $i < strlen($string); $i++) {
            $char = substr($string, $i, 1);
            $keychar = substr($key, ($i % strlen($key)) - 1, 1);
            $char = chr(ord($char) - ord($keychar));
            $result .= $char;
        }
        return $result;
    }

    /**
     * Hace un hash de un string
     *
     * @param string            $msg    El mensaje
     * @param string|resource   $key    La llave. Para HS*, un string; para RS*, debe ser un resource de una llave pública openssl
     * @param string            $alg    El algoritmo de hashing
     *                                  Los algoritmos soportados son: 'HS256', 'HS384', 'HS512' and 'RS256'
     *
     * @return string|int Un string encriptado o un int que representa el error
     */
    public static function hash($msg, $key, $alg = 'HS256')
    {
        if (empty(static::$supported_algs_hash[$alg])) {
            return self::NOT_SUPPORTED_ALGORITHM;
        }
        list($function, $algorithm) = static::$supported_algs_hash[$alg];
        switch ($function) {
            case 'hash_hmac':
                return hash_hmac($algorithm, $msg, $key, true);
            case 'openssl':
                $signature = '';
                $success = openssl_sign($msg, $signature, $key, $algorithm);
                if (!$success) {
                    return self::OPEN_SSL_UNABLE_SIGN;
                } else {
                    return $signature;
                }
        }
    }

    /**
     * Verifica un hash a partir del mensaje, la llave y el método de hashing
     *
     * @param string            $msg        El menssaje
     * @param string            $signature  El hashing
     * @param string|resource   $key        La llave. Para HS*, un string; para RS*, debe ser un resource de una llave pública openssl
     * @param string            $alg        El algoritmo de hashing
     *
     * @return bool|int True, si el hash coincide con el string; false, si no; o un int que representa el error
     * @throws \DomainException si open_ssl no soporta el algoritmo o si ocurre algún otro error
     * @use self::safeStrlen
     */
    public static function hashVerify(string $msg, string $signature, $key, string $alg = 'HS256')
    {
        if (empty(self::$supported_algs_hash[$alg])) {
            return self::NOT_SUPPORTED_ALGORITHM;
        }
        list($function, $algorithm) = self::$supported_algs_hash[$alg];
        switch ($function) {
            case 'openssl':
                $success = openssl_verify($msg, $signature, $key, $algorithm);
                if ($success === 1) {
                    return true;
                } elseif ($success === 0) {
                    return false;
                }
                // returns 1 en exito, 0 en fallo, -1 en error.
                throw new DomainException(
                    'OpenSSL error: ' . openssl_error_string()
                );
            case 'hash_hmac':
            default:
                $hash = hash_hmac($algorithm, $msg, $key, true);
                if (function_exists('hash_equals')) {
                    return hash_equals($signature, $hash);
                }
                $len = min(self::safeStrlen($signature), self::safeStrlen($hash));
                $status = 0;
                for ($i = 0; $i < $len; $i++) {
                    $status |= (ord($signature[$i]) ^ ord($hash[$i]));
                }
                $status |= (self::safeStrlen($signature) ^ self::safeStrlen($hash));
                return ($status === 0);
        }
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
     * Establece la llave usada para la codificación
     * @param string $key Llave
     * @return void
     */
    public static function setSecretKey(string $key)
    {
        self::$secret_key = $key;
    }

    /**
     * Obtiene la llave usada para la codificación
     * @return string
     * La llave
     */
    public static function getSecretKey()
    {
        return self::$secret_key;
    }

    /**
     * Obtiene el número de bytes en un string criptográfico
     *
     * @param string
     *
     * @return int
     */
    private static function safeStrlen(string $str)
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($str, '8bit');
        }
        return strlen($str);
    }

    /** @ignore */
    private static $secret_key = 'secret';

    /** @ignore */
    public static $supported_algs_hash = array(
        'HS256' => array('hash_hmac', 'SHA256'),
        'HS512' => array('hash_hmac', 'SHA512'),
        'HS384' => array('hash_hmac', 'SHA384'),
        'RS256' => array('openssl', 'SHA256'),
        'RS384' => array('openssl', 'SHA384'),
        'RS512' => array('openssl', 'SHA512'),
    );
    const HS256 = 'HS256';
    const HS512 = 'HS512';
    const HS384 = 'HS384';
    const RS256 = 'RS256';
    const RS384 = 'RS384';
    const RS512 = 'RS512';
    const NOT_SUPPORTED_ALGORITHM = 0;
    const OPEN_SSL_UNABLE_SIGN = 1;
}
