<?php

namespace PiecesPHP\Core\Database\Export\Plugins;

use PiecesPHP\Core\Database\Export\Interfaces\OutputPluginInterface;

/**
 * Class MemoryOutput
 * 
 * Plugin para exportar a un string en memoria.
 */
class MemoryOutput implements OutputPluginInterface
{
    protected string $buffer = "";

    /**
     * @inheritDoc
     */
    public function init(array $options): bool
    {
        $this->buffer = "";
        return true;
    }

    /**
     * @inheritDoc
     */
    public function write(string $data): void
    {
        $this->buffer .= $data;
    }

    /**
     * @inheritDoc
     */
    public function finalize(): mixed
    {
        return $this->buffer;
    }

    /**
     * @inheritDoc
     */
    public function getFilename(): ?string
    {
        return null;
    }
}
