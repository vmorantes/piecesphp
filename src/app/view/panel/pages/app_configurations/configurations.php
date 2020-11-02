<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Controller\AppConfigController;
$langGroup = AppConfigController::LANG_GROUP;
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

    //Inicializaciones generales
    $('.ui.top.menu .item').tab()
    $('.ui.checkbox').checkbox()
    $('.ui.dropdown.additions')
        .dropdown({
            allowAdditions: true
        })

    //Eventos

    //Formulario ssl
    genericFormHandler(
        'form[ssl-configuration-form]', {
            onSetFormData: (formData, form) => {

                formData.set(
                    'value[auto_tls]',
                    form.find(`[name="value[auto_tls]"]`).parent().checkbox('is checked') ? true :
                    false
                )
                formData.set(
                    'value[auth]',
                    form.find(`[name="value[auth]"]`).parent().checkbox('is checked') ? true : false
                )

                return formData
            },
        }
    )

})
</script>
<style>
.ui.form {
    max-width: 800px;
}
</style>
