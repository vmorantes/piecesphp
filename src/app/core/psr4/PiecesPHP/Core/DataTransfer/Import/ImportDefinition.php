<?php

/**
 * ImportDefinition.php
 */

namespace PiecesPHP\Core\DataTransfer\Import;

/**
 * ImportDefinition - Lo que un módulo implementa para importar un tipo de dato.
 *
 * @package     PiecesPHP\Core\DataTransfer\Import
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
abstract class ImportDefinition
{
    /**
     * En kebab-case: forma parte del nombre de la ruta, que es el permiso.
     *
     * @return string
     */
    abstract public function key(): string;

    /**
     * @return string
     */
    abstract public function title(): string;

    /**
     * @return int[]
     */
    abstract public function allowedUserTypes(): array;

    /**
     * @return Column[]
     */
    abstract public function columns(): array;

    /**
     * @return string[]
     */
    public function acceptedExtensions(): array
    {
        return ['xlsx', 'csv'];
    }

    /**
     * @return int
     */
    public function maxSizeMB(): int
    {
        return 5;
    }

    /**
     * @return int
     */
    public function maxRows(): int
    {
        return 5000;
    }

    /**
     * Errores que solo se ven mirando todas las filas (p. ej. duplicados dentro del archivo).
     *
     * @param ParsedRow[] $rows
     * @return array<int,string[]> posición => errores
     */
    public function validateAll(array $rows): array
    {
        return [];
    }

    /**
     * Solo se llama si TODAS las filas son válidas. Tiene que ser atómica: o entran todas, o ninguna.
     *
     * @param ParsedRow[] $rows
     * @return ImportArtifacts|null
     * @throws ImportPersistException
     */
    abstract public function persist(array $rows): ?ImportArtifacts;
}
