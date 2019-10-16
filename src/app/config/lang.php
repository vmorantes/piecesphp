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
    'fr',
]);
