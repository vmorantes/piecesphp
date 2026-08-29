<?php
/**
 * Idiomas soportados
 */

/**
 * Si la configuracion 'lang_by_url' se establece en true, la aplicación tomará
 * el primer segmento de la URL que concuerte con 'allowed_langs' para establecer el idioma.
 * Ej.: De la URL http://www.example.com/en tomaría el valor 'en' y si no concuerda con ningún
 * idioma permitido o no hay segmento alguno el valor por defecto será 'es'.
 * NOTA: Tiene prioridad sobre lang_by_browser si el idioma en URL es explícito.
 */
set_config('lang_by_url', true);

/**
 * Si la configuracion 'lang_by_browser' se establece en true, la aplicación definirá
 * 'default_lang' a partir de getPreferredLanguageByHeader.
 * NOTA: Tiene prioridad sobre lang_by_url en el estado base, pero no con idioma en URL explícito.
 */
set_config('lang_by_browser', true);

/**
 * Si la configuracion 'lang_by_cookie' se establece en true, la aplicación definirá
 * 'app_lang' (lenguaje actual, no por defecto) a partir de del valor de la cookie llamada como se define en 'cookie_lang_definer'
 * y del parámetro url i18n.
 * NOTA: Tiene prioridad sobre lang_by_url y lang_by_browser
 */
set_config('lang_by_cookie', true);
//Se define a partir del parámetro de URL i18n
set_config('cookie_lang_definer', 'PREFER_LANG_BY_COOKIE');

/**
 * ┌──────────────────────────────────────────────────────────────────────────────────┐
 * │  CÓMO SE AÑADE UN IDIOMA — EL EJEMPLO ESTÁ COMENTADO EN ESTE MISMO ARCHIVO        │
 * └──────────────────────────────────────────────────────────────────────────────────┘
 *
 * Este proyecto se sirve en 'es' y 'en'. Los otros cuatro se dejan COMENTADOS a
 * propósito: no son un resto, son la receta completa. Para dar de alta un idioma se
 * descomenta su línea en los OCHO sitios de este archivo —están todos marcados con
 * «IDIOMA COMENTADO»— y se atienden los TRES de fuera:
 *
 *   1. `allowed_langs` .............. lo hace válido como segmento de URL y en el selector
 *   2. `autoTranslateFromLangGroupHTMLIgnoreLangs` .. sáltalo si NO quieres traducción automática
 *   3. `no_scan_langs` .............. sácalo de aquí si quieres que `scan-missing-lang` lo audite
 *   4. `locale_langs` ............... `setlocale()` de PHP; varios candidatos por si el sistema no trae uno
 *   5. `lc_time_names_mysql` ........ nombres de mes y día que devuelve MySQL; ver AE2 sobre los candidatos
 *   6. `format_date_lang` ........... formato de fecha de PHP
 *   7. `format_date_lang_sql` ....... el mismo formato, en la sintaxis de MySQL
 *   8. `get_fomantic_flag_by_lang` .. la bandera del selector (el código de PAÍS no siempre es el de idioma:
 *                                      'en' usa 'gb')
 *
 *   FUERA DE AQUÍ:
 *   · `app/lang/<código>.php` y el `<código>.php` de cada módulo con carpeta `lang/`.
 *     Si falta, `LangInjector` lo salta y `__()` devuelve el texto en español: no rompe,
 *     pero se ve el idioma equivocado.
 *   · `statics/core/js/translations/<código>.js` y su entrada en `configurations.js`.
 *     Sin eso el front cae al idioma por defecto.
 *   · `config/assets.php`: el idioma de CKEditor. Y si el módulo usa elFinder, mira
 *     `FileManager/Statics/js/file-manager.js`, que traduce códigos ('pt' → 'pt_BR').
 */

/**
 * Array con el identificador de los idiomas permitidos, este debe coincidir
 * con el nombre de su archivo correspondiente en app/lang/ sin la extensión '.php'
 * ya que es implícita.
 */
set_config('allowed_langs', [
    'es',
    'en',
    //IDIOMA COMENTADO: descomentar para dar de alta el idioma. Ver el bloque de arriba.
    //'fr',
    //'de',
    //'it',
    //'pt',
]);

//Idiomas no traducibles automáticamente con autoTranslateFromLangGroupHTML
add_to_front_configurations('autoTranslateFromLangGroupHTMLIgnoreLangs', [
    'es',
    'en',
    //IDIOMA COMENTADO
    //'fr',
    //'de',
    //'it',
    //'pt',
]);

//Idiomas y grupos para ignorar en el registro de traducciones faltantes (missing-lang-messages). Idiomas para añadir aunque no esté en los permitidos (additional_langs_to_scan).
set_config('no_scan_langs', [
    'es',
    //'en',
    //IDIOMA COMENTADO
    //'fr',
    //'de',
    //'it',
    //'pt',
]);
set_config('no_scan_lang_groups', [
    'locationBackend-names',
]);
set_config('additional_langs_to_scan', [
    //'es',
    //'en',
    //'fr',
    //'de',
    //'it',
    //'pt',
]);

