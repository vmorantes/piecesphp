<?php

/**
 * PreferSlugsFiller.php
 */

namespace Terminal\Jobs;

use PiecesPHP\Core\Database\EntityMapperExtensible;
use Terminal\Tasks\SchemeCreateTask;

/**
 * PreferSlugsFiller.
 *
 * Rellena de golpe los `preferSlug` que falten, para no esperar a que alguien navegue.
 *
 * NO sustituye al relleno perezoso de `objectToMapper()`: lo complementa. El perezoso cubre la
 * fila que aparece un día suelta; esto cubre la importación entera. Ver T61.
 *
 * @package     Terminal\Jobs
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class PreferSlugsFiller
{

    /**
     * Los mappers que tienen `preferSlug`, DESCUBIERTOS. Una lista escrita a mano se queda
     * atrás en cuanto alguien añada un módulo.
     *
     * @return array<string, EntityMapperExtensible> Indexado por nombre de tabla.
     */
    public static function mappersWithSlug(): array
    {
        $found = \Closure::bind(
            static fn () => SchemeCreateTask::discover('all'),
            null,
            SchemeCreateTask::class
        )();

        $result = [];
        foreach ($found['mappers'] as $mapper) {
            if (!$mapper instanceof EntityMapperExtensible) {
                continue;
            }
            $reflection = new \ReflectionClass($mapper);
            $fields = $reflection->getProperty('fields')->getValue($mapper);
            if (!is_array($fields) || !array_key_exists('preferSlug', $fields)) {
                continue;
            }
            $table = (string) $reflection->getProperty('table')->getValue($mapper);
            if ($table !== '') {
                $result[$table] = $mapper;
            }
        }

        return $result;
    }

    /**
     * @param string|null $onlyTable Si se indica, solo esa tabla.
     * @return array{tables: int, filled: int, skipped: int, detail: array<string, int>}
     */
    public static function run(?string $onlyTable = null): array
    {
        $summary = ['tables' => 0, 'filled' => 0, 'skipped' => 0, 'detail' => []];

        foreach (self::mappersWithSlug() as $table => $mapper) {
            if ($onlyTable !== null && $onlyTable !== $table) {
                continue;
            }
            $class = get_class($mapper);
            //Un mapper con preferSlug que no use el trait se salta, no revienta.
            if (!method_exists($class, 'mintPreferSlugIfMissing')) {
                continue;
            }
            $model = $class::model();
            $database = $model->getDatabase();
            if ($database === null) {
                continue;
            }
            $statement = $database->prepare("SELECT `id` FROM `{$table}` WHERE `preferSlug` IS NULL");
            $statement->execute();
            $pending = $statement->fetchAll(\PDO::FETCH_COLUMN);
            $pending = is_array($pending) ? $pending : [];

            $summary['tables']++;
            $filled = 0;
            foreach ($pending as $row) {
                $id = (int) $row;
                if ($id === 0) {
                    continue;
                }
                $element = new $class($id);
                //El MISMO camino que el relleno perezoso: acuñado atómico y condicional.
                //El MISMO camino que el relleno perezoso, guarda de nombre incluida.
                if ($class::mintPreferSlugIfMissing($element)) {
                    $filled++;
                } else {
                    $summary['skipped']++;
                }
            }
            $summary['filled'] += $filled;
            $summary['detail'][$table] = $filled;
        }

        return $summary;
    }

}
