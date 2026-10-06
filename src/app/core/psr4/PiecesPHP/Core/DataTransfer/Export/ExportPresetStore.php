<?php

/**
 * ExportPresetStore.php
 */

namespace PiecesPHP\Core\DataTransfer\Export;

/**
 * ExportPresetStore - Dónde se guardan los filtros de exportación con nombre de cada usuario.
 *
 * Las reglas (longitud del nombre, límite, validación de la query) las pone quien llama; el almacén solo guarda.
 *
 * @package     PiecesPHP\Core\DataTransfer\Export
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
interface ExportPresetStore
{
    /**
     * @param int $userID
     * @param string $exportKey
     * @return array<string,array<string,mixed>> nombre => query
     */
    public function all(int $userID, string $exportKey): array;

    /**
     * Con un nombre que ya existe, lo sustituye.
     *
     * @param int $userID
     * @param string $exportKey
     * @param string $name
     * @param array<string,mixed> $query
     * @return void
     */
    public function save(int $userID, string $exportKey, string $name, array $query): void;

    /**
     * @param int $userID
     * @param string $exportKey
     * @param string $name
     * @return void
     */
    public function delete(int $userID, string $exportKey, string $name): void;
}
