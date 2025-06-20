<?php

/**
 * ReportsManageQueries.php
 */

namespace ReportsManage\Queries;

use ApplicationCalls\Mappers\ApplicationCallsMapper;
use App\Locations\LocationsLang;
use App\Locations\Mappers\CountryMapper;
use App\Model\UsersModel;
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
     * @param int $pollIdentifier
     * @return array
     */
    public static function genericReportData(int $pollIdentifier = null)
    {

        //Total de usuarios generales
        $Colombia = CountryMapper::getBy('Colombia', 'name');
        $Francia = CountryMapper::getBy('Francia', 'name');
        $totalResearchersQty = self::getTotalResearchersQty();
        $totalResearchersQtyColombia = self::getTotalResearchersQty($Colombia !== null ? $Colombia->id : -1);
        $totalResearchersQtyFrancia = self::getTotalResearchersQty($Francia !== null ? $Francia->id : -1);
        $totalResearchersQtyOthers = $totalResearchersQty - $totalResearchersQtyColombia - $totalResearchersQtyFrancia;

        //Total de organizaciones
        $totalOrganizationsQty = self::getTotalOrganizationsQty();
        $totalOrganizationsQtyColombia = self::getTotalOrganizationsQty($Colombia !== null ? $Colombia->id : -1);
        $totalOrganizationsQtyFrancia = self::getTotalOrganizationsQty($Francia !== null ? $Francia->id : -1);
        $totalOrganizationsQtyOthers = $totalOrganizationsQty - $totalOrganizationsQtyColombia - $totalOrganizationsQtyFrancia;

        //Total de tipos de convocatorias
        $totalApplicationsCallsQty = self::getTotalApplicationsCallsByTypeQty();
        $totalApplicationsCallsBilateralProjectQty = self::getTotalApplicationsCallsByTypeQty(ApplicationCallsMapper::CONTENT_TYPE_BILATERAL_PROJECT);
        $totalApplicationsCallsFundingOpportunityQty = self::getTotalApplicationsCallsByTypeQty(ApplicationCallsMapper::CONTENT_TYPE_FUNDING_OPPORTUNITY);

        //Total de publicaciones
        $totalApprovedPublicationsQty = self::getTotalPublicationsQty(SystemApprovalsMapper::STATUS_APPROVED);
        $totalPendingPublicationsQty = self::getTotalPublicationsQty(SystemApprovalsMapper::STATUS_PENDING);
        $totalPublicationsQty = $totalApprovedPublicationsQty + $totalPendingPublicationsQty;

        //Total de tokens restantes
        $totalExpectedTokens = GenericContentPseudoMapper::getContentData(GenericContentPseudoMapper::CONTENT_TOKENS_LIMIT);
        $totalUsedTokensByModel = (array) GenericContentPseudoMapper::getContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED);
        $totalUsedTokenAll = array_reduce($totalUsedTokensByModel, function ($carry, $item) {
            return $carry + $item;
        }, 0);
        $totalRemainingTokens = $totalExpectedTokens - $totalUsedTokenAll;

        $data = [
            //Total de usuarios generales
            "researchersData" => [
                "totalResearchersQty" => $totalResearchersQty,
                "totalResearchersQtyColombia" => $totalResearchersQtyColombia,
                "totalResearchersQtyFrancia" => $totalResearchersQtyFrancia,
                "totalResearchersQtyOthers" => $totalResearchersQtyOthers,
                "chartData" => [
                    "series" => [
                        $totalResearchersQtyColombia,
                        $totalResearchersQtyFrancia,
                        $totalResearchersQtyOthers,
                    ],
                    "labels" => [
                        __(self::LANG_GROUP, 'Colombia'),
                        __(self::LANG_GROUP, 'Francia'),
                        __(self::LANG_GROUP, 'Otros países'),
                    ],
                    "colors" => [
                        '#3558A2',
                        '#254079',
                        '#7A7A7A',
                    ],
                    "style" => [
                        'fontSize' => '14px',
                        'fontWeight' => '500',
                        'lineHeight' => '19px',
                        'fontFamily' => get_config('font_family_global'),
                        'colors' => ['#FFFFFF'],
                    ],
                    "background" => '#FFFFFF',
                    "unitText" => __(self::LANG_GROUP, 'usuarios generales'),
                ],
            ],
            //Total de organizaciones
            "organizationsData" => [
                "totalOrganizationsQty" => $totalOrganizationsQty,
                "totalOrganizationsQtyColombia" => $totalOrganizationsQtyColombia,
                "totalOrganizationsQtyFrancia" => $totalOrganizationsQtyFrancia,
                "totalOrganizationsQtyOthers" => $totalOrganizationsQtyOthers,
                "chartData" => [
                    "series" => [
                        $totalOrganizationsQtyColombia,
                        $totalOrganizationsQtyFrancia,
                        $totalOrganizationsQtyOthers,
                    ],
                    "labels" => [
                        __(self::LANG_GROUP, 'Colombia'),
                        __(self::LANG_GROUP, 'Francia'),
                        __(self::LANG_GROUP, 'Otros países'),
                    ],
                    "colors" => [
                        '#3558A2',
                        '#254079',
                        '#7A7A7A',
                    ],
                    "style" => [
                        'fontSize' => '14px',
                        'fontWeight' => '500',
                        'lineHeight' => '19px',
                        'fontFamily' => get_config('font_family_global'),
                        'colors' => ['#FFFFFF'],
                    ],
                    "background" => '#FFFFFF',
                    "unitText" => __(self::LANG_GROUP, 'organización(es)'),
                ],
            ],
            //Total de tipos de convocatorias
            "totalApplicationsCallsQty" => $totalApplicationsCallsQty,
            "totalApplicationsCallsBilateralProjectQty" => $totalApplicationsCallsBilateralProjectQty,
            "totalApplicationsCallsFundingOpportunityQty" => $totalApplicationsCallsFundingOpportunityQty,
            //Total de publicaciones
            "totalApprovedPublicationsQty" => $totalApprovedPublicationsQty,
            "totalPendingPublicationsQty" => $totalPendingPublicationsQty,
            "publicationsProjectionData" => [
                "title" => __(self::LANG_GROUP, 'Creadas'),
                "projectionTitle" => __(self::LANG_GROUP, 'Aprobadas'),
                "first" => [
                    "title" => __(LocationsLang::LANG_GROUP_NAMES, 'Colombia'),
                    "barValue" => $totalPublicationsQty,
                    "progressValue" => $totalPendingPublicationsQty,
                    "markerValue" => $totalApprovedPublicationsQty,
                ],
                "second" => [
                    "title" => __(LocationsLang::LANG_GROUP_NAMES, 'Francia'),
                    "barValue" => $totalPublicationsQty,
                    "progressValue" => $totalPendingPublicationsQty,
                    "markerValue" => $totalApprovedPublicationsQty,
                ],
            ],
            //Total de tokens restantes
            "totalRemainingTokens" => $totalRemainingTokens,
        ];

        return $data;
    }

    /**
     * @param int $countryID
     * @return int
     */
    public static function getTotalResearchersQty(int $countryID = null)
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

        //Filtrar por país
        if ($countryID !== null) {
            $whereSegment->addCritery(new WhereItem("{$table}.country", WhereItem::EQUAL_OPERATOR, $countryID));
        }

        $model->select("COUNT({$table}.id) AS total");
        $model->join($joinSegment->getTable(), $joinSegment, $joinSegment->getType());
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

    /**
     * @param int $countryID
     * @return int
     */
    public static function getTotalOrganizationsQty(int $countryID = null)
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

        //Filtrar por país
        if ($countryID !== null) {
            $whereSegment->addCritery(new WhereItem("{$table}.country", WhereItem::EQUAL_OPERATOR, $countryID));
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

    /**
     * @param string $contentType
     * @return int
     */
    public static function getTotalApplicationsCallsByTypeQty(string $contentType = null)
    {

        $table = ApplicationCallsMapper::TABLE;
        $tableAppovals = SystemApprovalsMapper::TABLE;
        $model = ApplicationCallsMapper::model();

        //Select
        $model->select();

        $whereSegment = new WhereSegment();
        $havingSegment = new HavingSegment();

        //Filtrar por estado
        $whereSegment->addCritery(new WhereItem("{$table}.status", WhereItem::EQUAL_OPERATOR, ApplicationCallsMapper::ACTIVE));

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

        //Filtrar por tipo de contenido
        if ($contentType !== null) {
            $whereSegment->addCritery(new WhereItem("{$table}.contentType", WhereItem::EQUAL_OPERATOR, $contentType));
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

    /**
     * @param string $approvalStatus
     * @return int
     */
    public static function getTotalPublicationsQty(string $approvalStatus = null)
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
