<?php
use PiecesPHP\Core\BaseController;
use PiecesPHP\UserSystem\UserSystemFeaturesLang;
$langGroup = UserSystemFeaturesLang::LANG_GROUP;
$baseController = new BaseController();
set_config('cache_stamp_render_files', false); //Desactiva añadir cacheStamp en las URLL
$baseController->render('mailing/template_base', [
    'text' => $text,
    'code' => $code,
    //'text_button' => __($langGroup, 'TEXTO'),
    'note' => $note,
    'langGroup' => $langGroup,
]);
set_config('cache_stamp_render_files', true); //Reactiva añadir cacheStamp en las URLL
