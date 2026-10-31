<?php

/**
 * @pcsphp-config ambos
 * Qué conviene editar aquí: qué idiomas ofrece tu aplicación y cómo se elige el de cada visita (sección del clon). Las tablas de locales, formatos y banderas son del framework.
 */
/**
 * Idiomas soportados
 */

//── Del clon ──
//El primer segmento de la URL, si es un idioma permitido (/en). Prioridades: source-docs/project/docs/piecesphp/content/configuration.md, «lang.php».
set_config('lang_by_url', true);

//El idioma preferido del navegador (Accept-Language); cede ante un idioma explícito en la URL.
set_config('lang_by_browser', true);

//La cookie 'cookie_lang_definer', que se pone con ?i18n=: gana a la URL y al navegador.
set_config('lang_by_cookie', true);
//Se define a partir del parámetro de URL i18n
set_config('cookie_lang_definer', 'PREFER_LANG_BY_COOKIE');

/**
 * `allowed_langs` es EL INTERRUPTOR: lo que no esté ahí no existe, y lo demás de este archivo
 * puede quedarse vivo sin molestar. Cómo se añade un idioma: `.agents/context/08-i18n.md`.
 */

//Cada código necesita su src/app/lang/<código>.php: sin él, el idioma se ignora en silencio.
set_config('allowed_langs', [
    'es',
    'en',
    //@codigo-comentado · Descomentar da de alta el idioma. Ver `.agents/context/08-i18n.md`.
    //'fr',
    //'de',
    //'it',
    //'pt',
]);

//Idiomas no traducibles automáticamente con autoTranslateFromLangGroupHTML
add_to_front_configurations('autoTranslateFromLangGroupHTMLIgnoreLangs', [
    'es',
    'en',
    'fr',
    'de',
    'it',
    'pt',
]);

//Idiomas y grupos para ignorar en el registro de traducciones faltantes (missing-lang-messages). Idiomas para añadir aunque no esté en los permitidos (additional_langs_to_scan).
set_config('no_scan_langs', [
    'es',
    //'en',
    'fr',
    'de',
    'it',
    'pt',
]);
set_config('no_scan_lang_groups', [
    'locationBackend-names',
]);
set_config('additional_langs_to_scan', [
    //@codigo-comentado · Descomentar revisa ese idioma aunque no esté en allowed_langs.
    //'es',
    //'en',
    //'fr',
    //'de',
    //'it',
    //'pt',
]);

//── Del framework: no lo edites ──
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
    'fr' => get_config('get_locale_versions_by_locale')(['fr_FR']),
    'de' => get_config('get_locale_versions_by_locale')(['de_DE']),
    'it' => get_config('get_locale_versions_by_locale')(['it_IT']),
    'pt' => get_config('get_locale_versions_by_locale')(['pt_PT']),
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
    'fr' => [
        'fr_FR',
    ],
    'de' => [
        'de_DE',
    ],
    'it' => [
        'it_IT',
    ],
    'pt' => [
        'pt_PT',
    ],
]);

/**
 * Formatos de fechas según idioma
 */
set_config('format_date_lang', [
    //'es' => 'l, d/F/Y', // Sábado, 08/Mayo/2021
    //'en' => 'l, Y/F/d', // Saturday, 2021/May/08
    'es' => 'd/m/Y', // 08/05/2021
    'en' => 'm/d/Y',
    'fr' => 'm/d/Y',
    'de' => 'm/d/Y',
    'it' => 'm/d/Y',
    'pt' => 'd/m/Y',
]);
set_config('format_date_lang_sql', [
    'es' => '%d/%m/%Y', // 08/05/2021
    'en' => '%Y/%m/%d',
    'fr' => '%Y/%m/%d',
    'de' => '%Y/%m/%d',
    'it' => '%Y/%m/%d',
    'pt' => '%d/%m/%Y',
]);

//Las banderas de Fomantic por idioma.
/**
 * @function get_fomantic_flag_by_lang
 * @see https://fomantic-ui.com/elements/flag.html
 */
set_config('get_fomantic_flag_by_lang', function (string $langCode, string $size = '', ?float $currentOpacity = null) {

    $flags = [
        'es' => "<i{CURRENT}class='{$size} es flag'></i>",
        'en' => "<i{CURRENT}class='{$size} gb flag'></i>",
        'fr' => "<i{CURRENT}class='{$size} fr flag'></i>",
        'de' => "<i{CURRENT}class='{$size} de flag'></i>",
        'it' => "<i{CURRENT}class='{$size} it flag'></i>",
        'pt' => "<i{CURRENT}class='{$size} pt flag'></i>",
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
    //GenericContentPseudoMapper::CONTENT_DYNAMIC_TRANSLATIONS,
    'dataConfigName' => 'DYNAMIC_TRANSLATIONS',
    //GenericContentPseudoMapper::CONTENT_DYNAMIC_TRANSLATIONS_UPDATED_AT,
    'lastDateConfigName' => 'DYNAMIC_TRANSLATIONS_UPDATED_AT',
]);
