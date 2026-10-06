<?php

/**
 * BackupPolicy.php
 */

namespace PiecesPHP\Core\Backups;

use DateTimeImmutable;

/**
 * BackupPolicy.
 *
 * La política de respaldos: cada cuánto se respalda, cuántos se conservan y qué tablas
 * salen sin sus filas. Sus valores por omisión rigen mientras nadie la configure, así que
 * el framework respalda y conserva desde el primer día. Ver ADR 0038.
 *
 * @package     PiecesPHP\Core\Backups
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class BackupPolicy
{

    /**
     * La opción que guarda la política (JSON), escrita por la pantalla «Respaldos».
     */
    const CONFIG_NAME = 'backup_policy';

    const DEFAULT_ENABLED = true;
    const DEFAULT_INTERVAL_MINUTES = 1440;
    const DEFAULT_ROTATE = true;
    const DEFAULT_KEEP_RECENT = 24;
    const DEFAULT_KEEP_DAILY = 30;
    const DEFAULT_KEEP_WEEKLY = 12;
    const DEFAULT_KEEP_MONTHLY = 24;

    const MIN_INTERVAL_MINUTES = 60;
    const MAX_INTERVAL_MINUTES = 10080;

    /**
     * Nunca cero: la conservación no puede dejar la instalación sin ningún respaldo.
     */
    const MIN_KEEP_RECENT = 1;
    const MAX_KEEP_RECENT = 1000;

    const MIN_KEEP_PERIOD = 0;
    const MAX_KEEP_PERIOD = 1000;

    /**
     * Las tablas que un módulo o un clon saca de los datos desde código, con su motivo.
     *
     * Registro estático, como el de avisos: lo de código no se puede quitar desde el panel.
     *
     * @var array<string,string>
     */
    protected static array $codeExcludedDataTables = [];

    /**
     * La política por omisión, que rige mientras no haya ninguna guardada.
     *
     * @return array{enabled:bool,interval_minutes:int,rotate:bool,keep_recent:int,keep_daily:int,keep_weekly:int,keep_monthly:int,data_excluded_tables:string[]}
     */
    public static function defaults(): array
    {
        return [
            'enabled' => self::DEFAULT_ENABLED,
            'interval_minutes' => self::DEFAULT_INTERVAL_MINUTES,
            'rotate' => self::DEFAULT_ROTATE,
            'keep_recent' => self::DEFAULT_KEEP_RECENT,
            'keep_daily' => self::DEFAULT_KEEP_DAILY,
            'keep_weekly' => self::DEFAULT_KEEP_WEEKLY,
            'keep_monthly' => self::DEFAULT_KEEP_MONTHLY,
            'data_excluded_tables' => [],
        ];
    }

    /**
     * La política con su forma, o null si CUALQUIER campo no la tiene.
     *
     * Se rechaza entera: una política a medias borraría archivos con unos límites que nadie
     * pidió. Función pura: las tablas que existen se le pasan, no las consulta.
     *
     * @param mixed $raw Array, objeto o JSON: la columna de la configuración se lee como objetos.
     * @param string[]|null $existingTables Las tablas de la base. Con null NO se comprueba que
     *                                      las de `data_excluded_tables` existan, solo su tipo:
     *                                      la comprobación contra la base es del guardado, que
     *                                      es quien puede rechazar con 400.
     * @return array{enabled:bool,interval_minutes:int,rotate:bool,keep_recent:int,keep_daily:int,keep_weekly:int,keep_monthly:int,data_excluded_tables:string[]}|null
     */
    public static function normalize(mixed $raw, ?array $existingTables = null): ?array
    {
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (is_object($raw)) {
            $raw = get_object_vars($raw);
        }
        if (!is_array($raw)) {
            return null;
        }

        $enabled = $raw['enabled'] ?? null;
        $rotate = $raw['rotate'] ?? null;
        if (!is_bool($enabled) || !is_bool($rotate)) {
            return null;
        }

        $interval = self::normalizeInt($raw['interval_minutes'] ?? null, self::MIN_INTERVAL_MINUTES, self::MAX_INTERVAL_MINUTES);
        $keepRecent = self::normalizeInt($raw['keep_recent'] ?? null, self::MIN_KEEP_RECENT, self::MAX_KEEP_RECENT);
        $keepDaily = self::normalizeInt($raw['keep_daily'] ?? null, self::MIN_KEEP_PERIOD, self::MAX_KEEP_PERIOD);
        $keepWeekly = self::normalizeInt($raw['keep_weekly'] ?? null, self::MIN_KEEP_PERIOD, self::MAX_KEEP_PERIOD);
        $keepMonthly = self::normalizeInt($raw['keep_monthly'] ?? null, self::MIN_KEEP_PERIOD, self::MAX_KEEP_PERIOD);
        if ($interval === null || $keepRecent === null || $keepDaily === null || $keepWeekly === null || $keepMonthly === null) {
            return null;
        }

        $tables = $raw['data_excluded_tables'] ?? [];
        if (is_object($tables)) {
            $tables = get_object_vars($tables);
        }
        if (!is_array($tables)) {
            return null;
        }
        $normalizedTables = [];
        foreach ($tables as $table) {
            if (!is_string($table) || trim($table) === '') {
                return null;
            }
            $table = trim($table);
            if ($existingTables !== null && !in_array($table, $existingTables, true)) {
                return null;
            }
            if (!in_array($table, $normalizedTables, true)) {
                $normalizedTables[] = $table;
            }
        }

        return [
            'enabled' => $enabled,
            'interval_minutes' => $interval,
            'rotate' => $rotate,
            'keep_recent' => $keepRecent,
            'keep_daily' => $keepDaily,
            'keep_weekly' => $keepWeekly,
            'keep_monthly' => $keepMonthly,
            'data_excluded_tables' => $normalizedTables,
        ];
    }

    /**
     * La política guardada, normalizada; si no hay o es inválida, la de por omisión.
     *
     * Una política inválida deja una línea en el registro y NO revienta: la llaman el cronjob
     * y el aviso del sistema, y una excepción aquí dejaría la instalación sin respaldos.
     *
     * @return array{enabled:bool,interval_minutes:int,rotate:bool,keep_recent:int,keep_daily:int,keep_weekly:int,keep_monthly:int,data_excluded_tables:string[]}
     */
    public static function current(): array
    {
        $stored = get_config(self::CONFIG_NAME);
        if ($stored === null || $stored === '' || $stored === false) {
            return self::defaults();
        }
        $normalized = self::normalize($stored);
        if ($normalized === null) {
            log_exception(new \Exception('La opción ' . self::CONFIG_NAME . ' no tiene forma válida: rige la política por omisión.'));
            return self::defaults();
        }
        return $normalized;
    }

    /**
     * Declara, desde código, que una tabla sale del respaldo sin sus filas.
     *
     * Para un módulo o un clon, por ejemplo desde `config/extensions/`. La pantalla las
     * enseña con su motivo y no deja quitarlas.
     *
     * @param string $table
     * @param string $reason Por qué, para que quien lea la pantalla lo sepa.
     * @return void
     */
    public static function excludeDataOf(string $table, string $reason): void
    {
        $table = trim($table);
        if ($table === '') {
            return;
        }
        self::$codeExcludedDataTables[$table] = $reason;
    }

    /**
     * Las tablas sin filas declaradas en código, tabla => motivo.
     *
     * @return array<string,string>
     */
    public static function codeExcludedDataTables(): array
    {
        return self::$codeExcludedDataTables;
    }

    /**
     * Todas las tablas que salen sin filas: las del panel y las del código, sin repetir.
     *
     * @return string[]
     */
    public static function dataExcludedTables(): array
    {
        $tables = self::current()['data_excluded_tables'];
        foreach (array_keys(self::$codeExcludedDataTables) as $table) {
            if (!in_array($table, $tables, true)) {
                $tables[] = $table;
            }
        }
        return $tables;
    }

    /**
     * Si toca respaldar: la política está activa y el respaldo más reciente es más viejo
     * que el intervalo, o no hay ninguno.
     *
     * La edad sale de la FECHA DEL NOMBRE, no de la del disco, que cambia al copiar la
     * carpeta. Ver ADR 0038 §2.
     *
     * @param DateTimeImmutable|null $now Para las pruebas; por omisión, ahora.
     * @param string|null $directory Para las pruebas; por omisión, `dumps/`.
     * @return bool
     */
    public static function isDue(?DateTimeImmutable $now = null, ?string $directory = null): bool
    {
        $policy = self::current();
        if ($policy['enabled'] !== true) {
            return false;
        }
        $now = $now ?? new DateTimeImmutable();
        $latest = BackupRotation::latestDate($directory ?? self::dumpsDirectory());
        if ($latest === null) {
            return true;
        }
        $elapsedMinutes = ($now->getTimestamp() - $latest->getTimestamp()) / 60;
        return $elapsedMinutes >= $policy['interval_minutes'];
    }

    /**
     * La carpeta de los respaldos. Único sitio que la nombra.
     *
     * @return string
     */
    public static function dumpsDirectory(): string
    {
        return basepath('dumps');
    }

    /**
     * Un entero dentro de su rango, o null.
     *
     * Un booleano cumple `is_int` en ningún caso, pero `true` sí pasaría un `(int)`: por eso
     * se rechaza el tipo antes de comparar.
     *
     * @param mixed $value
     * @param int $min
     * @param int $max
     * @return int|null
     */
    protected static function normalizeInt(mixed $value, int $min, int $max): ?int
    {
        if (is_string($value) && $value !== '' && ctype_digit($value)) {
            //La configuración viaja como texto desde el formulario.
            $value = (int) $value;
        }
        if (!is_int($value) || $value < $min || $value > $max) {
            return null;
        }
        return $value;
    }

}
