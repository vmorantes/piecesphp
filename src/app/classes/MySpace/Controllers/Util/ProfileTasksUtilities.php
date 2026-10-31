<?php

/**
 * ProfileTasksUtilities.php
 */

namespace MySpace\Controllers\Util;

use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;

/**
 * ProfileTasksUtilities.
 *
 * @package     MySpace\Controllers\Util
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class ProfileTasksUtilities extends AdminPanelController
{

    /**
     * Genera el código SQL para la creación de entidades
     *
     * @param bool $echo Si es verdadero, el código SQL se muestra en la salida y termina la ejecución. Si es falso, el código SQL se devuelve.
     * @return string|void El código SQL generado o nada si $echo es verdadero.
     */
    public static function generateSQL(bool $echo = false)
    {
        //Generar SQL
        $sqlCreate = [
            (new \PiecesPHP\Core\Database\SchemeCreator(new UserProfileMapper()))->getSQL(),
        ];
        $sql = implode("\r\n", $sqlCreate);
        if ($echo) {
            header('Content-Type: text/sql');
            echo $sql;
            exit;
        }

        return $sql;

    }

    /**
     * Los usuarios de UsersModel::TYPES_USER_SHOULD_HAVE_PROFILE que no tienen perfil y, con $doIt, el perfil de cada uno
     * ({@see UserProfileMapper::ensureFor()}). Es el cuerpo de `bin/cli profiles-create-missing`: al actualizar una
     * instalación se rellenan una vez. Ya no corre en cada petición (P266): el alta crea el perfil.
     *
     * @param bool $doIt Con true crea los que faltan; con false solo los enumera.
     * @return array{missing:int[],created:int[],failed:int[]}
     */
    public static function generateMissingProfiles(bool $doIt = true): array
    {
        $missing = [];
        $created = [];
        $failed = [];

        $userTable = UsersModel::TABLE;
        $profileTable = UserProfileMapper::TABLE;
        $model = UsersModel::model();
        $typesShouldHaveProfile = implode(', ', UsersModel::TYPES_USER_SHOULD_HAVE_PROFILE);

        $model->select([
            "{$userTable}.id AS userID",
            "{$userTable}.type",
            "{$profileTable}.id AS profileID",
        ])->leftJoin($profileTable, [
            "{$profileTable}.belongsTo" => "{$userTable}.id",
        ])->having([
            "profileID" => [
                'and_or' => 'AND',
                'IS NULL' => '',
            ],
            "{$userTable}.type" => [
                'and_or' => 'AND',
                'IN' => "({$typesShouldHaveProfile})",
            ],
        ]);

        $model->execute();
        $result = $model->result();

        foreach ($result ?? [] as $element) {
            $userID = (int) $element->userID;
            $missing[] = $userID;
            if ($doIt) {
                if (UserProfileMapper::ensureFor($userID)) {
                    $created[] = $userID;
                } else {
                    $failed[] = $userID;
                }
            }
        }

        return ['missing' => $missing, 'created' => $created, 'failed' => $failed];
    }

}
