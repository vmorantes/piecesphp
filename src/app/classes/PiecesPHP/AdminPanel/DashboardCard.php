<?php

/**
 * DashboardCard.php
 */

namespace PiecesPHP\AdminPanel;

use PiecesPHP\UserSystem\UserDataPackage;

/**
 * DashboardCard - Una tarjeta del inicio del panel: la declara el módulo dueño de lo que enseña, no el inicio.
 *
 * Se ve si el usuario tiene permiso a `permissionRoute`: el nombre de la ruta es el permiso, como en todo el framework.
 * Su contenido lo da una función que recibe al usuario y devuelve un DashboardCardBody (cifras, acción o HTML); si
 * devuelve null, la tarjeta no se pinta (por ejemplo, «Tareas pendientes» sin tareas). Plegada, esa función NO se llama:
 * no hay cifras que enseñar; se pinta su barra y, si lo declara, su pie plegado (`collapsedFooterCaption`).
 *
 * @package     PiecesPHP\AdminPanel
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class DashboardCard
{
    const KEY_PATTERN = '/^[a-z0-9]+(-[a-z0-9]+)*$/';

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $icon;

    /**
     * @var string
     */
    private $permissionRoute;

    /**
     * @var callable(UserDataPackage):(DashboardCardBody|null)
     */
    private $content;

    /**
     * @var int
     */
    private $position;

    /**
     * @var bool
     */
    private $collapsible;

    /**
     * @var bool
     */
    private $collapsedByDefault;

    /**
     * @var string|null
     */
    private $collapsedFooterCaption;

    /**
     * @var string|\Closure|null
     */
    private $collapsedFooterUrl;

    /**
     * @var string
     */
    private $help;

    /**
     * @param string $key Solo minúsculas, cifras y guiones
     * @param string $title Ya traducido
     * @param string $icon Clases de un icono de Fomantic, sin «icon»
     * @param string $permissionRoute El nombre de la ruta cuyo permiso hace visible la tarjeta
     * @param callable(UserDataPackage):(DashboardCardBody|null) $content Lo que enseña; null la oculta
     * @param int $position De menor a mayor; a igual posición, el orden de registro
     * @param bool $collapsible Si el usuario la puede plegar
     * @param bool $collapsedByDefault Plegada hasta que el usuario la despliegue (solo con $collapsible)
     * @param string|null $collapsedFooterCaption Plegada, el texto de su pie, ya traducido; sin él, plegada no lleva pie
     * @param string|\Closure|null $collapsedFooterUrl Plegada, adónde lleva la flecha del pie. Una Closure(): ?string se
     *      resuelve al pintar, no al registrar: al registrar, las rutas aún no están todas
     * @param string $help El texto de la ayuda, ya traducido; vacío, sin icono de ayuda
     * @throws \InvalidArgumentException si la key no tiene la forma de KEY_PATTERN, o si nace plegada sin poder plegarse
     */
    public function __construct(string $key, string $title, string $icon, string $permissionRoute, callable $content, int $position = 100, bool $collapsible = false, bool $collapsedByDefault = false, ?string $collapsedFooterCaption = null, string|\Closure|null $collapsedFooterUrl = null, string $help = '')
    {
        if (preg_match(self::KEY_PATTERN, $key) !== 1) {
            throw new \InvalidArgumentException("La key de tarjeta «{$key}» no es válida.");
        }
        if ($collapsedByDefault && !$collapsible) {
            throw new \InvalidArgumentException("La tarjeta «{$key}» nace plegada pero no se puede plegar.");
        }
        $this->key = $key;
        $this->title = $title;
        $this->icon = $icon;
        $this->permissionRoute = $permissionRoute;
        $this->content = $content;
        $this->position = $position;
        $this->collapsible = $collapsible;
        $this->collapsedByDefault = $collapsedByDefault;
        $this->collapsedFooterCaption = $collapsedFooterCaption;
        $this->collapsedFooterUrl = $collapsedFooterUrl;
        $this->help = $help;
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
    public function title(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function icon(): string
    {
        return $this->icon;
    }

    /**
     * @return string
     */
    public function permissionRoute(): string
    {
        return $this->permissionRoute;
    }

    /**
     * @return int
     */
    public function position(): int
    {
        return $this->position;
    }

    /**
     * @return bool
     */
    public function collapsible(): bool
    {
        return $this->collapsible;
    }

    /**
     * @return bool
     */
    public function collapsedByDefault(): bool
    {
        return $this->collapsedByDefault;
    }

    /**
     * @return string
     */
    public function help(): string
    {
        return $this->help;
    }

    /**
     * @return string|null
     */
    public function collapsedFooterCaption(): ?string
    {
        return $this->collapsedFooterCaption;
    }

    /**
     * @return string|null
     */
    public function collapsedFooterUrl(): ?string
    {
        $url = $this->collapsedFooterUrl instanceof \Closure ? ($this->collapsedFooterUrl)() : $this->collapsedFooterUrl;
        return is_string($url) && $url !== '' ? $url : null;
    }

    /**
     * @param UserDataPackage $user
     * @return DashboardCardBody|null Lo que enseña; null si no tiene nada que enseñar
     * @throws \UnexpectedValueException si la función devuelve otra cosa (las de antes devolvían HTML)
     */
    public function content(UserDataPackage $user): ?DashboardCardBody
    {
        $body = ($this->content)($user);
        if ($body !== null && !$body instanceof DashboardCardBody) {
            throw new \UnexpectedValueException("La tarjeta «{$this->key}» debe devolver un DashboardCardBody o null, no " . get_debug_type($body) . '.');
        }
        return $body;
    }
}
