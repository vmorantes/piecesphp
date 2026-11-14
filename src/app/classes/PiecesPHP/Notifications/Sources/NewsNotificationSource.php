<?php

/**
 * NewsNotificationSource.php
 */

namespace PiecesPHP\Notifications\Sources;

use News\Mappers\NewsCategoryMapper;
use News\Mappers\NewsMapper;
use News\Mappers\NewsReadedMapper;
use PiecesPHP\Core\Config;
use PiecesPHP\Notifications\NotificationMessage;
use PiecesPHP\Notifications\Notifications;
use PiecesPHP\Notifications\NotificationSource;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * NewsNotificationSource - Las noticias internas como fuente de la lista (ADR 0073), con la regla de visibilidad del
 * listado de noticias (NewsMapper::isVisibleByValues(), más su traducción al idioma de la petición) y su lectura de
 * news_readed_relationship. Elige sobre filas planas, con consultas fijas; solo construye las noticias que devuelve.
 * Solo se registra con NEWS_MODULE.
 *
 * @phpstan-import-type NotificationItem from NotificationSource
 * @package     PiecesPHP\Notifications\Sources
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class NewsNotificationSource implements NotificationSource
{
    const KEY = Notifications::NEWS_SOURCE;

    /**
     * Largo del texto de una noticia en la lista.
     */
    const BODY_LENGTH = 160;

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
        $visible = $this->visibleIDs($userID);
        $readIDs = $this->readIDs($userID);
        $read = count(array_filter($visible, fn(int $id) => isset($readIDs[$id])));
        return ['all' => count($visible), 'unread' => count($visible) - $read, 'read' => $read];
    }

    /**
     * @inheritDoc
     */
    public function items(int $userID, ?bool $read, int $limit): array
    {
        $readIDs = $this->readIDs($userID);
        $items = [];
        foreach ($this->visibleIDs($userID) as $id) {
            $isRead = isset($readIDs[$id]);
            if ($read !== null && $read !== $isRead) {
                continue;
            }
            $news = new NewsMapper($id);
            $created = $news->createdAt instanceof \DateTimeInterface ? $news->createdAt->getTimestamp() : (int) strtotime((string) $news->createdAt);
            $body = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $news->currentLangData('content'))) ?? '');
            $items[] = [
                'source' => self::KEY,
                'id' => (string) $news->id,
                'level' => NotificationMessage::LEVEL_INFO,
                'icon' => 'newspaper outline',
                'title' => (string) $news->currentLangData('newsTitle'),
                'body' => mb_strlen($body) > self::BODY_LENGTH ? rtrim(mb_substr($body, 0, self::BODY_LENGTH)) . '…' : $body,
                'createdAt' => $created,
                'read' => $isRead,
                'url' => null,
                'news' => [
                    'title' => (string) $news->currentLangData('newsTitle'),
                    'content' => (string) $news->currentLangData('content'),
                    'color' => self::categoryColor($news),
                ],
            ];
            if (count($items) >= $limit) {
                break;
            }
        }
        return $items;
    }

    /**
     * @inheritDoc
     */
    public function markRead(int $userID, string $id): bool
    {
        if (preg_match('/^[1-9][0-9]*$/', $id) !== 1 || !NewsMapper::existsByID((int) $id)) {
            return false;
        }
        //Una noticia que no le llega no se puede marcar: para él no existe.
        if (!$this->isVisible(new NewsMapper((int) $id), new UserDataPackage($userID))) {
            return false;
        }
        //addRecord() lee antes de insertar: no duplica la fila.
        return NewsReadedMapper::addRecord($userID, (int) $id);
    }

    /**
     * @inheritDoc
     */
    public function markAllRead(int $userID): int
    {
        $readIDs = $this->readIDs($userID);
        $marked = 0;
        foreach ($this->visibleIDs($userID) as $id) {
            if (!isset($readIDs[$id]) && NewsReadedMapper::addRecord($userID, $id)) {
                $marked++;
            }
        }
        return $marked;
    }

    /**
     * Los ids de las noticias que el listado le daría, la más nueva primero: una consulta, sin construir ningún mapper.
     *
     * @param int $userID
     * @return int[]
     */
    private function visibleIDs(int $userID): array
    {
        $statement = Notifications::database()->prepare('SELECT id, status, startDate, endDate, profilesTarget, meta FROM `' . NewsMapper::TABLE . '` WHERE status = ? ORDER BY createdAt DESC, id DESC');
        $statement->execute([NewsMapper::ACTIVE]);
        $user = new UserDataPackage($userID);
        $userType = (int) $user->type;
        $lang = Config::get_lang();
        $visible = [];
        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $meta = is_string($row['meta']) ? json_decode($row['meta']) : null;
            $meta = is_object($meta) ? $meta : new \stdClass();
            $targets = is_string($row['profilesTarget']) ? json_decode($row['profilesTarget'], true) : null;
            $visibleByRule = NewsMapper::isVisibleByValues(
                (int) $row['status'],
                (int) ($meta->draft ?? 0),
                self::date($row['startDate']),
                self::date($row['endDate']),
                is_array($targets) ? $targets : (array) $targets,
                $userType,
            );
            //Los valores por defecto de las meta-propiedades de NewsMapper: baseLang, el idioma por defecto.
            $baseLang = is_string($meta->baseLang ?? null) ? $meta->baseLang : Config::get_default_lang();
            $langData = $meta->langData ?? null;
            if ($visibleByRule && ($baseLang === $lang || (is_object($langData) && isset($langData->$lang)))) {
                $visible[] = (int) $row['id'];
            }
        }
        return $visible;
    }

    /**
     * @param mixed $value
     * @return \DateTime|null
     */
    private static function date(mixed $value): ?\DateTime
    {
        return is_string($value) && $value !== '' ? new \DateTime($value) : null;
    }

    /**
     * La regla del listado de noticias: la de NewsMapper y, como NewsController::_all(), una traducción al idioma actual.
     *
     * @param NewsMapper $news
     * @param UserDataPackage $user
     * @return bool
     */
    private function isVisible(NewsMapper $news, UserDataPackage $user): bool
    {
        if (!$news->isVisibleTo($user)) {
            return false;
        }
        $lang = Config::get_lang();
        $langData = $news->langData;
        return $news->baseLang === $lang || (is_object($langData) && isset($langData->$lang));
    }

    /**
     * El color de su categoría, el de la ventana de la noticia; sin categoría, el de por omisión.
     *
     * @param NewsMapper $news
     * @return string
     */
    private static function categoryColor(NewsMapper $news): string
    {
        $category = is_object($news->category) ? $news->category : null;
        $categoryID = $category !== null ? (int) ($category->id ?? 0) : (int) $news->category;
        if ($category === null && $categoryID > 0 && NewsCategoryMapper::existsByID($categoryID)) {
            $category = new NewsCategoryMapper($categoryID);
        }
        $color = $category !== null && is_string($category->color ?? null) ? $category->color : '';
        return $color !== '' ? $color : NewsCategoryMapper::DEFAULT_COLOR;
    }

    /**
     * @param int $userID
     * @return array<int,true>
     */
    private function readIDs(int $userID): array
    {
        $statement = Notifications::database()->prepare('SELECT news FROM `' . NewsReadedMapper::TABLE . '` WHERE readerUser = ?');
        $statement->execute([$userID]);
        $ids = [];
        foreach ($statement->fetchAll(\PDO::FETCH_COLUMN) as $id) {
            $ids[(int) $id] = true;
        }
        return $ids;
    }
}
