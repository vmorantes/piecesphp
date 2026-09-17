<?php

/**
 * ImportReport.php
 */

namespace PiecesPHP\Core\DataTransfer\Import;

/**
 * ImportReport - El resultado de una importación. El JSON lleva solo las filas con errores y nunca los artefactos.
 *
 * @package     PiecesPHP\Core\DataTransfer\Import
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class ImportReport implements \JsonSerializable
{
    /**
     * @var int
     */
    private $totalRows;

    /**
     * @var RowResult[]
     */
    private $rowResults;

    /**
     * @var bool
     */
    private $persisted;

    /**
     * @var string[]
     */
    private $headerErrors;

    /**
     * @var ImportArtifacts|null
     */
    private $artifacts;

    /**
     * @param int $totalRows
     * @param RowResult[] $rowResults
     * @param bool $persisted
     * @param string[] $headerErrors
     * @param ImportArtifacts|null $artifacts
     */
    public function __construct(int $totalRows, array $rowResults, bool $persisted, array $headerErrors = [], ?ImportArtifacts $artifacts = null)
    {
        $this->totalRows = $totalRows;
        $this->rowResults = array_values(array_filter($rowResults, fn($r) => $r instanceof RowResult));
        $this->persisted = $persisted;
        $this->headerErrors = array_values($headerErrors);
        $this->artifacts = $artifacts;
    }

    /**
     * @return int
     */
    public function totalRows(): int
    {
        return $this->totalRows;
    }

    /**
     * @return int
     */
    public function validRows(): int
    {
        return count(array_filter($this->rowResults, fn(RowResult $r) => $r->isValid()));
    }

    /**
     * @return int
     */
    public function invalidRows(): int
    {
        return count($this->rowResults) - $this->validRows();
    }

    /**
     * @return RowResult[]
     */
    public function rowResults(): array
    {
        return $this->rowResults;
    }

    /**
     * @return bool
     */
    public function persisted(): bool
    {
        return $this->persisted;
    }

    /**
     * @return string[]
     */
    public function headerErrors(): array
    {
        return $this->headerErrors;
    }

    /**
     * @return ImportArtifacts|null
     */
    public function artifacts(): ?ImportArtifacts
    {
        return $this->artifacts;
    }

    /**
     * @return array{total:int,valid:int,invalid:int,persisted:bool,headerErrors:string[],rows:array<int,array{position:int,errors:string[]}>}
     */
    public function jsonSerialize(): array
    {
        $rows = [];
        foreach ($this->rowResults as $result) {
            if (!$result->isValid()) {
                $rows[] = ['position' => $result->position(), 'errors' => $result->errors()];
            }
        }
        return [
            'total' => $this->totalRows,
            'valid' => $this->validRows(),
            'invalid' => $this->invalidRows(),
            'persisted' => $this->persisted,
            'headerErrors' => $this->headerErrors,
            'rows' => $rows,
        ];
    }
}
