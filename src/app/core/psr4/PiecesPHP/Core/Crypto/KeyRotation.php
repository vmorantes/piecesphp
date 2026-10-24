<?php

/**
 * KeyRotation.php
 */
namespace PiecesPHP\Core\Crypto;

/**
 * KeyRotation - Cambiar la clave de la aplicación sin perder nada (ADR 0061 §7).
 *
 * rotate(): cada dato declarado en EncryptedDataRegistry se lee con el llavero viejo y se guarda con el nuevo, en UNA
 * transacción; el llavero nuevo se escribe antes de confirmar. Si algo falla, la transacción se deshace y el archivo
 * vuelve a su contenido de antes: no cambia nada.
 * Lo que no se puede leer (otra clave que nadie declaró, un dato dañado) se deja como estaba y se cuenta.
 *
 * retire(): quita una clave anterior solo si ningún dato declarado la usa.
 *
 * @package     PiecesPHP\Core\Crypto
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class KeyRotation
{
    /**
     * La tabla de las opciones (SettingsModel): una opción declarada vive en su columna `value`, como JSON.
     */
    const OPTIONS_TABLE = 'pcsphp_app_config';

    /**
     * @var \PDO
     */
    private $database;

    /**
     * @var array<string,EncryptedData>
     */
    private $sites;

    /**
     * @param \PDO $database
     * @param array<string,EncryptedData>|null $sites Por omisión, EncryptedDataRegistry::all()
     */
    public function __construct(\PDO $database, ?array $sites = null)
    {
        $this->database = $database;
        $this->sites = $sites ?? EncryptedDataRegistry::all();
    }

    /**
     * Vuelve a cifrar todo lo declarado con la clave actual de $to y escribe $to en $keyringPath.
     *
     * @param AppKeyring $from El llavero con que se leen los datos
     * @param AppKeyring $to El llavero nuevo (su actual cifra; debe conservar las anteriores que hagan falta)
     * @param string $keyringPath
     * @return array<string,array{rows:int,resealed:int,unreadable:int,note:string}> Recuento por sitio
     * @throws \Throwable Si algo falla: la base y el llavero quedan como estaban
     */
    public function rotate(AppKeyring $from, AppKeyring $to, string $keyringPath): array
    {
        $previousContent = is_file($keyringPath) ? file_get_contents($keyringPath) : null;
        if ($previousContent === false) {
            throw new \RuntimeException('No se pudo leer el llavero actual para poder deshacer.');
        }
        $report = [];
        $fileWritten = false;
        if (!$this->database->beginTransaction()) {
            throw new \RuntimeException('No se pudo abrir la transacción.');
        }
        try {
            foreach ($this->sites as $key => $site) {
                $report[$key] = $this->resealSite($site, $from, $to);
            }
            if (!$to->save($keyringPath)) {
                throw new \RuntimeException('No se pudo escribir el llavero nuevo.');
            }
            $fileWritten = true;
            if (!$this->database->commit()) {
                throw new \RuntimeException('No se pudo confirmar la transacción.');
            }
        } catch (\Throwable $exception) {
            if ($this->database->inTransaction()) {
                //RETORNO-IGNORADO: ya se está saliendo por una excepción, que es la que sube; deshacer es lo último que queda.
                $this->database->rollBack();
            }
            if ($fileWritten) {
                $this->restoreKeyring($keyringPath, $previousContent, $from);
            }
            throw $exception;
        }
        return $report;
    }

    /**
     * Vuelve a cifrar con la clave ACTUAL solo lo que no está ya con ella: lo de un formato anterior a Sealed (TOTP en
     * claro, cuerpos en AES-CBC, opciones ofuscadas) y lo cifrado con una clave anterior. Sin cambiar la clave ni tocar
     * el llavero, en UNA transacción. Idempotente: la segunda vez no encuentra nada.
     *
     * @param AppKeyring $keyring
     * @return array<string,array{rows:int,resealed:int,unreadable:int,note:string}> Recuento por sitio (rows: los que no estaban al día)
     * @throws \Throwable Si algo falla: la base queda como estaba
     */
    public function sealLegacy(AppKeyring $keyring): array
    {
        $report = [];
        if (!$this->database->beginTransaction()) {
            throw new \RuntimeException('No se pudo abrir la transacción.');
        }
        try {
            foreach ($this->sites as $key => $site) {
                $report[$key] = $this->resealSite($site, $keyring, $keyring, true);
            }
            if (!$this->database->commit()) {
                throw new \RuntimeException('No se pudo confirmar la transacción.');
            }
        } catch (\Throwable $exception) {
            if ($this->database->inTransaction()) {
                //RETORNO-IGNORADO: ya se está saliendo por una excepción, que es la que sube; deshacer es lo último que queda.
                $this->database->rollBack();
            }
            throw $exception;
        }
        return $report;
    }

    /**
     * Cuántos datos declarados usan cada huella, y cuántos están en un formato anterior a Sealed.
     *
     * @return array{fingerprints:array<string,int>,legacy:int}
     */
    public function usage(): array
    {
        $fingerprints = [];
        $legacy = 0;
        foreach ($this->sites as $site) {
            foreach ($this->storedValues($site) as $stored) {
                $fingerprint = Sealed::fingerprintOf($stored);
                if ($fingerprint === null) {
                    $legacy++;
                } else {
                    $fingerprints[$fingerprint] = ($fingerprints[$fingerprint] ?? 0) + 1;
                }
            }
        }
        return ['fingerprints' => $fingerprints, 'legacy' => $legacy];
    }

    /**
     * Quita una clave anterior del llavero si ningún dato declarado la usa. Con datos en un formato anterior a Sealed
     * tampoco: no dicen con qué clave se guardaron.
     *
     * @param AppKeyring $keyring
     * @param string $fingerprint
     * @param string $keyringPath
     * @return string '' si la quitó; si no, por qué no
     */
    public function retire(AppKeyring $keyring, string $fingerprint, string $keyringPath): string
    {
        if (hash_equals($keyring->currentFingerprint(), $fingerprint)) {
            return 'Es la clave actual: no se puede retirar.';
        }
        if (!in_array($fingerprint, $keyring->previousFingerprints(), true)) {
            return 'No hay ninguna clave anterior con esa huella.';
        }
        $usage = $this->usage();
        if (($usage['fingerprints'][$fingerprint] ?? 0) > 0) {
            return 'La usan ' . $usage['fingerprints'][$fingerprint] . ' dato(s) declarado(s): rote la clave antes de retirarla.';
        }
        if ($usage['legacy'] > 0) {
            return "Hay {$usage['legacy']} dato(s) guardados en un formato anterior que no dicen su clave: rote la clave antes de retirar ninguna.";
        }
        return $keyring->withoutPrevious($fingerprint)->save($keyringPath) ? '' : 'No se pudo escribir el llavero.';
    }

    /**
     * @param EncryptedData $site
     * @param AppKeyring $from
     * @param AppKeyring $to
     * @param bool $onlyStale Solo lo que no está ya cifrado con la clave actual de $to (sealLegacy())
     * @return array{rows:int,resealed:int,unreadable:int,note:string}
     */
    private function resealSite(EncryptedData $site, AppKeyring $from, AppKeyring $to, bool $onlyStale = false): array
    {
        $result = ['rows' => 0, 'resealed' => 0, 'unreadable' => 0, 'note' => ''];
        if ($site->kind() === EncryptedData::KIND_NOT_STORED) {
            $result['note'] = 'no se guarda';
            return $result;
        }
        [$table, $column, $primaryKey, $where, $whereValues] = $this->location($site);
        if (!$this->columnExists($table, $column)) {
            $result['note'] = 'la tabla o la columna no existe en esta base';
            return $result;
        }
        $select = $this->database->prepare("SELECT `{$primaryKey}` AS pk, `{$column}` AS stored FROM `{$table}` WHERE {$where}");
        self::runStatement($select, $whereValues);
        $update = $this->database->prepare("UPDATE `{$table}` SET `{$column}` = ? WHERE `{$primaryKey}` = ?");
        foreach ($select->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $stored = $this->decodeStored($site, $row['stored']);
            if ($stored === null || $stored === '') {
                continue;
            }
            if ($onlyStale && Sealed::fingerprintOf($stored) === $to->currentFingerprint()) {
                continue;
            }
            $result['rows']++;
            $plain = $site->read($stored, $from);
            if ($plain === null) {
                $result['unreadable']++;
                continue;
            }
            self::runStatement($update, [$this->encodeStored($site, Sealed::seal($plain, $site->purpose(), $to), $row['stored']), $row['pk']]);
            $result['resealed']++;
        }
        return $result;
    }

    /**
     * @param EncryptedData $site
     * @return string[]
     */
    private function storedValues(EncryptedData $site): array
    {
        if ($site->kind() === EncryptedData::KIND_NOT_STORED) {
            return [];
        }
        [$table, $column, , $where, $whereValues] = $this->location($site);
        if (!$this->columnExists($table, $column)) {
            return [];
        }
        $select = $this->database->prepare("SELECT `{$column}` AS stored FROM `{$table}` WHERE {$where}");
        self::runStatement($select, $whereValues);
        $values = [];
        foreach ($select->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $stored = $this->decodeStored($site, $row['stored']);
            if ($stored !== null && $stored !== '') {
                $values[] = $stored;
            }
        }
        return $values;
    }

    /**
     * Tabla, columna, clave primaria y filtro de un sitio. Los nombres los valida EncryptedData; los valores van por
     * marcador.
     *
     * @param EncryptedData $site
     * @return array{0:string,1:string,2:string,3:string,4:array<int,string>}
     */
    private function location(EncryptedData $site): array
    {
        if ($site->kind() === EncryptedData::KIND_OPTION) {
            return [self::OPTIONS_TABLE, 'value', 'id', '`name` = ?', [(string) $site->option()]];
        }
        return [(string) $site->table(), (string) $site->column(), $site->primaryKey(), '1 = 1', []];
    }

    /**
     * Una columna guarda el valor tal cual. Una opción (SettingsModel, columna `json`) guarda una cadena tal cual, pero
     * puede llegar como literal JSON ("…"): se aceptan las dos formas.
     *
     * @param EncryptedData $site
     * @param mixed $raw
     * @return string|null
     */
    private function decodeStored(EncryptedData $site, $raw): ?string
    {
        if (!is_string($raw)) {
            return null;
        }
        if ($site->kind() === EncryptedData::KIND_OPTION && str_starts_with($raw, '"')) {
            $decoded = json_decode($raw, true);
            return is_string($decoded) ? $decoded : null;
        }
        return $raw;
    }

    /**
     * En la misma forma en que estaba (ver decodeStored()).
     *
     * @param EncryptedData $site
     * @param string $value
     * @param mixed $previousRaw
     * @return string
     */
    private function encodeStored(EncryptedData $site, string $value, $previousRaw): string
    {
        return $site->kind() === EncryptedData::KIND_OPTION && is_string($previousRaw) && str_starts_with($previousRaw, '"') ? (string) json_encode($value) : $value;
    }

    /**
     * @param string $table
     * @param string $column
     * @return bool
     */
    private function columnExists(string $table, string $column): bool
    {
        $statement = $this->database->prepare('SELECT COUNT(*) FROM `information_schema`.`columns` WHERE `table_schema` = DATABASE() AND `table_name` = ? AND `column_name` = ?');
        self::runStatement($statement, [$table, $column]);
        return (int) $statement->fetchColumn() > 0;
    }

    /**
     * @param \PDOStatement $statement
     * @param array<int,mixed> $values
     * @return void
     * @throws \RuntimeException Si no se ejecuta (con ERRMODE_EXCEPTION ya lanza PDO; esto cubre el modo silencioso)
     */
    private static function runStatement(\PDOStatement $statement, array $values): void
    {
        if (!$statement->execute($values)) {
            throw new \RuntimeException('Una sentencia de la rotación no se ejecutó.');
        }
    }

    /**
     * Devuelve el archivo del llavero a lo que tenía antes de rotate().
     *
     * @param string $path
     * @param string|null $previousContent null: no existía
     * @param AppKeyring $from
     * @return void
     */
    private function restoreKeyring(string $path, ?string $previousContent, AppKeyring $from): void
    {
        if ($previousContent === null) {
            //RETORNO-IGNORADO: sin archivo previo, quitar el nuevo devuelve el estado de antes; si falla, lo dice la excepción que sigue subiendo.
            @unlink($path);
            return;
        }
        $restored = @file_put_contents($path, $previousContent, \LOCK_EX);
        if ($restored === false) {
            //RETORNO-IGNORADO: último recurso; el contenido es el mismo llavero que se leyó al empezar.
            $from->save($path);
        }
    }
}
