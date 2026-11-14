<?php

/**
 * Notifications.php
 */

namespace PiecesPHP\Notifications;

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Roles;
use PiecesPHP\Notifications\Mappers\NotificationMapper;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * Notifications - El canal de notificaciones por usuario (ADR 0073): una llamada para avisar y una lista que mezcla, por
 * fecha, las fuentes registradas.
 *
 * ```php
 * Notifications::notify($userID, new NotificationMessage(kind: 'approval-resolved', level: NotificationMessage::LEVEL_SUCCESS,
 *     langGroup: MiModuloLang::LANG_GROUP, titleKey: 'Tu solicitud fue aprobada', bodyKey: 'Ya puedes entrar a %name%.',
 *     params: ['%name%' => $name]));
 * ```
 *
 * @phpstan-import-type NotificationItem from NotificationSource
 * @package     PiecesPHP\Notifications
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class Notifications
{
    const FILTER_ALL = 'all';
    const FILTER_NEWS = 'news';
    const FILTER_UNREAD = 'unread';
    const FILTER_READ = 'read';

    /**
     * La fuente de las noticias internas: con ella existe el filtro «news».
     */
    const NEWS_SOURCE = 'news';

    /**
     * Tope de elementos por página.
     */
    const MAX_PER_PAGE = 50;

    /**
     * Desde esta cifra, la insignia dice «99+»: un número de tres cifras no cabe en el contador. Igual en
     * NotificationsList.badge() (notifications-list.js).
     */
    const BADGE_MAX = 99;

    /**
     * @var array<string,NotificationSource>
     */
    private static $sources = [];

    /**
     * Solo para las suites: provoca un fallo de notify() para medir que no cambia la respuesta de quien notifica.
     *
     * @var bool
     */
    private static $failForTesting = false;

    /**
     * Las no leídas por usuario en esta petición: la barra las pide más de una vez.
     *
     * @var array<int,int>
     */
    private static $unreadMemo = [];

    /**
     * Avisa a uno o a varios usuarios. Nunca lanza: un fallo se anota en el registro de errores y devuelve lo creado
     * hasta ahí. Los ids que no son de un usuario se descartan.
     *
     * @param int|int[] $users
     * @param NotificationMessage $message
     * @return int Cuántas se crearon
     */
    public static function notify(int|array $users, NotificationMessage $message): int
    {
        $created = 0;
        try {
            if (self::$failForTesting) {
                throw new \RuntimeException('Fallo de notify() provocado por una prueba.');
            }
            $ids = array_values(array_unique(array_filter(array_map('intval', is_array($users) ? $users : [$users]), fn(int $id) => $id !== 0)));
            if ($ids === []) {
                return 0;
            }
            $database = self::database();
            $existing = $database->prepare('SELECT id FROM `' . UsersModel::TABLE . '` WHERE id = ?');
            $valid = [];
            foreach ($ids as $id) {
                $existing->execute([$id]);
                if ($existing->fetchColumn() !== false) {
                    $valid[] = $id;
                }
            }
            if ($valid === []) {
                return 0;
            }
            $json = fn(array $value): ?string => $value === [] ? null : json_encode($value, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
            $params = $json($message->params);
            $routeParams = $json($message->routeParams);
            $meta = $json(['icon' => $message->iconOrDefault()] + $message->meta);
            $insert = $database->prepare('INSERT INTO `' . NotificationMapper::TABLE . '` (`user`, kind, level, langGroup, titleKey, bodyKey, params, routeName, routeParams, createdAt, readAt, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?)');
            $now = date('Y-m-d H:i:s');
            foreach ($valid as $userID) {
                $insert->execute([$userID, $message->kind, $message->level, $message->langGroup, $message->titleKey, $message->bodyKey, $params, $message->routeName, $routeParams, $now, $meta]);
                $created++;
            }
            self::purge($database);
        } catch (\Throwable $e) {
            //RETORNO-IGNORADO: la referencia no la necesita nadie; quien llama sigue con lo creado hasta ahí.
            log_exception($e);
        }
        return $created;
    }

    /**
     * Solo para las suites, como SystemAlertRegistry::swapForTesting(): true hace fallar cada notify() hasta volver a false.
     *
     * @param bool $fail
     * @return void
     */
    public static function failForTesting(bool $fail): void
    {
        self::$failForTesting = $fail;
    }

    /**
     * Registra una fuente de la lista.
     *
     * @param NotificationSource $source
     * @return void
     * @throws \InvalidArgumentException si su clave ya está registrada
     */
    public static function register(NotificationSource $source): void
    {
        if (array_key_exists($source->key(), self::$sources)) {
            throw new \InvalidArgumentException("Ya hay una fuente de notificaciones con la clave «{$source->key()}».");
        }
        self::$sources[$source->key()] = $source;
    }

    /**
     * @param string $key
     * @return bool false si no estaba
     */
    public static function remove(string $key): bool
    {
        if (!array_key_exists($key, self::$sources)) {
            return false;
        }
        unset(self::$sources[$key]);
        return true;
    }

    /**
     * @return array<string,NotificationSource>
     */
    public static function sources(): array
    {
        return self::$sources;
    }

    /**
     * @param string $key
     * @return NotificationSource|null
     */
    public static function source(string $key): ?NotificationSource
    {
        return self::$sources[$key] ?? null;
    }

    /**
     * Los filtros que hay: sin la fuente de noticias, no hay «news».
     *
     * @return string[]
     */
    public static function filters(): array
    {
        $filters = [self::FILTER_ALL, self::FILTER_UNREAD, self::FILTER_READ];
        if (isset(self::$sources[self::NEWS_SOURCE])) {
            array_splice($filters, 1, 0, [self::FILTER_NEWS]);
        }
        return $filters;
    }

    /**
     * Los contadores de cada filtro.
     *
     * @param int $userID
     * @return array<string,int>
     */
    public static function counts(int $userID): array
    {
        $counts = [self::FILTER_ALL => 0, self::FILTER_UNREAD => 0, self::FILTER_READ => 0];
        foreach (self::$sources as $key => $source) {
            $own = $source->counts($userID);
            $counts[self::FILTER_ALL] += $own['all'];
            $counts[self::FILTER_UNREAD] += $own['unread'];
            $counts[self::FILTER_READ] += $own['read'];
            if ($key === self::NEWS_SOURCE) {
                $counts[self::FILTER_NEWS] = $own['all'];
            }
        }
        return $counts;
    }

    /**
     * Las no leídas, para la insignia de la campana. Nunca tumba la página: un fallo cuenta 0.
     *
     * @param int $userID
     * @return int
     */
    public static function unread(int $userID): int
    {
        if (!array_key_exists($userID, self::$unreadMemo)) {
            try {
                self::$unreadMemo[$userID] = self::counts($userID)[self::FILTER_UNREAD];
            } catch (\Throwable) {
                self::$unreadMemo[$userID] = 0;
            }
        }
        return self::$unreadMemo[$userID];
    }

    /**
     * El texto de la insignia: la cifra hasta BADGE_MAX y «99+» por encima. La etiqueta accesible lleva la cifra real.
     *
     * @param int $unread
     * @return string
     */
    public static function badgeText(int $unread): string
    {
        return $unread > self::BADGE_MAX ? self::BADGE_MAX . '+' : (string) $unread;
    }

    /**
     * Una página de la lista, mezclada por fecha (la más nueva primero).
     *
     * @param int $userID
     * @param string $filter Uno de filters()
     * @param int $page Desde 1
     * @param int $perPage Hasta MAX_PER_PAGE
     * @return array{items:list<array<string,mixed>>,counts:array<string,int>,hasMore:bool}
     * @throws \InvalidArgumentException con un filtro que no existe
     */
    public static function page(int $userID, string $filter, int $page, int $perPage): array
    {
        if (!in_array($filter, self::filters(), true)) {
            throw new \InvalidArgumentException("Filtro de notificaciones desconocido: «{$filter}».");
        }
        $page = max(1, $page);
        $perPage = max(1, min(self::MAX_PER_PAGE, $perPage));
        $read = $filter === self::FILTER_READ ? true : ($filter === self::FILTER_UNREAD ? false : null);
        $sources = $filter === self::FILTER_NEWS ? [self::$sources[self::NEWS_SOURCE]] : array_values(self::$sources);
        $limit = $page * $perPage;
        $merged = [];
        foreach ($sources as $source) {
            array_push($merged, ...$source->items($userID, $read, $limit));
        }
        usort($merged, fn(array $a, array $b): int => [$b['createdAt'], $b['source'], $b['id']] <=> [$a['createdAt'], $a['source'], $a['id']]);
        $counts = self::counts($userID);
        $items = array_map(function (array $item): array {
            $item['createdAt'] = date(\DATE_ATOM, $item['createdAt']);
            return $item;
        }, array_slice($merged, ($page - 1) * $perPage, $perPage));
        return [
            'items' => $items,
            'counts' => $counts,
            'hasMore' => $limit < ($counts[$filter] ?? 0),
        ];
    }

    /**
     * El texto de una clave en el idioma de la petición, con sus parámetros.
     *
     * @param string $langGroup
     * @param string|null $key
     * @param array<mixed> $params
     * @return string
     */
    public static function text(string $langGroup, ?string $key, array $params): string
    {
        if ($key === null || $key === '') {
            return '';
        }
        $pairs = [];
        foreach ($params as $name => $value) {
            if (is_scalar($value)) {
                $pairs[(string) $name] = (string) $value;
            }
        }
        return strtr(__($langGroup, $key), $pairs);
    }

    /**
     * El enlace de una notificación, resuelto ahora: null si la ruta ya no existe, le faltan parámetros o el usuario
     * no tiene su permiso. Nunca lanza.
     *
     * @param string|null $routeName
     * @param array<mixed> $routeParams
     * @param int|null $userType
     * @return string|null
     */
    public static function url(?string $routeName, array $routeParams, ?int $userType): ?string
    {
        if ($routeName === null || $routeName === '') {
            return null;
        }
        try {
            if ($userType !== null && !Roles::hasPermissions($routeName, $userType, true)) {
                return null;
            }
            $url = get_route($routeName, $routeParams, true);
            return is_string($url) && $url !== '' ? $url : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return \PDO
     */
    public static function database(): \PDO
    {
        return (new BaseModel())->getDatabase() ?? throw new \RuntimeException('Sin conexión a la base para las notificaciones.');
    }

    /**
     * Borra las leídas de más de RETENTION_DAYS. Al escribir, como las sesiones: sin tarea programada que un clon pueda no
     * tener activa.
     *
     * @param \PDO $database
     * @return void
     */
    private static function purge(\PDO $database): void
    {
        $statement = $database->prepare('DELETE FROM `' . NotificationMapper::TABLE . '` WHERE readAt IS NOT NULL AND readAt < ?');
        $statement->execute([date('Y-m-d H:i:s', time() - NotificationMapper::RETENTION_DAYS * 86400)]);
    }
}
