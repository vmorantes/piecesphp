<?php

namespace App\LangMessages;

//Hereda los mensajes que estan en lang/default.php
$lang = [
    'datatables' => [
        'ASC'  => '<i class="ui icon arrow down"></i>Ascendente',
        'DESC' => '<i class="ui icon arrow up"></i>Descendente',
	],
    'userLogin' => [
        'NEED_HELP_TO_LOGIN' => '¿Necesitas ${ot}ayuda para ingresar?${ct}',
        'WELCOME_MSG'        => '<h1>¡Hola!</h1><span>Bienvenidos</span>',
    ],
];

return $lang;
