<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

//El formulario de alta del tipo «admin-organization»: el genérico. Un proyecto puede reescribirlo conservando las clases `users create` y los `name`.

$formMode = 'create';
$formType = 'admin-organization';
require dirname(__DIR__, 2) . '/user-form.php';
