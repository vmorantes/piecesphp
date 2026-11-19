<?php

/**
 * DashboardCardBody.php
 */

namespace PiecesPHP\AdminPanel;

/**
 * DashboardCardBody - Lo que enseña una tarjeta del inicio, que la pinta como la tarjeta de resumen del tema
 * (statistic-card.php con `figure`): unas cifras, una acción (acceso directo) o, para lo que no encaje, HTML.
 *
 * Lo devuelve la función de contenido de cada DashboardCard; el inicio lo pinta. Así todas las tarjetas tienen la misma
 * forma sin que cada módulo la escriba.
 *
 * @package     PiecesPHP\AdminPanel
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class DashboardCardBody
{
    const KIND_NUMBERS = 'numbers';
    const KIND_ACTION = 'action';
    const KIND_HTML = 'html';

    /**
     * En desuso: el inicio ya no pinta el color de las cifras; se retira en la 11.0.0.
     */
    const COLORS = ['green', 'orange'];

    /**
     * @var string
     */
    private $kind;

    /**
     * @var array<int,array{value:int|string,concept:string,subconcept:?string,icon:?string,color:?string}>
     */
    private $items = [];

    /**
     * @var string|null
     */
    private $footerCaption = null;

    /**
     * @var string|null
     */
    private $footerUrl = null;

    /**
     * @var string
     */
    private $url = '';

    /**
     * @var string
     */
    private $description = '';

    /**
     * @var string
     */
    private $html = '';

    private function __construct(string $kind)
    {
        $this->kind = $kind;
    }

    /**
     * De una a tres cifras, en la misma tarjeta.
     *
     * @param array<int,array{value:int|string,concept:string,subconcept?:string|null,icon?:string|null,color?:string|null}> $items
     *      `concept` y `subconcept`, ya traducidos. `icon` y `color` (`green` u `orange`), en desuso: el inicio ya no los
     *      pinta; se retiran en la 11.0.0 (un color fuera de la lista sigue lanzando).
     * @param string|null $footerCaption El texto del pie, ya traducido
     * @param string|null $footerUrl Adónde lleva el enlace del pie
     * @return self
     * @throws \InvalidArgumentException con cero o más de tres cifras, o con una cifra mal formada
     */
    public static function numbers(array $items, ?string $footerCaption = null, ?string $footerUrl = null): self
    {
        $count = count($items);
        if ($count < 1 || $count > 3) {
            throw new \InvalidArgumentException("Una tarjeta del inicio lleva de una a tres cifras, no {$count}.");
        }
        $body = new self(self::KIND_NUMBERS);
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item) || !isset($item['value'], $item['concept']) || !(is_int($item['value']) || is_string($item['value'])) || !is_string($item['concept'])) {
                throw new \InvalidArgumentException("La cifra {$index} de la tarjeta necesita `value` (int o string) y `concept` (string).");
            }
            $color = $item['color'] ?? null;
            if ($color !== null && !in_array($color, self::COLORS, true)) {
                throw new \InvalidArgumentException("El color de la cifra {$index} solo puede ser " . implode(' u ', self::COLORS) . '.');
            }
            $body->items[] = [
                'value' => $item['value'],
                'concept' => $item['concept'],
                'subconcept' => isset($item['subconcept']) && is_string($item['subconcept']) ? $item['subconcept'] : null,
                'icon' => isset($item['icon']) && is_string($item['icon']) ? $item['icon'] : null,
                'color' => $color,
            ];
        }
        $body->footerCaption = $footerCaption;
        $body->footerUrl = $footerUrl;
        return $body;
    }

    /**
     * Una acción: la tarjeta entera es un enlace (acceso directo), con el título de la tarjeta y esta frase. Sin icono:
     * el de la tarjeta está en desuso.
     *
     * @param string $url
     * @param string $description Ya traducida
     * @param string|null $footerCaption El texto del pie, ya traducido; sin él, la tarjeta no lleva pie
     * @return self
     */
    public static function action(string $url, string $description, ?string $footerCaption = null): self
    {
        $body = new self(self::KIND_ACTION);
        $body->url = $url;
        $body->description = $description;
        $body->footerCaption = $footerCaption;
        return $body;
    }

    /**
     * La salida para lo que no sea cifras ni acción: el HTML va dentro de la tarjeta de estadística, ya escapado por quien
     * llama.
     *
     * @param string $html
     * @param string|null $footerCaption El texto del pie, ya traducido
     * @param string|null $footerUrl Adónde lleva el enlace del pie
     * @return self
     */
    public static function html(string $html, ?string $footerCaption = null, ?string $footerUrl = null): self
    {
        $body = new self(self::KIND_HTML);
        $body->html = $html;
        $body->footerCaption = $footerCaption;
        $body->footerUrl = $footerUrl;
        return $body;
    }

    /**
     * @return string Una de las constantes KIND_*
     */
    public function kind(): string
    {
        return $this->kind;
    }

    /**
     * @return array<int,array{value:int|string,concept:string,subconcept:?string,icon:?string,color:?string}>
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * @return string|null
     */
    public function footerCaption(): ?string
    {
        return $this->footerCaption;
    }

    /**
     * @return string|null
     */
    public function footerUrl(): ?string
    {
        return $this->footerUrl;
    }

    /**
     * @return string
     */
    public function url(): string
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function htmlContent(): string
    {
        return $this->html;
    }
}
