<?php

/**
 * BaseToken.php
 */
namespace PiecesPHP\Core;

/**
 * BaseToken - Implementación básica para generar tokens.
 *
 * @category Autenticación
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class BaseToken
{

    /**
     * Genera un token.
     * @param mixed $data Información contenida en el token
     * @param string $key La llave
     * @param int $time Fecha de creación en segundos
     * @param int $expire Fecha de expiración en segundos
     * @param bool $aud
     * @return string
     * Devuele un string codificado.
     */
    public static function setToken($data, string $key = null, int $time = null, int $expire = null, bool $aud = false)
    {
        $key = is_null($key) ? self::getSecretKey() : $key;

        if ($time == null) {
            $time = time();
        }

        if ($expire == null) {
            $expire = $time + self::$expire_time;
        }

        $data = BaseHashEncryption::encrypt(json_encode($data), $key);

        if ($aud) {
            $token = array(
                'iat' => $time,
                'exp' => $expire,
                'aud' => self::aud(),
                'data' => $data,
            );
        } else {
            $token = array(
                'iat' => $time,
                'exp' => $expire,
                'data' => $data,
            );
        }

        return self::encode($token, $key);
    }

    /**
     * Verifica un token.
     * Verifica que el token no esté vacío.
     * Verifica que el token no haya expirado.
     * Verifica que el aud del token sea el mismo que desde donde se accede
     * @param string $token Token
     * @param string $key La llave
     * @param array  $allowed_algs Lista de los algoritmos de hashing soportados.
     *                             Los algoritmos son 'HS256', 'HS384', 'HS512' y 'RS256'
     * @return boolean|string
     * Si está bien devuelve true
     * Si está vacío devuelve BaseToken::INVALID_TOKEN_SUPPLIED.
     * Si expiró devuelve BaseToken::EXPIRED_TOKEN.
     * Si el usuario es diferente devuelve BaseToken::INVALID_USER_LOGGIN.
     * @use self::decode
     * @use self::isExpire
     */
    public static function check(string $token, string $key = null, array $allowed_algs = null)
    {
        $key = is_null($key) ? self::getSecretKey() : $key;

        if (empty($token)) {
            return self::INVALID_TOKEN_SUPPLIED;
        }

        $allowed_algs = is_null($allowed_algs) ? self::$encrypt : $allowed_algs;

        $expired = self::isExpire($token, $key, $allowed_algs);
        if ($expired === true) {
            return self::EXPIRED_TOKEN;
        } else if ($expired !== false) {
            return $expired;
        }

        $decode = self::decode($token, $key, $allowed_algs);

        if (isset($decode->aud) && $decode->aud !== self::aud()) {
            return self::INVALID_USER_LOGGIN;
        }
        return true;
    }

    /**
     * Verifica si el token expiró.
     * @param string $token El JWT
     * @param string $key La llave
     * @param array  $allowed_algs Lista de los algoritmos de hashing soportados.
     *                             Los algoritmos son 'HS256', 'HS384', 'HS512' y 'RS256'
     * @return boolean|string true en caso de haber expirado; de lo contrario, false. Si ocurre otro error, devuelve el código del error.
     * @use self::decode
     */
    public static function isExpire(string $token, string $key = null, array $allowed_algs = null)
    {
        $key = is_null($key) ? self::getSecretKey() : $key;

        $allowed_algs = is_null($allowed_algs) ? self::$encrypt : $allowed_algs;

        $now = time();

        $exp = self::decode(
            $token,
            $key,
            $allowed_algs
        );

        if ($exp === self::EXPIRED_TOKEN) {
            return true;
        } else {
            $exp = self::decode(
                $token,
                $key,
                $allowed_algs
            );
            if (isset($exp->exp)) {
                if ($now >= $exp->exp) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return $exp;
            }
        }
    }

    /**
     * Obtiene la información guardada en $data con BaseToken::setToken
     *
     * @param string $token El JWT
     * @param string $key La llave
     * @param array  $allowed_algs Lista de los algoritmos de hashing soportados.
     *                             Los algoritmos son 'HS256', 'HS384', 'HS512' y 'RS256'
     * @param string $ignore_expired Si es true devuelve la información aunque haya expirado el token
     * @return mixed Devuelve la información que haya sido almacenada en el token, EXPIRED_TOKEN o el código del error si sucede alguno
     * @use self::decode
     */
    public static function getData(string $token, string $key = null, array $allowed_algs = null, bool $ignore_expired = false)
    {
        $key = is_null($key) ? self::getSecretKey() : $key;

        $allowed_algs = is_null($allowed_algs) ? self::$encrypt : $allowed_algs;

        $expired = self::isExpire($token, $key, $allowed_algs);

        $data = null;

        if ($expired === false) {

            $data = self::decode(
                $token,
                $key,
                self::$encrypt,
                $ignore_expired
            )->data;

            $data = BaseHashEncryption::decrypt($data, $key);
            $data = json_decode($data);

        } else {
            if ($expired === true) {
                if (!$ignore_expired) {
                    return self::EXPIRED_TOKEN;
                }
            } else {
                return $expired;
            }
        }

        return $data;
    }

    /**
     * Obtiene exp del JWT
     *
     * @param string $token El JWT
     * @param string $key La llave
     * @param array  $allowed_algs Lista de los algoritmos de hashing soportados.
     *                             Los algoritmos son 'HS256', 'HS384', 'HS512' y 'RS256'
     * @param string $ignore_expired Si es true devuelve la información aunque haya expirado el token
     * @return mixed Devuelve la fecha de expiración del token, EXPIRED_TOKEN o el código del error si sucede alguno
     * @use self::decode
     */
    public static function getExpired(string $token, string $key = null, array $allowed_algs = null, bool $ignore_expired = false)
    {
        $key = is_null($key) ? self::getSecretKey() : $key;

        $allowed_algs = is_null($allowed_algs) ? self::$encrypt : $allowed_algs;

        $expired = self::isExpire($token, $key, $allowed_algs);

        $data = null;

        if ($expired === false) {

            $data = self::decode(
                $token,
                $key,
                self::$encrypt,
                $ignore_expired
            )->exp;

        } else {
            if ($expired === true) {
                if (!$ignore_expired) {
                    return self::EXPIRED_TOKEN;
                }
            } else {
                return $expired;
            }
        }

        return $data;
    }

    /**
     * Obtiene iat del JWT
     *
     * @param string $token El JWT
     * @param string $key La llave
     * @param array  $allowed_algs Lista de los algoritmos de hashing soportados.
     *                             Los algoritmos son 'HS256', 'HS384', 'HS512' y 'RS256'
     * @param string $ignore_expired Si es true devuelve la información aunque haya expirado el token
     * @return mixed Devuelve la fecha de creación del token, EXPIRED_TOKEN o el código del error si sucede alguno
     * @use self::decode
     */
    public static function getCreated(string $token, string $key = null, array $allowed_algs = null, bool $ignore_expired = false)
    {
        $key = is_null($key) ? self::getSecretKey() : $key;

        $allowed_algs = is_null($allowed_algs) ? self::$encrypt : $allowed_algs;

        $expired = self::isExpire($token, $key, $allowed_algs);

        $data = null;

        if ($expired === false) {

            $data = self::decode(
                $token,
                $key,
                self::$encrypt,
                $ignore_expired
            )->iat;

        } else {
            if ($expired === true) {
                if (!$ignore_expired) {
                    return self::EXPIRED_TOKEN;
                }
            } else {
                return $expired;
            }
        }

        return $data;
    }

    /**
     * Establece un valor para aud.
     * @return string
     * Devuelve un string con sha1()
     */
    private static function aud()
    {
        $aud = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $aud = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $aud = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $aud = $_SERVER['REMOTE_ADDR'];
        }

        $aud .= @$_SERVER['HTTP_USER_AGENT'];
        $aud .= gethostname();

        return sha1($aud);
    }

    /**
     * Convierte en un string JWT y aplica hash a un objeto o array PHP.
     *
     * @param object|array  $payload    Objeto o array
     * @param string        $key        La llave
     *                                  Si el algoritmo es asimétrico, debe sel la llave privada.
     * @param string        $alg        Algoritmo de hashing.
     *                                  Los soportados son 'HS256', 'HS384', 'HS512' y 'RS256'
     * @param mixed         $keyId
     * @param array         $head       Un array con los elementos del header
     *
     * @return string A signed JWT
     *
     * @use self::jsonEncode
     * @use self::urlsafeB64Encode
     */
    public static function encode($payload, string $key = null, string $alg = 'HS256', $keyId = null, array $head = null)
    {
        $key = is_null($key) ? self::getSecretKey() : $key;

        $header = array('typ' => 'JWT', 'alg' => $alg);
        if ($keyId !== null) {
            $header['kid'] = $keyId;
        }
        if (isset($head) && is_array($head)) {
            $header = array_merge($head, $header);
        }
        $segments = array();
        $segments[] = self::urlsafeB64Encode(self::jsonEncode($header));
        $segments[] = self::urlsafeB64Encode(self::jsonEncode($payload));
        $signing_input = implode('.', $segments);
        $signature = self::sign($signing_input, $key, $alg);
        $segments[] = self::urlsafeB64Encode($signature);
        return implode('.', $segments);
    }

    /**
     * Decodifica un string JWT en un objeto PHP.
     *
     * @param string        $jwt            El JWT
     * @param string|array  $key            La llave o mapa de llaves.
     *                                      Si el algoritmo de hashing es simétrico la llave debe ser la llave pública.
     * @param array         $allowed_algs   Lista de los algoritmos de hashing soportados.
     *                                      Los algoritmos son 'HS256', 'HS384', 'HS512' y 'RS256'
     * @param string        $ignore_expired Si es true decodifica el token aunque haya expirado
     *
     * @return object|int El payload del JWT como un objeto PHP o un int que representa un error.
     *
     * @use self::jsonDecode
     * @use self::urlsafeB64Decode
     */
    public static function decode(string $jwt, $key = null, array $allowed_algs = array(), bool $ignore_expired = false)
    {
        $key = is_null($key) ? self::getSecretKey() : $key;
        $timestamp = is_null(self::$timestamp) ? time() : self::$timestamp;
        if (empty($key)) {
            return self::EMPTY_KEY;
        }
        $tks = explode('.', $jwt);
        if (count($tks) != 3) {
            return self::WRONG_NUMBER_SEGMENTS;
        }
        list($headb64, $bodyb64, $cryptob64) = $tks;
        if (null === ($header = self::jsonDecode(self::urlsafeB64Decode($headb64)))) {
            return self::INVALID_ENCODING_HEADER;
        }
        if (null === $payload = self::jsonDecode(self::urlsafeB64Decode($bodyb64))) {
            return self::INVALID_ENCODING_CLAIMS;
        }
        if (false === ($sig = self::urlsafeB64Decode($cryptob64))) {
            return self::INVALID_ENCODING_SIGNATURE;
        }
        if (empty($header->alg)) {
            return self::EMPTY_ALGORITHM;
        }
        if (empty(self::$supported_algs[$header->alg])) {
            return self::UNSUPPORTED_ALGORITHM;
        }
        if (!in_array($header->alg, $allowed_algs)) {
            return self::NOT_ALLOWED_ALGORITHM;
        }
        if (is_array($key) || $key instanceof \ArrayAccess) {
            if (isset($header->kid)) {
                if (!isset($key[$header->kid])) {
                    return self::INVALID_KEY_ID;
                }
                $key = $key[$header->kid];
            } else {
                return self::EMPTY_KEY_ID;
            }
        }
        // Verificar hash
        if (!self::verify("$headb64.$bodyb64", $sig, $key, $header->alg)) {
            return self::SIGNATURE_VERIFICATION_FAILED;
        }
        // Si nbf está definido, verifica si ya se puede usar el token
        if (isset($payload->nbf) && $payload->nbf > ($timestamp + self::$leeway)) {
            return self::NOT_USED_YET;
        }
        // Si nbf no existe esta comprobación es útil para evitar que se use antes de tiempo, según su fecha de creación
        if (isset($payload->iat) && $payload->iat > ($timestamp + self::$leeway)) {
            return self::NOT_USED_YET;
        }

        if (!$ignore_expired) {
            // Verifica si el token expiró
            if (isset($payload->exp) && ($timestamp - self::$leeway) >= $payload->exp) {
                return self::EXPIRED_TOKEN;
            }
        }

        return $payload;
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
    public static function sign($msg, $key = null, $alg = 'HS256')
    {
        $key = is_null($key) ? self::getSecretKey() : $key;

        if (empty(static::$supported_algs[$alg])) {
            return self::NOT_SUPPORTED_ALGORITHM;
        }
        list($function, $algorithm) = static::$supported_algs[$alg];
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
     */
    public static function verify(string $msg, string $signature, $key = null, string $alg = 'HS256')
    {
        $key = is_null($key) ? self::getSecretKey() : $key;
        if (empty(self::$supported_algs[$alg])) {
            return self::NOT_SUPPORTED_ALGORITHM;
        }
        list($function, $algorithm) = self::$supported_algs[$alg];
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

    /** @var int $leeway Margen de tiempo extra al verificar nbf, iat o exp */
    public static $leeway = 0;

    /** @var int|null $timestamp Valor por defecto de creación time() si es null */
    public static $timestamp = null;

    /** @var array $supported_algs Array asociativo de lo métodos de encriptación soportados */
    public static $supported_algs = array(
        'HS256' => array('hash_hmac', 'SHA256'),
        'HS512' => array('hash_hmac', 'SHA512'),
        'HS384' => array('hash_hmac', 'SHA384'),
        'RS256' => array('openssl', 'SHA256'),
        'RS384' => array('openssl', 'SHA384'),
        'RS512' => array('openssl', 'SHA512'),
    );

    /** @var array $encrypt Tipo de encriptación por defecto. */
    public static $encrypt = ['HS256'];

    /** @var string $secret_key Llave usada para la encriptación. */
    private static $secret_key = 'secret';

    /** @var string|null $aud AUD del token. */
    private static $aud = null;

    /** @var int $expire_time Tiempo de expiración por defecto. */
    private static $expire_time = 3600;

    const INVALID_USER_LOGGIN = 'INVALID_USER_LOGGIN';
    const EXPIRED_TOKEN = 'EXPIRED_TOKEN';
    const INVALID_TOKEN_SUPPLIED = 'INVALID_TOKEN_SUPPLIED';
    const NOT_SUPPORTED_ALGORITHM = 'NOT_SUPPORTED_ALGORITHM';
    const OPEN_SSL_UNABLE_SIGN = 'OPEN_SSL_UNABLE_SIGN';
    const EMPTY_KEY = 'EMPTY_KEY';
    const WRONG_NUMBER_SEGMENTS = 'WRONG_NUMBER_SEGMENTS';
    const INVALID_ENCODING_HEADER = 'INVALID_ENCODING_HEADER';
    const INVALID_ENCODING_CLAIMS = 'INVALID_ENCODING_CLAIMS';
    const INVALID_ENCODING_SIGNATURE = 'INVALID_ENCODING_SIGNATURE';
    const EMPTY_ALGORITHM = 'EMPTY_ALGORITHM';
    const UNSUPPORTED_ALGORITHM = 'UNSUPPORTED_ALGORITHM';
    const NOT_ALLOWED_ALGORITHM = 'NOT_ALLOWED_ALGORITHM';
    const INVALID_KEY_ID = 'INVALID_KEY_ID';
    const EMPTY_KEY_ID = 'EMPTY_KEY_ID';
    const SIGNATURE_VERIFICATION_FAILED = 'SIGNATURE_VERIFICATION_FAILED';
    const NOT_USED_YET = 'NOT_USED_YET';
}
