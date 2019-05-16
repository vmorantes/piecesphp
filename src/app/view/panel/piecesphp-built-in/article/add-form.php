<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>

<div style="max-width:850px;">

    <h3>Agregar <?=$title;?></h3>

    <div class="ui buttons">
        <a href="<?=$back_link;?>" class="ui button blue"><i class="icon left arrow"></i></a>
    </div>

    <br><br>

    <form pcs-generic-handler-js method='POST' action="<?=$action;?>" class="ui form">

        <div class="field required">
            <label>Título</label>
            <input type="text" name="title" maxlength="255">
        </div>

        <div class="field required">
            <label>Categoría</label>
            <select class='ui dropdown' name="category"><?= $options_categories; ?></select>
        </div>
		
        <div class="field required">
            <label>Descripción</label>
            <div image-process="<?= $quill_proccesor_link; ?>" image-name="image" rich-editor-js
                editor-target="[name='content']"></div>
            <textarea name="content" required></textarea>
        </div>

        <div class="two fields">
			<div class="field" calendar-group-js='periodo' start>
				<label>Iniciar</label>
				<input type="text" name="start_date" autocomplete="off">
			</div>
			<div class="field" calendar-group-js='periodo' end>
				<label>Finalizar</label>
				<input type="text" name="end_date" autocomplete="off">
			</div>
		</div>

        <div class="field">
            <button type="submit" class="ui button green">Guardar</button>
        </div>

    </form>
</div>

<script>
window.onload = () => {
}
</script>
