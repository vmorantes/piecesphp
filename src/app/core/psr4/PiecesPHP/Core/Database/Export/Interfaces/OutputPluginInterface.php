<?php

namespace PiecesPHP\Core\Database\Export\Interfaces;

/**
 * Interface OutputPluginInterface
 * 
 * Interfaz para los plugins que definen el destino de la exportación (Archivo, GZIP, etc).
 */
interface OutputPluginInterface
{
    /**
     * Inicializa el plugin de salida (ej: abre un archivo).
     * 
     * @param array $options
     * @return bool
     */
    public function init(array $options): bool;

    /**
     * Escribe datos en la salida.
     * 
     * @param string $data
     * @return void
     */
    public function write(string $data): void;

    /**
     * Finaliza el plugin de salida (ej: cierra un archivo).
     * 
     * @return mixed
     */
    public function finalize(): mixed;

    /**
     * Devuelve el nombre del archivo si es aplicable.
     * 
     * @return string|null
     */
    public function getFilename(): ?string;
}
