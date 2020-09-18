<?php

$langGroup = MAIL_TEMPLATES_LANG_GROUP;

$this->render('usuarios/mail/template_base_problem', [
    'text' => __($langGroup, 'Recuperación de contraseña') . ':',
    'url' => $url,
    'code' => $code,
    'text_button' => __($langGroup, 'Ingresar el código'),
    'note' => __($langGroup, 'MENSAJE_DE_VALIDEZ'),
]);
