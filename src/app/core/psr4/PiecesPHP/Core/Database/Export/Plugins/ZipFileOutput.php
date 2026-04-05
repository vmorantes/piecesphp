<?php

namespace PiecesPHP\Core\Database\Export\Plugins;

use PiecesPHP\Core\Database\Export\Interfaces\OutputPluginInterface;
use ZipArchive;
use Exception;

/**
 * Class ZipFileOutput
 * 
 * Plugin de salida para archivos comprimidos con ZIP.
 * Debido a la naturaleza de ZipArchive, se utiliza un archivo temporal para recolectar el contenido
 * y se comprime en el paso final.
 */
class ZipFileOutput implements OutputPluginInterface
{
    /** @var string Ruta completa al archivo ZIP de salida */
    protected string $zipFilename = '';
    /** @var string Nombre del archivo dentro del ZIP (ej: database.sql) */
    protected string $internalFilename = '';
    /** @var string Ruta al archivo temporal para recolectar el contenido */
    protected string $tempFile = '';
    /** @var resource|null Puntero al archivo temporal */
    protected $tempFp = null;

    /**
     * @inheritDoc
     */
    public function init(array $options): bool
    {
        $this->internalFilename = !empty($options['filename']) ? $options['filename'] : 'database.sql';
        $this->zipFilename = $this->internalFilename;

        if (strtolower(substr($this->zipFilename, -4)) !== '.zip') {
            $this->zipFilename .= '.zip';
        }

        if (!class_exists('ZipArchive')) {
            throw new Exception("La clase ZipArchive no está disponible en este servidor.");
        }

        // Crear un archivo temporal para ir escribiendo el contenido
        $this->tempFile = tempnam(sys_get_temp_dir(), 'export_zip_');
        $this->tempFp = fopen($this->tempFile, 'w+');

        if (!$this->tempFp) {
            throw new Exception("No se pudo crear el archivo temporal de recolección para el ZIP.");
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function write(string $content): void
    {
        if ($this->tempFp) {
            fwrite($this->tempFp, $content);
        }
    }

    /**
     * @inheritDoc
     */
    public function finalize(): bool
    {
        if (!$this->tempFp) return false;

        fclose($this->tempFp);
        $this->tempFp = null;

        $zip = new ZipArchive();
        if ($zip->open($this->zipFilename, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $zip->addFile($this->tempFile, basename($this->internalFilename));
            $zip->close();
            
            // Limpiar archivo temporal
            if (file_exists($this->tempFile)) {
                unlink($this->tempFile);
            }
            
            return true;
        }

        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }

        throw new Exception("No se pudo abrir o crear el archivo ZIP: {$this->zipFilename}");
    }

    /**
     * Devuelve el nombre del archivo ZIP generado.
     * 
     * @return string
     */
    public function getFilename(): string
    {
        return $this->zipFilename;
    }
}
