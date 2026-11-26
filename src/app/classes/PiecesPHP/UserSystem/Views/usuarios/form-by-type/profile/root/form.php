<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

//El formulario de perfil del tipo «root»: el genérico. Un proyecto puede reescribirlo conservando las clases `users profile` y los `name`.

$formMode = 'profile';
$formType = 'root';
require dirname(__DIR__, 2) . '/user-form.php';
