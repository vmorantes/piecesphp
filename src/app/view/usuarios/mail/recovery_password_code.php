<?php
$this->render('usuarios/mail/template_base_problem', [
    'text' => __('mailTemplates', 'Recuperación de contraseña') . ':',
    'url' => $url,
    'code' => $code,
    'text_button' => __('mailTemplates', 'Ingresar el código'),
    'note' => __('mailTemplates', 'MENSAJE_DE_VALIDEZ'),
]);
