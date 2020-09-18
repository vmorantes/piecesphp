
<?php

$langGroup = MAIL_TEMPLATES_LANG_GROUP;

$this->render('usuarios/mail/template_base_problem', [
    'text' => __($langGroup, 'Recuperación de contraseña'),
    'url' => $url,
    'text_button' => __($langGroup, 'Click para restablecer su contraseña'),
    'note' => __($langGroup, 'MENSAJE_DE_VALIDEZ'),
]);
