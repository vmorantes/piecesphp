<?php

/**
 * AppKeyCommands.php
 */

namespace Terminal\Tasks;

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Crypto\AppKeyring;
use PiecesPHP\Core\Crypto\KeyRotation;

/**
 * AppKeyCommands - Lo que hacen generate-app-key, store-app-key, rotate-app-key, retire-app-key y seal-legacy-data
 * (ADR 0061 §5, §7 y §11).
 *
 * Aparte de las tareas para que las pruebas lo ejerzan sobre un llavero temporal. NUNCA imprime una clave: solo su
 * huella.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class AppKeyCommands
{
    /**
     * Crea el llavero con una clave nueva. Solo si no hay llavero: con uno, se rota. La clave de ahora (la de config.php
     * o la de relleno) queda como anterior, así lo cifrado con ella se sigue leyendo.
     *
     * @param string $keyringPath
     * @param AppKeyring $current
     * @return string[] Las líneas que se imprimen
     * @throws \RuntimeException
     */
    public static function generate(string $keyringPath, AppKeyring $current): array
    {
        if (is_file($keyringPath)) {
            throw new \RuntimeException('Ya hay un llavero en ' . $keyringPath . '. Para cambiar la clave: bin/cli rotate-app-key.');
        }
        $keyring = $current->rotatedTo(AppKeyring::generateKey());
        if (!$keyring->save($keyringPath)) {
            throw new \RuntimeException('No se pudo escribir ' . $keyringPath . '.');
        }
        AppKeyring::set($keyring);
        return [
            'Clave nueva guardada en ' . $keyringPath . ' (huella ' . $keyring->currentFingerprint() . ').',
            'La anterior (huella ' . $current->currentFingerprint() . ') queda en el llavero: lo cifrado con ella se sigue leyendo.',
            'Las sesiones y los tokens emitidos con la anterior dejan de valer. Si estaba en config.php, quítela de allí.',
        ];
    }

    /**
     * Mueve la clave de config.php al llavero, SIN cambiarla.
     *
     * @param string $keyringPath
     * @param AppKeyring $current
     * @return string[]
     * @throws \RuntimeException
     */
    public static function store(string $keyringPath, AppKeyring $current): array
    {
        if (is_file($keyringPath)) {
            throw new \RuntimeException('Ya hay un llavero en ' . $keyringPath . ': no hay nada que mover.');
        }
        if (Config::app_key_is_placeholder($current->current())) {
            throw new \RuntimeException('La clave actual es la de relleno: no se guarda. Genere una con bin/cli generate-app-key.');
        }
        $keyring = new AppKeyring($current->current(), [], AppKeyring::SOURCE_KEYRING);
        if (!$keyring->save($keyringPath)) {
            throw new \RuntimeException('No se pudo escribir ' . $keyringPath . '.');
        }
        AppKeyring::set($keyring);
        return [
            'Clave guardada en ' . $keyringPath . ' (huella ' . $keyring->currentFingerprint() . '), la misma de antes.',
            'Quítela ya de config.php: ese archivo se versiona.',
        ];
    }

    /**
     * Rota: clave nueva, todo lo declarado vuelto a cifrar con ella, la vieja al llavero. Sin respaldo hecho no rota.
     *
     * @param string $keyringPath
     * @param AppKeyring $current
     * @param \PDO $database
     * @param callable():(string|null) $backup Hace el respaldo y devuelve su archivo, o null si falló
     * @return string[]
     * @throws \Throwable Si algo falla: nada cambia
     */
    public static function rotate(string $keyringPath, AppKeyring $current, \PDO $database, callable $backup): array
    {
        $backupFile = $backup();
        if (!is_string($backupFile) || $backupFile === '') {
            throw new \RuntimeException('El respaldo de la base falló: no se rota.');
        }
        $next = $current->rotatedTo(AppKeyring::generateKey());
        $report = (new KeyRotation($database))->rotate($current, $next, $keyringPath);
        AppKeyring::set($next);
        $lines = [
            'Respaldo: ' . $backupFile,
            'Clave nueva (huella ' . $next->currentFingerprint() . '); la anterior (huella ' . $current->currentFingerprint() . ') queda en el llavero.',
        ];
        foreach ($report as $site => $counts) {
            $lines[] = "  {$site}: " . ($counts['note'] !== '' ? $counts['note'] : "{$counts['resealed']} de {$counts['rows']} vueltos a cifrar" . ($counts['unreadable'] > 0 ? ", {$counts['unreadable']} ilegibles (se dejan como estaban)" : ''));
        }
        $lines[] = 'Las sesiones y los tokens emitidos con la anterior dejan de valer.';
        return $lines;
    }

    /**
     * Vuelve a cifrar con la clave actual lo que quedó en un formato de antes de la 9 (o con una clave anterior), SIN
     * cambiar la clave ni cerrar sesiones. Sin respaldo hecho no escribe.
     *
     * @param AppKeyring $current
     * @param \PDO $database
     * @param callable():(string|null) $backup Hace el respaldo y devuelve su archivo, o null si falló
     * @return string[]
     * @throws \Throwable Si algo falla: nada cambia
     */
    public static function sealLegacy(AppKeyring $current, \PDO $database, callable $backup): array
    {
        $backupFile = $backup();
        if (!is_string($backupFile) || $backupFile === '') {
            throw new \RuntimeException('El respaldo de la base falló: no se escribe nada.');
        }
        $report = (new KeyRotation($database))->sealLegacy($current);
        $lines = [
            'Respaldo: ' . $backupFile,
            'Clave actual (huella ' . $current->currentFingerprint() . '), sin cambiarla.',
        ];
        foreach ($report as $site => $counts) {
            $lines[] = "  {$site}: " . ($counts['note'] !== '' ? $counts['note'] : "{$counts['resealed']} de {$counts['rows']} pendientes vueltos a cifrar" . ($counts['unreadable'] > 0 ? ", {$counts['unreadable']} ilegibles (se dejan como estaban)" : ''));
        }
        return $lines;
    }

    /**
     * @param string $keyringPath
     * @param AppKeyring $current
     * @param \PDO $database
     * @param string $fingerprint
     * @return string[]
     * @throws \RuntimeException Si no se puede retirar
     */
    public static function retire(string $keyringPath, AppKeyring $current, \PDO $database, string $fingerprint): array
    {
        $why = (new KeyRotation($database))->retire($current, $fingerprint, $keyringPath);
        if ($why !== '') {
            throw new \RuntimeException($why);
        }
        AppKeyring::set($current->withoutPrevious($fingerprint));
        return ['Clave anterior ' . $fingerprint . ' retirada del llavero.'];
    }

    /**
     * La ruta del llavero de la instalación, o una excepción que dice qué falta.
     *
     * @return string
     * @throws \RuntimeException
     */
    public static function installationPath(): string
    {
        $path = AppKeyring::defaultPath();
        if ($path === null) {
            throw new \RuntimeException('No hay carpeta secure-keys junto a src/ (ni en ../private/): créela antes.');
        }
        return $path;
    }

    /**
     * La conexión de la aplicación, o una excepción.
     *
     * @return \PDO
     * @throws \RuntimeException
     */
    public static function database(): \PDO
    {
        $database = (new BaseModel())->getDatabase();
        if (!$database instanceof \PDO) {
            throw new \RuntimeException('No hay conexión a la base de datos.');
        }
        return $database;
    }
}
