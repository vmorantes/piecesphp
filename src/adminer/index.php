<?php
$isLocal = false;
if (isset($_SERVER['HTTP_HOST'])) {
    $host = $_SERVER['HTTP_HOST'];
    // Comprueba si el host es "localhost" o termina con ".localhost"
    $isLocal = $host === 'localhost' || mb_substr($host, -10) === '.localhost';
}
define('_DEV_MODE_', $isLocal);

$httpUser = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null;
$httpPassword = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null;
$user = '$2y$05$xWPh.ux.6Bg7h0Yg0boaYO6Zsy/TdoraJyjKA4Vl71vFGpJHLJnQW';
$password = '$2y$05$24rRWb5rGvHtmDbQLIj5BePEE0rpIL65CnQUGniAIzTCzbE2kkMX2';

$valid = $httpUser !== null && $httpPassword !== null;
$valid = $valid && password_verify($httpPassword, $password) && password_verify($httpUser, $user);
if ($valid) {
    if (_DEV_MODE_) {
        ini_set('display_errors', 1);
        function permanentLogin()
        {
            // key used for permanent login
            return '0839b0a8df9ea45fe280c47428fca941';
        }
    }
    require 'adminer.php';
} else {
    header('WWW-Authenticate: Basic realm="Adminer"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<h1>Acceso denegado</h1>';
    exit;
}
