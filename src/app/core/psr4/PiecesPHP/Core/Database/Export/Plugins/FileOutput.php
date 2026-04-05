<?php

namespace PiecesPHP\Core\Database\Export\Plugins;

use PiecesPHP\Core\Database\Export\Interfaces\OutputPluginInterface;

/**
 * Class FileOutput
 * 
 * Plugin para exportar a un archivo de texto plano.
 */
class FileOutput implements OutputPluginInterface
{
    protected $fp = null;
    protected ?string $filename = null;

    /**
     * @inheritDoc
     */
    public function init(array $options): bool
    {
        $this->filename = $options['filename'] ?? 'export.sql';
        $this->fp = fopen($this->filename, 'w');
        return (bool) $this->fp;
    }

    /**
     * @inheritDoc
     */
    public function write(string $data): void
    {
        if ($this->fp) {
            fwrite($this->fp, $data);
        }
    }

    /**
     * @inheritDoc
     */
    public function finalize(): mixed
    {
        if ($this->fp) {
            fclose($this->fp);
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }
}
