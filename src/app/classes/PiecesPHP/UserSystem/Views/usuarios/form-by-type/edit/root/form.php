<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

//El formulario de edición del tipo «root»: el genérico. Un proyecto puede reescribirlo conservando las clases `users edit` y los `name`.

$formMode = 'edit';
$formType = 'root';
require dirname(__DIR__, 2) . '/user-form.php';
