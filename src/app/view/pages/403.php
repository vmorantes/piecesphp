<?php
$errorPageTitle = __('page403', '403 - Acceso denegado');
$errorCode = '';
$errorIcon = 'lock';
$errorHeading = __('page403', 'No tiene permisos para ver esta sección');
$errorText = __('page403', 'El enlace al que intenta ingresar no está disponible para su acceso.');
$errorFootnote = __('page403', 'Código 403');
$errorActions = [['text' => __('page404', 'Ir a Inicio'), 'url' => isset($url) && is_string($url) ? $url : base_url()]];
if (!isset($showReportButton) || $showReportButton === true) {
    $errorActions[] = ['text' => __('page403', 'Reportar problema'), 'url' => \PiecesPHP\UserSystem\Controllers\UserProblemsController::routeName('other-problems-form')];
}
require __DIR__ . '/error-page.php';
