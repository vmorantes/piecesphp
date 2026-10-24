<?php

/**
 * EncryptedDataRegistry.php
 */
namespace PiecesPHP\Core\Crypto;

use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\ConfigHelpers\MailjetConfig;
use PiecesPHP\SystemStatus\Mappers\MailLogMapper;
use PiecesPHP\UserSystem\ORM\OTPSecretsUsersMapper;

/**
 * EncryptedDataRegistry - Lo que la aplicación guarda cifrado (ADR 0061 §6), con la forma de SystemAlertRegistry.
 *
 * Un módulo declara lo suyo con register(). Lo del framework lo declara registerCore(), que all() llama siempre.
 * verify-integrity falla si algo cifra con un propósito que no está aquí.
 *
 * @package     PiecesPHP\Core\Crypto
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class EncryptedDataRegistry
{
    /**
     * @var array<string,EncryptedData>
     */
    private static $sites = [];

    /**
     * @var bool
     */
    private static $coreRegistered = false;

    /**
     * @param EncryptedData $site
     * @return void
     * @throws \InvalidArgumentException Con una clave repetida
     */
    public static function register(EncryptedData $site): void
    {
        if (array_key_exists($site->key(), self::$sites)) {
            throw new \InvalidArgumentException("El sitio cifrado «{$site->key()}» ya está registrado.");
        }
        self::$sites[$site->key()] = $site;
    }

    /**
     * @return array<string,EncryptedData>
     */
    public static function all(): array
    {
        self::registerCore();
        return self::$sites;
    }

    /**
     * Los propósitos declarados, sin repetir.
     *
     * @return string[]
     */
    public static function purposes(): array
    {
        $purposes = array_values(array_unique(array_map(fn(EncryptedData $site): string => $site->purpose(), self::all())));
        sort($purposes);
        return $purposes;
    }

    /**
     * Lo del framework: los datos del token (no se guardan), la configuración de correo y de Mailjet (opciones), el
     * secreto TOTP y el cuerpo del registro de correos (columnas).
     *
     * @return void
     */
    public static function registerCore(): void
    {
        if (self::$coreRegistered) {
            return;
        }
        self::$coreRegistered = true;

        self::register(new EncryptedData('token-data', Sealed::PURPOSE_TOKEN_DATA, EncryptedData::KIND_NOT_STORED));

        //Antes de la 9 se guardaban con la ofuscación y la clase como clave, que es pública: se leen una vez y se guardan bien.
        foreach ([['mail-config', Sealed::PURPOSE_MAIL_CONFIG, MailConfig::CONFIG_NAME, MailConfig::class], ['mailjet-config', Sealed::PURPOSE_MAILJET_CONFIG, MailjetConfig::CONFIG_NAME, MailjetConfig::class]] as [$key, $purpose, $option, $obfuscationKey]) {
            self::register(new EncryptedData($key, $purpose, EncryptedData::KIND_OPTION, $option, null, 'id', static function (string $stored) use ($obfuscationKey): ?string {
                $compressed = BaseHashEncryption::deobfuscate($stored, $obfuscationKey);
                //Lo que no descomprime no es la configuración: null, nunca basura.
                return @gzuncompress($compressed) !== false ? $compressed : null;
            }));
        }

        //Antes de la 9 el secreto TOTP estaba en claro (base32).
        self::register(new EncryptedData('totp-secret', Sealed::PURPOSE_TOTP_SECRET, EncryptedData::KIND_COLUMN, OTPSecretsUsersMapper::TABLE, 'secret', 'id', static function (string $stored): ?string {
            return preg_match('/^[A-Z2-7]+=*$/', $stored) === 1 ? $stored : null;
        }));

        //Antes de la 9 el cuerpo iba en AES-CBC con la clave derivada (ADR 0051): se prueba con cada clave del llavero.
        self::register(new EncryptedData('mail-log-body', Sealed::PURPOSE_MAIL_LOG_BODY, EncryptedData::KIND_COLUMN, MailLogMapper::TABLE, 'body', 'id', static function (string $stored, AppKeyring $keyring): ?string {
            foreach (array_merge([$keyring->current()], array_column($keyring->previous(), 'key')) as $rawKey) {
                $plain = BaseHashEncryption::decryptBidirectionalHash($stored, Config::app_key_derived(Sealed::PURPOSE_MAIL_LOG_BODY, $rawKey));
                if (is_string($plain)) {
                    return $plain;
                }
            }
            return null;
        }));
    }

    /**
     * Cambia el registro y devuelve el anterior, para devolverlo al terminar (con lo del framework ya dentro).
     *
     * @param array<string,EncryptedData>|null $restore
     * @return array<string,EncryptedData>
     */
    public static function swapForTesting(?array $restore = null): array
    {
        $previous = self::all();
        self::$sites = $restore ?? [];
        self::$coreRegistered = $restore !== null;
        return $previous;
    }
}
