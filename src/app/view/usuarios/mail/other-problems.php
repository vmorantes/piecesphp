<h1><?= __('mailTemplates', 'Mensaje'); ?></h1>

<p><strong><?= __('mailTemplates', 'Asunto'); ?>: <?= $subject; ?></strong></p>
<p><strong><?= __('mailTemplates', 'E-mail'); ?>: <?= $mail; ?></strong></p>
<p><strong><?= __('mailTemplates', 'Nombre'); ?>: <?= $name; ?></strong></p>
<p><strong><?= __('mailTemplates', 'Mensaje'); ?>: <?= $message; ?></strong></p>
<?php if(isset($extra) && is_array($extra) && count($extra) > 0):?>
<h2><?= __('mailTemplates', 'Extra'); ?>:</h2>
<?php foreach($extra as $content):?>
<p><strong><?= $content['display']; ?>: <?= $content['text']; ?></strong></p>
<?php endforeach;?>
<?php endif;?>
