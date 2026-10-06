<?php

/**
 * ExportDefinition.php
 */

namespace PiecesPHP\Core\DataTransfer\Export;

use PiecesPHP\Core\DataTransfer\Import\ImportDefinition;
use PiecesPHP\Core\DataTransfer\InterfaceLevel;
use PiecesPHP\Core\DataTransfer\Import\ImportRunner;

/**
 * ExportDefinition - Lo que un módulo implementa para exportar un tipo de dato.
 *
 * @package     PiecesPHP\Core\DataTransfer\Export
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
abstract class ExportDefinition
{
    /** Solo la ruta de descarga: URL, terminal, integraciones. */
    const INTERFACE_NONE = InterfaceLevel::NONE;
    /** Formulario generado desde parameters() y descarga. */
    const INTERFACE_AUTO = InterfaceLevel::AUTO;
    /** Lo del AUTO, más vista previa, elegir y ordenar columnas y el hueco formPartial(). */
    const INTERFACE_EXTENDED = InterfaceLevel::EXTENDED;
    /** La definición pone su vista (customView()) sobre las mismas rutas. */
    const INTERFACE_CUSTOM = InterfaceLevel::CUSTOM;

    const PREVIEW_ROWS = 20;

    /** Claves de la URL que no puede usar un parámetro. */
    const RESERVED_QUERY_KEYS = ['format', 'columns'];

    /**
     * @return string
     */
    abstract public function key(): string;

    /**
     * @return string
     */
    abstract public function title(): string;

    /**
     * Una frase para la portada; vacía si no hace falta.
     *
     * @return string
     */
    public function description(): string
    {
        return '';
    }

    /**
     * @return int[]
     */
    abstract public function allowedUserTypes(): array;

    /**
     * Los filtros que acepta; por defecto, ninguno.
     *
     * @return ExportParameter[]
     */
    public function parameters(): array
    {
        return [];
    }

    /**
     * @param ExportContext $context
     * @return ExportColumn[]
     */
    abstract public function columns(ExportContext $context): array;

    /**
     * @param ExportContext $context
     * @return iterable<array<string,mixed>>
     */
    abstract public function rows(ExportContext $context): iterable;

    /**
     * @return int Una de las constantes INTERFACE_*
     */
    public function interfaceLevel(): int
    {
        return self::INTERFACE_AUTO;
    }

    /**
     * Nivel 2 o más: un .php del proyecto que el formulario incluye tras los filtros,
     * con acceso a $definition, $parameters y $langGroup.
     *
     * @return string|null
     */
    public function formPartial(): ?string
    {
        return null;
    }

    /**
     * Nivel 3, obligatoria: la vista .php del proyecto que sustituye al formulario.
     *
     * @return string|null
     */
    public function customView(): ?string
    {
        return null;
    }

    /**
     * Comprueba el nivel de interfaz y, en el 3, la vista propia. Se llama al registrar.
     *
     * @return void
     * @throws \InvalidArgumentException error de quien programa
     */
    final public function checkInterface(): void
    {
        InterfaceLevel::check($this->interfaceLevel(), $this->customView(), $this->key());
    }

    /**
     * Estilo de las hojas del XLSX que no traen el suyo.
     *
     * @param ExportContext $context
     * @return ExportStyle
     */
    public function style(ExportContext $context): ExportStyle
    {
        return ExportStyle::report();
    }

    /**
     * La hoja de columns()/rows(), la misma tabla que el CSV.
     *
     * @param ExportContext $context
     * @return ExportSheet
     */
    final public function mainSheet(ExportContext $context): ExportSheet
    {
        $columns = $this->columns($context);
        $selected = $context->selectedColumns();
        if ($selected === null) {
            return new ExportSheet($this->title(), $columns, $this->rows($context));
        }
        //El único sitio donde se aplica la elección: XLSX, CSV y vista previa pasan por aquí.
        $byKey = [];
        foreach ($columns as $column) {
            $byKey[$column->key()] = $column;
        }
        $chosen = [];
        foreach ($selected as $key) {
            if (array_key_exists($key, $byKey)) {
                $chosen[] = $byKey[$key];
            }
        }
        $dropped = array_values(array_diff(array_keys($byKey), $selected));
        return (new ExportSheet($this->title(), $chosen, $this->rows($context)))->dropColumns($dropped);
    }

    /**
     * Las hojas del XLSX, en orden; la primera es la activa al abrir.
     *
     * @param ExportContext $context
     * @return ExportSheet[]
     */
    public function sheets(ExportContext $context): array
    {
        return [$this->mainSheet($context)];
    }

    /**
     * Vía de escape: el libro ya montado, antes de guardarse. Solo XLSX.
     *
     * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $book
     * @param ExportContext $context
     * @return void
     */
    public function afterBuild(\PhpOffice\PhpSpreadsheet\Spreadsheet $book, ExportContext $context): void
    {
    }

    /**
     * Acción tras generar el archivo y antes de enviarlo (p. ej. marcar como exportados los registros incluidos).
     *
     * Si lanza, NO se descarga nada: la respuesta es un 500 genérico y la excepción se registra. Por eso la acción
     * debe ser atómica (una transacción): o se aplica entera con la descarga, o no se aplica.
     *
     * @param ExportContext $context
     * @param ExportResult $result
     * @return void
     */
    public function afterExport(ExportContext $context, ExportResult $result): void
    {
    }

    /**
     * Doble vía declarada: la clase ImportDefinition que puede reimportar este archivo; null si no hay.
     *
     * @return string|null
     */
    public function importDefinition(): ?string
    {
        return null;
    }

    /**
     * Lo que impide reimportar la hoja principal con importDefinition(); vacío si cuadra o si no declara importador.
     *
     * @param ExportContext $context
     * @return string[]
     */
    final public function roundTripProblems(ExportContext $context): array
    {
        $importClass = $this->importDefinition();
        if ($importClass === null) {
            return [];
        }
        if (!class_exists($importClass) || !is_subclass_of($importClass, ImportDefinition::class)) {
            return ["«{$importClass}» no extiende " . ImportDefinition::class . '.'];
        }
        /** @var ImportDefinition $import */
        $import = new $importClass();

        $problems = [];
        $importColumns = [];
        foreach ($import->columns() as $column) {
            $importColumns[$column->key()] = $column;
        }
        $exported = [];
        foreach ($this->columns($context) as $column) {
            $exported[$column->key()] = true;
            if (!array_key_exists($column->key(), $importColumns)) {
                $problems[] = "La columna exportada «{$column->key()}» no existe en el importador.";
            }
            //La reimportación leería el resultado de la fórmula, no el dato.
            if ($column->formulaTemplate() !== null) {
                $problems[] = "La columna exportada «{$column->key()}» es una fórmula.";
            }
        }
        foreach ($importColumns as $key => $column) {
            if ($column->required() && !array_key_exists($key, $exported)) {
                $problems[] = "La columna obligatoria «{$key}» del importador no se exporta.";
            }
        }
        return $problems;
    }

    /**
     * Nombre del archivo descargado, sin extensión: la pone el controlador según el formato.
     *
     * @param ExportContext $context
     * @return string
     */
    public function fileName(ExportContext $context): string
    {
        return $this->key() . '-' . date('Ymd-His');
    }

    /**
     * Valida TODOS los filtros y junta TODOS los errores en una sola excepción.
     *
     * @param array<string,mixed> $query
     * @param object|null $user
     * @return ExportContext
     * @throws ExportParameterException con los errores del usuario
     * @throws \InvalidArgumentException si dos filtros comparten una clave de la URL o usan «format» (error de quien programa)
     */
    final public function buildContext(array $query, ?object $user): ExportContext
    {
        $parameters = $this->parameters();

        //«format» elige xlsx o csv y «columns» las columnas, en la misma URL.
        $taken = array_combine(self::RESERVED_QUERY_KEYS, self::RESERVED_QUERY_KEYS);
        foreach ($parameters as $parameter) {
            foreach ($parameter->queryKeys() as $queryKey) {
                if (array_key_exists($queryKey, $taken)) {
                    throw new \InvalidArgumentException("La clave «{$queryKey}» de «{$parameter->key()}» ya la usa «{$taken[$queryKey]}».");
                }
                $taken[$queryKey] = $parameter->key();
            }
        }

        $values = [];
        $errors = [];
        foreach ($parameters as $parameter) {
            try {
                $values[$parameter->key()] = $parameter->parse($query);
            } catch (ExportParameterException $e) {
                $errors = array_merge($errors, $e->errors());
            }
        }
        if (count($errors) > 0) {
            throw new ExportParameterException($errors);
        }

        $byKey = [];
        foreach ($parameters as $parameter) {
            $byKey[$parameter->key()] = $parameter;
        }
        $context = new ExportContext($values, $user, $byKey);
        $selected = $this->parseSelectedColumns($query['columns'] ?? null, $context);
        return $selected === null ? $context : new ExportContext($values, $user, $byKey, $selected);
    }

    /**
     * «columns[]=a&columns[]=b» o «columns=a,b»: orden del archivo, sin repetidos; vacío o ausente → null (todas).
     *
     * @param mixed $raw
     * @param ExportContext $context
     * @return string[]|null
     * @throws ExportParameterException
     */
    private function parseSelectedColumns($raw, ExportContext $context): ?array
    {
        $items = is_array($raw) ? $raw : (is_scalar($raw) ? explode(',', (string) $raw) : []);
        $keys = [];
        foreach ($items as $item) {
            $key = is_scalar($item) ? trim((string) $item) : '';
            if ($key !== '' && !in_array($key, $keys, true)) {
                $keys[] = $key;
            }
        }
        if (count($keys) === 0) {
            return null;
        }
        if ($this->interfaceLevel() < self::INTERFACE_EXTENDED) {
            throw new ExportParameterException([__(ImportRunner::LANG_GROUP, 'Este exportador no permite elegir columnas.')]);
        }

        $declared = [];
        foreach ($this->columns($context) as $column) {
            $declared[$column->key()] = $column;
        }
        $errors = [];
        foreach ($keys as $key) {
            if (!array_key_exists($key, $declared)) {
                $errors[] = sprintf(__(ImportRunner::LANG_GROUP, 'La columna «%s» no existe.'), $key);
            }
        }
        foreach ($keys as $key) {
            foreach (array_key_exists($key, $declared) ? $declared[$key]->formulaKeys() : [] as $needed) {
                if (!in_array($needed, $keys, true)) {
                    $label = array_key_exists($needed, $declared) ? $declared[$needed]->label() : $needed;
                    $errors[] = sprintf(__(ImportRunner::LANG_GROUP, 'La columna %s necesita la columna %s.'), $declared[$key]->label(), $label);
                }
            }
        }
        if (count($errors) > 0) {
            throw new ExportParameterException($errors);
        }
        return $keys;
    }
}
