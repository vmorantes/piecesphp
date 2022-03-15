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
 * Array con el identificador de los idiomas permitidos, este debe coincidir
 * con el nombre de su archivo correspondiente en app/lang/ sin la extensión '.php'
 * ya que es implícita.
 */
set_config('allowed_langs', [
    'es',
    'en',
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
]);

/**
 * Array con los códigos de localidad para las conexiones a base de datos
 */
set_config('lc_time_names_mysql', [
    'es' => 'es_ES',
    'en' => 'en_US',
]);

/**
 * Formatos de fechas según idioma
 */
set_config('format_date_lang', [
    'es' => 'l, d/F/Y', // Sábado, 08/Mayo/2021
    'en' => 'l, Y/F/d',
]);
