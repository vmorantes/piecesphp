<?php

/**
 * ProfileTasksUtilities.php
 */

namespace MySpace\Controllers\Util;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use MySpace\Exceptions\SafeException;
use PiecesPHP\Core\Config;
use PiecesPHP\UserSystem\Profile\SubMappers\InterestResearchAreasMapper;
use PiecesPHP\UserSystem\Profile\SubMappers\OrganizationPreviousExperiencesMapper;
use PiecesPHP\UserSystem\Profile\SubMappers\PreviousExperiencesMapper;
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
            (new \PiecesPHP\Core\Database\SchemeCreator(new InterestResearchAreasMapper()))->getSQL(),
            (new \PiecesPHP\Core\Database\SchemeCreator(new PreviousExperiencesMapper()))->getSQL(),
            (new \PiecesPHP\Core\Database\SchemeCreator(new OrganizationPreviousExperiencesMapper()))->getSQL(),
        ];
        $sql = strReplaceTemplate(implode("\r\n", $sqlCreate), [
            'belongsTo` int' => 'belongsTo` bigint',
            'createdBy` int' => 'createdBy` bigint',
            'modifiedBy` int' => 'modifiedBy` bigint',
        ]);
        if ($echo) {
            header('Content-Type: text/sql');
            echo $sql;
            exit;
        }

        return $sql;

    }

    /**
     * Genera los perfiles faltantes para los usuarios que deberían tener uno.
     *
     * Este método verifica qué usuarios no tienen un perfil asociado y los crea automáticamente.
     * Se utiliza para asegurar que todos los usuarios que necesitan un perfil tengan uno, especialmente
     * después de una actualización o migración de datos.
     *
     * @param bool $doIt Si es verdadero, el método ejecuta la generación de perfiles faltantes. Si es falso, no hace nada.
     */
    public static function generateMissingProfiles(bool $doIt = true)
    {

        if ($doIt) {
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

            foreach ($result as $element) {
                UserProfileMapper::getProfile($element->userID);
            }

        }
    }

    /**
     * Genera áreas de investigación predeterminadas.
     *
     * Este método itera sobre un arreglo de nombres de áreas de investigación y las crea automáticamente.
     * Se utiliza para asegurar que ciertas áreas de investigación estén disponibles en el sistema.
     *
     * @param array $areasNames Arreglo de nombres de áreas de investigación a generar.
     */
    public static function generateDefaultInterestResearchAreas(array $areasNames = [])
    {
        foreach ($areasNames as $areaName => $color) {
            if (is_string($areaName)) {
                try {
                    $areaMapper = InterestResearchAreasMapper::getBy($areaName, 'areaName');
                    $areaMapper = $areaMapper !== null ? new InterestResearchAreasMapper($areaMapper->id) : new InterestResearchAreasMapper();
                    $areaMapper->baseLang = 'es';
                    $areaMapper->color = $color;
                    $areaMapper->areaName = lang(GLOBAL_LANG_GROUP, $areaName, $areaMapper->baseLang);
                    foreach (Config::get_allowed_langs() as $lang) {
                        if ($lang !== $areaMapper->baseLang) {
                            $areaMapper->setLangData($lang, 'areaName', lang(GLOBAL_LANG_GROUP, $areaName, $lang));
                        }
                    }
                    $areaMapper->id !== null ? $areaMapper->update() : $areaMapper->save();
                } catch (SafeException $e) {}
            }
        }
    }

}
