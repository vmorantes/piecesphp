<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>

<main class="os-ticket-vew">

    <section class="main-body-header">
        <div class="head">
            <h2 class="tittle"><?= __($langGroup, 'OsTicket'); ?></h2>
            <span class="sub-tittle"><?= __($langGroup, 'Descripción_OsTicket'); ?></span>
        </div>
        <div class="body-card">
            <form action="<?= $actionURL; ?>" method="POST" class="ui form os-ticket">

                <div class="field">
                    <label><?= __($langGroup, 'URL'); ?></label>
                    <input type="text" name="url" value="<?= $url; ?>" placeholder="<?= __($langGroup, 'https://api.dominio.com/'); ?>">
                </div>

                <div class="field">
                    <label><?= __($langGroup, 'Key'); ?></label>
                    <input autocomplete="off" type="text" name="key" value="<?= $key; ?>" placeholder="ABCD123456EFGH">
                </div>

                <div class="field right">
                    <button type="submit" class="ui button primary"><?= __($langGroup, 'Guardar'); ?></button>
                </div>

            </form>
        </div>
    </section>

</main>
