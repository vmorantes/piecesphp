<?php

/**
 * GeoPointsResult.php
 */

namespace PiecesPHP\Geo;

/**
 * GeoPointsResult - Lo que devuelve una fuente: los puntos, hasta la cota, y cuántos había antes de ella.
 *
 * @package     PiecesPHP\Geo
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class GeoPointsResult
{
    /**
     * @var GeoPoint[]
     */
    private $points;

    /**
     * @var int
     */
    private $total;

    /**
     * @param GeoPoint[] $points
     * @param int $total Cuántos hay en el encuadre con los filtros, ANTES de la cota
     */
    public function __construct(array $points, int $total)
    {
        $this->points = array_values($points);
        $this->total = max($total, count($this->points));
    }

    /**
     * @return GeoPoint[]
     */
    public function points(): array
    {
        return $this->points;
    }

    /**
     * @return int
     */
    public function total(): int
    {
        return $this->total;
    }

    /**
     * @return bool
     */
    public function truncated(): bool
    {
        return $this->total > count($this->points);
    }
}
