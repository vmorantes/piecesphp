<?php

/**
 * OrganizationsGeoSource.php
 */

namespace Organizations\Geo;

use Organizations\Controllers\OrganizationProfileController;
use Organizations\Mappers\OrganizationMapper;
use Organizations\OrganizationsLang;
use PiecesPHP\Core\Database\ActiveRecordModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\HavingItem;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\HavingSegment;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Statics\ProtectedUploads;
use PiecesPHP\Geo\GeoFilter;
use PiecesPHP\Geo\GeoPoint;
use PiecesPHP\Geo\GeoPointsResult;
use PiecesPHP\Geo\GeoQuery;
use PiecesPHP\Geo\GeoSource;
use PiecesPHP\Geo\GeoSourceSql;
use PiecesPHP\Geo\GeoThumbnail;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use SystemApprovals\SystemApprovalsRoutes;

/**
 * OrganizationsGeoSource - Las organizaciones en el geovisor: ni la global ni una inactiva o eliminada, con su perfil
 * público visible y aprobada si hay aprobaciones (P136). Su posición vive en `meta`, y se lee en el SQL: sin un mapper
 * por fila.
 *
 * @package     Organizations\Geo
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class OrganizationsGeoSource implements GeoSource
{
    const KEY = 'organizations';

    const FILTER_SEARCH = 'search';
    const FILTER_ORGANIZATIONS = 'organizations';

    /**
     * Solo un número decimal sale como coordenada: lo demás (vacío, texto) es NULL y no pasa el encuadre.
     */
    const NUMBER_PATTERN = '^-?[0-9]+([.][0-9]+)?$';

    /**
     * @return string
     */
    public function key(): string
    {
        return self::KEY;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return __(OrganizationsLang::LANG_GROUP, 'Organizaciones');
    }

    /**
     * @return string
     */
    public function permissionRoute(): string
    {
        return 'profile-organization-admin-profile';
    }

    /**
     * @return string
     */
    public function marker(): string
    {
        return GeoSource::MARKER_AVATAR;
    }

    /**
     * @return string
     */
    public function colorVariable(): string
    {
        return '--geo-organizations-color';
    }

    /**
     * @return string
     */
    public function cardView(): string
    {
        return __DIR__ . '/../Views/geo/card.php';
    }

    /**
     * @return GeoFilter[]
     */
    public function filters(): array
    {
        $model = OrganizationMapper::model();
        $model->select(self::fields());
        $model->having(self::visibleHaving());
        $model->orderBy('name ASC');
        $model->execute();
        $options = [];
        foreach ($model->result() ?? [] as $row) {
            $options[(int) $row->id] = (string) $row->name;
        }
        return [
            new GeoFilter(self::FILTER_SEARCH, __(OrganizationsLang::LANG_GROUP, 'Buscar'), GeoFilter::TYPE_TEXT),
            new GeoFilter(self::FILTER_ORGANIZATIONS, __(OrganizationsLang::LANG_GROUP, 'Organizaciones'), GeoFilter::TYPE_OPTIONS, $options),
        ];
    }

    /**
     * @param GeoQuery $query
     * @return GeoPointsResult
     */
    public function points(GeoQuery $query): GeoPointsResult
    {
        $model = OrganizationMapper::model();
        $model->select(self::fields());
        self::applyFilters($model, $query);
        [$total, $rows] = GeoSourceSql::countAndFetch($model, 'id ASC', $query->limit());

        $points = [];
        foreach ($rows as $row) {
            if (GeoQuery::isValidCoordinate($row['geoLongitude'] ?? null, $row['geoLatitude'] ?? null)) {
                $id = (int) $row['id'];
                $logoFile = self::logoFile($row['logo'] ?? null);
                $image = $logoFile !== null ? GeoThumbnail::url(self::KEY, $id, $logoFile) : self::logoURL($row['logo'] ?? null);
                $points[] = new GeoPoint($id, (float) $row['geoLongitude'], (float) $row['geoLatitude'], (string) $row['name'], $image);
            }
        }
        return new GeoPointsResult($points, $total);
    }

    /**
     * @param int $id
     * @return array<string,mixed>|null
     */
    public function pointDetail(int $id): ?array
    {
        $row = self::visibleRow($id);
        if ($row === null) {
            return null;
        }

        return [
            'name' => (string) $row->name,
            'city' => $row->cityName !== null ? (string) $row->cityName : null,
            'country' => $row->countryName !== null ? (string) $row->countryName : null,
            'url' => OrganizationProfileController::routeName('profile', ['organizationID' => $id]),
        ];
    }

    /**
     * El archivo del logo, si su punto se ve y el logo es un archivo de la aplicación.
     *
     * @param int $id
     * @return string|null
     */
    public function thumbnailSource(int $id): ?string
    {
        $row = self::visibleRow($id);
        return $row !== null ? self::logoFile($row->logo ?? null) : null;
    }

    /**
     * La fila de un punto que points() devolvería, buscada por su id en WHERE, o null.
     *
     * @param int $id
     * @return \stdClass|null
     */
    protected static function visibleRow(int $id): ?\stdClass
    {
        $where = GeoSourceSql::addBboxWhere(new WhereSegment(), self::coordinatePrefilter('longitude'), self::coordinatePrefilter('latitude'), new GeoQuery());
        $where->addCritery(new WhereItem(OrganizationMapper::TABLE . '.id', WhereItem::EQUAL_OPERATOR, $id));

        $model = OrganizationMapper::model();
        $model->select(self::fields());
        $model->where($where);
        $model->having(GeoSourceSql::addBbox(self::visibleHaving(), 'geoLongitude', 'geoLatitude', new GeoQuery()));
        $model->execute(false, 1, 1);
        $row = ($model->result() ?? [])[0] ?? null;
        return $row instanceof \stdClass && GeoQuery::isValidCoordinate($row->geoLongitude, $row->geoLatitude) ? $row : null;
    }

    /**
     * El archivo en disco de un logo guardado como ruta relativa a la aplicación (así lo guarda el formulario), solo si
     * existe y queda dentro de ella; null para una URL de fuera o lo que no resuelve. El del formulario es privado: en
     * disco lleva el sufijo de ProtectedUploads, y resolve() lo encuentra por su nombre público.
     *
     * @param mixed $logo
     * @return string|null
     */
    protected static function logoFile($logo): ?string
    {
        if (!is_string($logo) || trim($logo) === '' || preg_match('#^(https?:)?//#i', trim($logo)) === 1) {
            return null;
        }
        $base = realpath(basepath());
        [$resolved] = ProtectedUploads::resolve(basepath(ltrim(trim($logo), '/')));
        $file = $resolved !== null ? realpath($resolved) : false;
        return $base !== false && $file !== false && is_file($file) && str_starts_with($file, $base . \DIRECTORY_SEPARATOR) ? $file : null;
    }

    /**
     * La imagen del punto: el logo tal cual si ya es una URL absoluta o desde la raíz, y si no, relativo a la aplicación
     * (así lo guarda el formulario). Sin logo, null: el cliente pinta las iniciales.
     *
     * @param mixed $logo
     * @return string|null
     */
    protected static function logoURL($logo): ?string
    {
        if (!is_string($logo) || trim($logo) === '') {
            return null;
        }
        $logo = trim($logo);
        return preg_match('#^(https?://|/(?![/\\\\]))#i', $logo) === 1 ? $logo : baseurl($logo);
    }

    /**
     * Los de siempre, más la posición leída de `meta` como número.
     *
     * @return string[]
     */
    protected static function fields(): array
    {
        $fields = OrganizationMapper::fieldsToSelect();
        foreach (['longitude' => 'geoLongitude', 'latitude' => 'geoLatitude'] as $property => $alias) {
            $fields[] = self::coordinate($property) . " AS {$alias}";
        }
        return $fields;
    }

    /**
     * La coordenada leída de `meta` como número, o NULL. La misma expresión sirve al SELECT y al WHERE.
     *
     * @param string $property longitude o latitude
     * @return string
     */
    protected static function coordinate(string $property): string
    {
        $table = OrganizationMapper::TABLE;
        $pattern = self::NUMBER_PATTERN;
        $value = "JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$.{$property}'))";
        return "IF({$value} REGEXP '{$pattern}', {$value} + 0, NULL)";
    }

    /**
     * La coordenada de `meta` para el WHERE: un prefiltro que deja pasar todo lo que coordinate() acepta (un texto que
     * no es número vale 0 o su prefijo numérico, y el HAVING lo descarta después). No es coordinate() porque el marcador
     * de WhereItem se fabrica con el texto de la expresión y `$`, `^`, `?` o `+` lo rompen: aquí no hay ninguno.
     *
     * @param string $property longitude o latitude
     * @return string
     */
    protected static function coordinatePrefilter(string $property): string
    {
        $table = OrganizationMapper::TABLE;
        return "CAST(JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, CONCAT(CHAR(36), '.{$property}'))) AS DOUBLE)";
    }

    /**
     * El encuadre va dos veces: en WHERE, como prefiltro sobre `meta` (sin índice posible: es JSON), para que la
     * subconsulta de aprobación y el resto del SELECT solo se calculen para lo que puede caer dentro; y en HAVING, el
     * exacto, sobre los alias. La visibilidad, la búsqueda y las organizaciones van en HAVING porque son alias.
     *
     * @param ActiveRecordModel $model
     * @param GeoQuery $query
     * @return void
     * @throws \PiecesPHP\Geo\GeoQueryException si un filtro trae un valor que no sirve
     */
    protected static function applyFilters(ActiveRecordModel $model, GeoQuery $query): void
    {
        $search = GeoSourceSql::textFilter($query, self::FILTER_SEARCH);
        $organizations = GeoSourceSql::idsFilter($query, self::FILTER_ORGANIZATIONS);

        $having = GeoSourceSql::addBbox(self::visibleHaving(), 'geoLongitude', 'geoLatitude', $query);
        if ($search !== null) {
            GeoSourceSql::addSearch($having, ['name', 'fullLocation'], $search);
        }
        if (count($organizations) > 0) {
            GeoSourceSql::addIds($having, 'id', $organizations);
        }

        $model->where(GeoSourceSql::addBboxWhere(new WhereSegment(), self::coordinatePrefilter('longitude'), self::coordinatePrefilter('latitude'), $query));
        $model->having($having);
    }

    /**
     * Lo que hace visible a una organización en el mapa, sea cual sea el encuadre.
     *
     * @return HavingSegment
     */
    protected static function visibleHaving(): HavingSegment
    {
        $criteria = [
            new HavingItem('id', HavingItem::NOT_EQUAL_OPERATOR, OrganizationMapper::INITIAL_ID_GLOBAL, HavingItem::AND_OPERATOR),
            new HavingItem('publicVisible', HavingItem::EQUAL_OPERATOR, 1, HavingItem::AND_OPERATOR),
        ];
        if (SystemApprovalsRoutes::ENABLE) {
            $criteria[] = new HavingItem('systemApprovalStatus', HavingItem::EQUAL_OPERATOR, SystemApprovalsMapper::STATUS_APPROVED, HavingItem::AND_OPERATOR);
        }
        return GeoSourceSql::addIds(new HavingSegment($criteria), 'status', [OrganizationMapper::ACTIVE, OrganizationMapper::PENDING_APPROVAL]);
    }
}
