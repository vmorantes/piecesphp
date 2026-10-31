<?php

/**
 * StatusLabel.php
 */
namespace PiecesPHP\Core\Utilities\Helpers;

/**
 * StatusLabel
 *
 * La etiqueta de estado del diseño (design.md §3.5): `ui <color> basic circular label` con `circle icon`, y `ui label`
 * para lo neutro. Siempre `basic`: sobre un `ui <color> label` relleno, la regla de contraste automático del tema da
 * texto negro. Descartable: un módulo puede pintar la suya.
 *
 * Los textos llegan ya traducidos por quien llama, como en todo el framework; aquí se escapan.
 *
 * @category    Helpers
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class StatusLabel
{

    const POSITIVE = 'green';
    const NEGATIVE = 'red';
    const WARNING = 'yellow';
    const INFO = 'teal';

    /**
     * El estado con su color. Un color que no sea uno de los cuatro del diseño cae a neutral().
     *
     * @param string $text Texto ya traducido.
     * @param string $color Una de las constantes de la clase.
     * @return string
     */
    public static function html(string $text, string $color): string
    {
        if (!in_array($color, [self::POSITIVE, self::NEGATIVE, self::WARNING, self::INFO], true)) {
            return self::neutral($text);
        }
        return "<span class=\"ui {$color} basic circular label\"><i class=\"circle icon\" aria-hidden=\"true\"></i>" . escape_html($text) . '</span>';
    }

    /**
     * El estado sin color: lo que no es positivo, negativo, advertencia ni informativo.
     *
     * @param string $text Texto ya traducido.
     * @return string
     */
    public static function neutral(string $text): string
    {
        return '<span class="ui label">' . escape_html($text) . '</span>';
    }

}
