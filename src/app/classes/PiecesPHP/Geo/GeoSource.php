<?php

/**
 * GeoSource.php
 */

namespace PiecesPHP\Geo;

/**
 * GeoSource - Una fuente de puntos del geovisor. Cada módulo implementa las suyas y las registra en GeoSourceRegistry
 * desde su propio Routes; el geovisor solo pinta lo que ellas devuelven.
 *
 * @package     PiecesPHP\Geo
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
interface GeoSource
{
    const MARKER_DOT = 'dot';
    const MARKER_AVATAR = 'avatar';

    /**
     * Única en el registro, y la que viaja en la respuesta.
     *
     * @return string
     */
    public function key(): string;

    /**
     * Ya traducido.
     *
     * @return string
     */
    public function title(): string;

    /**
     * El nombre de ruta cuyo permiso hace visible la fuente: quien no lo tiene, no la ve ni la pide.
     *
     * @return string
     */
    public function permissionRoute(): string;

    /**
     * GeoSource::MARKER_DOT o GeoSource::MARKER_AVATAR.
     *
     * @return string
     */
    public function marker(): string;

    /**
     * El nombre de una variable CSS (`--geo-…`), nunca un color.
     *
     * @return string
     */
    public function colorVariable(): string;

    /**
     * Los filtros que entiende; los que no declara, los ignora.
     *
     * @return GeoFilter[]
     */
    public function filters(): array;

    /**
     * Los puntos del encuadre con los filtros, hasta la cota de la consulta.
     *
     * @param GeoQuery $query
     * @return GeoPointsResult
     * @throws GeoQueryException si un filtro declarado trae un valor que no le sirve
     */
    public function points(GeoQuery $query): GeoPointsResult;

    /**
     * Los datos de la tarjeta de un punto, o null si la fuente no lo devolvería en points().
     *
     * @param int $id
     * @return array<string,mixed>|null
     */
    public function pointDetail(int $id): ?array;

    /**
     * El archivo ORIGINAL de la imagen de un punto, en disco, del que se saca su miniatura; o null si no tiene imagen o
     * si la fuente no lo devolvería en points(). Nunca una URL: la miniatura la genera y la sirve GeoThumbnail.
     *
     * @param int $id
     * @return string|null
     */
    public function thumbnailSource(int $id): ?string;

    /**
     * Ruta absoluta de la vista de su tarjeta: solo marcado fijo, con data-geo-field y data-geo-href donde va cada clave de pointDetail().
     *
     * @return string
     */
    public function cardView(): string;
}
