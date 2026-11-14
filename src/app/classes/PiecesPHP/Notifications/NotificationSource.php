<?php

/**
 * NotificationSource.php
 */

namespace PiecesPHP\Notifications;

/**
 * NotificationSource - Una fuente de la lista de notificaciones (ADR 0073): la tabla del canal, las noticias internas o
 * la que registre un proyecto con Notifications::register(). Cada fuente solo da y toca lo del usuario que se le pasa.
 *
 * @phpstan-type NotificationItem array{source:string,id:string,level:string,icon:string,title:string,body:string,createdAt:int,read:bool,url:?string,news:?array{title:string,content:string,color:string}}
 * @package     PiecesPHP\Notifications
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
interface NotificationSource
{
    /**
     * La clave de la fuente: viaja en cada elemento y en «marcar leída».
     *
     * @return string
     */
    public function key(): string;

    /**
     * @param int $userID
     * @return array{all:int,unread:int,read:int}
     */
    public function counts(int $userID): array;

    /**
     * Las más nuevas primero; `createdAt` en segundos de Unix. `news`, lo que pinta la ventana de una noticia; null en las demás.
     *
     * @param int $userID
     * @param bool|null $read null: todas; true: solo leídas; false: solo no leídas
     * @param int $limit
     * @return list<NotificationItem>
     */
    public function items(int $userID, ?bool $read, int $limit): array;

    /**
     * @param int $userID
     * @param string $id
     * @return bool false si no existe o no es de ese usuario: quien llama responde 404 y no cambia nada
     */
    public function markRead(int $userID, string $id): bool;

    /**
     * @param int $userID
     * @return int Cuántas marcó
     */
    public function markAllRead(int $userID): int;
}
