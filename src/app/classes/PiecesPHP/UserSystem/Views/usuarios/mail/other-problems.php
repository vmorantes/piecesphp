<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\Core\BaseController;
$langGroup = LANG_GROUP;
$baseController = new BaseController();

$extraData = [];
$extraDataAdded = false;
if (isset($extra) && is_array($extra) && !empty($extra)) {
    $extraData[] = "<h2>" . __($langGroup, 'Extra') . "</h2>";
    foreach ($extra as $content) {
        if (is_array($content) && isset($content['display'], $content['text']) && is_string($content['display']) && is_string($content['text'])) {
            $extraDisplayTitle = htmlspecialchars($content['display'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $extraText = htmlspecialchars($content['text'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $extraData[] = "<p><strong>{$extraDisplayTitle}: {$extraText}</strong></p>";
            $extraDataAdded = true;
        }
    }
}
$extraData = $extraDataAdded ? implode("\n", $extraData) : '';

//Vienen de un formulario público sin sesión: se escapan antes de entrar en el HTML del correo.
$subject = htmlspecialchars((string) $subject, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$mail = htmlspecialchars((string) $mail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$name = htmlspecialchars((string) $name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$message = htmlspecialchars((string) $message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$title = __($langGroup, 'Mensaje');
$labelA = __($langGroup, 'Enviado desde');
$labelB = __($langGroup, 'Asunto');
$labelC = __($langGroup, 'E-mail');
$labelD = __($langGroup, 'Nombre');
$labelE = __($langGroup, 'Mensaje');
$text = <<<EOF
<h1>$title</h1>
<p><strong>$labelA: $originURL</strong></p>
<p><strong>$labelB: $subject</strong></p>
<p><strong>$labelC: $mail</strong></p>
<p><strong>$labelD: $name</strong></p>
<p><strong>$labelE: $message</strong></p>
$extraData
EOF;

set_config('cache_stamp_render_files', false); //Desactiva añadir cacheStamp en las URL
$baseController->render('mailing/template_base', [
    'text' => $text,
    'langGroup' => $langGroup,
]);
set_config('cache_stamp_render_files', true); //Reactiva añadir cacheStamp en las URL
