
<?php
$this->render('usuarios/mail/template_base_problem', [
    'text' => __('general', 'password_recovery'),
    'url' => $url,
    'text_button' => __('general', 'click_for_restore_password'),
    'note' => 'Recuerde que el enlace tiene una validez de 24 horas, después de ese tiempo debe generar uno nuevo',
]);
