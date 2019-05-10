<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var \App\Locations\Mappers\PointMapper $element
 */
$element;
?>

<div style="max-width:850px;">

    <h3>Editar <?= $title; ?></h3>

    <div class="ui buttons">
        <a href="<?=$back_link;?>" class="ui button blue"><i class="icon left arrow"></i></a>
    </div>

    <br><br>

    <form pcs-generic-handler-js method='POST' action="<?=$action;?>" class="ui form">

        <input type="hidden" name="id" value="<?=$element->id;?>">

        <div class="field required">
            <label>País</label>
            <select required name="country"
                locations-component-auto-filled-country="<?=$element->city->state->country->id;?>"></select>
        </div>

        <div class="field required">
            <label>Departamento</label>
            <select required name="state"
                locations-component-auto-filled-state="<?=$element->city->state->id;?>"></select>
        </div>

        <div class="field required">
            <label>Ciudad</label>
            <select required name="city" locations-component-auto-filled-city="<?=$element->city->id;?>"></select>
        </div>

        <div class="field required">
            <label>Dirección <small>(localidad, caserío, barrio, etc...)</small></label>
            <input type="text" name="address" required value="<?=$element->address;?>">
        </div>

        <div class="field required">
            <label class=''>Ubicación en el mapa de la localidad</label>
            <input longitude-mapbox-handler name='longitude' type='hidden' required value="<?= $element->longitude;?>">
            <input latitude-mapbox-handler name='latitude' type='hidden' required value="<?= $element->latitude;?>">
        </div>

        <div class="field">
            <button set-satelital-view class="ui mini button red inverted">Vista satelital</button>
            <button set-draw-view class="ui mini button red inverted">Vista de dibujo</button>
            <button set-center-view class="ui mini button red inverted">Centrar</button>
            <small>(para mayor precisión, puede mover el cursor de posición)</small>
        </div>

        <div class="field">
            <div id="map">
            </div>
        </div>

        <div class="field required">
            <label>Nombre</label>
            <input type="text" name="name" maxlength="255" value="<?=$element->name;?>">
        </div>

        <div class="field required">
            <label>Activo/Inactivo</label>
            <select required name="active">
                <?=$status_options;?>
            </select>
        </div>

        <div class="field">
            <button type="submit" class="ui button green">Guardar</button>
        </div>

    </form>
</div>
