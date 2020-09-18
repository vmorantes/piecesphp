<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>

<div style="max-width:850px;">

    <h3><?= __(LOCATIONS_LANG_GROUP, 'Agregar'); ?> <?= $title; ?></h3>

    <div class="ui buttons">
        <a href="<?=$back_link;?>" class="ui button blue"><i class="icon left arrow"></i></a>
    </div>

    <br><br>

    <form pcs-generic-handler-js method='POST' action="<?= $action;?>" class="ui form">

        <div class="field required">
            <label><?= __(LOCATIONS_LANG_GROUP, 'País'); ?></label>
            <select required name="country" locations-component-auto-filled-country></select>
        </div>

        <div class="field required">
            <label><?= __(LOCATIONS_LANG_GROUP, 'Departamento'); ?></label>
            <select required name="state" locations-component-auto-filled-state></select>
        </div>

        <div class="field required">
            <label><?= __(LOCATIONS_LANG_GROUP, 'Ciudad'); ?></label>
            <select required name="city" locations-component-auto-filled-city></select>
        </div>

        <div class="field required">
            <label><?= __(LOCATIONS_LANG_GROUP, 'Dirección'); ?> <small><?= __(LOCATIONS_LANG_GROUP, '(localidad, caserío, barrio, etc...)'); ?></small></label>
            <input type="text" name="address" required>
        </div>

        <div class="field required">
            <label class=''><?= __(LOCATIONS_LANG_GROUP, 'Ubicación en el mapa de la localidad'); ?></label>
            <input longitude-mapbox-handler name='longitude' type='hidden' required>
            <input latitude-mapbox-handler name='latitude' type='hidden' required>
        </div>

        <div class="field">
            <button set-satelital-view class="ui mini button red inverted"><?= __(LOCATIONS_LANG_GROUP, 'Vista satelital'); ?></button>
            <button set-draw-view class="ui mini button red inverted"><?= __(LOCATIONS_LANG_GROUP, 'Vista de dibujo'); ?></button>
            <button set-center-view class="ui mini button red inverted"><?= __(LOCATIONS_LANG_GROUP, 'Centrar'); ?></button>
            <small><?= __(LOCATIONS_LANG_GROUP, '(para mayor precisión, puede mover el cursor de posición)'); ?></small>
        </div>

        <div class="field">
            <div id="map">
            </div>
        </div>

        <div class="field required">
            <label><?= __(LOCATIONS_LANG_GROUP, 'Nombre'); ?></label>
            <input type="text" name="name" maxlength="255">
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
