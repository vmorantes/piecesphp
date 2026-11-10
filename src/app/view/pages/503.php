<?php
$errorPageTitle = __('page503', '503 - En mantenimiento');
$errorCode = '';
$errorIcon = 'wrench';
$errorHeading = __('page503', 'Mantenimiento');
$errorText = __('page503', 'en curso, no es posible usar la plataforma en un tiempo.');
$errorFootnote = date('d/m/y / h:i A');
$errorActions = [];
//Sin sesión viva, el acceso: root y los tipos permitidos entran por ahí (P299). `current_user` solo lo fija src/index.php
//con sesión viva; sin ella, get_config() da false. Con sesión, nada: el que la ve ya ha entrado y está detenido.
if (!(get_config('current_user') instanceof \stdClass)) {
    $errorActions[] = ['text' => __('page503', 'Ir al acceso'), 'url' => \PiecesPHP\UserSystem\Controllers\UsersController::routeName('form-login'), 'icon' => 'sign in alternate'];
}
require __DIR__ . '/error-page.php';
