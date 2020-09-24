<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Controller\AppConfigController;
$langGroup = AppConfigController::LANG_GROUP_FORMS_2;
$isFirstTitle = true;
$isFirstItem = true;
?>

<div class="container-medium">

    <div class="ui top attached tabular menu">
        <?php foreach($tabsTitles as $name => $text): ?>
        <?php if($isFirstTitle): $isFirstTitle = false;?>
        <a class="item active" data-tab="<?= $name; ?>"><?= $text; ?></a>
        <?php else: ?>
        <a class="item" data-tab="<?= $name; ?>"><?= $text; ?></a>
        <?php endif;?>
        <?php endforeach;?>
    </div>

    <?php foreach($tabsItems as $name => $content): ?>
    <?php if($isFirstItem): $isFirstItem = false;?>
    <div class="ui bottom attached tab segment active" data-tab="<?= $name; ?>">
        <?= $content; ?>
    </div>
    <?php else: ?>
    <div class="ui bottom attached tab segment" data-tab="<?= $name; ?>">
        <?= $content; ?>
    </div>
    <?php endif;?>
    <?php endforeach;?>

</div>

<script>
window.addEventListener('load', function(e) {
    $('.ui.top.menu.main .item').tab({
        context: 'parent',
    })
    $('.ui.top.menu.second .item').tab({
        context: 'parent',
    })
})
</script>

<style>
.ui.form {
    max-width: 450px;
}

.image-preview.favicon {
    max-width: 90px;
}

.image-preview.logo {
    max-width: 200px;
}

.image-preview.background {
    width: 320px;
    max-width: 100%;
}
</style>
