<?php
/**
 * Idiomas soportados
 */

/**
 * Si la configuracion 'lang_by_url' se establece en true, la aplicación tomará
 * el primer segmento de la URL que concuerte con 'allowed_langs' para establecer el idioma.
 * Ej.: De la URL http://www.example.com/en tomaría el valor 'en' y si no concuerda con ningún
 * idioma permitido o no hay segmento alguno el valor por defecto será 'es'.
 */
set_config('lang_by_url', true);

/**
 * Si la configuracion 'default_lang_by_browser' se establece en true, la aplicación definirá
 * 'default_lang' a partir de getPreferredLanguageByHeader
 */
set_config('default_lang_by_browser', false);

/**
 * Array con el identificador de los idiomas permitidos, este debe coincidir
 * con el nombre de su archivo correspondiente en app/lang/ sin la extensión '.php'
 * ya que es implícita.
 */
set_config('allowed_langs', [
    'es',
    'en',
    'fr',
    'de',
    'it',
    'pt',
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

/**
 * Array con los códigos de localidad según el idioma
 */
set_config('locale_langs', [
    'es' => [
        'es_CO.utf8',
        'es_ES.utf8',
    ],
    'en' => 'en_US.utf8',
    'fr' => 'fr_FR.utf8',
    'de' => 'de_DE.utf8',
    'it' => 'it_IT.utf8',
    'pt' => 'pt_PT.utf8',
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

/**
 * @function get_fomantic_flag_by_lang
 * Banderas de fomantic según idioma
 * @see https://fomantic-ui.com/elements/flag.html
 */
set_config('get_fomantic_flag_by_lang', function (string $langCode, string $size = '', float $currentOpacity = null) {

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
