<?php

/**
 * ReportsManageQueries.php
 */

namespace ReportsManage\Queries;

use PiecesPHP\App\Locations\LocationsLang;
use PiecesPHP\App\Locations\Mappers\CountryMapper;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper;
use PiecesPHP\Core\Database\ORM\Statements\Critery\JoinItem;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\HavingSegment;
use PiecesPHP\Core\Database\ORM\Statements\JoinSegment;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use Publications\Mappers\PublicationMapper;
use ReportsManage\ReportsManageLang;
use SystemApprovals\Mappers\SystemApprovalsMapper;

/**
 * ReportsManageQueries.
 *
 * @package     ReportsManage\Queries
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class ReportsManageQueries
{
    const ROLES_WITH_REPORTS = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN_GRAL,
        UsersModel::TYPE_USER_INSTITUCIONAL,
    ];

    const LANG_GROUP = ReportsManageLang::LANG_GROUP;

    public function __construct()
    {}

    /**
     * @return array
     */
    public static function genericReportData()
    {

        //Personas y organizaciones, repartidas por país: los dos países con más registros y «otros» (P196 b)
        $people = self::countryBreakdown(self::getResearchersByCountry());
        $organizations = self::countryBreakdown(self::getOrganizationsByCountry());

        //Total de publicaciones
        $totalApprovedPublicationsQty = self::getTotalPublicationsQty(SystemApprovalsMapper::STATUS_APPROVED);
        $totalPendingPublicationsQty = self::getTotalPublicationsQty(SystemApprovalsMapper::STATUS_PENDING);

        //Total de tokens restantes
        $totalExpectedTokens = GenericContentPseudoMapper::getContentData(GenericContentPseudoMapper::CONTENT_TOKENS_LIMIT);
        $totalUsedTokensByModel = (array) GenericContentPseudoMapper::getContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED);
        $totalUsedTokenAll = array_reduce($totalUsedTokensByModel, function ($carry, $item) {
            return $carry + $item;
        }, 0);
        $totalRemainingTokens = $totalExpectedTokens - $totalUsedTokenAll;

        $data = [
            //Total de usuarios generales
            "peopleData" => $people + [
                "chartData" => self::chartData($people, __(self::LANG_GROUP, 'usuarios generales')),
            ],
            //Total de organizaciones
            "organizationsData" => $organizations + [
                "chartData" => self::chartData($organizations, __(self::LANG_GROUP, 'organización(es)')),
            ],
            //Total de publicaciones
            "totalApprovedPublicationsQty" => $totalApprovedPublicationsQty,
            "totalPendingPublicationsQty" => $totalPendingPublicationsQty,
            //Total de tokens restantes
            "totalRemainingTokens" => $totalRemainingTokens,
        ];

        return $data;
    }

    /**
     * Las personas que cuentan (tipo usuario general o administrador de organización, con perfil, activas y aprobadas),
     * agrupadas por el país de su perfil.
     *
     * @return list<array{country:int|null,name:string|null,qty:int}>
     */
    public static function getResearchersByCountry(): array
    {

        $table = UserProfileMapper::TABLE;
        $tableUser = UsersModel::TABLE;
        $tableAppovals = SystemApprovalsMapper::TABLE;
        $model = UserProfileMapper::model();

        //Select
        $model->select();

        $whereSegment = new WhereSegment();
        $havingSegment = new HavingSegment();
        $joinSegment = new JoinSegment([], $tableUser, JoinSegment::TYPE_INNER);

        //Asociar tabla de usuarios
        $joinSegment->addCritery(new JoinItem("{$tableUser}.id", JoinItem::EQUAL_OPERATOR, "{$table}.belongsTo"));

        //Filtrar por tipo de usuario
        $types = implode(',', [
            UsersModel::TYPE_USER_ADMIN_ORG,
            UsersModel::TYPE_USER_GENERAL,
        ]);
        $joinSegment->addCritery(new JoinItem("{$tableUser}.type", JoinItem::IN_OPERATOR, "({$types})"));

        //Filtrar por estado
        $joinSegment->addCritery(new JoinItem("{$tableUser}.status", JoinItem::EQUAL_OPERATOR, UsersModel::STATUS_USER_ACTIVE));

        //Filtrar por aprobación
        $whereSegmentApprovals = new WhereSegment();
        $whereSegmentApprovals->addCritery(new WhereItem("{$tableAppovals}.referenceValue", WhereItem::EQUAL_OPERATOR, "{$table}.belongsTo"));
        $whereSegmentApprovals->addCritery(new WhereItem("{$tableAppovals}.referenceTable", WhereItem::EQUAL_OPERATOR, "'{$tableUser}'"));
        $whereSegmentApprovals = strReplaceTemplate((string) $whereSegmentApprovals, $whereSegmentApprovals->getReplacementValues());
        $subQueryApprovals = "SELECT {$tableAppovals}.status FROM {$tableAppovals} {$whereSegmentApprovals} LIMIT 1";
        $whereSegment->addCritery(new WhereItem(
            "({$subQueryApprovals})",
            WhereItem::EQUAL_OPERATOR,
            '"' . SystemApprovalsMapper::STATUS_APPROVED . '"',
            '',
            null,
            false
        ));

        //El nombre sale de la misma columna por la que se agrupa: una consulta por recuento.
        $countries = CountryMapper::PREFIX_TABLE . CountryMapper::TABLE;
        $model->select("{$table}.country AS country, (SELECT {$countries}.name FROM {$countries} WHERE {$countries}.id = {$table}.country) AS name, COUNT({$table}.id) AS qty");
        $model->groupBy("{$table}.country");
        $model->join($joinSegment->getTable(), $joinSegment, $joinSegment->getType());
        if ($whereSegment->countCriteria() > 0) {
            $model->where($whereSegment);
        }
        if ($havingSegment->countCriteria() > 0) {
            $model->having($havingSegment);
        }
        $model->execute();
        $result = $model->result();

        return self::countryRows(is_array($result) ? $result : []);
    }

    /**
     * Las organizaciones que cuentan (activas y aprobadas, sin la base), agrupadas por su país.
     *
     * @return list<array{country:int|null,name:string|null,qty:int}>
     */
    public static function getOrganizationsByCountry(): array
    {

        $table = OrganizationMapper::TABLE;
        $tableAppovals = SystemApprovalsMapper::TABLE;
        $model = OrganizationMapper::model();

        //Select
        $model->select();

        $whereSegment = new WhereSegment();
        $havingSegment = new HavingSegment();

        //Ignorar organización base
        $whereSegment->addCritery(new WhereItem("{$table}.id", WhereItem::NOT_EQUAL_OPERATOR, OrganizationMapper::INITIAL_ID_GLOBAL));

        //Filtrar por estado
        $whereSegment->addCritery(new WhereItem("{$table}.status", WhereItem::EQUAL_OPERATOR, OrganizationMapper::ACTIVE));

        //Filtrar por aprobación
        $whereSegmentApprovals = new WhereSegment();
        $whereSegmentApprovals->addCritery(new WhereItem("{$tableAppovals}.referenceValue", WhereItem::EQUAL_OPERATOR, "{$table}.id"));
        $whereSegmentApprovals->addCritery(new WhereItem("{$tableAppovals}.referenceTable", WhereItem::EQUAL_OPERATOR, "'{$table}'"));
        $whereSegmentApprovals = strReplaceTemplate((string) $whereSegmentApprovals, $whereSegmentApprovals->getReplacementValues());
        $subQueryApprovals = "SELECT {$tableAppovals}.status FROM {$tableAppovals} {$whereSegmentApprovals} LIMIT 1";
        $whereSegment->addCritery(new WhereItem(
            "({$subQueryApprovals})",
            WhereItem::EQUAL_OPERATOR,
            '"' . SystemApprovalsMapper::STATUS_APPROVED . '"',
            '',
            null,
            false
        ));

        //El nombre sale de la misma columna por la que se agrupa: una consulta por recuento.
        $countries = CountryMapper::PREFIX_TABLE . CountryMapper::TABLE;
        $model->select("{$table}.country AS country, (SELECT {$countries}.name FROM {$countries} WHERE {$countries}.id = {$table}.country) AS name, COUNT({$table}.id) AS qty");
        $model->groupBy("{$table}.country");
        if ($whereSegment->countCriteria() > 0) {
            $model->where($whereSegment);
        }
        if ($havingSegment->countCriteria() > 0) {
            $model->having($havingSegment);
        }
        $model->execute();
        $result = $model->result();

        return self::countryRows(is_array($result) ? $result : []);
    }

    /**
     * Los dos países con más registros y «otros»: el resto de países más lo que no tiene país (P196 b). A igual cuenta,
     * por nombre. Pura: es lo que prueba core/reports-by-country.
     *
     * @param list<array{country:int|null,name:string|null,qty:int}> $rows Un recuento por país; country o name null es «sin país»
     * @param int $top
     * @return array{total:int,byCountry:list<array{country:int,name:string,qty:int}>,others:int}
     */
    public static function splitTopCountries(array $rows, int $top = 2): array
    {
        $withCountry = [];
        $withoutCountry = 0;
        $total = 0;
        foreach ($rows as $row) {
            $total += $row['qty'];
            if ($row['country'] === null || $row['name'] === null || $row['qty'] <= 0) {
                $withoutCountry += $row['qty'];
                continue;
            }
            $withCountry[] = ['country' => $row['country'], 'name' => $row['name'], 'qty' => $row['qty']];
        }
        usort($withCountry, fn (array $a, array $b): int => $b['qty'] <=> $a['qty'] ?: strnatcasecmp($a['name'], $b['name']));
        $first = array_slice($withCountry, 0, $top);
        $others = $withoutCountry + array_sum(array_column(array_slice($withCountry, $top), 'qty'));
        return ['total' => $total, 'byCountry' => $first, 'others' => $others];
    }

    /**
     * @param list<array{country:int|null,name:string|null,qty:int}> $rows
     * @return array{total:int,byCountry:list<array{country:int,name:string,qty:int}>,others:int}
     */
    private static function countryBreakdown(array $rows): array
    {
        $rows = array_map(fn (array $row): array => ['name' => $row['name'] !== null ? __(LocationsLang::LANG_GROUP_NAMES, $row['name']) : null] + $row, $rows);
        return self::splitTopCountries($rows);
    }

    /**
     * @param array<mixed> $result Las filas de las consultas por país
     * @return list<array{country:int|null,name:string|null,qty:int}>
     */
    private static function countryRows(array $result): array
    {
        $rows = [];
        foreach ($result as $row) {
            $country = is_object($row) && is_numeric($row->country ?? null) ? (int) $row->country : null;
            $rows[] = [
                'country' => $country,
                'name' => is_object($row) && is_string($row->name ?? null) ? $row->name : null,
                'qty' => is_object($row) ? (int) ($row->qty ?? 0) : 0,
            ];
        }
        return $rows;
    }

    /**
     * @param array{total:int,byCountry:list<array{country:int,name:string,qty:int}>,others:int} $breakdown
     * @param string $unitText
     * @return array<string,mixed>
     */
    private static function chartData(array $breakdown, string $unitText): array
    {
        return [
            "series" => array_merge(array_column($breakdown['byCountry'], 'qty'), [$breakdown['others']]),
            "labels" => array_merge(array_column($breakdown['byCountry'], 'name'), [__(self::LANG_GROUP, 'Otros países')]),
            "style" => [
                'fontSize' => '14px',
                'fontWeight' => '500',
                'lineHeight' => '19px',
                'fontFamily' => get_config('font_family_global'),
                'colors' => ['#FFFFFF'],
            ],
            "background" => '#FFFFFF',
            "unitText" => $unitText,
        ];
    }

    /**
     * @param string $approvalStatus
     * @return int
     */
    public static function getTotalPublicationsQty(?string $approvalStatus = null)
    {

        $table = PublicationMapper::TABLE;
        $tableAppovals = SystemApprovalsMapper::TABLE;
        $model = PublicationMapper::model();

        //Select
        $model->select();

        $whereSegment = new WhereSegment();
        $havingSegment = new HavingSegment();

        //Filtrar por estado
        $whereSegment->addCritery(new WhereItem("{$table}.status", WhereItem::EQUAL_OPERATOR, PublicationMapper::ACTIVE));

        /* Filtrar por aprobación */
        //Si no se especifica, se filtra solo aprobados
        $approvalStatus = $approvalStatus ?? SystemApprovalsMapper::STATUS_APPROVED;
        //Si se especifica, se filtra por el estado. Si se especifica 'ALL', se filtra todos los estados
        $approvalStatus = $approvalStatus === 'ALL' ? null : $approvalStatus;
        if ($approvalStatus !== null) {
            $whereSegmentApprovals = new WhereSegment();
            $whereSegmentApprovals->addCritery(new WhereItem("{$tableAppovals}.referenceValue", WhereItem::EQUAL_OPERATOR, "{$table}.id"));
            $whereSegmentApprovals->addCritery(new WhereItem("{$tableAppovals}.referenceTable", WhereItem::EQUAL_OPERATOR, "'{$table}'"));
            $whereSegmentApprovals = strReplaceTemplate((string) $whereSegmentApprovals, $whereSegmentApprovals->getReplacementValues());
            $subQueryApprovals = "SELECT {$tableAppovals}.status FROM {$tableAppovals} {$whereSegmentApprovals} LIMIT 1";
            $whereSegment->addCritery(new WhereItem(
                "({$subQueryApprovals})",
                WhereItem::EQUAL_OPERATOR,
                "'{$approvalStatus}'",
                '',
                null,
                false
            ));
        }

        $model->select("COUNT({$table}.id) AS total");
        if ($whereSegment->countCriteria() > 0) {
            $model->where($whereSegment);
        }
        if ($havingSegment->countCriteria() > 0) {
            $model->having($havingSegment);
        }
        $model->execute();
        $result = $model->result();
        $total = !empty($result) ? (int) $result[0]->total : 0;

        return $total;
    }

}
