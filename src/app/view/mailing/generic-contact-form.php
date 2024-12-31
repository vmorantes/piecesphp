<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\Core\BaseController;
$langGroup = LANG_GROUP;
$baseController = new BaseController();

$titleName = __($langGroup, 'Nombre');
$titleEmail = __($langGroup, 'E-mail');
$titleSubject = __($langGroup, 'Asunto');
$titleUpdates = __($langGroup, 'Acepta que le envíen actualizaciones');
$titleMessage = __($langGroup, 'Mensaje');
$yesOrNo = $updates ? __($langGroup, 'Sí') : __($langGroup, 'No');
$text = <<<EOF
<h1>$title</h1>
<p>
    <strong>$titleName:</strong> $name
    <br>

    <strong>$titleEmail:</strong> $email
    <br>

    <strong>$titleSubject:</strong> $subject
    <br>

    <strong>$titleUpdates:</strong> $yesOrNo
    <br>

    <strong>$titleMessage:</strong>
    <br>
    $message
</p>
EOF;

set_config('cache_stamp_render_files', false); //Desactiva añadir cacheStamp en las URLL
$baseController->render('mailing/template_base', [
    'text' => $text,
    'langGroup' => $langGroup,
]);
set_config('cache_stamp_render_files', true); //Reactiva añadir cacheStamp en las URLL
