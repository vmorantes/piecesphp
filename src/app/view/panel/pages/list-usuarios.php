<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>"); ?>
<h3><?= __('general', 'users'); ?></h3>
<div>
	<a href="<?= get_route('form-usuarios'); ?>" class="ui mini green button">Crear</a>
</div>
<br><br>
<?= $tabla; ?>
