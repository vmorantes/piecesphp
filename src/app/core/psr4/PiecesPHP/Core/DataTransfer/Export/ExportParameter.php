<?php

/**
 * ExportParameter.php
 */

namespace PiecesPHP\Core\DataTransfer\Export;

use PiecesPHP\Core\DataTransfer\Import\ImportRunner;

/**
 * ExportParameter - Un filtro que la exportación declara: tipo, etiqueta, obligatorio, valor por defecto y ayuda.
 *
 * Un valor inválido es un error con su mensaje, nunca un valor silencioso.
 *
 * @package     PiecesPHP\Core\DataTransfer\Export
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class ExportParameter
{
    const TYPE_TEXT = 'text';
    const TYPE_INTEGER = 'integer';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_CHOICE = 'choice';
    const TYPE_MULTI_CHOICE = 'multiChoice';
    const TYPE_DATE = 'date';
    const TYPE_DATE_RANGE = 'dateRange';

    const MAX_TEXT_LENGTH = 500;

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $label;

    /**
     * @var string
     */
    private $type;

    /**
     * @var array<int|string,string>
     */
    private $options;

    /**
     * @var int|null
     */
    private $min;

    /**
     * @var int|null
     */
    private $max;

    /**
     * @var bool
     */
    private $required = false;

    /**
     * @var mixed
     */
    private $default = null;

    /**
     * @var string|null
     */
    private $help = null;

    /**
     * @param string $key
     * @param string $label
     * @param string $type
     * @param array<int|string,string> $options
     * @param int|null $min
     * @param int|null $max
     */
    private function __construct(string $key, string $label, string $type, array $options = [], ?int $min = null, ?int $max = null)
    {
        //La key va a la URL y al nombre del campo del formulario.
        if (preg_match('/^[a-z][a-z0-9_]*$/', $key) !== 1) {
            throw new \InvalidArgumentException("La key «{$key}» del parámetro no es válida: /^[a-z][a-z0-9_]*$/.");
        }
        if (in_array($type, [self::TYPE_CHOICE, self::TYPE_MULTI_CHOICE], true) && count($options) === 0) {
            throw new \InvalidArgumentException("El parámetro «{$key}» no tiene opciones.");
        }
        $this->key = $key;
        $this->label = $label;
        $this->type = $type;
        $this->options = $options;
        $this->min = $min;
        $this->max = $max;
    }

    /**
     * @param string $key
     * @param string $label
     * @return self
     */
    public static function text(string $key, string $label): self
    {
        return new self($key, $label, self::TYPE_TEXT);
    }

    /**
     * @param string $key
     * @param string $label
     * @param int|null $min
     * @param int|null $max
     * @return self
     */
    public static function integer(string $key, string $label, ?int $min = null, ?int $max = null): self
    {
        return new self($key, $label, self::TYPE_INTEGER, [], $min, $max);
    }

    /**
     * Tres estados: sí, no o sin filtro (null).
     *
     * @param string $key
     * @param string $label
     * @return self
     */
    public static function boolean(string $key, string $label): self
    {
        return new self($key, $label, self::TYPE_BOOLEAN);
    }

    /**
     * @param string $key
     * @param string $label
     * @param array<int|string,string> $options valor => etiqueta
     * @return self
     */
    public static function choice(string $key, string $label, array $options): self
    {
        return new self($key, $label, self::TYPE_CHOICE, $options);
    }

    /**
     * @param string $key
     * @param string $label
     * @param array<int|string,string> $options valor => etiqueta
     * @return self
     */
    public static function multiChoice(string $key, string $label, array $options): self
    {
        return new self($key, $label, self::TYPE_MULTI_CHOICE, $options);
    }

    /**
     * @param string $key
     * @param string $label
     * @return self
     */
    public static function date(string $key, string $label): self
    {
        return new self($key, $label, self::TYPE_DATE);
    }

    /**
     * En la URL son dos claves: {key}_from y {key}_to.
     *
     * @param string $key
     * @param string $label
     * @return self
     */
    public static function dateRange(string $key, string $label): self
    {
        return new self($key, $label, self::TYPE_DATE_RANGE);
    }

    /**
     * @param bool $required
     * @return static
     */
    public function required(bool $required = true): static
    {
        $this->required = $required;
        return $this;
    }

    /**
     * @param mixed $value
     * @return static
     */
    public function defaultValue($value): static
    {
        $this->default = $value;
        return $this;
    }

    /**
     * @param string $text
     * @return static
     */
    public function help(string $text): static
    {
        $this->help = $text;
        return $this;
    }

    /**
     * @return string
     */
    public function key(): string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function label(): string
    {
        return $this->label;
    }

    /**
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * @return array<int|string,string>
     */
    public function options(): array
    {
        return $this->options;
    }

    /**
     * @return int|null
     */
    public function min(): ?int
    {
        return $this->min;
    }

    /**
     * @return int|null
     */
    public function max(): ?int
    {
        return $this->max;
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @return string|null
     */
    public function helpText(): ?string
    {
        return $this->help;
    }

    /**
     * @return string[]
     */
    public function queryKeys(): array
    {
        return $this->type === self::TYPE_DATE_RANGE ? ["{$this->key}_from", "{$this->key}_to"] : [$this->key];
    }

    /**
     * Lee el valor de la query y lo devuelve ya tipado.
     *
     * @param array<string,mixed> $query
     * @return mixed
     * @throws ExportParameterException
     */
    public function parse(array $query)
    {
        if ($this->type === self::TYPE_DATE_RANGE) {
            return $this->parseDateRange($query);
        }

        $raw = $query[$this->key] ?? null;
        $isEmpty = $raw === null || (is_string($raw) && trim($raw) === '') || (is_array($raw) && count($raw) === 0);

        if ($isEmpty) {
            $value = $this->default ?? ($this->type === self::TYPE_MULTI_CHOICE ? [] : null);
            if ($this->required && ($value === null || $value === [])) {
                $this->fail(__(ImportRunner::LANG_GROUP, 'obligatorio'));
            }
            return $value;
        }

        switch ($this->type) {
            case self::TYPE_TEXT:
                return $this->parseText($raw);
            case self::TYPE_INTEGER:
                return $this->parseInteger($raw);
            case self::TYPE_BOOLEAN:
                return $this->parseBoolean($raw);
            case self::TYPE_CHOICE:
                return $this->parseChoice($raw);
            case self::TYPE_MULTI_CHOICE:
                return $this->parseMultiChoice($raw);
            default:
                return $this->parseDate($raw, $this->key);
        }
    }

    /**
     * @param mixed $raw
     * @return string
     */
    private function parseText($raw): string
    {
        if (!is_scalar($raw)) {
            $this->fail(__(ImportRunner::LANG_GROUP, 'debe ser un texto'));
        }
        $value = trim((string) $raw);
        if (mb_strlen($value) > self::MAX_TEXT_LENGTH) {
            $this->fail(sprintf(__(ImportRunner::LANG_GROUP, 'no puede pasar de %d caracteres'), self::MAX_TEXT_LENGTH));
        }
        return $value;
    }

    /**
     * @param mixed $raw
     * @return int
     */
    private function parseInteger($raw): int
    {
        $text = is_scalar($raw) ? trim((string) $raw) : '';
        if (preg_match('/^-?\d+$/', $text) !== 1) {
            $this->fail(__(ImportRunner::LANG_GROUP, 'debe ser un número entero'));
        }
        $value = (int) $text;
        if (($this->min !== null && $value < $this->min) || ($this->max !== null && $value > $this->max)) {
            $this->fail(sprintf(__(ImportRunner::LANG_GROUP, 'debe estar entre %s y %s'), $this->min ?? '−∞', $this->max ?? '∞'));
        }
        return $value;
    }

    /**
     * @param mixed $raw
     * @return bool
     */
    private function parseBoolean($raw): bool
    {
        $text = is_scalar($raw) ? mb_strtolower(trim((string) $raw)) : '';
        if (in_array($text, ['yes', '1', 'true'], true)) {
            return true;
        }
        if (in_array($text, ['no', '0', 'false'], true)) {
            return false;
        }
        $this->fail(__(ImportRunner::LANG_GROUP, 'debe ser sí o no'));
    }

    /**
     * Lista blanca: solo una clave declarada en las opciones.
     *
     * @param mixed $raw
     * @return int|string
     */
    private function parseChoice($raw)
    {
        $text = is_scalar($raw) ? trim((string) $raw) : '';
        foreach (array_keys($this->options) as $option) {
            if ((string) $option === $text) {
                return $option;
            }
        }
        $this->fail(__(ImportRunner::LANG_GROUP, 'no es una opción válida'));
    }

    /**
     * @param mixed $raw
     * @return list<int|string>
     */
    private function parseMultiChoice($raw): array
    {
        $items = is_array($raw) ? $raw : explode(',', is_scalar($raw) ? (string) $raw : '');
        $chosen = [];
        foreach ($items as $item) {
            $text = is_scalar($item) ? trim((string) $item) : '';
            $match = null;
            foreach (array_keys($this->options) as $option) {
                if ((string) $option === $text) {
                    $match = $option;
                    break;
                }
            }
            if ($match === null) {
                $this->fail(sprintf(__(ImportRunner::LANG_GROUP, '«%s» no es una opción válida'), $text));
            }
            if (!in_array($match, $chosen, true)) {
                $chosen[] = $match;
            }
        }
        if ($this->required && count($chosen) === 0) {
            $this->fail(__(ImportRunner::LANG_GROUP, 'obligatorio'));
        }
        return $chosen;
    }

    /**
     * @param mixed $raw
     * @param string $queryKey
     * @return \DateTimeImmutable
     */
    private function parseDate($raw, string $queryKey): \DateTimeImmutable
    {
        $text = is_scalar($raw) ? trim((string) $raw) : '';
        //Estricto: 2026-02-30 se desborda a marzo y el formato de vuelta no coincide.
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $text);
        if ($date === false || $date->format('Y-m-d') !== $text) {
            $this->fail(sprintf(__(ImportRunner::LANG_GROUP, '«%s» no es una fecha válida (AAAA-MM-DD)'), $text));
        }
        return $date;
    }

    /**
     * @param array<string,mixed> $query
     * @return DateRange
     */
    private function parseDateRange(array $query): DateRange
    {
        [$fromKey, $toKey] = $this->queryKeys();
        $read = function (string $queryKey) use ($query): ?\DateTimeImmutable {
            $raw = $query[$queryKey] ?? null;
            if ($raw === null || (is_string($raw) && trim($raw) === '')) {
                return null;
            }
            return $this->parseDate($raw, $queryKey);
        };
        $from = $read($fromKey);
        $to = $read($toKey);

        if ($from === null && $to === null && $this->default instanceof DateRange) {
            return $this->default;
        }
        if ($this->required && $from === null && $to === null) {
            $this->fail(__(ImportRunner::LANG_GROUP, 'obligatorio'));
        }
        //Llegó al revés: se intercambian, y las horas van con el papel, no con la fecha.
        if ($from !== null && $to !== null && $from > $to) {
            [$from, $to] = [$to, $from];
        }
        return new DateRange(
            $from !== null ? $from->setTime(0, 0, 0) : null,
            $to !== null ? $to->setTime(23, 59, 59) : null
        );
    }

    /**
     * @param string $message
     * @return never
     * @throws ExportParameterException
     */
    private function fail(string $message): never
    {
        throw new ExportParameterException([sprintf('%s: %s', $this->label, $message)]);
    }
}
