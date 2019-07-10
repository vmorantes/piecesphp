<h1>Mensaje</h1>

<p><strong>Asunto: <?= $subject; ?></strong></p>
<p><strong>E-mail: <?= $mail; ?></strong></p>
<p><strong>Nombre: <?= $name; ?></strong></p>
<p><strong>Mensaje: <?= $message; ?></strong></p>
<?php if(isset($extra) && is_array($extra) && count($extra) > 0):?>
<h2>Extra:</h2>
<?php foreach($extra as $content):?>
<p><strong><?= $content['display']; ?>: <?= $content['text']; ?></strong></p>
<?php endforeach;?>
<?php endif;?>