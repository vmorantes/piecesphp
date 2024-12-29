<?php
define('_DEV_MODE_', false);

$httpUser = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null;
$httpPassword = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null;
$user = '$2y$10$KulwTp7Shm28baDBTALDtui5vFlZzjilacuYPs/StYLHm6RSeBeQ2';
$password = '$2y$10$UKOuG4XUi10YYqN46u6hueE6XRb72ZtLLRIt7qw2WFO3z3MWFF1Aa';

$valid = $httpUser !== null && $httpPassword !== null;
$valid = $valid && password_verify($httpPassword, $password) && password_verify($httpUser, $user);

if ($valid) {
    require 'production-index.php';    
} else {
    header('WWW-Authenticate: Basic realm="Adminer"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<h1>Acceso denegado</h1>';
    exit;
}
