<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>

<div class="ui header"><?=  __($langGroup, 'OsTicket'); ?></div>

<div class="container-os-ticket">

    <form action="<?= $actionURL; ?>" method="POST" class="ui form os-ticket">

        <div class="field">
            <label><?= __($langGroup, 'URL'); ?></label>
            <input type="text" name="url" value="<?= $url; ?>" placeholder="<?= __($langGroup, 'https://api.dominio.com/'); ?>">
        </div>

        <div class="field">
            <label><?= __($langGroup, 'Key'); ?></label>
            <input autocomplete="off" type="text" name="key" value="<?= $key; ?>" placeholder="ABCD123456EFGH">
        </div>

        <div class="field">
            <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
        </div>

    </form>

</div>
