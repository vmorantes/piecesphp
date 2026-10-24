<?php

/**
 * Sealed.php
 */
namespace PiecesPHP\Core\Crypto;

use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\Config;

/**
 * Sealed - Cifrar de verdad (ADR 0061 §1-§3): AES-256-GCM, autenticado, con una llave por propósito sacada de app_key.
 *
 * Formato: `v2:<huella>:<base64url(iv|tag|ct)>`. La huella dice con qué clave del llavero se cifró, así que lo cifrado
 * antes de rotar se sigue leyendo. open() nunca devuelve basura: o el texto original, o null.
 *
 * Un propósito nuevo se declara en EncryptedDataRegistry; verify-integrity falla si algo cifra con uno sin declarar.
 *
 * @package     PiecesPHP\Core\Crypto
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class Sealed
{
    const VERSION = 'v2';
    const CIPHER = 'aes-256-gcm';
    const IV_LENGTH = 12;
    const TAG_LENGTH = 16;

    const PURPOSE_TOKEN_DATA = 'token-data';
    const PURPOSE_MAIL_CONFIG = 'mail-config';
    const PURPOSE_MAILJET_CONFIG = 'mailjet-config';
    const PURPOSE_MAIL_LOG_BODY = 'mail-log-body';
    const PURPOSE_TOTP_SECRET = 'totp-secret';

    /**
     * @param string $plain
     * @param string $purpose Uno declarado en EncryptedDataRegistry
     * @param AppKeyring|null $keyring Por omisión, el de la aplicación; cifra siempre con su clave actual
     * @return string
     * @throws \RuntimeException Si openssl no cifra: nunca se devuelve algo que no se pueda abrir
     */
    public static function seal(string $plain, string $purpose, ?AppKeyring $keyring = null): string
    {
        $keyring ??= AppKeyring::get();
        $iv = random_bytes(self::IV_LENGTH);
        $tag = '';
        $ciphertext = openssl_encrypt($plain, self::CIPHER, self::purposeKey($keyring->current(), $purpose), \OPENSSL_RAW_DATA, $iv, $tag, '', self::TAG_LENGTH);
        if (!is_string($ciphertext) || strlen($tag) !== self::TAG_LENGTH) {
            throw new \RuntimeException('No se pudo cifrar con ' . self::CIPHER . '.');
        }
        return self::VERSION . ':' . $keyring->currentFingerprint() . ':' . BaseHashEncryption::urlSafeB64Encode($iv . $tag . $ciphertext);
    }

    /**
     * El texto original, o null si no tiene el formato, ninguna clave del llavero casa con su huella o la etiqueta no
     * verifica (manipulado, otro propósito u otra clave).
     *
     * @param string $sealed
     * @param string $purpose
     * @param AppKeyring|null $keyring
     * @return string|null
     */
    public static function open(string $sealed, string $purpose, ?AppKeyring $keyring = null): ?string
    {
        $keyring ??= AppKeyring::get();
        $parts = self::parse($sealed);
        if ($parts === null) {
            return null;
        }
        $key = $keyring->keyFor($parts['fingerprint']);
        if ($key === null) {
            return null;
        }
        $plain = openssl_decrypt($parts['ciphertext'], self::CIPHER, self::purposeKey($key, $purpose), \OPENSSL_RAW_DATA, $parts['iv'], $parts['tag']);
        return is_string($plain) ? $plain : null;
    }

    /**
     * Si tiene el formato de Sealed (no si se puede abrir).
     *
     * @param string $value
     * @return bool
     */
    public static function isSealed(string $value): bool
    {
        return self::parse($value) !== null;
    }

    /**
     * La huella de la clave con que se cifró; null si no tiene el formato.
     *
     * @param string $sealed
     * @return string|null
     */
    public static function fingerprintOf(string $sealed): ?string
    {
        $parts = self::parse($sealed);
        return $parts !== null ? $parts['fingerprint'] : null;
    }

    /**
     * La llave de un propósito: 32 bytes derivados de la clave cruda (Config::app_key_derived()).
     *
     * @param string $rawKey
     * @param string $purpose
     * @return string
     */
    private static function purposeKey(string $rawKey, string $purpose): string
    {
        return (string) hex2bin(Config::app_key_derived($purpose, $rawKey));
    }

    /**
     * @param string $value
     * @return array{fingerprint:string,iv:string,tag:string,ciphertext:string}|null
     */
    private static function parse(string $value): ?array
    {
        $parts = explode(':', $value, 3);
        if (count($parts) !== 3 || $parts[0] !== self::VERSION || preg_match('/^[0-9a-f]{' . AppKeyring::FINGERPRINT_LENGTH . '}$/', $parts[1]) !== 1) {
            return null;
        }
        if (preg_match('/^[A-Za-z0-9_-]+$/', $parts[2]) !== 1) {
            return null;
        }
        $raw = BaseHashEncryption::urlSafeB64Decode($parts[2]);
        if (strlen($raw) < self::IV_LENGTH + self::TAG_LENGTH) {
            return null;
        }
        return [
            'fingerprint' => $parts[1],
            'iv' => substr($raw, 0, self::IV_LENGTH),
            'tag' => substr($raw, self::IV_LENGTH, self::TAG_LENGTH),
            'ciphertext' => substr($raw, self::IV_LENGTH + self::TAG_LENGTH),
        ];
    }
}
