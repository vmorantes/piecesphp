<?php
//========================================================================================
/*                                                                                      *
 *                           CONFIGURACIONES DE ENVÍO DE MAIL                           *
 *                                                                                      */
//========================================================================================
/**
 *
 * $config['mail']: Configuraciones para el envío de correos usadas por la clase PiecesPHP\Core\Mailer.
 * Se usa la forma $config['mail']['CONFIGURACIÓN'].
 * Las configuraciones aceptadas son [smtp_debug,is_smtp,protocol,host,auth,user,password,port].
 *
 * Nótese que son las que solicita PHPMailer puesto que el sistema de correos está basado en esa librería.
 *
 * Por ejemplo:
 *
 * $config['mail']['smtp_debug'] = PHPMailer\PHPMailer\SMTP::DEBUG_OFF;
 * $config['mail']['is_smtp'] = true; //Si esta el false no se aplica ninguna configuración
 * $config['mail']['protocol'] = 'ssl';
 * $config['mail']['host'] = 'smtp.host.com';
 * $config['mail']['auth'] = true;
 * $config['mail']['user'] = 'correo@correo.com';
 * $config['mail']['password'] = '123456';
 * $config['mail']['port'] = 465;
 *
 */

//──── Mailing ───────────────────────────────────────────────────────────────────────────
$config['mail']['smtp_debug'] = 0;
$config['mail']['auto_tls'] = true;
$config['mail']['smpt_options'] = [];
$config['mail']['is_smtp'] = true;
$config['mail']['protocol'] = 'ssl';
$config['mail']['host'] = 'smtp.zoho.com';
$config['mail']['auth'] = true;
$config['mail']['user'] = 'correo@correo.com';
$config['mail']['password'] = '123456';
$config['mail']['port'] = 465;
