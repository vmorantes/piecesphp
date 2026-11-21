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
//Vienen de un formulario público sin sesión: se escapan antes de entrar en el HTML del correo.
$name = htmlspecialchars((string) $name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$email = htmlspecialchars((string) $email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$subject = htmlspecialchars((string) $subject, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$message = htmlspecialchars((string) $message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
//Los campos de más del formulario: etiqueta y valor llegan del visitante, sin escapar. Van en su propia sección
//para que no se confundan con los del sistema.
$extraLines = '';
foreach (isset($extraFields) && is_array($extraFields) ? $extraFields : [] as $extraField) {
    $extraLabel = htmlspecialchars((string) ($extraField['label'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $extraValue = htmlspecialchars((string) ($extraField['value'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $extraLines .= "\n    <br>\n    <strong>{$extraLabel}:</strong> {$extraValue}";
}
if ($extraLines !== '') {
    $titleExtra = __($langGroup, 'Extra');
    $extraLines = "\n<hr>\n<p>\n    <strong>{$titleExtra}</strong>{$extraLines}\n</p>";
}
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
</p>$extraLines
EOF;

set_config('cache_stamp_render_files', false); //Desactiva añadir cacheStamp en las URL
$baseController->render('mailing/template_base', [
    'text' => $text,
    'langGroup' => $langGroup,
]);
set_config('cache_stamp_render_files', true); //Reactiva añadir cacheStamp en las URL
