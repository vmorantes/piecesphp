<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<?php if(mb_strlen($actionSSLURL) > 0): ?>

<form ssl-configuration-form action="<?= $actionSSLURL; ?>" method="POST" class="ui form">

    <div class="field required">
        <label><?= __($langGroup, 'Dominio')?></label>
        <input required type="text" name="domain" value="<?= trim(str_replace(array('http://','https://'), '', baseurl()), '/') ?>" placeholder="example.com...">
    </div>

    <div class="field required">
        <label><?= __($langGroup, 'Carpeta pública del dominio')?></label>
        <input required type="text" name="folder" value="<?= basepath(); ?>" placeholder="/home/user/example.com/public_html">
    </div>

    <div class="field">
        <label>Email</label>
        <input required type="email" name="email" placeholder="info@example.com...">
    </div>

    <div class="field">
        <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
    </div>

</form>

<?php endif; ?>
