<?php
$errorPageTitle = __('page503', '503 - En mantenimiento');
$errorCode = '';
$errorIcon = 'wrench';
$errorHeading = __('page503', 'Mantenimiento');
$errorText = __('page503', 'en curso, no es posible usar la plataforma en un tiempo.');
$errorFootnote = date('d/m/y / h:i A');
$errorActions = [];
require __DIR__ . '/error-page.php';
