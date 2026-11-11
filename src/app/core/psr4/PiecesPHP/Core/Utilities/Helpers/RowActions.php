<?php

/**
 * RowActions.php
 */
namespace PiecesPHP\Core\Utilities\Helpers;

use PiecesPHP\AdminPanel\Controllers\AdminPanelController;

/**
 * RowActions
 *
 * La celda de acciones de una fila de listado (diseño de contenidos, §2.2, con P125): hasta tres acciones, todas a la
 * vista como botones de icono del tema (`ui icon button`; eliminar, `ui negative basic icon button`), cada uno con su
 * texto en aria-label y en un tooltip de Fomantic; con más de tres, la directa a la vista y el resto en un menú «⋯».
 * Tres variantes más (design.md §3.9): la principal de la fila, la que no aplica a esta fila y el botón de texto.
 * Descartable: un módulo puede seguir armando sus botones.
 *
 * Los textos llegan ya traducidos por quien llama, como en todo el framework; aquí se escapan TODOS, con su dirección.
 *
 * @category    Helpers
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class RowActions
{

    /** P125: hasta cuántas acciones van todas a la vista; con más, el resto pasa al menú «⋯». */
    const MAX_VISIBLE = 3;

    const VARIANT_PRIMARY = 'primary';
    const VARIANT_INACTIVE = 'inactive';
    const VARIANT_TEXT = 'text';
    const VARIANT_NEGATIVE = 'negative';

    /**
     * Una acción del menú o la directa.
     *
     * @param string $text Texto ya traducido.
     * @param string $url
     * @param string $icon Nombre del icono de Fomantic (p. ej. «edit», «external alternate»).
     * @param array<string,string> $attributes Atributos extra del enlace (p. ej. `target`), con el mismo filtro que los de
     *      eliminar.
     * @return array{text:string,url:string,icon:string,confirm:string|null,attributes:array<string,string>,variant?:string}
     */
    public static function action(string $text, string $url, string $icon, array $attributes = []): array
    {
        return ['text' => $text, 'url' => $url, 'icon' => $icon, 'confirm' => null, 'attributes' => $attributes];
    }

    /**
     * La acción de eliminar: en rojo y con la pregunta de confirmación en `data-confirm`. Sin JS de borrado: quien la use
     * lee `data-url` y `data-confirm`.
     *
     * @param string $text Texto ya traducido.
     * @param string $url
     * @param string $confirm Pregunta ya traducida.
     * @param array<string,string> $attributes Atributos extra del ítem (nombre => valor), para que un módulo conserve su
     *      manejador de borrado. El nombre se filtra y el valor se escapa.
     * @return array{text:string,url:string,icon:string,confirm:string|null,attributes:array<string,string>,variant?:string}
     */
    public static function delete(string $text, string $url, string $confirm, array $attributes = []): array
    {
        return ['text' => $text, 'url' => $url, 'icon' => 'trash alternate', 'confirm' => $confirm, 'attributes' => $attributes];
    }

    /**
     * La acción principal de la fila (`ui primary icon button`): una sola, y solo si la fila tiene una (design.md §3.5).
     *
     * @param string $text Texto ya traducido.
     * @param string $url
     * @param string $icon Nombre del icono de Fomantic.
     * @param array<string,string> $attributes Atributos extra del enlace, con el mismo filtro que los de action().
     * @return array{text:string,url:string,icon:string,confirm:string|null,attributes:array<string,string>,variant?:string}
     */
    public static function primary(string $text, string $url, string $icon, array $attributes = []): array
    {
        return ['variant' => self::VARIANT_PRIMARY] + self::action($text, $url, $icon, $attributes);
    }

    /**
     * Una acción destructiva que no es borrar (expulsar, revocar): en rojo como eliminar (`ui negative basic icon button`),
     * pero con su enlace y sus atributos, sin la confirmación genérica de borrado; la confirma quien la maneja.
     *
     * @param string $text Texto ya traducido.
     * @param string $url
     * @param string $icon Nombre del icono de Fomantic.
     * @param array<string,string> $attributes Atributos extra del enlace, con el mismo filtro que los de action().
     * @return array{text:string,url:string,icon:string,confirm:string|null,attributes:array<string,string>,variant?:string}
     */
    public static function negative(string $text, string $url, string $icon, array $attributes = []): array
    {
        return ['variant' => self::VARIANT_NEGATIVE] + self::action($text, $url, $icon, $attributes);
    }

    /**
     * La acción que no aplica a esta fila: se ve, sigue enfocable y enseña «No aplica», pero no hace nada al pulsarla. Por
     * eso es un `button` con `aria-disabled` y sin el atributo `disabled`, que le quitaría el foco y el tooltip.
     *
     * @param string $text Texto ya traducido.
     * @param string $icon Nombre del icono de Fomantic.
     * @return array{text:string,url:string,icon:string,confirm:string|null,attributes:array<string,string>,variant?:string}
     */
    public static function inactive(string $text, string $icon): array
    {
        return ['variant' => self::VARIANT_INACTIVE] + self::action($text, '', $icon);
    }

    /**
     * Un botón de texto en la fila (`ui basic button`), como «Ver cuerpo» en Correos enviados.
     *
     * @param string $text Texto ya traducido.
     * @param string $url
     * @param array<string,string> $attributes Atributos extra del enlace, con el mismo filtro que los de action().
     * @return array{text:string,url:string,icon:string,confirm:string|null,attributes:array<string,string>,variant?:string}
     */
    public static function text(string $text, string $url, array $attributes = []): array
    {
        return ['variant' => self::VARIANT_TEXT] + self::action($text, $url, '', $attributes);
    }

    /**
     * El HTML de la celda. Con tres acciones o menos (la directa incluida), todas a la vista; con más, la directa a la
     * vista y las de `$menu` en el «⋯». Sin acciones de menú no hay menú.
     *
     * @param array{text:string,url:string,icon:string,confirm:string|null,attributes:array<string,string>,variant?:string}|null $direct
     * @param array<array{text:string,url:string,icon:string,confirm:string|null,attributes:array<string,string>,variant?:string}> $menu
     * @return string
     */
    public static function cell(?array $direct, array $menu = []): string
    {
        $html = [];
        $allVisible = ($direct !== null ? 1 : 0) + count($menu) <= self::MAX_VISIBLE;
        //El tooltip del último botón de la celda se abre hacia la izquierda: en el borde de la tabla no cabe centrado.
        $visible = array_merge($direct !== null ? [$direct] : [], $allVisible ? $menu : []);
        $lastVisible = count($visible) - 1;

        foreach ($visible as $index => $action) {
            $html[] = self::button($action, $allVisible && $index === $lastVisible);
        }

        if (!$allVisible) {
            $more = escape_html(__(AdminPanelController::ADMIN_LANG_GROUP, 'Más acciones'));
            $items = [];
            foreach ($menu as $action) {
                $text = escape_html($action['text']);
                $url = escape_html($action['url']);
                $icon = self::icon($action['icon']);
                $extra = self::attributes($action['attributes']);
                $variant = $action['variant'] ?? '';
                if ($variant === self::VARIANT_INACTIVE) {
                    $items[] = "<div class=\"disabled item\"><i class=\"{$icon} icon\"></i>{$text}</div>";
                } elseif ($variant === self::VARIANT_TEXT) {
                    $items[] = "<a class=\"item\" href=\"{$url}\"{$extra}>{$text}</a>";
                } elseif ($action['confirm'] !== null) {
                    $confirm = escape_html($action['confirm']);
                    $items[] = "<a class=\"item\" href=\"#\" data-url=\"{$url}\" data-confirm=\"{$confirm}\"{$extra}><i class=\"red {$icon} icon\"></i><span class=\"ui red text\">{$text}</span></a>";
                } else {
                    $items[] = "<a class=\"item\" href=\"{$url}\"{$extra}><i class=\"{$icon} icon\"></i>{$text}</a>";
                }
            }
            $html[] = "<div class=\"ui icon top right pointing dropdown button\" row-actions-menu tabindex=\"0\" title=\"{$more}\" aria-label=\"{$more}\">"
                . '<i class="ellipsis horizontal icon"></i><div class="menu">' . implode('', $items) . '</div></div>';
        }

        //Un espacio entre botones: en el tema los botones no tienen margen propio.
        return '<div class="row-actions">' . implode(' ', $html) . '</div>';
    }

    /**
     * Una acción como botón de icono a la vista. La de eliminar no es un enlace que borre: su dirección va en `data-url` y
     * su pregunta en `data-confirm`; y va en rojo (destructivo).
     *
     * @param array{text:string,url:string,icon:string,confirm:string|null,attributes:array<string,string>,variant?:string} $action
     * @param bool $last El último de la celda: su tooltip se abre hacia la izquierda
     * @return string
     */
    private static function button(array $action, bool $last): string
    {
        $variant = $action['variant'] ?? '';
        $position = $last ? 'top right' : 'top center';
        $text = escape_html($action['text']);
        $url = escape_html($action['url']);
        $icon = self::icon($action['icon']);
        $extra = self::attributes($action['attributes']);
        if ($variant === self::VARIANT_INACTIVE) {
            $label = escape_html(sprintf(__(AdminPanelController::ADMIN_LANG_GROUP, '%s (no aplica)'), $action['text']));
            $tooltip = escape_html(__(AdminPanelController::ADMIN_LANG_GROUP, 'No aplica'));
            return "<button type=\"button\" class=\"ui icon button disabled\" aria-disabled=\"true\" aria-label=\"{$label}\" data-tooltip=\"{$tooltip}\" data-position=\"{$position}\" data-inverted=\"\"><i class=\"{$icon} icon\" aria-hidden=\"true\"></i></button>";
        }
        if ($variant === self::VARIANT_TEXT) {
            return "<a class=\"ui basic button\" href=\"{$url}\"{$extra}>{$text}</a>";
        }
        //Eliminar sale en rojo, color de estado de Fomantic: borrar no se pinta como una acción más (PO, 2026-10-10).
        $class = $action['confirm'] !== null || $variant === self::VARIANT_NEGATIVE ? 'ui negative basic icon button' : ($variant === self::VARIANT_PRIMARY ? 'ui primary icon button' : 'ui icon button');
        $target = $action['confirm'] !== null
            ? 'href="#" data-url="' . $url . '" data-confirm="' . escape_html($action['confirm']) . '"'
            : 'href="' . $url . '"';
        return "<a class=\"{$class}\" {$target} aria-label=\"{$text}\" data-tooltip=\"{$text}\" data-position=\"{$position}\" data-inverted=\"\"{$extra}><i class=\"{$icon} icon\"></i></a>";
    }

    /**
     * Los atributos extra: el nombre, solo letras, números, guiones, puntos, dos puntos y subrayados, y nunca un `on…`
     * (un manejador de eventos); el valor, escapado. Pública porque las piezas de contenido (page-header.php,
     * form-action-bar.php) pintan los suyos con este mismo filtro: una sola implementación.
     *
     * @param array<string,string> $attributes
     * @return string Con un espacio delante de cada atributo, o vacío.
     */
    public static function attributes(array $attributes): string
    {
        $html = '';
        foreach ($attributes as $name => $value) {
            $name = (string) preg_replace('/[^a-zA-Z0-9_:.-]/', '', (string) $name);
            if ($name !== '' && stripos($name, 'on') !== 0) {
                $html .= ' ' . $name . '="' . escape_html($value) . '"';
            }
        }
        return $html;
    }

    /**
     * Solo letras, números, guiones y espacios: el nombre del icono va dentro de un atributo `class`.
     *
     * @param string $icon
     * @return string
     */
    private static function icon(string $icon): string
    {
        return trim((string) preg_replace('/[^a-z0-9 -]/', '', mb_strtolower($icon)));
    }

}
