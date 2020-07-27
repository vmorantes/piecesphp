<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>

<div style="max-width: 800px; margin: 0 auto;">

    <img src="<?= $logo ?>" style="display: inline-block; margin: 30px auto; max-width: 100%;">

    <h1><?= $title; ?></h1>

    <p>
        <strong><?= __(LANG_GROUP, 'Nombre'); ?>:</strong> <?= $name; ?>
        <br>

        <strong><?= __(LANG_GROUP, 'E-mail'); ?>:</strong> <?= $email; ?>
        <br>

        <strong><?= __(LANG_GROUP, 'Asunto'); ?>:</strong> <?= $subject; ?>
        <br>

        <strong><?= __(LANG_GROUP, 'Acepta que le envíen actualizaciones'); ?>:</strong> <?= $updates ? __(LANG_GROUP, 'Sí') : __(LANG_GROUP, 'No'); ?>
        <br>

		<strong><?= __(LANG_GROUP, 'Mensaje'); ?>:</strong> 
		<br>
		<?= $message; ?>
    </p>

</div>
