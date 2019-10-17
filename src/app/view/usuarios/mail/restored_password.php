<?php
$this->render('usuarios/mail/template_base_problem', [
    'text' => __('mailTemplates', 'Su nueva contraseña es') . ': ',
    'url' => $url,
    'text_button' => __('mailTemplates', 'Iniciar sesión.'),
]);
