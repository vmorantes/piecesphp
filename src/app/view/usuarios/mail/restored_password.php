<?php

$langGroup = MAIL_TEMPLATES_LANG_GROUP;

$this->render('usuarios/mail/template_base_problem', [
    'text' => __($langGroup, 'Su nueva contraseña es') . ': ',
    'url' => $url,
    'text_button' => __($langGroup, 'Iniciar sesión.'),
]);
