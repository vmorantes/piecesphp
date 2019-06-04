<?php
$this->render('usuarios/mail/template_base_problem', [
    'text' => __('general', 'new_password_is'),
    'url' => $url,
    'text_button' => __('general', 'loging'),
]);