/**
 * Array con los códigos de localidad según el idioma
 */
set_config('get_locale_versions_by_locale', function (array $locales) {
    $localeVersions = [];
    $versions = [
        '',
        'utf8',
        'UTF-8',
    ];
    foreach ($locales as $locale) {
        if (is_string($locale)) {
            foreach ($versions as $version) {
                $localeVersions[] = $locale . (mb_strlen($version) > 0 ? ".{$version}" : $version);
            }
        }
    }
    return $localeVersions;
});
set_config('locale_langs', [
    'es' => get_config('get_locale_versions_by_locale')(['es_CO', 'es_ES', 'es_MX']),
    'en' => get_config('get_locale_versions_by_locale')(['en_US']),
    //IDIOMA COMENTADO
    //'fr' => get_config('get_locale_versions_by_locale')(['fr_FR']),
    //'de' => get_config('get_locale_versions_by_locale')(['de_DE']),
    //'it' => get_config('get_locale_versions_by_locale')(['it_IT']),
    //'pt' => get_config('get_locale_versions_by_locale')(['pt_PT']),
]);

/**
 * Array con los códigos de localidad para las conexiones a base de datos
 */
set_config('lc_time_names_mysql', [
    'es' => [
        'es_ES',
        'es_CO',
        'es_MX',
    ],
    'en' => [
        'en_US',
    ],
    //IDIOMA COMENTADO
    //'fr' => [
    //    'fr_FR',
    //],
    //'de' => [
    //    'de_DE',
    //],
    //'it' => [
    //    'it_IT',
    //],
    //'pt' => [
    //    'pt_PT',
    //],
]);

/**
 * Formatos de fechas según idioma
 */
set_config('format_date_lang', [
    //'es' => 'l, d/F/Y', // Sábado, 08/Mayo/2021
    //'en' => 'l, Y/F/d', // Saturday, 2021/May/08
    'es' => 'd/m/Y', // 08/05/2021
    'en' => 'm/d/Y',
    //IDIOMA COMENTADO
    //'fr' => 'm/d/Y',
    //'de' => 'm/d/Y',
    //'it' => 'm/d/Y',
    //'pt' => 'd/m/Y',
]);
set_config('format_date_lang_sql', [
    'es' => '%d/%m/%Y', // 08/05/2021
    'en' => '%Y/%m/%d',
    //IDIOMA COMENTADO
    //'fr' => '%Y/%m/%d',
    //'de' => '%Y/%m/%d',
    //'it' => '%Y/%m/%d',
    //'pt' => '%d/%m/%Y',
]);

/**
 * @function get_fomantic_flag_by_lang
 * Banderas de fomantic según idioma
 * @see https://fomantic-ui.com/elements/flag.html
 */
set_config('get_fomantic_flag_by_lang', function (string $langCode, string $size = '', ?float $currentOpacity = null) {

    $flags = [
        'es' => "<i{CURRENT}class='{$size} es flag'></i>",
        'en' => "<i{CURRENT}class='{$size} gb flag'></i>",
        //IDIOMA COMENTADO: la bandera es un código de PAÍS, no de idioma ('en' usa 'gb').
        //'fr' => "<i{CURRENT}class='{$size} fr flag'></i>",
        //'de' => "<i{CURRENT}class='{$size} de flag'></i>",
        //'it' => "<i{CURRENT}class='{$size} it flag'></i>",
        //'pt' => "<i{CURRENT}class='{$size} pt flag'></i>",
    ];

    $currentLang = \PiecesPHP\Core\Config::get_lang();
    $currentOpacity = !is_null($currentOpacity) ? $currentOpacity : 1;

    foreach ($flags as $lang => $flagHTML) {
        $onCurrent = [
            '{CURRENT}' => " current-lang style='opacity: {$currentOpacity};' ",
        ];
        $onNotCurrent = [
            '{CURRENT}' => " ",
        ];
        $flagHTML = $lang == $currentLang ? strReplaceTemplate($flagHTML, $onCurrent) : strReplaceTemplate($flagHTML, $onNotCurrent);
        $flags[$lang] = $flagHTML;
    }

    return array_key_exists($langCode, $flags) ? $flags[$langCode] : '';
});

/**
 * Configuración de traducciones dinámicas
 */
set_config('DYNAMIC_TRANSLATIONS', [
    'folderName' => 'dynamic-translations',
    'filePrefix' => 'dynamic-translations-',
    //GenericContentPseudoMapper::CONTENT_DYNAMIC_TRANSCALTIONS,
    'dataConfigName' => 'DYNAMIC_TRANSCALTIONS',
    //GenericContentPseudoMapper::CONTENT_DYNAMIC_TRANSLATIONS_UPDATED_AT,
    'lastDateConfigName' => 'DYNAMIC_TRANSLATIONS_UPDATED_AT',
]);
