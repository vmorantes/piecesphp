<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>

<section class="module-view-container">

    <?php if(isset($breadcrumbs)): ?>
    <div class="breadcrumb">
        <?= $breadcrumbs ?>
    </div>
    <?php endif; ?>


    <div class="limiter-content">

        <div class="section-title">
            <div class="title"><?= __(LOCATIONS_LANG_GROUP, 'Agregar'); ?> <?= $title; ?></div>
            <?php if(isset($description) && is_string($description) && mb_strlen(trim($description)) > 0): ?>
            <div class="description"><?= $description; ?></div>
            <?php endif; ?>
        </div>

        <br>

        <div class="container-standard-form">
            <form pcs-generic-handler-js method='POST' action="<?= $action;?>" class="ui form">

                <div class="field required">
                    <label><?= __(LOCATIONS_LANG_GROUP, 'Nombre'); ?></label>
                    <input type="text" name="name" maxlength="255" required>
                </div>

                <div class="field">
                    <label><?= __(LOCATIONS_LANG_GROUP, 'Código'); ?></label>
                    <input type="text" name="code" maxlength="255">
                </div>

                <div class="field">
                    <label>
                        <?= __(LOCATIONS_LANG_GROUP, 'Región'); ?>
                        <br>
                        <small>(<?= __(LOCATIONS_LANG_GROUP, 'Puede escribir la región y presionar ENTER si no la encuentra en el listado.'); ?>)</small>
                    </label>
                    <select class="ui dropdown regions search" name="region"><?= $regionsOptions; ?></select>
                </div>

                <div class="field required">
                    <label><?= __(LOCATIONS_LANG_GROUP, 'Activo/Inactivo'); ?></label>
                    <select required name="active">
                        <?= $status_options; ?>
                    </select>
                </div>

                <div class="field">
                    <button type="submit" class="ui button green"><?= __(LOCATIONS_LANG_GROUP, 'Guardar'); ?></button>
                </div>

            </form>
        </div>

    </div>

</section>

<script>
window.addEventListener('load', function() {
    configFomanticDropdown('.ui.dropdown.regions', {
        allowAdditions: true,
    })
})
</script>
