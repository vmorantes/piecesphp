<?php

/**
 * @pcsphp-config clon
 * Qué conviene editar aquí: los valores de las cookies de tu instalación. En producción con HTTPS, `secure` en true.
 */
//========================================================================================
/*                                                                                      *
 *                              CONFIGURACIONES DE COOKIES                              *
 *                                                                                      */
//========================================================================================
/**
 *  $config['cookies']: Configuraciones de las cookies [lifetime,path,domain,secure,httponly,samesite]
 * Ver http://php.net/manual/en/session.configuration.php En los apartados session.cookie*
 *
 * Nota: el ejemplo anterior se refiere a los valores que la aplicación asume por defecto.
 *
 */

//──── Cookies ───────────────────────────────────────────────────────────────────────────
$config['cookies']['lifetime'] = 0;
$config['cookies']['path'] = '/';
$config['cookies']['domain'] = $_SERVER['HTTP_HOST'];
$config['cookies']['secure'] = false;
$config['cookies']['httponly'] = false;
//Lax, Strict o None. Otro valor cuenta como Lax, y None sin secure también: el navegador la descartaría.
$config['cookies']['samesite'] = 'Lax';
