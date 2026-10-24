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
 * vista como botones de icono con el color de marca (la directa, el base; las demás, el secundario); con más de tres, la
 * directa a la vista y el resto en un menú «⋯». Descartable: un módulo puede seguir armando sus botones.
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

    /**
     * Una acción del menú o la directa.
     *
     * @param string $text Texto ya traducido.
     * @param string $url
     * @param string $icon Nombre del icono de Fomantic (p. ej. «edit», «external alternate»).
     * @param array<string,string> $attributes Atributos extra del enlace (p. ej. `target`), con el mismo filtro que los de
     *      eliminar.
     * @return array{text:string,url:string,icon:string,confirm:string|null,attributes:array<string,string>}
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
     * @return array{text:string,url:string,icon:string,confirm:string|null,attributes:array<string,string>}
     */
    public static function delete(string $text, string $url, string $confirm, array $attributes = []): array
    {
        return ['text' => $text, 'url' => $url, 'icon' => 'trash alternate', 'confirm' => $confirm, 'attributes' => $attributes];
    }

    /**
     * El HTML de la celda. Con tres acciones o menos (la directa incluida), todas a la vista; con más, la directa a la
     * vista y las de `$menu` en el «⋯». Sin acciones de menú no hay menú.
     *
     * @param array{text:string,url:string,icon:string,confirm:string|null,attributes:array<string,string>}|null $direct
     * @param array<array{text:string,url:string,icon:string,confirm:string|null,attributes:array<string,string>}> $menu
     * @return string
     */
    public static function cell(?array $direct, array $menu = []): string
    {
        $html = [];

        if ($direct !== null) {
            $html[] = self::button($direct, 'ui icon button brand-color');
        }

        if (($direct !== null ? 1 : 0) + count($menu) <= self::MAX_VISIBLE) {
            foreach ($menu as $action) {
                $html[] = self::button($action, 'ui icon button brand-color alt');
            }
        } else {
            $more = escape_html(__(AdminPanelController::ADMIN_LANG_GROUP, 'Más acciones'));
            $items = [];
            foreach ($menu as $action) {
                $text = escape_html($action['text']);
                $url = escape_html($action['url']);
                $icon = self::icon($action['icon']);
                $extra = self::attributes($action['attributes']);
                if ($action['confirm'] !== null) {
                    $confirm = escape_html($action['confirm']);
                    $items[] = "<a class=\"item\" href=\"#\" data-url=\"{$url}\" data-confirm=\"{$confirm}\"{$extra}><i class=\"red {$icon} icon\"></i><span class=\"ui red text\">{$text}</span></a>";
                } else {
                    $items[] = "<a class=\"item\" href=\"{$url}\"{$extra}><i class=\"{$icon} icon\"></i>{$text}</a>";
                }
            }
            $html[] = "<div class=\"ui icon top right pointing dropdown button brand-color alt\" row-actions-menu tabindex=\"0\" title=\"{$more}\" aria-label=\"{$more}\">"
                . '<i class="ellipsis horizontal icon"></i><div class="menu">' . implode('', $items) . '</div></div>';
        }

        return '<div class="row-actions">' . implode('', $html) . '</div>';
    }

    /**
     * Una acción como botón de icono a la vista. La de eliminar no es un enlace que borre: su dirección va en `data-url` y
     * su pregunta en `data-confirm`; y va en rojo, sea cual sea `$class`.
     *
     * @param array{text:string,url:string,icon:string,confirm:string|null,attributes:array<string,string>} $action
     * @param string $class
     * @return string
     */
    private static function button(array $action, string $class): string
    {
        //Eliminar sale en rojo, color de estado de Fomantic: borrar no se pinta como una acción más (PO, 2026-10-10).
        if ($action['confirm'] !== null) {
            $class = 'ui icon red button';
        }
        $text = escape_html($action['text']);
        $url = escape_html($action['url']);
        $icon = self::icon($action['icon']);
        $extra = self::attributes($action['attributes']);
        $target = $action['confirm'] !== null
            ? 'href="#" data-url="' . $url . '" data-confirm="' . escape_html($action['confirm']) . '"'
            : 'href="' . $url . '"';
        return "<a class=\"{$class}\" {$target} title=\"{$text}\" aria-label=\"{$text}\"{$extra}><i class=\"{$icon} icon\"></i></a>";
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
