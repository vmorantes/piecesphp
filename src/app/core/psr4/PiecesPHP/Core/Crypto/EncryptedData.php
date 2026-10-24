<?php

/**
 * EncryptedData.php
 */
namespace PiecesPHP\Core\Crypto;

/**
 * EncryptedData - Un sitio donde se guarda algo cifrado con Sealed (ADR 0061 §6): una columna de una tabla, una opción
 * de la configuración, o nada (un propósito que no se guarda, como los datos del token).
 *
 * rotate-app-key lee cada sitio con la clave vieja y lo guarda con la nueva; retire-app-key mira las huellas de lo
 * guardado antes de quitar una clave.
 *
 * @package     PiecesPHP\Core\Crypto
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class EncryptedData
{
    const KIND_COLUMN = 'column';
    const KIND_OPTION = 'option';
    const KIND_NOT_STORED = 'not-stored';

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $purpose;

    /**
     * @var string
     */
    private $kind;

    /**
     * @var string|null
     */
    private $table;

    /**
     * @var string|null
     */
    private $column;

    /**
     * @var string
     */
    private $primaryKey;

    /**
     * @var string|null
     */
    private $option;

    /**
     * @var (callable(string,AppKeyring):(string|null))|null
     */
    private $legacyOpen;

    /**
     * @param string $key kebab-case
     * @param string $purpose El de Sealed
     * @param string $kind KIND_*
     * @param string|null $tableOrOption La tabla (KIND_COLUMN) o el nombre de la opción (KIND_OPTION)
     * @param string|null $column La columna (KIND_COLUMN)
     * @param string $primaryKey La clave primaria de la tabla (KIND_COLUMN)
     * @param (callable(string,AppKeyring):(string|null))|null $legacyOpen Lee lo guardado en un formato anterior a
     * Sealed; null si no lo hay
     * @throws \InvalidArgumentException
     */
    public function __construct(string $key, string $purpose, string $kind, ?string $tableOrOption = null, ?string $column = null, string $primaryKey = 'id', ?callable $legacyOpen = null)
    {
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $key) !== 1 || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $purpose) !== 1) {
            throw new \InvalidArgumentException("La clave «{$key}» y el propósito «{$purpose}» van en kebab-case.");
        }
        if (!in_array($kind, [self::KIND_COLUMN, self::KIND_OPTION, self::KIND_NOT_STORED], true)) {
            throw new \InvalidArgumentException("Tipo «{$kind}» no válido.");
        }
        $identifier = '/^[A-Za-z_][A-Za-z0-9_]*$/';
        if ($kind === self::KIND_COLUMN && (preg_match($identifier, (string) $tableOrOption) !== 1 || preg_match($identifier, (string) $column) !== 1 || preg_match($identifier, $primaryKey) !== 1)) {
            throw new \InvalidArgumentException("El sitio «{$key}» necesita tabla, columna y clave primaria válidas: van dentro del SQL.");
        }
        if ($kind === self::KIND_OPTION && ($tableOrOption === null || trim($tableOrOption) === '')) {
            throw new \InvalidArgumentException("El sitio «{$key}» necesita el nombre de la opción.");
        }
        $this->key = $key;
        $this->purpose = $purpose;
        $this->kind = $kind;
        $this->table = $kind === self::KIND_COLUMN ? $tableOrOption : null;
        $this->column = $kind === self::KIND_COLUMN ? $column : null;
        $this->primaryKey = $primaryKey;
        $this->option = $kind === self::KIND_OPTION ? $tableOrOption : null;
        $this->legacyOpen = $legacyOpen;
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
    public function purpose(): string
    {
        return $this->purpose;
    }

    /**
     * @return string
     */
    public function kind(): string
    {
        return $this->kind;
    }

    /**
     * @return string|null
     */
    public function table(): ?string
    {
        return $this->table;
    }

    /**
     * @return string|null
     */
    public function column(): ?string
    {
        return $this->column;
    }

    /**
     * @return string
     */
    public function primaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * @return string|null
     */
    public function option(): ?string
    {
        return $this->option;
    }

    /**
     * Lo guardado en claro: Sealed si tiene su formato, y si no el lector del formato anterior. null si no se lee.
     *
     * @param string $stored
     * @param AppKeyring $keyring
     * @return string|null
     */
    public function read(string $stored, AppKeyring $keyring): ?string
    {
        if (Sealed::isSealed($stored)) {
            return Sealed::open($stored, $this->purpose, $keyring);
        }
        return $this->legacyOpen !== null ? ($this->legacyOpen)($stored, $keyring) : null;
    }
}
