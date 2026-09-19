<?php

namespace PiecesPHP\Core\Database\Export\Plugins;

use PiecesPHP\Core\Database\Export\Interfaces\OutputPluginInterface;
use Exception;

/**
 * Class Bz2FileOutput
 * 
 * Plugin de salida para archivos comprimidos con Bzip2.
 */
class Bz2FileOutput implements OutputPluginInterface
{
    /** @var string Ruta completa al archivo de salida */
    protected string $filename = '';
    /** @var resource|null Puntero al archivo bz2 */
    protected $fp = null;

    /**
     * @inheritDoc
     */
    public function init(array $options): bool
    {
        $this->filename = !empty($options['filename']) ? $options['filename'] : 'database.sql';
        
        if (strtolower(substr($this->filename, -4)) !== '.bz2') {
            $this->filename .= '.bz2';
        }

        if (!function_exists('bzopen')) {
            throw new Exception("La extensión Bzip2 no está habilitada en este servidor.");
        }

        $this->fp = bzopen($this->filename, 'w');
        
        if (!$this->fp) {
            throw new Exception("No se pudo abrir el archivo para escritura: {$this->filename}");
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function write(string $content): void
    {
        if ($this->fp) {
            bzwrite($this->fp, $content);
        }
    }

    /**
     * @inheritDoc
     */
    public function finalize(): bool
    {
        if ($this->fp) {
            bzclose($this->fp);
            $this->fp = null;
            return true;
        }
        return false;
    }

    /**
     * Devuelve el nombre del archivo generado.
     * 
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }
}
