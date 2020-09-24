<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<?php if(mb_strlen($actionCustomImagesURL) > 0): ?>

<br><br><br><br>

<?php foreach (get_config('backgrounds') as $index => $background): ?>

<form pcs-generic-handler-js action="<?= $actionCustomImagesURL; ?>" method="POST" class="ui form">

    <div class="image-preview background">
        <img src="<?=$background;?>">
    </div>

    <div class="field">
        <label><?= __($langGroup, 'Cambiar'); ?></label>
        <input type="file" name="<?= "background-" . ($index + 1);?>" accept="image/jpeg" required>
    </div>

    <div class="field">
        <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
    </div>

</form>

<br><br><br><br><br>

<?php endforeach;?>

<?php endif; ?>
