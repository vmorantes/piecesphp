<?php

/**
 * TableNotificationSource.php
 */

namespace PiecesPHP\Notifications\Sources;

use PiecesPHP\Notifications\Mappers\NotificationMapper;
use PiecesPHP\Notifications\NotificationMessage;
use PiecesPHP\Notifications\Notifications;
use PiecesPHP\Notifications\NotificationSource;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * TableNotificationSource - Las notificaciones del canal (pcsphp_notifications). Toda consulta lleva el usuario: lo
 * ajeno no existe para quien lo pide.
 *
 * @phpstan-import-type NotificationItem from NotificationSource
 * @package     PiecesPHP\Notifications\Sources
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class TableNotificationSource implements NotificationSource
{
    const KEY = 'system';

    /**
     * Si la tabla existe, una vez por petición: un clon que aún no aplicó su actualización ve la lista vacía, no un error
     * en cada página (la barra cuenta las no leídas al pintarse).
     *
     * @var bool|null
     */
    private static $tableExistsMemo = null;

    /**
     * @inheritDoc
     */
    public function key(): string
    {
        return self::KEY;
    }

    /**
     * @inheritDoc
     */
    public function counts(int $userID): array
    {
        if (!self::tableExists()) {
            return ['all' => 0, 'unread' => 0, 'read' => 0];
        }
        $statement = Notifications::database()->prepare('SELECT COUNT(*) AS total, COALESCE(SUM(readAt IS NULL), 0) AS unread FROM `' . NotificationMapper::TABLE . '` WHERE `user` = ?');
        $statement->execute([$userID]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        $all = is_array($row) ? (int) $row['total'] : 0;
        $unread = is_array($row) ? (int) $row['unread'] : 0;
        return ['all' => $all, 'unread' => $unread, 'read' => $all - $unread];
    }

    /**
     * @inheritDoc
     */
    public function items(int $userID, ?bool $read, int $limit): array
    {
        if (!self::tableExists()) {
            return [];
        }
        $select = 'SELECT id, level, langGroup, titleKey, bodyKey, params, routeName, routeParams, createdAt, readAt, meta FROM `' . NotificationMapper::TABLE . '` WHERE `user` = ?';
        $order = ' ORDER BY createdAt DESC, id DESC LIMIT ?';
        $sql = match ($read) {
            null => $select . $order,
            true => $select . ' AND readAt IS NOT NULL' . $order,
            false => $select . ' AND readAt IS NULL' . $order,
        };
        $statement = Notifications::database()->prepare($sql);
        $statement->bindValue(1, $userID, \PDO::PARAM_INT);
        $statement->bindValue(2, max(1, $limit), \PDO::PARAM_INT);
        $statement->execute();
        $userType = $this->userType($userID);
        $items = [];
        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $params = self::decode($row['params']);
            $meta = self::decode($row['meta']);
            $level = (string) $row['level'];
            $created = strtotime((string) $row['createdAt']);
            $items[] = [
                'source' => self::KEY,
                'id' => (string) $row['id'],
                'level' => $level,
                'icon' => is_string($meta['icon'] ?? null) ? $meta['icon'] : (NotificationMessage::DEFAULT_ICONS[$level] ?? NotificationMessage::DEFAULT_ICONS[NotificationMessage::LEVEL_INFO]),
                'title' => Notifications::text((string) $row['langGroup'], (string) $row['titleKey'], $params),
                'body' => Notifications::text((string) $row['langGroup'], is_string($row['bodyKey']) ? $row['bodyKey'] : null, $params),
                'createdAt' => $created !== false ? $created : 0,
                'read' => $row['readAt'] !== null,
                'url' => Notifications::url(is_string($row['routeName']) ? $row['routeName'] : null, self::decode($row['routeParams']), $userType),
                'news' => null,
            ];
        }
        return $items;
    }

    /**
     * @inheritDoc
     */
    public function markRead(int $userID, string $id): bool
    {
        if (preg_match('/^[1-9][0-9]*$/', $id) !== 1 || !self::tableExists()) {
            return false;
        }
        $database = Notifications::database();
        $own = $database->prepare('SELECT id FROM `' . NotificationMapper::TABLE . '` WHERE id = ? AND `user` = ?');
        $own->execute([(int) $id, $userID]);
        if ($own->fetchColumn() === false) {
            return false;
        }
        $mark = $database->prepare('UPDATE `' . NotificationMapper::TABLE . '` SET readAt = ? WHERE id = ? AND `user` = ? AND readAt IS NULL');
        $mark->execute([date('Y-m-d H:i:s'), (int) $id, $userID]);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function markAllRead(int $userID): int
    {
        if (!self::tableExists()) {
            return 0;
        }
        $mark = Notifications::database()->prepare('UPDATE `' . NotificationMapper::TABLE . '` SET readAt = ? WHERE `user` = ? AND readAt IS NULL');
        $mark->execute([date('Y-m-d H:i:s'), $userID]);
        return $mark->rowCount();
    }

    /**
     * @return bool
     */
    public static function tableExists(): bool
    {
        if (self::$tableExistsMemo === null) {
            $statement = Notifications::database()->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
            $statement->execute([NotificationMapper::TABLE]);
            self::$tableExistsMemo = (int) $statement->fetchColumn() > 0;
        }
        return self::$tableExistsMemo;
    }

    /**
     * @param int $userID
     * @return int|null
     */
    private function userType(int $userID): ?int
    {
        $current = getLoggedFrameworkUser();
        if ($current !== null && (int) $current->id === $userID) {
            return (int) $current->type;
        }
        $statement = Notifications::database()->prepare('SELECT type FROM `' . UsersModel::TABLE . '` WHERE id = ?');
        $statement->execute([$userID]);
        $type = $statement->fetchColumn();
        return $type !== false ? (int) $type : null;
    }

    /**
     * @param mixed $json
     * @return array<mixed>
     */
    private static function decode(mixed $json): array
    {
        if (!is_string($json) || $json === '') {
            return [];
        }
        $value = json_decode($json, true);
        return is_array($value) ? $value : [];
    }
}
