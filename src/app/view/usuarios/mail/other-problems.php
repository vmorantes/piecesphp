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
        if (isset($content['display']) && isset($content['text'])) {
            $extraDisplayTitle = $content['display'];
            $extraText = $content['text'];
            $extraData[] = "<p><strong>{$extraDisplayTitle}: {$extraText}</strong></p>";
            $extraDataAdded = true;
        }
    }
}
$extraData = $extraDataAdded ? implode('\n', $extraData) : '';

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
