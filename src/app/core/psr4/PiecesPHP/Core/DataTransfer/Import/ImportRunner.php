<?php

/**
 * ImportRunner.php
 */

namespace PiecesPHP\Core\DataTransfer\Import;

use PiecesPHP\Core\DataTransfer\Source\RowSource;

/**
 * ImportRunner - Lee, valida todas las filas y persiste el archivo entero solo si ninguna tiene errores.
 *
 * @package     PiecesPHP\Core\DataTransfer\Import
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class ImportRunner
{
    //Grupo propio sin inyector, como UploadedFileAdapter: los textos ya están en español.
    const LANG_GROUP = 'DataTransfer';

    /**
     * @param ImportDefinition $definition
     * @param RowSource $source
     * @return ImportReport
     */
    public function run(ImportDefinition $definition, RowSource $source): ImportReport
    {
        $columns = $definition->columns();

        //Cabecera del archivo → key de la columna declarada. Lo no declarado no entra.
        $columnByIndex = [];
        $lookup = [];
        foreach ($columns as $column) {
            foreach (array_merge([$column->key(), $column->label()], $column->aliases()) as $name) {
                $lookup[self::normalize($name)] = $column->key();
            }
        }
        foreach ($source->headers() as $index => $header) {
            $normalized = self::normalize((string) $header);
            if ($normalized !== '' && isset($lookup[$normalized]) && !in_array($lookup[$normalized], $columnByIndex, true)) {
                $columnByIndex[$index] = $lookup[$normalized];
            }
        }

        $headerErrors = [];
        foreach ($columns as $column) {
            if ($column->required() && !in_array($column->key(), $columnByIndex, true)) {
                $headerErrors[] = sprintf(__(self::LANG_GROUP, '%s: falta la columna obligatoria.'), $column->label());
            }
        }
        if (count($headerErrors) > 0) {
            return new ImportReport(0, [], false, $headerErrors);
        }

        /** @var ParsedRow[] $parsedRows */
        $parsedRows = [];
        $position = 0;
        foreach ($source->rows() as $cells) {
            $position++;
            $values = [];
            foreach ($columns as $column) {
                $values[$column->key()] = null;
            }
            $empty = true;
            foreach ($columnByIndex as $index => $key) {
                $cell = $cells[$index] ?? null;
                $cell = $cell === null ? null : trim((string) $cell);
                $values[$key] = $cell === '' ? null : $cell;
                if ($values[$key] !== null) {
                    $empty = false;
                }
            }
            if ($empty) {
                continue;
            }
            $parsedRows[] = new ParsedRow($position, $values);
        }

        $totalRows = count($parsedRows);
        if ($totalRows > $definition->maxRows()) {
            return new ImportReport($totalRows, [], false, [
                sprintf(__(self::LANG_GROUP, 'El archivo tiene %d filas y el máximo es %d.'), $totalRows, $definition->maxRows()),
            ]);
        }

        $errorsByPosition = [];
        foreach ($parsedRows as $row) {
            $errors = [];
            foreach ($columns as $column) {
                $value = $row->get($column->key());
                if ($value === null) {
                    if ($column->required()) {
                        $errors[] = sprintf(__(self::LANG_GROUP, '%s: obligatorio'), $column->label());
                    }
                    continue;
                }
                $error = $column->validate($value, $row);
                if ($error !== null) {
                    $errors[] = $error;
                }
            }
            $errorsByPosition[$row->position()] = $errors;
        }
        foreach ($definition->validateAll($parsedRows) as $position => $extra) {
            if (array_key_exists($position, $errorsByPosition) && is_array($extra)) {
                $errorsByPosition[$position] = array_merge($errorsByPosition[$position], array_values(array_filter($extra, 'is_string')));
            }
        }

        $rowResults = [];
        $allValid = true;
        foreach ($errorsByPosition as $position => $errors) {
            $rowResults[] = new RowResult($position, $errors);
            if (count($errors) > 0) {
                $allValid = false;
            }
        }

        //Todo o nada por archivo (ADR 0022 §3): con una sola fila inválida no se persiste ninguna.
        if (!$allValid || $totalRows === 0) {
            return new ImportReport($totalRows, $rowResults, false);
        }

        try {
            $artifacts = $definition->persist($parsedRows);
        } catch (ImportPersistException $e) {
            return new ImportReport($totalRows, $rowResults, false, [$e->getMessage()]);
        } catch (\Throwable $e) {
            log_exception($e);
            return new ImportReport($totalRows, $rowResults, false, [
                __(self::LANG_GROUP, 'No se pudo guardar la importación por un error inesperado. No se guardó ninguna fila.'),
            ]);
        }

        return new ImportReport($totalRows, $rowResults, true, [], $artifacts);
    }

    /**
     * @param string $name
     * @return string
     */
    private static function normalize(string $name): string
    {
        return (string) preg_replace('/\s+/u', ' ', mb_strtolower(trim($name)));
    }
}
