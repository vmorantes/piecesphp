<?php
use PiecesPHP\UserSystem\Controllers\UserProblemsController;
use PiecesPHP\UserSystem\Controllers\UsersController;

$errorHasSession = getLoggedFrameworkUser() !== null;
$errorHome = isset($url) && is_string($url) ? $url : base_url();
//El regreso a la página pedida, solo si el acceso lo aceptaría (mismo origen y bajo la ruta base).
$errorLoginURL = UsersController::routeName('form-login');
$errorNext = UsersController::safeNextURL(get_current_url());
if ($errorNext !== null) {
    $errorLoginURL .= (str_contains($errorLoginURL, '?') ? '&' : '?') . 'next=' . rawurlencode($errorNext);
}

$errorPageTitle = __('page404', '404 - Página no encontrada');
$errorCode = '404';
$errorIcon = '';
$errorHeading = __('page404', 'Página no encontrada');
$errorText = $errorHasSession
    ? __('page404', 'El enlace al que intenta ingresar ya no existe o fue cambiado.')
    : __('page404', 'La dirección que buscas no existe o requiere que inicies sesión. Verifica el enlace o ingresa con tu usuario para continuar.');
$errorActions = $errorHasSession ? [
    ['text' => __('page404', 'Volver al inicio'), 'url' => $errorHome],
] : [
    ['text' => __('page404', 'Iniciar sesión'), 'url' => $errorLoginURL, 'icon' => 'sign in alternate'],
    ['text' => __('page404', 'Volver al inicio'), 'url' => $errorHome],
];
$errorActions[] = ['text' => __('page404', 'Reportar problema'), 'url' => UserProblemsController::routeName('other-problems-form')];
require __DIR__ . '/error-page.php';
