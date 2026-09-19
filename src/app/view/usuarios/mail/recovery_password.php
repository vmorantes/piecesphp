
<?php
use PiecesPHP\Core\BaseController;
$langGroup = MAIL_TEMPLATES_LANG_GROUP;
$baseController = new BaseController();
set_config('cache_stamp_render_files', false); //Desactiva añadir cacheStamp en las URLL
$baseController->render('mailing/template_base', [
    'text' => __($langGroup, 'Recuperación de contraseña'),
    'url' => $url,
    'text_button' => __($langGroup, 'Click para restablecer su contraseña'),
    'note' => __($langGroup, 'MENSAJE_DE_VALIDEZ'),
    'langGroup' => $langGroup,
]);
set_config('cache_stamp_render_files', true); //Reactiva añadir cacheStamp en las URLL
