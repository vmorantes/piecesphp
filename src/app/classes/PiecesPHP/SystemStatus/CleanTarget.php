<?php

/**
 * CleanTarget.php
 */

namespace PiecesPHP\SystemStatus;

use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * CleanTarget - Una cosa que la pantalla «Estado y cachés» sabe borrar por separado: su botón, quién puede pulsarlo,
 * cómo se borra y, si tiene tamaño, su fila en la lista.
 *
 * @package     PiecesPHP\SystemStatus
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class CleanTarget
{
    /** La fila es un tamaño en bytes (KB o MB). */
    const UNIT_BYTES = 'bytes';
    /** La fila es un número; si declara una segunda clave, es el total del que la primera es parte («N de M»). */
    const UNIT_NUMBER = 'number';
    /** La fila es una marca de texto (la versión de los estáticos). */
    const UNIT_STAMP = 'stamp';

    /**
     * @var string
     */
    private $key;

    /**
     * @var callable():string
     */
    private $label;

    /**
     * @var callable():string
     */
    private $confirm;

    /**
     * @var callable():array<string,mixed>
     */
    private $clean;

    /**
     * @var int
     */
    private $position;

    /**
     * @var int[]
     */
    private $allowedTypes;

    /**
     * @var array{position:int,label:callable():string,hint:(callable():string)|null,unit:string,keys:string[],status:callable():array<string,mixed>,icon:string}|null
     */
    private $row;

    /**
     * @param string $key kebab-case: el `target` que manda el botón
     * @param callable():string $label El texto del botón, ya traducido
     * @param callable():string $confirm La pregunta antes de borrar, ya traducida
     * @param callable():array<string,mixed> $clean Borra y devuelve `before`, `after`, `message` y `freed`, y opcionalmente
     * `extra` (claves que van al acuse) y `log` ([mensaje de LogsMapper, sus parámetros sin usuario ni destino]); o `error`
     * y `httpStatus` si no pudo
     * @param int $position El orden del botón (de menor a mayor)
     * @param int[] $allowedTypes Los tipos de usuario que pueden pulsarlo
     * @throws \InvalidArgumentException
     */
    public function __construct(string $key, callable $label, callable $confirm, callable $clean, int $position, array $allowedTypes = [UsersModel::TYPE_USER_ROOT])
    {
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $key) !== 1) {
            throw new \InvalidArgumentException("La key «{$key}» de la limpieza no está en kebab-case.");
        }
        $allowedTypes = array_values(array_filter($allowedTypes, 'is_int'));
        if (count($allowedTypes) === 0) {
            throw new \InvalidArgumentException("Nadie puede pulsar la limpieza «{$key}».");
        }
        $this->key = $key;
        $this->label = $label;
        $this->confirm = $confirm;
        $this->clean = $clean;
        $this->position = $position;
        $this->allowedTypes = $allowedTypes;
    }

    /**
     * La fila de la lista: lo que ocupa (o cuenta) lo que borra este destino, con su icono.
     *
     * @param int $position El orden de la fila (de menor a mayor)
     * @param callable():string $label El texto de la fila, ya traducido
     * @param string $unit UNIT_BYTES, UNIT_NUMBER o UNIT_STAMP
     * @param string[] $keys Las claves de estado que pinta, en el acuse y en la pantalla
     * @param callable():array<string,mixed> $status Sus claves de estado con su valor (puede dar más de las que pinta)
     * @param (callable():string)|null $hint Una aclaración bajo el texto, ya traducida
     * @param string $icon El icono decorativo de la fila: un nombre de icono de Fomantic («sync», «images»), solo letras
     * minúsculas, números, guiones y espacios; con otro carácter se rechaza, no se limpia
     * @return self
     * @throws \InvalidArgumentException
     */
    public function withRow(int $position, callable $label, string $unit, array $keys, callable $status, ?callable $hint = null, string $icon = 'archive'): self
    {
        if (preg_match('/^[a-z0-9-]+(?: [a-z0-9-]+)*$/', $icon) !== 1) {
            throw new \InvalidArgumentException("Icono «{$icon}» no válido en la fila de «{$this->key}»: un nombre de icono de Fomantic.");
        }
        if (!in_array($unit, [self::UNIT_BYTES, self::UNIT_NUMBER, self::UNIT_STAMP], true)) {
            throw new \InvalidArgumentException("Unidad «{$unit}» no válida en la fila de «{$this->key}»: bytes, number o stamp.");
        }
        $keys = array_values(array_filter($keys, 'is_string'));
        if (count($keys) === 0) {
            throw new \InvalidArgumentException("La fila de «{$this->key}» no pinta ninguna clave de estado.");
        }
        $this->row = ['position' => $position, 'label' => $label, 'hint' => $hint, 'unit' => $unit, 'keys' => $keys, 'status' => $status, 'icon' => $icon];
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
        return (string) ($this->label)();
    }

    /**
     * @return string
     */
    public function confirmation(): string
    {
        return (string) ($this->confirm)();
    }

    /**
     * @return int
     */
    public function position(): int
    {
        return $this->position;
    }

    /**
     * @return int[]
     */
    public function allowedTypes(): array
    {
        return $this->allowedTypes;
    }

    /**
     * @return array<string,mixed>
     */
    public function clean(): array
    {
        return ($this->clean)();
    }

    /**
     * @return bool
     */
    public function hasRow(): bool
    {
        return $this->row !== null;
    }

    /**
     * @return int
     */
    public function rowPosition(): int
    {
        return $this->row['position'] ?? PHP_INT_MAX;
    }

    /**
     * @return string
     */
    public function rowLabel(): string
    {
        return $this->row !== null ? (string) ($this->row['label'])() : '';
    }

    /**
     * @return string
     */
    public function rowHint(): string
    {
        return $this->row !== null && $this->row['hint'] !== null ? (string) ($this->row['hint'])() : '';
    }

    /**
     * @return string
     */
    public function rowUnit(): string
    {
        return $this->row['unit'] ?? '';
    }

    /**
     * @return string El nombre del icono de Fomantic de la fila, o '' si no tiene fila
     */
    public function rowIcon(): string
    {
        return $this->row['icon'] ?? '';
    }

    /**
     * @return string[]
     */
    public function rowKeys(): array
    {
        return $this->row['keys'] ?? [];
    }

    /**
     * @return array<string,mixed>
     */
    public function status(): array
    {
        return $this->row !== null ? ($this->row['status'])() : [];
    }
}
