<?php
$langGroup = MAIL_TEMPLATES_LANG_GROUP;
?>
<h1><?= __($langGroup, 'Mensaje'); ?></h1>

<p><strong><?= __($langGroup, 'Enviado desde'); ?>: <?= $originURL; ?></strong></p>
<p><strong><?= __($langGroup, 'Asunto'); ?>: <?= $subject; ?></strong></p>
<p><strong><?= __($langGroup, 'E-mail'); ?>: <?= $mail; ?></strong></p>
<p><strong><?= __($langGroup, 'Nombre'); ?>: <?= $name; ?></strong></p>
<p><strong><?= __($langGroup, 'Mensaje'); ?>: <?= $message; ?></strong></p>
<?php if(isset($extra) && is_array($extra) && count($extra) > 0):?>
<h2><?= __($langGroup, 'Extra'); ?>:</h2>
<?php foreach($extra as $content):?>
<p><strong><?= $content['display']; ?>: <?= $content['text']; ?></strong></p>
<?php endforeach;?>
<?php endif;?>
