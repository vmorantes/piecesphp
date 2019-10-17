
<?php
$this->render('usuarios/mail/template_base_problem', [
    'text' => __('mailTemplates', 'Recuperación de contraseña'),
    'url' => $url,
    'text_button' => __('mailTemplates', 'Click para restablecer su contraseña'),
    'note' => __('mailTemplates', 'MENSAJE_DE_VALIDEZ'),
]);
