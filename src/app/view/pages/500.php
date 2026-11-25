<?php
/**
 * La página del error 500 fuera de local: solo el mensaje genérico y la referencia (P56). La pinta CustomSlimErrorHandler.
 *
 * @var string $errorMessage El mensaje genérico, con la referencia.
 * @var string $errorReference
 */
use PiecesPHP\UserSystem\Controllers\UserProblemsController;
use PiecesPHP\UserSystem\UserDataPackage;

$errorPageTitle = __('page500', '500 - Error interno');
$errorCode = '';
$errorIcon = 'exclamation triangle';
$errorHeading = __('page500', 'Error interno');
$errorText = $errorMessage;
$errorFootnote = $errorReference;
$errorActions = [
    ['text' => __('page500', 'Volver al inicio'), 'url' => base_url()],
    ['text' => __('page500', 'Reportar problema'), 'url' => UserProblemsController::routeName('other-problems-form')],
];
//Un 500 puede venir de la base caída: solo el enlace de arriba carga a la persona. Cargada (la clave de
//getLoggedFrameworkUser()), sus preferencias, que no tumban la página; si no, nadie vuelve a preguntar.
$errorWithoutSession = !(get_config('pcsphp_current_user_stored') instanceof UserDataPackage);
require __DIR__ . '/error-page.php';
