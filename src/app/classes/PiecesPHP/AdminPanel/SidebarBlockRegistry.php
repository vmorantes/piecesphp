<?php

/**
 * SidebarBlockRegistry.php
 */

namespace PiecesPHP\AdminPanel;

use PiecesPHP\UserSystem\UserDataPackage;

/**
 * SidebarBlockRegistry - El registro de los bloques de la barra lateral del panel. Cada módulo registra los suyos desde
 * su propio Routes; un clon quita (remove()) o sustituye (replace()) los que no quiera, desde su configuración.
 *
 * @package     PiecesPHP\AdminPanel
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class SidebarBlockRegistry
{
    /**
     * @var array<string,SidebarBlock>
     */
    private static $blocks = [];

    /**
     * @param SidebarBlock $block
     * @return void
     * @throws \InvalidArgumentException si la key ya está registrada
     */
    public static function register(SidebarBlock $block): void
    {
        if (array_key_exists($block->key(), self::$blocks)) {
            throw new \InvalidArgumentException("Ya hay un bloque de la barra lateral con la key «{$block->key()}».");
        }
        self::$blocks[$block->key()] = $block;
    }

    /**
     * @param string $key
     * @return bool false si no estaba
     */
    public static function remove(string $key): bool
    {
        if (!array_key_exists($key, self::$blocks)) {
            return false;
        }
        unset(self::$blocks[$key]);
        return true;
    }

    /**
     * Sustituye un bloque registrado por otro con la misma key, en su sitio del orden de registro.
     *
     * @param SidebarBlock $block
     * @return void
     * @throws \InvalidArgumentException si no hay ninguno con esa key
     */
    public static function replace(SidebarBlock $block): void
    {
        if (!array_key_exists($block->key(), self::$blocks)) {
            throw new \InvalidArgumentException("No hay ningún bloque de la barra lateral con la key «{$block->key()}» que sustituir.");
        }
        self::$blocks[$block->key()] = $block;
    }

    /**
     * @param string $key
     * @return SidebarBlock|null
     */
    public static function get(string $key): ?SidebarBlock
    {
        return self::$blocks[$key] ?? null;
    }

    /**
     * Por posición y, a igual posición, por orden de registro.
     *
     * @return array<string,SidebarBlock>
     */
    public static function all(): array
    {
        $blocks = self::$blocks;
        $order = array_flip(array_keys($blocks));
        uasort($blocks, fn(SidebarBlock $a, SidebarBlock $b): int => [$a->position(), $order[$a->key()]] <=> [$b->position(), $order[$b->key()]]);
        return $blocks;
    }

    /**
     * El HTML de un hueco: cada bloque que no quede vacío, en su envoltorio. Sin usuario o sin bloques, ''.
     *
     * @param string $slot Una de las constantes SidebarBlock::SLOT_*
     * @param UserDataPackage|null $user
     * @return string
     */
    public static function renderSlot(string $slot, ?UserDataPackage $user): string
    {
        if ($user === null) {
            return '';
        }
        $html = '';
        foreach (self::all() as $block) {
            if ($block->slot() !== $slot) {
                continue;
            }
            $content = $block->render($user);
            if (trim($content) === '') {
                continue;
            }
            $class = match ($block->show()) {
                SidebarBlock::SHOW_EXPANDED => ' expanded only',
                SidebarBlock::SHOW_COLLAPSED => ' collapsed only',
                default => '',
            };
            $html .= '<div class="sidebar-block' . $class . '" data-sidebar-block="' . escape_html($block->key()) . '">' . $content . '</div>';
        }
        return $html;
    }

    /**
     * SOLO PARA PRUEBAS: vacía el registro en memoria y devuelve lo que había, para reponerlo después.
     *
     * @internal
     * @param array<string,SidebarBlock>|null $restore
     * @return array<string,SidebarBlock>
     */
    public static function swapForTesting(?array $restore = null): array
    {
        $previous = self::$blocks;
        self::$blocks = $restore ?? [];
        return $previous;
    }
}
