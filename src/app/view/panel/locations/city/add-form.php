<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>

<div style="max-width:850px;">

    <h3>Agregar <?= $title; ?></h3>

    <div class="ui buttons">
        <a href="<?=$back_link;?>" class="ui button blue"><i class="icon left arrow"></i></a>
    </div>

    <br><br>

    <form pcs-generic-handler-js method='POST' action="<?= $action;?>" class="ui form">

        <div class="field required">
            <label>País</label>
            <select required name="country" locations-component-auto-filled-country></select>
        </div>

        <div class="field required">
            <label>Departamento</label>
            <select required name="state" locations-component-auto-filled-state></select>
        </div>

        <div class="field required">
            <label>Nombre</label>
            <input type="text" name="name" maxlength="255">
        </div>

        <div class="field required">
            <label>Activo/Inactivo</label>
            <select required name="active">
                <?= $status_options; ?>
            </select>
        </div>

        <div class="field">
            <button type="submit" class="ui button green">Guardar</button>
        </div>

    </form>
</div>
