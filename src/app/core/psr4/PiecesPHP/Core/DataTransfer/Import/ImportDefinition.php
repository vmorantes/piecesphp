<?php

/**
 * ImportDefinition.php
 */

namespace PiecesPHP\Core\DataTransfer\Import;

use PiecesPHP\Core\DataTransfer\InterfaceLevel;

/**
 * ImportDefinition - Lo que un módulo implementa para importar un tipo de dato.
 *
 * @package     PiecesPHP\Core\DataTransfer\Import
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
abstract class ImportDefinition
{
    /** Ninguna ruta web: solo la terminal. */
    const INTERFACE_NONE = InterfaceLevel::NONE;
    /** Formulario generado, acción y plantilla. */
    const INTERFACE_AUTO = InterfaceLevel::AUTO;
    /** Lo del AUTO, más el simulacro en la web y el hueco formPartial(). */
    const INTERFACE_EXTENDED = InterfaceLevel::EXTENDED;
    /** La definición pone su vista (customView()) sobre las mismas rutas. */
    const INTERFACE_CUSTOM = InterfaceLevel::CUSTOM;

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
     * Una frase para la portada; vacía si no hace falta.
     *
     * @return string
     */
    public function description(): string
    {
        return '';
    }

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
        //XLSX de base (P47); otro formato lo añade el módulo que lo necesite.
        return ['xlsx'];
    }

    /**
     * @return int Una de las constantes INTERFACE_*
     */
    public function interfaceLevel(): int
    {
        return self::INTERFACE_AUTO;
    }

    /**
     * Nivel 2 o más: un .php del proyecto que el formulario incluye, con acceso a $definition, $columns y $langGroup.
     *
     * @return string|null
     */
    public function formPartial(): ?string
    {
        return null;
    }

    /**
     * Nivel 3, obligatoria: la vista .php del proyecto que sustituye al formulario.
     *
     * @return string|null
     */
    public function customView(): ?string
    {
        return null;
    }

    /**
     * Comprueba el nivel de interfaz y, en el 3, la vista propia. Se llama al registrar.
     *
     * @return void
     * @throws \InvalidArgumentException error de quien programa
     */
    final public function checkInterface(): void
    {
        InterfaceLevel::check($this->interfaceLevel(), $this->customView(), $this->key());
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
     * Si persist() puede devolver artefactos: quien importa sin navegador (la terminal) exige antes dónde guardarlos.
     *
     * @return bool
     */
    public function mayProduceArtifacts(): bool
    {
        return false;
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
