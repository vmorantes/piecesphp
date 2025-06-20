<?php

/**
 * DynamicTranslationsHelper.php
 */

namespace PiecesPHP\LocalizationSystem\Util;

use PiecesPHP\Core\Helpers\Directories\DirectoryObject;
use PiecesPHP\LocalizationSystem\Packages\JSONTranslationsPackage;

/**
 * DynamicTranslationsHelper.
 *
 * @package     PiecesPHP\LocalizationSystem\Util
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class DynamicTranslationsHelper
{

    /**
     * Obtiene el archivo de traducciones dinámicas actual
     *
     * @return JSONTranslationsPackage
     */
    public static function getCurrentDynamicTranslationsJSON()
    {
        $DYNAMIC_TRANSLATIONS_CONFIG = get_config('DYNAMIC_TRANSLATIONS');
        $baseFolderName = $DYNAMIC_TRANSLATIONS_CONFIG['folderName'];
        $langDynamicTranslationsDirectory = new DirectoryObject(basepath("app/lang/{$baseFolderName}/"));
        $currentJSONFile = append_to_path_system($langDynamicTranslationsDirectory->getPath(), 'current-translations.json');
        $currentJSONData = new JSONTranslationsPackage(new \DateTime('1990-01-01 00:00:00'), []);
        if (file_exists($currentJSONFile)) {
            $currentJSON = file_get_contents($currentJSONFile);
            $currentJSONData = JSONTranslationsPackage::createFromJSON($currentJSON);
        }
        return $currentJSONData;
    }

    /**
     * Guarda el archivo de traducciones dinámicas actual
     *
     * @param JSONTranslationsPackage $currentJSONData
     * @return void
     */
    public static function saveCurrentDynamicTranslationsJSON(JSONTranslationsPackage $currentJSONData)
    {
        $DYNAMIC_TRANSLATIONS_CONFIG = get_config('DYNAMIC_TRANSLATIONS');
        $baseFolderName = $DYNAMIC_TRANSLATIONS_CONFIG['folderName'];
        $langDynamicTranslationsDirectory = new DirectoryObject(basepath("app/lang/{$baseFolderName}/"));
        $currentJSONFile = append_to_path_system($langDynamicTranslationsDirectory->getPath(), 'current-translations.json');
        file_put_contents($currentJSONFile, json_encode($currentJSONData, \JSON_UNESCAPED_UNICODE  | \JSON_UNESCAPED_SLASHES));
    }

}
