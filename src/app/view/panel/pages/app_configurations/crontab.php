<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>

<div class="ui header"><?=  __($langGroup, 'Crontab'); ?></div>

<div class="container-crontab">

    <form action="<?= $actionURL; ?>" method="POST" class="ui form crontab">

        <div class="ui header"><?= __($langGroup, 'Agregar nuevo cronjob'); ?></div>

        <div class="field">
            <label><?= __($langGroup, 'Tarea'); ?></label>
            <input type="text" name="crontask" value="php <?= app_basepath('index.php'); ?> route=nombre-de-la-ruta">
        </div>

        <div class="field">
            <label><?= __($langGroup, 'Tiempo para ejecutarse'); ?></label>
            <div crontab="1" class="crontab-selector"></div>
            <input crontab-input="1" type="text" name="crontab">
        </div>

        <div class="field">
            <button type="submit" class="ui button green"><?= __($langGroup, 'Agregar'); ?></button>
        </div>

    </form>

</div>
