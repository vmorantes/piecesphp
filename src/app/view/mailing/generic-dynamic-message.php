<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\Core\BaseController;
$langGroup = LANG_GROUP;
$baseController = new BaseController();

$text = <<<EOF
<h1>$title</h1>
EOF;

$text .= '<p>';
foreach ($message as $title => $value) {
    $text .= "<strong>{$title}:</strong> {$value} </br>";
}
$text .= '</p>';

set_config('cache_stamp_render_files', false); //Desactiva añadir cacheStamp en las URL
$baseController->render('mailing/template_base', [
    'text' => $text,
    'langGroup' => $langGroup,
]);
set_config('cache_stamp_render_files', true); //Reactiva añadir cacheStamp en las URL
