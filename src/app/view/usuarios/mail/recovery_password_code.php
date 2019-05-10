<?php
$this->render('usuarios/mail/template_base_problem', [
    'text' => __('general', 'password_recovery') . ':',
    'url' => $url,
    'code' => $code,
    'text_button' => __('general', 'enter_the_code'),
    'note' => 'Recuerde que este código tiene una validez de 24 horas, después de ese tiempo debe generar uno nuevo',
]);
