<?php

namespace PiecesPHP\Core\Database\Export\Plugins;

use PiecesPHP\Core\Database\Export\Interfaces\OutputPluginInterface;

/**
 * Class GzipFileOutput
 * 
 * Plugin para exportar a un archivo comprimido GZIP.
 */
class GzipFileOutput implements OutputPluginInterface
{
    protected $fp = null;
    protected ?string $filename = null;

    /**
     * @inheritDoc
     */
    public function init(array $options): bool
    {
        $this->filename = $options['filename'] ?? 'export.sql';
        if (substr($this->filename, -3) !== '.gz') {
            $this->filename .= '.gz';
        }
        $this->fp = gzopen($this->filename, 'w9');
        return (bool) $this->fp;
    }

    /**
     * @inheritDoc
     */
    public function write(string $data): void
    {
        if ($this->fp) {
            gzwrite($this->fp, $data);
        }
    }

    /**
     * @inheritDoc
     */
    public function finalize(): mixed
    {
        if ($this->fp) {
            gzclose($this->fp);
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
