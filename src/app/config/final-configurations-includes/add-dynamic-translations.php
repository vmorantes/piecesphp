<?php

use PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper;
use PiecesPHP\Core\BaseEventDispatcher;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Helpers\Directories\DirectoryObject;
use PiecesPHP\LocalizationSystem\Util\DynamicTranslationsHelper;

$addDynamicTranslationsLogicFunction = function () {

    /* Variables de configuración */
    $DYNAMIC_TRANSLATIONS_CONFIG = get_config('DYNAMIC_TRANSLATIONS');
    $baseFolderName = $DYNAMIC_TRANSLATIONS_CONFIG['folderName'];
    $filePrefix = $DYNAMIC_TRANSLATIONS_CONFIG['filePrefix'];
    $dataConfigName = $DYNAMIC_TRANSLATIONS_CONFIG['dataConfigName'];
    $lastDateConfigName = $DYNAMIC_TRANSLATIONS_CONFIG['lastDateConfigName'];

    /* Lectura de traducciones dinámicas presentes en php */
    $langsOptions = array_merge(Config::get_allowed_langs(), ['default']);
    $langDynamicTranslationsDirectory = new DirectoryObject(basepath("app/lang/{$baseFolderName}/"));
    $langDynamicTranslationsDirectory->process();
    $langDynamicTranslationsDirectoryLangs = $langDynamicTranslationsDirectory->getDirectories();

    //Archivos de traducciones dinámicas
    foreach ($langDynamicTranslationsDirectoryLangs as $langDynamicTranslationsDirectoryLang) {

        $langDynamicTranslationsDirectoryLang->process();
        $directoryLangName = $langDynamicTranslationsDirectoryLang->getBasename();
        $langDynamicTranslationsDirectoryLangFiles = $langDynamicTranslationsDirectoryLang->getFiles();

        if (in_array($directoryLangName, $langsOptions)) {

            foreach ($langDynamicTranslationsDirectoryLangFiles as $groupLangFile) {

                $groupLangFileExtension = $groupLangFile->getExtension();

                if ($groupLangFileExtension == 'php') {
                    $langGroup = str_replace('.php', '', $groupLangFile->getBasename());
                    $groupLangFileData = include $groupLangFile->getPath();
                    foreach ($groupLangFileData as $key => $value) {
                        Config::addLangMessage($directoryLangName, $langGroup, $key, $value);
                    }
                }
            }

        }
    }

    /* Lectura de traducciones dinámicas presentes en base de datos */

    //Registros en base de datos de traducciones dinámicas
    $currentJSONTranslationsPackage = DynamicTranslationsHelper::getCurrentDynamicTranslationsJSON();
    $currentJSONTranslationsData = $currentJSONTranslationsPackage->getData();
    $currentDatabaseData = GenericContentPseudoMapper::getContentData($dataConfigName);
    $currentDatabaseLastDate = GenericContentPseudoMapper::getContentData($lastDateConfigName);
    $currentDatabaseLastDate = $currentDatabaseLastDate instanceof \DateTime  ? $currentDatabaseLastDate : null;

    /* Actualizar traducciones locales del JSON con las de la base de datos, si es necesario */
    if ($currentDatabaseLastDate !== null && $currentDatabaseLastDate > $currentJSONTranslationsPackage->getUpdated()) {

        //Revisar si hay traducciones en la base de datos que no estén en el JSON
        foreach ($currentDatabaseData as $lang => $groupedTranslations) {
            //Crear el idioma si no existe
            if (!isset($currentJSONTranslationsData[$lang])) {
                $currentJSONTranslationsData[$lang] = [];
            }
            foreach ($groupedTranslations as $group => $translations) {
                //Crear el grupo si no existe
                if (!isset($currentJSONTranslationsData[$lang][$group])) {
                    $currentJSONTranslationsData[$lang][$group] = [];
                }
                //Añadir las traducciones del grupo
                foreach ($translations as $key => $value) {
                    $currentJSONTranslationsData[$lang][$group][$key] = $value;
                }
            }
        }

        //Guardar los datos en el JSON
        $currentJSONTranslationsPackage->setData($currentJSONTranslationsData);
        DynamicTranslationsHelper::saveCurrentDynamicTranslationsJSON($currentJSONTranslationsPackage);

        //Limpiar base de datos
        GenericContentPseudoMapper::setContentData($dataConfigName, []);
        GenericContentPseudoMapper::setContentData($lastDateConfigName, new \DateTime('1990-01-01 00:00:00'));

        //Agregar log de actualización
        $fileByUpdateDate = append_to_path_system($langDynamicTranslationsDirectory->getPath(), $filePrefix . date('Y-m-d H:i:s') . '.json');
        file_put_contents($fileByUpdateDate, json_encode($currentJSONTranslationsPackage, \JSON_UNESCAPED_UNICODE  | \JSON_UNESCAPED_SLASHES));

    }

    /* Inyectar traducciones del JSON en el sistema */
    $currentJSONTranslationsData = $currentJSONTranslationsPackage->getData();
    foreach ($currentJSONTranslationsData as $lang => $langTranslations) {
        foreach ($langTranslations as $group => $groupTranslations) {
            foreach ($groupTranslations as $key => $value) {
                Config::addLangMessage($lang, $group, $key, $value);
            }
        }
    }

    /**
     * @category GlobalMethodDispatch
     */
    BaseEventDispatcher::dispatch('AddDynamicTransaltions', 'added');

};

($addDynamicTranslationsLogicFunction)();
set_config('add_dynamic_translations', $addDynamicTranslationsLogicFunction);
