<?php

/**
 * GeoPoint.php
 */

namespace PiecesPHP\Geo;

/**
 * GeoPoint - Lo mínimo de un punto: lo demás se pide con el detalle.
 *
 * @package     PiecesPHP\Geo
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class GeoPoint
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var float
     */
    public $lng;

    /**
     * @var float
     */
    public $lat;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string|null
     */
    public $thumbnail;

    /**
     * @param int $id
     * @param float $lng
     * @param float $lat
     * @param string $title Tal cual: el cliente lo escapa
     * @param string|null $thumbnail
     */
    public function __construct(int $id, float $lng, float $lat, string $title, ?string $thumbnail = null)
    {
        $this->id = $id;
        $this->lng = $lng;
        $this->lat = $lat;
        $this->title = $title;
        $this->thumbnail = $thumbnail;
    }
}
