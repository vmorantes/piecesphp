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
 * Opciones de las cookies. Formato y trampas (lifetime): source-docs/project/docs/piecesphp/content/configuration.md, «cookies.php».
 */

//──── Cookies ───────────────────────────────────────────────────────────────────────────
$config['cookies']['lifetime'] = 0;
$config['cookies']['path'] = '/';
$config['cookies']['domain'] = $_SERVER['HTTP_HOST'];
$config['cookies']['secure'] = false;
$config['cookies']['httponly'] = false;
//Lax, Strict o None. Otro valor cuenta como Lax, y None sin secure también: el navegador la descartaría.
$config['cookies']['samesite'] = 'Lax';
