<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>

<main class="os-ticket-view">

    <section class="main-body-header">
        <div class="head">
            <h2 class="tittle"><?= __($langGroup, 'OsTicket'); ?></h2>
            <span class="sub-tittle"><?= __($langGroup, 'Integraciones'); ?></span>
        </div>
        <div class="body-card">
            <form action="<?= $actionURL; ?>" method="POST" class="ui form os-ticket">

                <div class="field">
                    <label><?= __($langGroup, 'URL'); ?></label>
                    <input type="text" name="url" value="<?= $url; ?>" placeholder="<?= __($langGroup, 'https://api.dominio.com/'); ?>">
                </div>

                <div class="field">
                    <label><?= __($langGroup, 'Key'); ?></label>
                    <?php //La clave no viaja al HTML: el campo va vacío y, vacío, al guardar la conserva. ?>
                    <input autocomplete="off" type="text" name="key" value="" placeholder="<?= mb_strlen((string) $key) > 0 ? __($langGroup, 'Hay una clave guardada: déjelo vacío para conservarla') : 'ABCD123456EFGH'; ?>">
                </div>

                <div class="field right">
                    <button type="submit" class="ui button primary"><?= __($langGroup, 'Guardar'); ?></button>
                </div>

            </form>
        </div>
    </section>

</main>
