<?php

/**
 * BackupRotation.php
 */

namespace PiecesPHP\Core\Backups;

use DateTimeImmutable;

/**
 * BackupRotation.
 *
 * La conservación de los respaldos: qué se queda y qué se borra, por niveles. Ver ADR 0038 §3.
 *
 * Solo toca los archivos cuyo nombre escribe `DbBackupTask`, en la raíz de la carpeta: un
 * volcado hecho a mano, con otro nombre o en una subcarpeta, no se borra nunca.
 *
 * @package     PiecesPHP\Core\Backups
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class BackupRotation
{

    /**
     * El nombre que escribe `DbBackupTask`: `date('d-m-Y_H-i-s-A')` más `.sql` o `.sql.gz`.
     *
     * La hora va en 24 horas y el AM/PM es redundante, así que la hora llega hasta 23.
     */
    const FILE_PATTERN = '/^(\d{2})-(\d{2})-(\d{4})_(\d{2})-(\d{2})-(\d{2})-(?:AM|PM)\.sql(?:\.gz)?$/';

    /**
     * El formato con el que se vuelve a escribir la marca de tiempo para comprobar el parseo.
     */
    const STAMP_FORMAT = 'd-m-Y_H-i-s';

    /**
     * La fecha de un respaldo, sacada de su NOMBRE, o null si no es uno de los nuestros.
     *
     * El sufijo AM/PM del nombre NO sirve para parsear: la hora ya va en 24 horas, así que
     * `d-m-Y_H-i-s-A` sobre «01-09-2026_13-16-22-PM» suma otras 12 horas y devuelve el día
     * siguiente a la 01:16. Medido el 2026-10-02. Se parsea sin el sufijo y se comprueba la
     * vuelta, que es lo que descarta una fecha imposible: `32-01-2026` rodaría a febrero.
     *
     * @param string $name Nombre del archivo, sin carpeta.
     * @return DateTimeImmutable|null
     */
    public static function dateFromName(string $name): ?DateTimeImmutable
    {
        if (preg_match(self::FILE_PATTERN, $name, $matches) !== 1) {
            return null;
        }
        $stamp = "{$matches[1]}-{$matches[2]}-{$matches[3]}_{$matches[4]}-{$matches[5]}-{$matches[6]}";
        $date = DateTimeImmutable::createFromFormat('!' . self::STAMP_FORMAT, $stamp);
        if ($date === false || $date->format(self::STAMP_FORMAT) !== $stamp) {
            return null;
        }
        return $date;
    }

    /**
     * Los nombres de archivo de la RAÍZ de la carpeta: ni subcarpetas ni lo que no es archivo.
     *
     * @param string $directory
     * @return string[]
     */
    public static function namesIn(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }
        $directory = rtrim($directory, '/\\');
        $names = [];
        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (!is_file("{$directory}/{$entry}")) {
                continue;
            }
            $names[] = $entry;
        }
        return $names;
    }

    /**
     * La fecha del respaldo más reciente de la carpeta, o null si no hay ninguno nuestro.
     *
     * @param string $directory
     * @return DateTimeImmutable|null
     */
    public static function latestDate(string $directory): ?DateTimeImmutable
    {
        $latest = null;
        foreach (self::namesIn($directory) as $name) {
            $date = self::dateFromName($name);
            if ($date === null) {
                continue;
            }
            if ($latest === null || $date > $latest) {
                $latest = $date;
            }
        }
        return $latest;
    }

    /**
     * Qué se conserva, qué se borra y qué no se mira, sobre una lista de nombres.
     *
     * FUNCIÓN PURA: ni toca el disco ni la hora. Se conserva la unión de los `keep_recent`
     * más nuevos y del más nuevo de cada uno de los últimos periodos CON respaldo (días,
     * semanas ISO y meses). Cuentan los periodos que tienen respaldo, no los del calendario:
     * una instalación parada un mes no pierde su historia por no haber respaldado.
     *
     * @param string[] $names
     * @param array{rotate:bool,keep_recent:int,keep_daily:int,keep_weekly:int,keep_monthly:int} $policy
     * @return array{keep:string[],delete:string[],ignored:string[]}
     */
    public static function plan(array $names, array $policy): array
    {
        $ignored = [];
        $entries = [];
        foreach ($names as $name) {
            $date = self::dateFromName($name);
            if ($date === null) {
                $ignored[] = $name;
                continue;
            }
            $entries[] = ['name' => $name, 'date' => $date];
        }

        //Del más nuevo al más viejo. El nombre desempata para que el plan no dependa del
        //orden en que el sistema de archivos los devuelva.
        usort($entries, static fn (array $a, array $b): int => [$b['date']->getTimestamp(), $b['name']] <=> [$a['date']->getTimestamp(), $a['name']]);

        sort($ignored);

        if (($policy['rotate'] ?? true) !== true) {
            //Como hasta ahora: no se borra nada.
            return [
                'keep' => array_column($entries, 'name'),
                'delete' => [],
                'ignored' => $ignored,
            ];
        }

        $keep = [];

        //Los más nuevos. Siempre uno como mínimo: la conservación no puede dejar cero.
        $recent = max(BackupPolicy::MIN_KEEP_RECENT, (int) ($policy['keep_recent'] ?? BackupPolicy::DEFAULT_KEEP_RECENT));
        foreach (array_slice($entries, 0, $recent) as $entry) {
            $keep[$entry['name']] = true;
        }

        //Un respaldo por periodo, y solo de los periodos que tienen alguno.
        $levels = [
            'keep_daily' => 'Y-m-d',
            'keep_weekly' => 'o-W',
            'keep_monthly' => 'Y-m',
        ];
        foreach ($levels as $field => $format) {
            $limit = (int) ($policy[$field] ?? 0);
            if ($limit <= 0) {
                continue;
            }
            $newestOfPeriod = [];
            foreach ($entries as $entry) {
                $period = $entry['date']->format($format);
                if (!array_key_exists($period, $newestOfPeriod)) {
                    //`$entries` va de nuevo a viejo: el primero de cada periodo es su más nuevo.
                    $newestOfPeriod[$period] = $entry['name'];
                }
            }
            foreach (array_slice($newestOfPeriod, 0, $limit, true) as $name) {
                $keep[$name] = true;
            }
        }

        $delete = [];
        foreach ($entries as $entry) {
            if (!array_key_exists($entry['name'], $keep)) {
                $delete[] = $entry['name'];
            }
        }

        $kept = array_keys($keep);
        sort($kept);
        sort($delete);

        return [
            'keep' => $kept,
            'delete' => $delete,
            'ignored' => $ignored,
        ];
    }

    /**
     * Lo que hace el cronjob después de respaldar: rota si el respaldo salió bien, y nada si no.
     *
     * Vive aquí, y no dentro del cronjob, para que la prueba pueda comprobar sobre una carpeta
     * temporal que un respaldo FALLIDO no borra nada. Ver LEY 24: una guarda que solo se ve en
     * verde no se ha visto funcionar.
     *
     * @param bool $backupSucceeded Si el respaldo de esta pasada salió bien.
     * @param string|null $writtenFile El archivo que escribió, si lo escribió.
     * @param string $directory La carpeta de los respaldos.
     * @param array{rotate:bool,keep_recent:int,keep_daily:int,keep_weekly:int,keep_monthly:int} $policy
     * @return array{rotated:bool,message:string,kept:int,deleted:string[],failed:string[]}
     */
    public static function afterBackup(bool $backupSucceeded, ?string $writtenFile, string $directory, array $policy): array
    {
        if (!$backupSucceeded) {
            //Un fallo repetido se llevaría los respaldos buenos. ADR 0038 §3.
            return [
                'rotated' => false,
                'message' => 'El respaldo falló: no se borró ningún respaldo.',
                'kept' => 0,
                'deleted' => [],
                'failed' => [],
            ];
        }

        $rotation = self::apply($directory, $policy, $writtenFile);
        $message = 'Respaldo ' . ($writtenFile !== null ? basename($writtenFile) : 'escrito')
            . ": se conservan {$rotation['kept']}, borrados " . count($rotation['deleted']) . '.';
        if (count($rotation['failed']) > 0) {
            $message .= ' No se pudieron borrar ' . count($rotation['failed']) . '.';
        }

        return [
            'rotated' => true,
            'message' => $message,
            'kept' => $rotation['kept'],
            'deleted' => $rotation['deleted'],
            'failed' => $rotation['failed'],
        ];
    }

    /**
     * Aplica la conservación a una carpeta: borra lo que sobra, uno a uno.
     *
     * Un borrado que falla va a `failed` y no corta el resto: un archivo sin permisos no
     * puede dejar la carpeta a medio limpiar para siempre.
     *
     * @param string $directory
     * @param array{rotate:bool,keep_recent:int,keep_daily:int,keep_weekly:int,keep_monthly:int} $policy
     * @param string|null $justWritten El respaldo de esta misma pasada, que nunca se borra.
     * @return array{kept:int,deleted:string[],failed:string[]}
     */
    public static function apply(string $directory, array $policy, ?string $justWritten = null): array
    {
        $plan = self::plan(self::namesIn($directory), $policy);
        $delete = $plan['delete'];

        if ($justWritten !== null && $justWritten !== '') {
            //El recién escrito siempre queda, aunque la política lo dejara fuera. No debería
            //pasar: es el más nuevo y `keep_recent` es como mínimo 1.
            $justWritten = basename($justWritten);
            $delete = array_values(array_filter($delete, static fn (string $name): bool => $name !== $justWritten));
        }

        $directory = rtrim($directory, '/\\');
        $deleted = [];
        $failed = [];
        foreach ($delete as $name) {
            if (@unlink("{$directory}/{$name}")) {
                $deleted[] = $name;
            } else {
                $failed[] = $name;
            }
        }

        return [
            'kept' => count($plan['keep']),
            'deleted' => $deleted,
            'failed' => $failed,
        ];
    }

}
