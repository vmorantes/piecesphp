<?php

/**
 * ResourceContract.php
 */

namespace PiecesPHP\PublicSite;

/**
 * ResourceContract - Un recurso de <pcs-list>: su nombre, sus campos (tipo y ejemplo) y la función que da los datos
 * reales al que pide. De los campos salen los data/<recurso>.js de la vista previa; un campo que no declara no existe.
 *
 * @package     PiecesPHP\PublicSite
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class ResourceContract
{

    const TYPE_TEXT = 'text';
    const TYPE_URL = 'url';
    const TYPE_IMAGE = 'image';
    const TYPE_DATE = 'date';
    const TYPE_NUMBER = 'number';

    const TYPES = [self::TYPE_TEXT, self::TYPE_URL, self::TYPE_IMAGE, self::TYPE_DATE, self::TYPE_NUMBER];

    /**
     * @var callable(int,MarkContext):array<int,array<string,scalar|null>>
     */
    private $provider;

    /**
     * @param string $name Con forma de nombre (minúsculas, números y guiones)
     * @param array<string,array{type:string,example:scalar}> $fields
     * @param callable(int,MarkContext):array<int,array<string,scalar|null>> $provider Hasta $count filas para quien pide:
     *        filtra ella por permisos y visibilidad, porque la marca pinta lo que reciba
     * @param array<int,array<string,scalar>> $examples Las filas de ejemplo; vacío, tres hechas con los ejemplos de los campos
     * @throws \InvalidArgumentException Si el nombre, un campo o un tipo no tienen la forma permitida
     */
    public function __construct(
        public readonly string $name,
        public readonly array $fields,
        callable $provider,
        public readonly array $examples = [],
    ) {
        if (!PublicSite::isValidName($name)) {
            throw new \InvalidArgumentException("El nombre del recurso «{$name}» no es válido: solo minúsculas, números y guiones.");
        }
        foreach ($fields as $field => $definition) {
            if (!is_string($field) || preg_match('/' . MarkRules::fieldPattern() . '/D', $field) !== 1) {
                throw new \InvalidArgumentException("El campo «{$field}» del recurso «{$name}» no es válido: letra inicial y luego letras, números o _.");
            }
            if (!is_array($definition) || !in_array($definition['type'] ?? null, self::TYPES, true) || !array_key_exists('example', $definition)) {
                throw new \InvalidArgumentException("El campo «{$field}» del recurso «{$name}» necesita «type» (" . implode(', ', self::TYPES) . ') y «example».');
            }
        }
        $this->provider = $provider;
    }

    /**
     * Las filas reales, solo con los campos declarados. Si la función lanza, se registra y no hay filas.
     *
     * @param int $count
     * @param MarkContext $context
     * @return array<int,array<string,string>>
     */
    public function rows(int $count, MarkContext $context): array
    {
        try {
            $rows = ($this->provider)($count, $context);
        } catch (\Throwable $e) {
            log_exception($e);
            return [];
        }
        $clean = [];
        foreach (is_array($rows) ? array_slice(array_values($rows), 0, $count) : [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $item = [];
            foreach ($this->fields as $field => $definition) {
                $value = $row[$field] ?? '';
                $item[$field] = is_scalar($value) ? (string) $value : '';
            }
            $clean[] = $item;
        }
        return $clean;
    }

    /**
     * Las filas de ejemplo de data/<recurso>.js.
     *
     * @return array<int,array<string,scalar>>
     */
    public function exampleRows(): array
    {
        if ($this->examples !== []) {
            return array_values($this->examples);
        }
        $rows = [];
        for ($i = 1; $i <= 3; $i++) {
            $row = [];
            foreach ($this->fields as $field => $definition) {
                $example = $definition['example'];
                $row[$field] = $definition['type'] === self::TYPE_TEXT && is_string($example) ? "{$example} {$i}" : $example;
            }
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * El data/<recurso>.js que lee la vista previa (contrato con el kit del diseñador).
     *
     * @return string
     */
    public function exampleScript(): string
    {
        $data = [
            'fields' => $this->fields,
            'items' => $this->exampleRows(),
        ];
        $flags = \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_HEX_TAG | \JSON_THROW_ON_ERROR;
        return "window.PCS_DATA = window.PCS_DATA || {};\nwindow.PCS_DATA[" . json_encode($this->name, $flags) . '] = ' . json_encode($data, $flags) . ";\n";
    }
}
