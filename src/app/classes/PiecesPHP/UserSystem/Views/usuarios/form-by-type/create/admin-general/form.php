<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @deprecated 10.45.0: ya no se pinta; UsersController pinta user-form.php con el código del tipo. Sale en la 11.0.0.
 */

$formMode = 'create';
$formType = 'admin-general';
require dirname(__DIR__, 2) . '/user-form.php';
