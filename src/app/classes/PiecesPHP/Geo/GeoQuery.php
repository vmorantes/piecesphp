<?php

/**
 * GeoQuery.php
 */

namespace PiecesPHP\Geo;

use PiecesPHP\Core\Routing\RequestRoute as Request;

/**
 * GeoQuery - Lo que se le pregunta a una fuente: encuadre, acercamiento, filtros y cota. Inmutable y validada al
 * construirse: un valor fuera de rango lanza GeoQueryException (un 400), nunca se recorta.
 *
 * @package     PiecesPHP\Geo
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class GeoQuery
{
    /**
     * La cota de puntos por fuente. La petición no la sube.
     */
    const DEFAULT_LIMIT = 5000;

    const MIN_ZOOM = 0;
    const MAX_ZOOM = 24;

    /**
     * [oeste, sur, este, norte]: el mundo.
     */
    const WORLD = [-180.0, -90.0, 180.0, 90.0];

    /**
     * @var float[] [oeste, sur, este, norte]
     */
    private $bbox;

    /**
     * @var int
     */
    private $zoom;

    /**
     * @var int
     */
    private $limit;

    /**
     * @var array<string,scalar|scalar[]>
     */
    private $filters;

    /**
     * @param float[]|null $bbox [oeste, sur, este, norte]; null es el mundo
     * @param int $zoom
     * @param array<string,mixed> $filters Por clave: un escalar o una lista de escalares
     * @param int $limit Entre 1 y DEFAULT_LIMIT; la petición nunca lo pasa (fromRequest() lo ignora)
     * @throws GeoQueryException
     */
    public function __construct(?array $bbox = null, int $zoom = self::MIN_ZOOM, array $filters = [], int $limit = self::DEFAULT_LIMIT)
    {
        $this->bbox = self::validBbox($bbox ?? self::WORLD);
        if ($zoom < self::MIN_ZOOM || $zoom > self::MAX_ZOOM) {
            throw new GeoQueryException(sprintf(__(GeoLang::LANG_GROUP, 'El acercamiento debe estar entre %d y %d.'), self::MIN_ZOOM, self::MAX_ZOOM));
        }
        if ($limit < 1 || $limit > self::DEFAULT_LIMIT) {
            throw new GeoQueryException(sprintf(__(GeoLang::LANG_GROUP, 'La cota debe estar entre 1 y %d.'), self::DEFAULT_LIMIT));
        }
        $this->zoom = $zoom;
        $this->limit = $limit;
        $this->filters = self::validFilters($filters);
    }

    /**
     * Desde la petición: `bbox` («oeste,sur,este,norte»), `zoom` y `filters[...]`. Un `limit` de la petición se ignora.
     *
     * @param Request $request
     * @return self
     * @throws GeoQueryException
     */
    public static function fromRequest(Request $request): self
    {
        $params = $request->getQueryParams();
        return self::fromParams(is_array($params) ? $params : []);
    }

    /**
     * Lo mismo que fromRequest(), desde los parámetros ya leídos.
     *
     * @param array<mixed> $params
     * @return self
     * @throws GeoQueryException
     */
    public static function fromParams(array $params): self
    {
        $bbox = null;
        if (array_key_exists('bbox', $params)) {
            $raw = $params['bbox'];
            $parts = is_string($raw) ? explode(',', $raw) : [];
            if (count($parts) !== 4) {
                throw new GeoQueryException(__(GeoLang::LANG_GROUP, 'El encuadre debe tener cuatro números: oeste, sur, este y norte.'));
            }
            $bbox = [];
            foreach ($parts as $part) {
                $part = trim($part);
                if (!is_numeric($part)) {
                    throw new GeoQueryException(__(GeoLang::LANG_GROUP, 'El encuadre debe tener cuatro números: oeste, sur, este y norte.'));
                }
                $bbox[] = (float) $part;
            }
        }

        $zoom = self::MIN_ZOOM;
        if (array_key_exists('zoom', $params)) {
            $raw = $params['zoom'];
            if (!is_string($raw) || preg_match('/^-?\d+$/', trim($raw)) !== 1) {
                throw new GeoQueryException(sprintf(__(GeoLang::LANG_GROUP, 'El acercamiento debe estar entre %d y %d.'), self::MIN_ZOOM, self::MAX_ZOOM));
            }
            $zoom = (int) trim($raw);
        }

        $filters = $params['filters'] ?? [];
        if (!is_array($filters)) {
            throw new GeoQueryException(__(GeoLang::LANG_GROUP, 'Los filtros no tienen una forma válida.'));
        }

        return new self($bbox, $zoom, $filters);
    }

    /**
     * @return float[] [oeste, sur, este, norte]
     */
    public function bbox(): array
    {
        return $this->bbox;
    }

    /**
     * @return float
     */
    public function west(): float
    {
        return $this->bbox[0];
    }

    /**
     * @return float
     */
    public function south(): float
    {
        return $this->bbox[1];
    }

    /**
     * @return float
     */
    public function east(): float
    {
        return $this->bbox[2];
    }

    /**
     * @return float
     */
    public function north(): float
    {
        return $this->bbox[3];
    }

    /**
     * @return int
     */
    public function zoom(): int
    {
        return $this->zoom;
    }

    /**
     * @return int
     */
    public function limit(): int
    {
        return $this->limit;
    }

    /**
     * @return array<string,scalar|scalar[]>
     */
    public function filters(): array
    {
        return $this->filters;
    }

    /**
     * @param string $key
     * @return scalar|scalar[]|null
     */
    public function filter(string $key)
    {
        return $this->filters[$key] ?? null;
    }

    /**
     * Si la coordenada sale en un mapa: numérica, en rango y no el (0,0), que es el valor por omisión y no un lugar.
     *
     * @param mixed $lng
     * @param mixed $lat
     * @return bool
     */
    public static function isValidCoordinate($lng, $lat): bool
    {
        if (!is_numeric($lng) || !is_numeric($lat)) {
            return false;
        }
        $lng = (float) $lng;
        $lat = (float) $lat;
        if ($lng < -180 || $lng > 180 || $lat < -90 || $lat > 90) {
            return false;
        }
        return !($lng == 0.0 && $lat == 0.0);
    }

    /**
     * @param mixed[] $bbox
     * @return float[]
     * @throws GeoQueryException
     */
    private static function validBbox(array $bbox): array
    {
        $bbox = array_values($bbox);
        $numbers = count($bbox) === 4 && count(array_filter($bbox, fn($value): bool => is_int($value) || is_float($value))) === 4;
        if (!$numbers) {
            throw new GeoQueryException(__(GeoLang::LANG_GROUP, 'El encuadre debe tener cuatro números: oeste, sur, este y norte.'));
        }
        [$west, $south, $east, $north] = array_map('floatval', $bbox);
        if ($west < -180 || $east > 180 || $south < -90 || $north > 90) {
            throw new GeoQueryException(__(GeoLang::LANG_GROUP, 'El encuadre se sale del mapa: la longitud va de -180 a 180 y la latitud de -90 a 90.'));
        }
        if (!($west < $east) || !($south < $north)) {
            throw new GeoQueryException(__(GeoLang::LANG_GROUP, 'El encuadre está invertido: el oeste va antes que el este y el sur antes que el norte.'));
        }
        return [$west, $south, $east, $north];
    }

    /**
     * @param array<mixed> $filters
     * @return array<string,scalar|scalar[]>
     * @throws GeoQueryException
     */
    private static function validFilters(array $filters): array
    {
        $valid = [];
        foreach ($filters as $key => $value) {
            $isList = is_array($value) && array_is_list($value) && count(array_filter($value, 'is_scalar')) === count($value);
            if (!is_string($key) || (!is_scalar($value) && !$isList)) {
                throw new GeoQueryException(__(GeoLang::LANG_GROUP, 'Los filtros no tienen una forma válida.'));
            }
            $valid[$key] = $value;
        }
        return $valid;
    }
}
