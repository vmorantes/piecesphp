<?php

/**
 * PersonsGeoSource.php
 */

namespace Directory\Geo;

use Directory\Controllers\ProfileController;
use Directory\DirectoryLang;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\Database\ActiveRecordModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\HavingItem;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\HavingSegment;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Geo\GeoFilter;
use PiecesPHP\Geo\GeoPoint;
use PiecesPHP\Geo\GeoPointsResult;
use PiecesPHP\Geo\GeoQuery;
use PiecesPHP\Geo\GeoSource;
use PiecesPHP\Geo\GeoSourceSql;
use PiecesPHP\Geo\GeoThumbnail;
use PiecesPHP\UserSystem\ORM\AvatarModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use SystemApprovals\SystemApprovalsRoutes;

/**
 * PersonsGeoSource - Las personas en el geovisor: un perfil público visible, de un usuario ni inactivo ni eliminado y
 * aprobado si hay aprobaciones (P136). El id de cada punto es el del usuario, que es el de su perfil público.
 *
 * @package     Directory\Geo
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class PersonsGeoSource implements GeoSource
{
    const KEY = 'persons';

    const FILTER_SEARCH = 'search';
    const FILTER_ORGANIZATIONS = 'organizations';

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
        return __(DirectoryLang::LANG_GROUP, 'Personas');
    }

    /**
     * @return string
     */
    public function permissionRoute(): string
    {
        return 'profile-admin-profile';
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
        return '--geo-persons-color';
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
        return [
            new GeoFilter(self::FILTER_SEARCH, __(DirectoryLang::LANG_GROUP, 'Buscar'), GeoFilter::TYPE_TEXT),
            new GeoFilter(self::FILTER_ORGANIZATIONS, __(DirectoryLang::LANG_GROUP, 'Organizaciones'), GeoFilter::TYPE_REMOTE_OPTIONS),
        ];
    }

    /**
     * Las organizaciones a las que puede pertenecer una persona del mapa: ni la global ni una inactiva o eliminada.
     *
     * @param string $key
     * @param string $search
     * @param int $limit
     * @return array<string|int,string>
     */
    public function filterOptions(string $key, string $search, int $limit): array
    {
        if ($key !== self::FILTER_ORGANIZATIONS) {
            return [];
        }
        $having = GeoSourceSql::addIds(new HavingSegment([
            new HavingItem('id', HavingItem::NOT_EQUAL_OPERATOR, OrganizationMapper::INITIAL_ID_GLOBAL, HavingItem::AND_OPERATOR),
        ]), 'status', [OrganizationMapper::ACTIVE, OrganizationMapper::PENDING_APPROVAL]);
        if ($search !== '') {
            GeoSourceSql::addSearch($having, ['name'], $search);
        }
        $model = OrganizationMapper::model();
        $model->select(OrganizationMapper::fieldsToSelect());
        $model->having($having);
        [, $rows] = GeoSourceSql::countAndFetch($model, 'name ASC', $limit);
        $options = [];
        foreach ($rows as $row) {
            $options[(int) $row['id']] = (string) $row['name'];
        }
        return $options;
    }

    /**
     * @param GeoQuery $query
     * @return GeoPointsResult
     */
    public function points(GeoQuery $query): GeoPointsResult
    {
        $model = UserProfileMapper::model();
        $model->select(UserProfileMapper::fieldsToSelect());
        static::applyFilters($model, $query);
        [$total, $rows] = GeoSourceSql::countAndFetch($model, 'belongsTo ASC', $query->limit());

        $points = [];
        foreach ($rows as $row) {
            if (GeoQuery::isValidCoordinate($row['longitude'] ?? null, $row['latitude'] ?? null)) {
                $id = (int) $row['belongsTo'];
                $points[] = new GeoPoint($id, (float) $row['longitude'], (float) $row['latitude'], (string) $row['fullname'], GeoThumbnail::url(self::KEY, $id, AvatarModel::getUserAvatarName($id)));
            }
        }
        return new GeoPointsResult($points, $total);
    }

    /**
     * @param int $id El del usuario
     * @return array<string,mixed>|null
     */
    public function pointDetail(int $id): ?array
    {
        $row = static::visibleRow($id);
        if ($row === null) {
            return null;
        }

        $organization = null;
        if ($row->organizationID !== null && (int) $row->organizationID !== OrganizationMapper::INITIAL_ID_GLOBAL) {
            $organizationModel = OrganizationMapper::model();
            $organizationModel->select(OrganizationMapper::fieldsToSelect());
            $organizationModel->having(new HavingSegment([
                new HavingItem('id', HavingItem::EQUAL_OPERATOR, (int) $row->organizationID, HavingItem::AND_OPERATOR),
            ]));
            $organizationModel->execute(false, 1, 1);
            $organizationRow = ($organizationModel->result() ?? [])[0] ?? null;
            $organization = $organizationRow !== null ? (string) $organizationRow->name : null;
        }

        return [
            'name' => (string) $row->fullname,
            'jobPosition' => $row->jobPosition !== null ? (string) $row->jobPosition : null,
            'organization' => $organization,
            'city' => $row->cityName !== null ? (string) $row->cityName : null,
            'country' => $row->countryName !== null ? (string) $row->countryName : null,
            'url' => ProfileController::routeName('profile', ['userID' => $id]),
        ];
    }

    /**
     * El avatar en disco del usuario, si su punto se ve.
     *
     * @param int $id El del usuario
     * @return string|null
     */
    public function thumbnailSource(int $id): ?string
    {
        return static::visibleRow($id) !== null && AvatarModel::hasUserAvatar($id) ? AvatarModel::getUserAvatarName($id) : null;
    }

    /**
     * La fila de un punto que points() devolvería, buscada por su id en WHERE (con índice), o null.
     *
     * @param int $id El del usuario
     * @return \stdClass|null
     */
    protected static function visibleRow(int $id): ?\stdClass
    {
        $table = UserProfileMapper::TABLE;
        $where = GeoSourceSql::addBboxWhere(new WhereSegment(), "{$table}.longitude", "{$table}.latitude", new GeoQuery());
        $where->addCritery(new WhereItem("{$table}.belongsTo", WhereItem::EQUAL_OPERATOR, $id));

        $model = UserProfileMapper::model();
        $model->select(UserProfileMapper::fieldsToSelect());
        $model->where($where);
        $model->having(static::visibleHaving());
        $model->execute(false, 1, 1);
        $row = ($model->result() ?? [])[0] ?? null;
        return $row instanceof \stdClass && GeoQuery::isValidCoordinate($row->longitude, $row->latitude) ? $row : null;
    }

    /**
     * El encuadre va en WHERE sobre las columnas, que tienen el índice `geo`: solo se calcula el SELECT de lo que cae
     * dentro. La visibilidad, la búsqueda y las organizaciones van en HAVING porque son alias del SELECT.
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

        $having = static::visibleHaving();
        if ($search !== null) {
            GeoSourceSql::addSearch($having, ['fullname', 'fullLocation'], $search);
        }
        if (count($organizations) > 0) {
            GeoSourceSql::addIds($having, 'organizationID', $organizations);
        }

        $table = UserProfileMapper::TABLE;
        $model->where(GeoSourceSql::addBboxWhere(new WhereSegment(), "{$table}.longitude", "{$table}.latitude", $query));
        $model->having($having);
    }

    /**
     * Lo que hace visible a una persona en el mapa, sea cual sea el encuadre.
     *
     * @return HavingSegment
     */
    protected static function visibleHaving(): HavingSegment
    {
        $criteria = [
            new HavingItem('publicVisible', HavingItem::EQUAL_OPERATOR, 1, HavingItem::AND_OPERATOR),
            new HavingItem('userStatus', HavingItem::NOT_EQUAL_OPERATOR, UsersModel::STATUS_USER_INACTIVE, HavingItem::AND_OPERATOR),
            new HavingItem('userStatus', HavingItem::NOT_EQUAL_OPERATOR, UsersModel::STATUS_USER_DELETED, HavingItem::AND_OPERATOR),
        ];
        if (SystemApprovalsRoutes::ENABLE) {
            $criteria[] = new HavingItem('systemApprovalStatus', HavingItem::EQUAL_OPERATOR, SystemApprovalsMapper::STATUS_APPROVED, HavingItem::AND_OPERATOR);
        }
        return new HavingSegment($criteria);
    }
}
