<?php

/**
 * ImportArtifacts.php
 */

namespace PiecesPHP\Core\DataTransfer\Import;

/**
 * ImportArtifacts - Lo que la persistencia entrega UNA sola vez (p. ej. credenciales generadas).
 *
 * El contenido no sale por serialize(), var_dump(), print_r() ni JSON: solo por content(), para descargarlo y olvidarlo.
 *
 * @package     PiecesPHP\Core\DataTransfer\Import
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class ImportArtifacts
{
    /**
     * @var string
     */
    private $filename;

    /**
     * @var string
     */
    private $mimeType;

    /**
     * @var string
     */
    private $content;

    /**
     * @param string $filename
     * @param string $mimeType
     * @param string $content
     */
    public function __construct(string $filename, string $mimeType, string $content)
    {
        $this->filename = $filename;
        $this->mimeType = $mimeType;
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function filename(): string
    {
        return $this->filename;
    }

    /**
     * @return string
     */
    public function mimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * @return string
     */
    public function content(): string
    {
        return $this->content;
    }

    /**
     * @return array{filename:string,mimeType:string}
     */
    public function __serialize(): array
    {
        return ['filename' => $this->filename, 'mimeType' => $this->mimeType];
    }

    /**
     * @param array{filename?:string,mimeType?:string} $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->filename = (string) ($data['filename'] ?? '');
        $this->mimeType = (string) ($data['mimeType'] ?? '');
        $this->content = '';
    }

    /**
     * @return array{filename:string,mimeType:string}
     */
    public function __debugInfo(): array
    {
        return ['filename' => $this->filename, 'mimeType' => $this->mimeType];
    }
}
