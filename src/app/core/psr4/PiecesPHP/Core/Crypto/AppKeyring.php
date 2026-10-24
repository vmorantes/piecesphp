<?php

/**
 * AppKeyring.php
 */
namespace PiecesPHP\Core\Crypto;

/**
 * AppKeyring - La clave de la aplicación y las anteriores (ADR 0061 §4-§5).
 *
 * Vive en `secure-keys/app-key.json`, fuera de git. Al rotar, la clave actual pasa a la lista de anteriores y sigue
 * sirviendo para LEER lo que cifró; cada valor cifrado dice con cuál por su huella.
 *
 * La instancia global la fija `Config` al arrancar; las pruebas la cambian con swapForTesting() y nunca tocan el
 * archivo real.
 *
 * @package     PiecesPHP\Core\Crypto
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class AppKeyring
{
    const FILE_NAME = 'app-key.json';

    const SOURCE_KEYRING = 'keyring';
    const SOURCE_CONFIG = 'config';
    const SOURCE_DEFAULT = 'default';

    /**
     * Caracteres de la huella: los primeros del sha256 de la clave CRUDA. No la revelan.
     */
    const FINGERPRINT_LENGTH = 8;

    /**
     * @var string
     */
    private $current;

    /**
     * @var array<int,array{key:string,replacedAt:string}> La más reciente primero
     */
    private $previous;

    /**
     * @var string SOURCE_*
     */
    private $source;

    /**
     * @var AppKeyring|null
     */
    private static $instance = null;

    /**
     * @param string $current
     * @param array<int,array{key:string,replacedAt:string}> $previous
     * @param string $source
     */
    public function __construct(string $current, array $previous = [], string $source = self::SOURCE_KEYRING)
    {
        $this->current = $current;
        $this->previous = array_values(array_filter($previous, fn($item) => is_array($item) && is_string($item['key'] ?? null) && $item['key'] !== ''));
        $this->source = $source;
    }

    /**
     * El llavero de un archivo; null si no existe o no tiene la forma esperada.
     *
     * @param string $path
     * @return AppKeyring|null
     */
    public static function fromFile(string $path): ?AppKeyring
    {
        if (!is_file($path)) {
            return null;
        }
        $content = @file_get_contents($path);
        $data = is_string($content) ? json_decode($content, true) : null;
        if (!is_array($data) || !is_string($data['current'] ?? null) || trim($data['current']) === '') {
            return null;
        }
        $previous = [];
        foreach (is_array($data['previous'] ?? null) ? $data['previous'] : [] as $item) {
            if (is_array($item) && is_string($item['key'] ?? null) && $item['key'] !== '') {
                $previous[] = ['key' => $item['key'], 'replacedAt' => is_string($item['replacedAt'] ?? null) ? $item['replacedAt'] : ''];
            }
        }
        return new AppKeyring($data['current'], $previous, self::SOURCE_KEYRING);
    }

    /**
     * La carpeta secure-keys de la instalación: la misma búsqueda que getKeyFromSecureKeys() (junto a src/ o en
     * ../private/). null si no hay ninguna.
     *
     * @return string|null
     */
    public static function directory(): ?string
    {
        $root = dirname(__DIR__, 7);
        foreach ([$root . '/secure-keys', $root . '/private/secure-keys'] as $candidate) {
            if (is_dir($candidate)) {
                return $candidate;
            }
        }
        return null;
    }

    /**
     * La ruta del llavero de la instalación; null si no hay carpeta secure-keys.
     *
     * @return string|null
     */
    public static function defaultPath(): ?string
    {
        $directory = self::directory();
        return $directory !== null ? $directory . '/' . self::FILE_NAME : null;
    }

    /**
     * @param string $key
     * @return string
     */
    public static function fingerprint(string $key): string
    {
        return substr(hash('sha256', $key), 0, self::FINGERPRINT_LENGTH);
    }

    /**
     * 64 caracteres hexadecimales aleatorios.
     *
     * @return string
     */
    public static function generateKey(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * @return string
     */
    public function current(): string
    {
        return $this->current;
    }

    /**
     * @return string
     */
    public function currentFingerprint(): string
    {
        return self::fingerprint($this->current);
    }

    /**
     * @return array<int,array{key:string,replacedAt:string}>
     */
    public function previous(): array
    {
        return $this->previous;
    }

    /**
     * @return string SOURCE_*
     */
    public function source(): string
    {
        return $this->source;
    }

    /**
     * La clave de esa huella, la actual o una anterior; null si no está.
     *
     * @param string $fingerprint
     * @return string|null
     */
    public function keyFor(string $fingerprint): ?string
    {
        if (hash_equals($this->currentFingerprint(), $fingerprint)) {
            return $this->current;
        }
        foreach ($this->previous as $item) {
            if (hash_equals(self::fingerprint($item['key']), $fingerprint)) {
                return $item['key'];
            }
        }
        return null;
    }

    /**
     * Las huellas de las anteriores, la más reciente primero.
     *
     * @return string[]
     */
    public function previousFingerprints(): array
    {
        return array_map(fn(array $item): string => self::fingerprint($item['key']), $this->previous);
    }

    /**
     * Un llavero con esa clave como actual y la de ahora como anterior. No escribe nada.
     *
     * @param string $newKey
     * @return AppKeyring
     */
    public function rotatedTo(string $newKey): AppKeyring
    {
        $previous = array_values(array_filter($this->previous, fn(array $item): bool => $item['key'] !== $newKey && $item['key'] !== $this->current));
        array_unshift($previous, ['key' => $this->current, 'replacedAt' => date('Y-m-d H:i:s')]);
        return new AppKeyring($newKey, $previous, self::SOURCE_KEYRING);
    }

    /**
     * Un llavero sin la anterior de esa huella. No escribe nada.
     *
     * @param string $fingerprint
     * @return AppKeyring
     */
    public function withoutPrevious(string $fingerprint): AppKeyring
    {
        $previous = array_values(array_filter($this->previous, fn(array $item): bool => !hash_equals(self::fingerprint($item['key']), $fingerprint)));
        return new AppKeyring($this->current, $previous, $this->source);
    }

    /**
     * Lo escribe de forma atómica (temporal y rename): un lector nunca ve un archivo a medias.
     *
     * @param string $path
     * @return bool
     */
    public function save(string $path): bool
    {
        $encoded = json_encode([
            'current' => $this->current,
            'previous' => $this->previous,
        ], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
        if (!is_string($encoded)) {
            return false;
        }
        $temporary = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';
        if (@file_put_contents($temporary, $encoded . "\n", \LOCK_EX) === false) {
            return false;
        }
        //RETORNO-IGNORADO: el permiso cierra la lectura a otros usuarios del sistema; si falla, rename() decide igual.
        @chmod($temporary, 0640);
        //El grupo de la carpeta: lo escribe la terminal o el servidor web, y el otro tiene que poder leerlo.
        $group = @filegroup(dirname($path));
        if (is_int($group)) {
            //RETORNO-IGNORADO: si no se puede, queda el grupo del que lo escribe; lo detecta el aviso de llavero ilegible.
            @chgrp($temporary, $group);
        }
        if (!@rename($temporary, $path)) {
            //RETORNO-IGNORADO: el temporal no se usa; si queda, no lo lee nadie (no tiene el nombre del llavero).
            @unlink($temporary);
            return false;
        }
        return true;
    }

    /**
     * Lo crea solo si no existe, de forma atómica (temporal y link(), que falla si ya está): si dos arranques generan a
     * la vez, gana uno y el otro lee el suyo.
     *
     * @param string $path
     * @return bool true si lo creó esta llamada
     */
    public function saveIfAbsent(string $path): bool
    {
        if (is_file($path)) {
            return false;
        }
        $temporary = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';
        if (!$this->save($temporary)) {
            return false;
        }
        $created = @link($temporary, $path);
        //RETORNO-IGNORADO: el temporal no se usa; si queda, no lo lee nadie (no tiene el nombre del llavero).
        @unlink($temporary);
        return $created;
    }

    /**
     * El llavero de la aplicación. Si nadie lo fijó (una prueba aislada), uno con la clave de Config.
     *
     * @return AppKeyring
     */
    public static function get(): AppKeyring
    {
        if (self::$instance === null) {
            self::$instance = new AppKeyring((string) \PiecesPHP\Core\Config::app_key(), [], self::SOURCE_DEFAULT);
        }
        return self::$instance;
    }

    /**
     * Si alguien fijó ya el llavero (Config al arrancar).
     *
     * @return bool
     */
    public static function isSet(): bool
    {
        return self::$instance !== null;
    }

    /**
     * Lo fija Config al arrancar, y rotate-app-key al terminar.
     *
     * @param AppKeyring $keyring
     * @return void
     */
    public static function set(AppKeyring $keyring): void
    {
        self::$instance = $keyring;
    }

    /**
     * Cambia el llavero global y devuelve el anterior, para devolverlo al terminar.
     *
     * @param AppKeyring|null $keyring
     * @return AppKeyring|null
     */
    public static function swapForTesting(?AppKeyring $keyring): ?AppKeyring
    {
        $previous = self::$instance;
        self::$instance = $keyring;
        return $previous;
    }
}
