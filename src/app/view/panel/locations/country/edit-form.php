<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>

<div style="max-width:850px;">

    <h3><?= __(LOCATIONS_LANG_GROUP, 'Editar'); ?> <?= $title; ?></h3>

    <div class="ui buttons">
        <a href="<?=$back_link;?>" class="ui button blue"><i class="icon left arrow"></i></a>
    </div>

    <br><br>

    <form pcs-generic-handler-js method='POST' action="<?= $action;?>" class="ui form">

        <input type="hidden" name="id" value="<?= $element->id; ?>">

        <div class="field required">
            <label><?= __(LOCATIONS_LANG_GROUP, 'Nombre'); ?></label>
            <input type="text" name="name" maxlength="255" value="<?= htmlentities($element->name); ?>" required>
        </div>

        <div class="field">
            <label><?= __(LOCATIONS_LANG_GROUP, 'Código'); ?></label>
            <input type="text" name="code" maxlength="255" value="<?= !is_null($element->code) ? htmlentities($element->code) : ''; ?>">
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
