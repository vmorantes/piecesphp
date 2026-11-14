<?php

/**
 * SidebarBlock.php
 */

namespace PiecesPHP\AdminPanel;

use PiecesPHP\UserSystem\UserDataPackage;

/**
 * SidebarBlock - Un bloque propio de un módulo o de un clon en uno de los huecos de la barra lateral del panel.
 * El HTML que devuelve su función se pinta TAL CUAL: lo que venga del usuario lo escapa quien la escribe.
 * Si devuelve '' (o solo espacios), el bloque no se pinta.
 *
 * @package     PiecesPHP\AdminPanel
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class SidebarBlock
{
    const KEY_PATTERN = DashboardCard::KEY_PATTERN;

    /**
     * Dentro de la marca, justo después de la versión.
     */
    const SLOT_BRAND_AFTER_META = 'brand-after-meta';
    /**
     * Dentro del menú, después de las entradas y antes de los logos del lateral contraído.
     */
    const SLOT_MENU_AFTER = 'menu-after';
    /**
     * En el pie, después del patrocinador y el crédito.
     */
    const SLOT_FOOTER = 'footer';

    const SLOTS = [self::SLOT_BRAND_AFTER_META, self::SLOT_MENU_AFTER, self::SLOT_FOOTER];

    const SHOW_ALWAYS = 'always';
    const SHOW_EXPANDED = 'expanded';
    const SHOW_COLLAPSED = 'collapsed';

    const SHOWS = [self::SHOW_ALWAYS, self::SHOW_EXPANDED, self::SHOW_COLLAPSED];

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $slot;

    /**
     * @var callable(UserDataPackage):mixed
     */
    private $render;

    /**
     * @var int
     */
    private $position;

    /**
     * @var string
     */
    private $show;

    /**
     * @param string $key Solo minúsculas, cifras y guiones
     * @param string $slot Una de las constantes SLOT_*
     * @param callable(UserDataPackage):mixed $render Devuelve el HTML del bloque; lo que no sea cadena cuenta como ''
     * @param int $position De menor a mayor; a igual posición, el orden de registro
     * @param string $show Una de las constantes SHOW_*: siempre, solo con el lateral expandido o solo contraído
     * @throws \InvalidArgumentException si la key no tiene la forma de KEY_PATTERN, o el hueco o la visibilidad no existen
     */
    public function __construct(string $key, string $slot, callable $render, int $position = 100, string $show = self::SHOW_ALWAYS)
    {
        if (preg_match(self::KEY_PATTERN, $key) !== 1) {
            throw new \InvalidArgumentException("La key de bloque de la barra lateral «{$key}» no es válida.");
        }
        if (!in_array($slot, self::SLOTS, true)) {
            throw new \InvalidArgumentException("El bloque de la barra lateral «{$key}» pide un hueco que no existe: «{$slot}».");
        }
        if (!in_array($show, self::SHOWS, true)) {
            throw new \InvalidArgumentException("El bloque de la barra lateral «{$key}» pide una visibilidad que no existe: «{$show}».");
        }
        $this->key = $key;
        $this->slot = $slot;
        $this->render = $render;
        $this->position = $position;
        $this->show = $show;
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
    public function slot(): string
    {
        return $this->slot;
    }

    /**
     * @return int
     */
    public function position(): int
    {
        return $this->position;
    }

    /**
     * @return string
     */
    public function show(): string
    {
        return $this->show;
    }

    /**
     * @param UserDataPackage $user
     * @return string El HTML del bloque; '' si la función no devuelve una cadena
     */
    public function render(UserDataPackage $user): string
    {
        $html = ($this->render)($user);
        return is_string($html) ? $html : '';
    }
}
