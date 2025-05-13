<?php

use App\Model\AppConfigModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Helpers\Directories\DirectoryObject;

$addDynamicTranslationsLogicFunction = function ($verboseResult = false) {

    $langsOptions = array_merge(Config::get_allowed_langs(), ['default']);
    $langDynamicTranslationsDirectory = new DirectoryObject(basepath('app/lang/dynamic-translations/'));
    $langDynamicTranslationsDirectory->process();
    $langDynamicTranslationsDirectoryLangs = $langDynamicTranslationsDirectory->getDirectories();
    $added = [];

    //Archivos de traducciones dinámicas
    foreach ($langDynamicTranslationsDirectoryLangs as $langDynamicTranslationsDirectoryLang) {

        $langDynamicTranslationsDirectoryLang->process();
        $directoryLangName = $langDynamicTranslationsDirectoryLang->getBasename();
        $langDynamicTranslationsDirectoryLangFiles = $langDynamicTranslationsDirectoryLang->getFiles();

        if (in_array($directoryLangName, $langsOptions)) {

            if (!isset($added[$directoryLangName])) {
                $added[$directoryLangName] = [];
            }

            foreach ($langDynamicTranslationsDirectoryLangFiles as $groupLangFile) {

                $groupLangFileExtension = $groupLangFile->getExtension();

                if ($groupLangFileExtension == 'php') {
                    $langGroup = str_replace('.php', '', $groupLangFile->getBasename());
                    $groupLangFileData = include $groupLangFile->getPath();
                    if (!isset($added[$directoryLangName][$langGroup])) {
                        $added[$directoryLangName][$langGroup] = [];
                    }
                    foreach ($groupLangFileData as $key => $value) {
                        Config::addLangMessage($directoryLangName, $langGroup, $key, $value);
                        $added[$directoryLangName][$langGroup][$key] = $value;
                    }
                }
            }

        }
    }

    //Registros en base de datos de traducciones dinámicas
    $translationsDatabase = new AppConfigModel('dynamicTranslations');
    if ($translationsDatabase->id !== null) {

        $dynamicTranslationsFolder = $langDynamicTranslationsDirectory->getPath();
        $translationsFromDatabase = $translationsDatabase->value;
        $updated = null;
        $translationsFromDatabaseIsObject = is_object($translationsFromDatabase);

        if ($translationsFromDatabaseIsObject) {

            $translationsFromDatabase = objectToArray($translationsFromDatabase);
            $updated = date('Y-m-d H:i:s');

            if (is_array($translationsFromDatabase)) {
                $translationsDatabase->value = base64_encode(json_encode([
                    'data' => $translationsFromDatabase,
                    'updated' => $updated,
                ], \JSON_UNESCAPED_UNICODE  | \JSON_UNESCAPED_SLASHES));
                $translationsDatabase->update();
            }

        } else {
            $translationsFromDatabase = is_string($translationsFromDatabase) ? base64_decode($translationsFromDatabase) : null;
            $translationsFromDatabase = is_string($translationsFromDatabase) ? json_decode($translationsFromDatabase, true) : null;
            $updated = $translationsFromDatabase !== null ? $translationsFromDatabase['updated'] : null;
            $translationsFromDatabase = $translationsFromDatabase !== null ? $translationsFromDatabase['data'] : [];
        }

        $fileByUpdateDate = append_to_path_system($dynamicTranslationsFolder, 'dynamic-translations-' . $updated . '.json');
        if (!file_exists($fileByUpdateDate)) {
            file_put_contents($fileByUpdateDate, json_encode($translationsFromDatabase, \JSON_UNESCAPED_UNICODE  | \JSON_UNESCAPED_SLASHES));
        }

        foreach ($translationsFromDatabase as $lang => $langTranslations) {
            if (!isset($added[$lang])) {
                $added[$lang] = [];
            }
            foreach ($langTranslations as $group => $groupTranslations) {
                if (!isset($added[$lang][$group])) {
                    $added[$lang][$group] = [];
                }
                foreach ($groupTranslations as $key => $value) {
                    Config::addLangMessage($lang, $group, $key, $value);
                    $added[$lang][$group][$key] = $value;
                }
            }
        }

    }

    if ($verboseResult) {
        var_dump_pretty($added);
        exit;
    }

};

($addDynamicTranslationsLogicFunction)(false);
set_config('add_dynamic_translations', $addDynamicTranslationsLogicFunction);
