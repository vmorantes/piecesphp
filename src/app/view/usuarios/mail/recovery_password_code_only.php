<?php
use PiecesPHP\Core\BaseController;
$langGroup = MAIL_TEMPLATES_LANG_GROUP;
$baseController = new BaseController();
set_config('cache_stamp_render_files', false); //Desactiva añadir cacheStamp en las URLL
$baseController->render('mailing/template_base', [
    'code' => $code,
    'text' => $text,
    'text_button' => $text_button,
    'note' => $note,
    'langGroup' => $langGroup,
]);
set_config('cache_stamp_render_files', true); //Reactiva añadir cacheStamp en las URLL
