<?php

namespace PiecesPHP\Core\Database\Export\Interfaces;

use PDO;

/**
 * Interface ExporterInterface
 * 
 * Contrato para el motor de exportación de base de datos.
 * 
 * Opciones soportadas:
 * - tables: string[] (lista de tablas a exportar).
 * - table_style: Enums\TableStyle|string (DROP_CREATE, CREATE).
 * - data_style: Enums\DataStyle|string (INSERT, REPLACE, TRUNCATE_INSERT).
 * - auto_increment: bool (incluir o no AUTO_INCREMENT en CREATE TABLE).
 * - triggers: bool (incluir triggers).
 * - routines: bool (incluir procedimientos y funciones).
 * - remove_definer: bool (eliminar DEFINER de todos los objetos).
 * - drop_if_exists_on_functions: bool (usar DROP FUNCTION/PROCEDURE IF EXISTS).
 * - create_if_not_exists: bool (usar CREATE TABLE IF NOT EXISTS).
 * - include_data: bool (incluir datos de las tablas).
 * - include_views: bool (incluir vistas).
 * - single_transaction: bool (usar una sola transacción para la exportación).
 * - filename: string (nombre del archivo de salida).
 * - exclude_tables: string[] (lista de tablas a omitir).
 * - where: array<string, string> (filtro WHERE por reporte, ej: ['users' => 'active = 1']).
 * - transformations: array<string, array<string, callable>> (funciones de transformación por tabla y columna).
 * - hex_blob: bool (si se deben exportar campos binarios como hexadecimal, default: true).
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
