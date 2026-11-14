<?php

/**
 * NotificationMessage.php
 */

namespace PiecesPHP\Notifications;

/**
 * NotificationMessage - Lo que se notifica (ADR 0073): claves y parámetros, nunca el texto ya traducido ni una URL. El
 * texto se pinta al leer, en el idioma de quien lee; el destino se resuelve al leer con el nombre de la ruta.
 *
 * @package     PiecesPHP\Notifications
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class NotificationMessage
{
    const LEVEL_INFO = 'info';
    const LEVEL_SUCCESS = 'success';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVELS = [self::LEVEL_INFO, self::LEVEL_SUCCESS, self::LEVEL_WARNING, self::LEVEL_ERROR];

    /**
     * El icono de cada nivel si no se da otro.
     */
    const DEFAULT_ICONS = [
        self::LEVEL_INFO => 'info circle',
        self::LEVEL_SUCCESS => 'check circle',
        self::LEVEL_WARNING => 'exclamation triangle',
        self::LEVEL_ERROR => 'times circle',
    ];

    /**
     * @param string $kind Clave estable del tipo, p. ej. «approval-resolved»
     * @param string $level Uno de LEVELS
     * @param string $langGroup Grupo de traducción de las claves
     * @param string $titleKey
     * @param string|null $bodyKey
     * @param array<string,scalar> $params Para strtr() sobre el texto traducido: ['%username%' => 'ana']
     * @param string|null $routeName El destino al pulsarla
     * @param array<string,scalar> $routeParams
     * @param string|null $icon Por omisión, el de su nivel
     * @param array<string,mixed> $meta
     * @throws \InvalidArgumentException con un nivel, un tipo, un grupo o un título vacíos o desconocidos
     */
    public function __construct(
        public readonly string $kind,
        public readonly string $level,
        public readonly string $langGroup,
        public readonly string $titleKey,
        public readonly ?string $bodyKey = null,
        public readonly array $params = [],
        public readonly ?string $routeName = null,
        public readonly array $routeParams = [],
        public readonly ?string $icon = null,
        public readonly array $meta = [],
    ) {
        if (!in_array($level, self::LEVELS, true)) {
            throw new \InvalidArgumentException("Nivel de notificación desconocido: «{$level}».");
        }
        if (trim($kind) === '' || trim($langGroup) === '' || trim($titleKey) === '') {
            throw new \InvalidArgumentException('Una notificación necesita tipo, grupo de traducción y título.');
        }
    }

    /**
     * @return string
     */
    public function iconOrDefault(): string
    {
        return $this->icon !== null && trim($this->icon) !== '' ? $this->icon : self::DEFAULT_ICONS[$this->level];
    }
}
