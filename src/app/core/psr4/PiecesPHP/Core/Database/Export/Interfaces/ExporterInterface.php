<?php

namespace PiecesPHP\Core\Database\Export\Interfaces;

use PDO;

/**
 * Interface ExporterInterface
 * 
 * Contrato para el motor de exportación de base de datos.
 */
interface ExporterInterface
{
    /**
     * Realiza la exportación utilizando el formato y salida configurados.
     * 
     * @param array $options Opciones de configuración.
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
