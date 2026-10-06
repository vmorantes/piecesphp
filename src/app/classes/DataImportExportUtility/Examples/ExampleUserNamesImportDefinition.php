<?php

/**
 * ExampleUserNamesImportDefinition.php
 */

namespace DataImportExportUtility\Examples;

use PiecesPHP\UserSystem\ORM\UsersModel;
use DataImportExportUtility\DataImportExportUtilityLang;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\DataTransfer\Import\Column;
use PiecesPHP\Core\DataTransfer\Import\ImportArtifacts;
use PiecesPHP\Core\DataTransfer\Import\ImportDefinition;
use PiecesPHP\Core\DataTransfer\Import\ImportPersistException;
use PiecesPHP\Core\DataTransfer\Import\ParsedRow;

/**
 * ExampleUserNamesImportDefinition - Ejemplo de la guía: actualiza nombre y apellidos de usuarios que ya existen.
 *
 * No está registrado en el panel: la guía lo incrusta y su suite lo registra en su proceso.
 *
 * @package     DataImportExportUtility\Examples
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class ExampleUserNamesImportDefinition extends ImportDefinition
{
    const LANG_GROUP = DataImportExportUtilityLang::LANG_GROUP;

    /**
     * @return string
     */
    public function key(): string
    {
        return 'example-user-names';
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return __(self::LANG_GROUP, 'Nombres de usuarios (ejemplo)');
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return __(self::LANG_GROUP, 'Actualiza nombre y apellidos de usuarios que ya existen (ejemplo de la guía).');
    }

    /**
     * @return int[]
     */
    public function allowedUserTypes(): array
    {
        return [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN_GRAL,
        ];
    }

    /**
     * Nivel 2: el formulario ofrece «Solo validar».
     *
     * @return int
     */
    public function interfaceLevel(): int
    {
        return self::INTERFACE_EXTENDED;
    }

    // --8<-- [start:import-columns]
    /**
     * @return Column[]
     */
    public function columns(): array
    {
        return [
            //La cabecera del archivo se reconoce por la key, la etiqueta o un alias, sin distinguir mayúsculas.
            (new Column('username', __(self::LANG_GROUP, 'Usuario'), true, ['usuario'], function (?string $value) {
                //El validador recibe el valor ya recortado y no vacío; devuelve el mensaje o null.
                return self::userIDByUsername((string) $value) !== null ? null : sprintf(__(self::LANG_GROUP, 'Usuario: «%s» no existe.'), $value);
            }))
                ->help(__(self::LANG_GROUP, 'El usuario que se actualiza; tiene que existir.'))
                ->example('ana.perez'),
            (new Column('firstname', __(self::LANG_GROUP, 'Primer nombre'), true))
                ->example('Ana'),
            (new Column('secondname', __(self::LANG_GROUP, 'Segundo nombre')))
                ->help(__(self::LANG_GROUP, 'Vacío lo deja vacío.'))
                ->example('María'),
            (new Column('first_lastname', __(self::LANG_GROUP, 'Primer apellido'), true))
                ->example('Pérez'),
            (new Column('second_lastname', __(self::LANG_GROUP, 'Segundo apellido')))
                ->help(__(self::LANG_GROUP, 'Vacío lo deja vacío.'))
                ->example('Gómez'),
        ];
    }
    // --8<-- [end:import-columns]

    // --8<-- [start:import-validate-all]
    /**
     * Lo que solo se ve mirando todas las filas: el mismo usuario dos veces en el archivo.
     *
     * @param ParsedRow[] $rows
     * @return array<int,string[]> posición de la fila => errores
     */
    public function validateAll(array $rows): array
    {
        $errors = [];
        $seen = [];
        foreach ($rows as $row) {
            $username = mb_strtolower((string) $row->get('username'));
            if ($username === '') {
                continue;
            }
            if (isset($seen[$username])) {
                $errors[$row->position()][] = sprintf(__(self::LANG_GROUP, 'Usuario: «%s» se repite en la fila %d.'), $row->get('username'), $seen[$username]);
            } else {
                $seen[$username] = $row->position();
            }
        }
        return $errors;
    }
    // --8<-- [end:import-validate-all]

    // --8<-- [start:import-persist]
    /**
     * Solo se llama si TODAS las filas son válidas, y nunca en un simulacro.
     *
     * Todo o nada: una transacción propia con la conexión compartida. Si una fila falla, no queda ninguna cambiada.
     *
     * @param ParsedRow[] $rows
     * @return ImportArtifacts|null
     * @throws ImportPersistException con un mensaje para quien importa
     */
    public function persist(array $rows): ?ImportArtifacts
    {
        $pdo = UsersModel::model()::getDb(Config::app_db('default')['db']);
        if ($pdo === null) {
            throw new ImportPersistException(__(self::LANG_GROUP, 'No se pudo guardar la importación; no se cambió ningún usuario.'));
        }
        try {
            $pdo->beginTransaction();
            foreach ($rows as $row) {
                $userID = self::userIDByUsername((string) $row->get('username'));
                if ($userID === null) {
                    throw new \RuntimeException('El usuario de la fila ' . $row->position() . ' ya no existe.');
                }
                //Solo estas columnas y por id: update() pasa los valores por marcadores, nunca en el texto del SQL.
                $model = UsersModel::model();
                $model->resetAll();
                $model->update([
                    'firstname' => (string) $row->get('firstname'),
                    'secondname' => (string) $row->get('secondname'),
                    'firstLastname' => (string) $row->get('first_lastname'),
                    'secondLastname' => (string) $row->get('second_lastname'),
                ])->where(new WhereSegment([WhereItem::isEqual('id', $userID)]))->execute();
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            //El detalle va al registro de errores; quien importa recibe un mensaje sin datos internos.
            log_exception($e);
            throw new ImportPersistException(__(self::LANG_GROUP, 'No se pudo guardar la importación; no se cambió ningún usuario.'));
        }
        //Este importador no genera entregables (como las credenciales de UsersImportDefinition).
        return null;
    }
    // --8<-- [end:import-persist]

    /**
     * @param string $username
     * @return int|null
     */
    private static function userIDByUsername(string $username): ?int
    {
        $model = UsersModel::model();
        $model->resetAll();
        $model->select(['id'])->where(new WhereSegment([WhereItem::isEqual('username', $username)]))->execute();
        $row = ((array) $model->result())[0] ?? null;
        return is_object($row) && isset($row->id) ? (int) $row->id : null;
    }
}
