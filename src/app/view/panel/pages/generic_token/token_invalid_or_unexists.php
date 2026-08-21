<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Controller\GenericTokenController;
$langGroup = GenericTokenController::LANG_GROUP;
?>

<div class="ui fixed inverted menu">
    <div class="ui container" id="conaner">
        <a href='<?= base_url(); ?>' class="header item">
            <?= get_title(); ?>
        </a>

    </div>
</div>

<br>
<br>
<br>
<br>

<div class="ui raised very padded text container segment">

    <h1 class="ui header"><?= __($langGroup, 'Información.'); ?></h1>

    <p><?= __($langGroup, 'El recurso al que intenta acceder ha expirado o ya ha sido utilizado.'); ?></p>

</div>
