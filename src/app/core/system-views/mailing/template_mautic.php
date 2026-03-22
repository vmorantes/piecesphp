<?php
use App\Controller\PublicAreaController;
use PiecesPHP\Core\Config;
$langGroup = isset($langGroup) && is_string($langGroup) ? $langGroup : MAILING_GENERAL_LANG_GROUP;
$header_image = isset($header_image) && is_string($header_image) ? $header_image : (
    is_local() ?
    "https://placehold.co/500x172.png?text=" . urlencode(get_config('title_app')) :
    base_url(get_config('mailing_logo'))
);
$unsuscriptionURL = isset($unsuscriptionURL) && is_string($unsuscriptionURL) ? $unsuscriptionURL : PublicAreaController::routeName('unsubscribe', ['identifier' => \PiecesPHP\Core\StringManipulate::urlSafeB64Encode(uniqid())]);
Config::set_lang('es');
$langGroup = 'TestPCSPHP-Lang';
?>

<?php if(mb_strlen($header_image) > 0): ?>
<p style="text-align: center;">
    <img style="max-width: 100%;" src="<?= $header_image; ?>" alt="<?= basename($header_image); ?>">
</p>
<br>
<?php endif; ?>

<p>
    <?= __($langGroup, 'Hola {name}'); ?>,
    <br>
    <?= __($langGroup, 'Tenemos información importante para tí.'); ?>
    <br>
    <strong><?= __($langGroup, '¡Conócela ahora mismo!'); ?></strong>
</p>

<ul>
    <li>
        Datos inventados 1
    </li>
    <li>
        Datos inventados 2
    </li>
    <li>
        Datos inventados 3
    </li>
</ul>

<a href="<?= baseurl(); ?>" target="_blank" style="font-size: 20px; font-weight: bold;">
    <?= __($langGroup, 'Ver más'); ?>
</a>

<hr style="margin: 30px 0;">

<p>
    <?= __($langGroup, '¿Necesitas ayuda? Contacta con nuestro equipo de soporte técnico [EMAIL_ADDRESS]'); ?>
</p>
<p>
    <?=strReplaceTemplate(__(MAILING_GENERAL_LANG_GROUP, 'UNSUSCRIBE_TEXT'), ['{{url}}' => $unsuscriptionURL]);?>
</p>