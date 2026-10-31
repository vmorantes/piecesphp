<?php
namespace PiecesPHP\Core\Database\Export\Interfaces;

use PiecesPHP\Core\Database\Database;

/**
 * Contrato del motor de exportación de base de datos. Las opciones y sus tipos, en el @param de export().
 */
interface ExporterInterface
{
    /**
     * Realiza la exportación utilizando el formato y salida configurados.
     *
     * @param array{
     *     tables?: string[],
     *     table_style?: \PiecesPHP\Core\Database\Export\Enums\TableStyle|string,
     *     data_style?: \PiecesPHP\Core\Database\Export\Enums\DataStyle|string,
     *     auto_increment?: bool,
     *     triggers?: bool,
     *     routines?: bool,
     *     remove_definer?: bool,
     *     drop_if_exists_on_functions?: bool,
     *     create_if_not_exists?: bool,
     *     include_data?: bool,
     *     include_views?: bool,
     *     single_transaction?: bool,
     *     filename?: string,
     *     exclude_tables?: string[],
     *     where?: array<string, string>,
     *     transformations?: array<string, array<string, callable>>,
     *     hex_blob?: bool
     * } $options Opciones de configuración.
     * @return mixed Depende del plugin de salida (string, bool, etc).
     */
    public function export(array $options = []): mixed;

    /**
     * Obtiene la lista de tablas de la base de datos.
     *
     * @return array
     */
    public function getTables(): array;

    /**
     * Obtiene los errores ocurridos durante el proceso.
     *
     * @return array
     */
    public function getErrors(): array;
}
