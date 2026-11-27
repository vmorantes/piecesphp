<?php

/**
 * OrganizationAdministration.php
 */

namespace Organizations\Util;

use Organizations\Mappers\OrganizationMapper;
use Organizations\OrganizationsRoutes;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * OrganizationAdministration - Si quien pide administra la organización de quien creó un registro (P414 b: «el
 * administrador de organización administra SU organización»).
 *
 * La organización de quien creó el registro es la de quien pide, y quien pide es su encargado, con dos límites: quien
 * pide es administrador de organización (un usuario general encargado no administra lo de los demás), salvo con
 * $requireOrgAdminType en false, como en Publicaciones; y la organización no es la base, a la que caen los usuarios sin
 * organización real. Con Organizaciones apagado, ser encargado no concede (P384 a).
 *
 * @package     Organizations\Util
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class OrganizationAdministration
{

    /**
     * @param mixed $createdBy El id de quien creó el registro.
     * @param UserDataPackage $current Quien pide.
     * @param bool $requireOrgAdminType Con false no se exige el tipo administrador de organización: en Publicaciones el
     * encargado puede ser de cualquier tipo con acceso a sus rutas, que no admiten ese tipo.
     * @return bool
     */
    public static function administersCreatorOf(mixed $createdBy, UserDataPackage $current, bool $requireOrgAdminType = true): bool
    {
        $currentOrganization = $current->organizationMapper;
        if (!OrganizationsRoutes::enabled() || $currentOrganization === null || !is_numeric($createdBy) || ($requireOrgAdminType && (int) $current->type !== UsersModel::TYPE_USER_ADMIN_ORG)) {
            return false;
        }
        //El creador existe siempre (la clave foránea createdBy impide borrarlo); sin él no hay organización.
        $creator = UsersModel::getBy((int) $createdBy, 'id');
        $creatorOrganizationID = $creator !== null && is_numeric($creator->organization) ? (int) $creator->organization : -1;
        if ($creatorOrganizationID !== (int) $currentOrganization->id || $creatorOrganizationID === OrganizationMapper::INITIAL_ID_GLOBAL) {
            return false;
        }
        $organization = OrganizationMapper::getBy($creatorOrganizationID, 'id', true);
        $administrator = $organization !== null ? $organization->administrator : null;
        $administratorID = $administrator instanceof UsersModel ? $administrator->id : (is_numeric($administrator) ? (int) $administrator : null);
        return $administratorID !== null && (int) $administratorID === (int) $current->id;
    }
}
