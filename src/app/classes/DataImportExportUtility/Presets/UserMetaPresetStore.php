<?php

/**
 * UserMetaPresetStore.php
 */

namespace DataImportExportUtility\Presets;

use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\DataTransfer\Export\ExportPresetStore;

/**
 * UserMetaPresetStore - Los filtros guardados de cada usuario, en users.meta bajo «dataTransferPresets».
 *
 * { "dataTransferPresets": { "<exportKey>": { "<nombre>": { …query… } } } }
 *
 * Cada escritura lee meta de la base justo antes, fusiona y actualiza SOLO la columna meta de ese id, con marcadores:
 * las demás claves de meta se conservan. Dos guardados simultáneos del mismo usuario: gana el último que escribe.
 *
 * @package     DataImportExportUtility\Presets
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class UserMetaPresetStore implements ExportPresetStore
{
    const META_KEY = 'dataTransferPresets';

    /**
     * @param int $userID
     * @param string $exportKey
     * @return array<string,array<string,mixed>>
     */
    public function all(int $userID, string $exportKey): array
    {
        $presets = $this->readMeta($userID)[self::META_KEY][$exportKey] ?? [];
        return is_array($presets) ? $presets : [];
    }

    /**
     * @param int $userID
     * @param string $exportKey
     * @param string $name
     * @param array<string,mixed> $query
     * @return void
     */
    public function save(int $userID, string $exportKey, string $name, array $query): void
    {
        $meta = $this->readMeta($userID);
        $meta[self::META_KEY] = is_array($meta[self::META_KEY] ?? null) ? $meta[self::META_KEY] : [];
        $meta[self::META_KEY][$exportKey] = is_array($meta[self::META_KEY][$exportKey] ?? null) ? $meta[self::META_KEY][$exportKey] : [];
        $meta[self::META_KEY][$exportKey][$name] = $query;
        $this->writeMeta($userID, $meta);
    }

    /**
     * @param int $userID
     * @param string $exportKey
     * @param string $name
     * @return void
     */
    public function delete(int $userID, string $exportKey, string $name): void
    {
        $meta = $this->readMeta($userID);
        if (!is_array($meta[self::META_KEY][$exportKey] ?? null) || !array_key_exists($name, $meta[self::META_KEY][$exportKey])) {
            return;
        }
        unset($meta[self::META_KEY][$exportKey][$name]);
        $this->writeMeta($userID, $meta);
    }

    /**
     * El meta del usuario, fresco de la base.
     *
     * @param int $userID
     * @return array<string,mixed>
     */
    private function readMeta(int $userID): array
    {
        $model = UsersModel::model();
        $model->resetAll();
        $model->select()->where(new WhereSegment([WhereItem::isEqual('id', $userID)]))->execute();
        $row = ((array) $model->result())[0] ?? null;
        $raw = is_object($row) ? ($row->meta ?? null) : null;
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }
        return is_array($raw) ? $raw : (is_object($raw) ? (array) json_decode((string) json_encode($raw), true) : []);
    }

    /**
     * Actualiza solo la columna meta de ese id.
     *
     * @param int $userID
     * @param array<string,mixed> $meta
     * @return void
     */
    private function writeMeta(int $userID, array $meta): void
    {
        $model = UsersModel::model();
        $model->resetAll();
        $model->update(['meta' => json_encode($meta, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE)])
            ->where(new WhereSegment([WhereItem::isEqual('id', $userID)]))
            ->execute();
    }
}
