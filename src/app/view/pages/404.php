<?php
$errorPageTitle = __('page404', '404 - Página no encontrada');
$errorCode = '404';
$errorIcon = '';
$errorHeading = __('page404', 'Página no encontrada');
$errorText = __('page404', 'El enlace al que intenta ingresar ya no existe o fue cambiado.');
$errorActions = [
    ['text' => __('page404', 'Ir a Inicio'), 'url' => isset($url) && is_string($url) ? $url : base_url()],
    ['text' => __('page404', 'Reportar problema'), 'url' => \PiecesPHP\UserSystem\Controllers\UserProblemsController::routeName('other-problems-form')],
];
require __DIR__ . '/error-page.php';
