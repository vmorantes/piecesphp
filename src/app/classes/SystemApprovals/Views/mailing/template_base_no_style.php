<?php
    use App\Controller\PublicAreaController;
    $langGroup = isset($langGroup) && is_string($langGroup) ? $langGroup : MAILING_GENERAL_LANG_GROUP;
    $header_image = isset($header_image) && is_string($header_image) ? $header_image : '';
    $text = isset($text) && is_string($text) ? $text : '';
    $reason = isset($reason) && is_string($reason) ? $reason : '';
    $hasCode = isset($code) && is_string($code);
    $note = isset($note) && is_string($note) ? $note : '';
    $hasURL = isset($url) && is_string($url);
    $text_button = isset($text_button) && is_string($text_button) ? $text_button : __(MAILING_GENERAL_LANG_GROUP, 'Clic aquí');
    $text_footer = isset($text_footer) && is_string($text_footer) ? $text_footer : "<p><span class='owner'>" . get_config('owner') . "</span></p>";
    $unsuscriptionURL = isset($unsuscriptionURL) && is_string($unsuscriptionURL) ? $unsuscriptionURL : PublicAreaController::routeName('unsubscribe', ['identifier' => \PiecesPHP\Core\StringManipulate::urlSafeB64Encode(uniqid())]);
?>

<?php if(mb_strlen($header_image) > 0): ?>
<p style="text-align: center;">
    <img style="max-width: 100%;" src="<?= $header_image; ?>" alt="<?= basename($header_image); ?>">
</p>
<br>
<?php endif; ?>

<?php if(mb_strlen($text) > 0): ?>
<p><?= $text; ?></p>
<?php endif; ?>
<?php if (mb_strlen(trim($reason)) > 0): ?>
<h3><?= __($langGroup, 'Con los siguientes comentarios:'); ?></h3>
<p><?=$reason;?></p>
<?php endif; ?>

<?php if(mb_strlen($note) > 0): ?>
<small>
    <address><?= $note; ?></address>
</small>
<br>
<?php endif; ?>

<?php if($hasCode): ?>
<h2 style="font-size: 32px; letter-spacing: 4px;"><?= $code; ?></h2>
<?php endif; ?>

<?php if ($hasURL): ?>
<a href="<?=$url;?>" target="_blank" style="font-size: 20px; font-weight: bold;">
    <?=$text_button;?>
</a>
<?php endif; ?>

<hr style="margin: 30px 0;">

<?php if(mb_strlen($text_footer) > 0): ?>
<?= $text_footer; ?>
<?php endif; ?>

<p>
    <?=strReplaceTemplate(__(MAILING_GENERAL_LANG_GROUP, 'UNSUSCRIBE_TEXT'), ['{{url}}' => $unsuscriptionURL]);?>
</p>