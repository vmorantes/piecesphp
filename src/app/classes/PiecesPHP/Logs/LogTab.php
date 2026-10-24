<?php

/**
 * LogTab.php
 */

namespace PiecesPHP\Logs;

use PiecesPHP\Core\Routing\RequestRoute as Request;

/**
 * LogTab - Una pestaña de la vista «Registros»: la declara el módulo dueño del registro, no la vista.
 *
 * Se ve si el usuario tiene permiso a `permissionRoute`: el nombre de la ruta es el permiso, como en todo el framework.
 *
 * @package     PiecesPHP\Logs
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class LogTab
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
     * @var callable(Request,string):string
     */
    private $content;

    /**
     * @var int
     */
    private $position;

    /**
     * @param string $key Va en ?tab=, así que solo minúsculas, cifras y guiones
     * @param string $title Ya traducido
     * @param string $icon Clases de un icono de Fomantic, sin «icon»
     * @param string $permissionRoute El nombre de la ruta cuyo permiso hace visible la pestaña
     * @param callable(Request,string):string $content Devuelve el HTML de la pestaña; recibe la petición y la URL de la
     *      propia pestaña, para que sus enlaces (filtros, páginas, sub-vistas) no escriban la dirección a mano
     * @param int $position De menor a mayor; a igual posición, el orden de registro
     * @throws \InvalidArgumentException si la key no tiene la forma de KEY_PATTERN
     */
    public function __construct(string $key, string $title, string $icon, string $permissionRoute, callable $content, int $position = 100)
    {
        if (preg_match(self::KEY_PATTERN, $key) !== 1) {
            throw new \InvalidArgumentException("La key de pestaña «{$key}» no es válida.");
        }
        $this->key = $key;
        $this->title = $title;
        $this->icon = $icon;
        $this->permissionRoute = $permissionRoute;
        $this->content = $content;
        $this->position = $position;
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
     * @param Request $request
     * @param string $tabURL
     * @return string
     */
    public function render(Request $request, string $tabURL): string
    {
        return (string) ($this->content)($request, $tabURL);
    }
}
