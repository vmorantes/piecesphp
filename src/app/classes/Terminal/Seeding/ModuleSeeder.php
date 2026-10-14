<?php

/**
 * ModuleSeeder.php
 */

namespace Terminal\Seeding;

use PiecesPHP\Core\Database\EntityMapper;

/**
 * ModuleSeeder.
 *
 * El contrato con el que un módulo aporta sus datos de prueba a `bin/cli seed`. Un módulo lo
 * cumple con una clase en `src/app/classes/<Módulo>/Seeds/` que extienda esta: el sembrador la
 * descubre allí y resuelve el orden por las dependencias que declare.
 *
 * Es una clase abstracta y no una interfaz porque trae lo que todo seeder repite: la cantidad
 * por defecto, la lista vacía de dependencias y el prefijo que hace reconocible lo sembrado.
 *
 * @package     Terminal\Seeding
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
abstract class ModuleSeeder
{

    /** Todo texto sembrado empieza así, para que se pueda reconocer y borrar sin ambigüedad. */
    const PREFIX = 'zz-siembra-';

    /**
     * El mapper cuya tabla siembra.
     *
     * @return class-string<EntityMapper>
     */
    abstract public function mapper(): string;

    /**
     * La columna de texto que lleva el prefijo en cada fila sembrada. El limpiado busca ahí lo
     * sembrado, y por eso es obligatoria: sin ella, una fila no se podría deshacer.
     *
     * @return string
     */
    abstract public function markerColumn(): string;

    /**
     * Cómo se genera la fila número `$index` (desde 0). Lo ya sembrado de las dependencias está
     * en `$context`; las referencias se piden con `$context->reference()`.
     *
     * @param int $index
     * @param SeedContext $context
     * @return array<string,mixed> Columna => valor.
     */
    abstract public function row(int $index, SeedContext $context): array;

    /**
     * Mappers que tienen que estar sembrados antes que este. El orden lo resuelve el sembrador.
     *
     * @return array<class-string<EntityMapper>>
     */
    public function dependsOn(): array
    {
        return [];
    }

    /**
     * Filas que la APLICACIÓN crea sola, en otras tablas, por cada fila sembrada, y que el limpiado
     * tiene que borrar antes que ella o la clave foránea lo impide. No es una cascada: se declaran.
     *
     * El valor es la columna que apunta al id de la fila sembrada, o `['column' => …, 'where' => […]]`
     * cuando esa columna sola no basta: una tabla que apunta a varias tablas por número de id
     * borraría, sin el `where`, filas de otra tabla con el mismo número.
     *
     * @return array<string,string|array{column: string, where: array<string,string|int>}> Tabla => columna o columna con condiciones.
     */
    public function ownedRows(): array
    {
        return [];
    }

    /**
     * La carpeta en disco que es SOLO de cada fila sembrada, y que el limpiado borra entera. Su nombre
     * sale de una columna de la fila y tiene que llevar el prefijo: lo que no lo lleve no se borra.
     *
     * @return array{base: string, column: string}|null `base` relativa a src/ y dentro de `statics/uploads/`.
     */
    public function ownedFolder(): ?array
    {
        return null;
    }

    /**
     * Cuántas filas tiene sentido sembrar si no se pide otra cantidad.
     *
     * @return int
     */
    public function defaultCount(): int
    {
        return 5;
    }

    /**
     * Un texto reconocible: `zz-siembra-<que>-<n>`, con `n` desde 1.
     *
     * @param string $what
     * @param int $index
     * @return string
     */
    protected function text(string $what, int $index): string
    {
        return self::PREFIX . $what . '-' . ($index + 1);
    }

}
