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

    //Los topes salen de los 17 JSON guardados (#065): 687 caracteres y 47 claves como máximo, por dos.
    const GROUP_PATTERN = '/^[A-Za-z0-9_\\\\-]{1,64}$/';
    const KEY_MAX_LENGTH = 1400;
    const KEYS_MAX_PER_GROUP = 100;
    const FORBIDDEN_TAGS = ['script', 'iframe', 'object', 'embed', 'style'];

    /**
     * Filtra lo que llega como traducción de unas claves: solo cuentan las pedidas, y cada valor
     * tiene que conservar las etiquetas de su clave, en el mismo orden, sin carga ejecutable.
     *
     * Motivos de rechazo: falta, no-es-texto, etiqueta-prohibida:<nombre>, atributo-on, javascript
     * y etiquetas-distintas.
     *
     * @param array<int|string,mixed> $keys
     * @param array<int|string,mixed> $aiResult
     * @return array{accepted: array<string,string>, rejected: array<string,string>}
     */
    public static function acceptTranslations(array $keys, array $aiResult): array
    {
        $accepted = [];
        $rejected = [];
        foreach ($keys as $key) {
            if (!is_string($key)) {
                continue;
            }
            if (!array_key_exists($key, $aiResult)) {
                $rejected[$key] = 'falta';
                continue;
            }
            $value = $aiResult[$key];
            if (!is_string($value) || trim($value) === '') {
                $rejected[$key] = 'no-es-texto';
                continue;
            }
            //Se mira el VALOR aunque la clave traiga lo mismo: lo que se guarda es el valor.
            $danger = self::dangerIn($value);
            if ($danger !== null) {
                $rejected[$key] = $danger;
                continue;
            }
            $keyTags = self::tagSequence($key);
            if ($keyTags === null) {
                $rejected[$key] = 'no-analizable';
                continue;
            }
            if (self::tagSequence($value) !== $keyTags) {
                $rejected[$key] = 'etiquetas-distintas';
                continue;
            }
            $accepted[$key] = $value;
        }
        return ['accepted' => $accepted, 'rejected' => $rejected];
    }

    /**
     * Los nombres de etiqueta en orden, con «/» delante de los de cierre.
     *
     * @param string $html
     * @return string[]|null null si la expresión no pudo analizar el texto: quien llama rechaza.
     */
    private static function tagSequence(string $html): ?array
    {
        if (preg_match_all('/<\s*(\/?)\s*([a-zA-Z][a-zA-Z0-9-]*)/', $html, $matches, \PREG_SET_ORDER) === false) {
            return null;
        }
        return array_map(fn (array $tag): string => $tag[1] . mb_strtolower($tag[2]), $matches);
    }

    /**
     * @param string $value
     * @return string|null El motivo de rechazo, o null si no trae carga.
     */
    private static function dangerIn(string $value): ?string
    {
        $tags = self::tagSequence($value);
        if ($tags === null) {
            return 'no-analizable';
        }
        foreach ($tags as $tag) {
            $name = ltrim($tag, '/');
            if (in_array($name, self::FORBIDDEN_TAGS, true)) {
                return "etiqueta-prohibida:{$name}";
            }
        }
        if (preg_match('/<[^>]*\son[a-z]+\s*=/i', $value) === 1) {
            return 'atributo-on';
        }
        if (preg_match('/javascript\s*:/i', $value) === 1) {
            return 'javascript';
        }
        return null;
    }

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
    public static function saveCurrentDynamicTranslationsJSON(JSONTranslationsPackage $currentJSONData): void
    {
        $DYNAMIC_TRANSLATIONS_CONFIG = get_config('DYNAMIC_TRANSLATIONS');
        $baseFolderName = $DYNAMIC_TRANSLATIONS_CONFIG['folderName'];
        $langDynamicTranslationsDirectory = new DirectoryObject(basepath("app/lang/{$baseFolderName}/"));
        $currentJSONFile = append_to_path_system($langDynamicTranslationsDirectory->getPath(), 'current-translations.json');
        file_put_contents($currentJSONFile, json_encode($currentJSONData, \JSON_UNESCAPED_UNICODE  | \JSON_UNESCAPED_SLASHES));
    }

}
