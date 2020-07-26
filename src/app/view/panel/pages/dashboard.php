<?php

use App\Model\UsersModel;

defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

$user = new UsersModel(get_config('current_user')->id);

?>
<h1><?= __('general', 'Bienvenido(a)'); ?><br><small><?= $user->getFullName(); ?></small></h1>
