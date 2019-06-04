<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>

<h3 class="ui dividing header">
    Crear noticia
</h3>
<form action="<?= get_route('blackboard-news-create'); ?>" method="POST" class="ui form" blackboard-news-create>
    <input type="hidden" name="author" value="<?=$this->user->id;?>">
    <div class="field required">
        <label>Tipo de perfil para el que será visible</label>
        <select required name="type" class="ui dropdown">
            <option value="">Elija una opción</option>
            <?php foreach($types as $name => $value): ?>
            <option value="<?= $value; ?>"><?= $name; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field required">
        <label>Título</label>
        <input type="text" name="title" required>
    </div>
    <div class="field required">
        <label>Mensaje</label>
        <div image-process="<?= get_route('blackboard-image-handler')?>" image-name="image" rich-editor-js editor-target="[name='text']"></div>
        <textarea name="text" required></textarea>
    </div>
    <div class="field">
        <label>Fecha de inicio</label>
        <div calendar-group-js="test" start="">
            <input type="text" name="start_date">
        </div>
    </div>
    <div class="field">
        <label>Fecha final</label>
        <div calendar-group-js="test" end="">
            <input type="text" name="end_date">
        </div>
    </div>
    <div class="field">
        <button class="ui button green" type="submit">Guardar</button>
    </div>
</form>
