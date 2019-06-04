<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>

<h3 class="ui dividing header">
    Crear noticia
</h3>
<form action="<?= get_route('blackboard-news-edit'); ?>" method="POST" class="ui form" blackboard-news-create>
    <input type="hidden" name="id" value="<?=$new->id;?>">
    <input type="hidden" name="author" value="<?=$new->author->id;?>">
    <div class="field required">
        <label>Tipo de perfil para el que será visible</label>
        <select required name="type" class="ui dropdown">
            <option value="">Elija una opción</option>
            <?php foreach($types as $name => $value): ?>
            <option <?= $value == $new->type ? 'selected' : ''; ?> value="<?= $value; ?>"><?= $name; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field required">
        <label>Título</label>
        <input type="text" name="title" required value="<?=$new->title;?>">
    </div>
    <div class="field required">
        <label>Mensaje</label>
        <div image-process="<?= get_route('blackboard-image-handler')?>" image-name="image" rich-editor-js editor-target="[name='text']"><?=$new->text;?></div>
        <textarea name="text" required><?=$new->text;?></textarea>
    </div>
    <div class="field">
        <label>Fecha de inicio</label>
        <div calendar-group-js="test" start>
            <input type="text" name="start_date" value="<?= !is_null($new->start_date) ? $new->start_date->format('d-m-Y h:i:s A') : '';?>">
        </div>
    </div>
    <div class="field">
        <label>Fecha final</label>
        <div calendar-group-js="test" end>
            <input type="text" name="end_date" value="<?= !is_null($new->end_date) ? $new->end_date->format('d-m-Y h:i:s A') : '';?>">
        </div>
    </div>
    <div class="field">
        <button class="ui button green" type="submit">Guardar</button>
    </div>
</form>
