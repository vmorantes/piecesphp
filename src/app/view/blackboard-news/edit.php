<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Controller\BlackboardNewsController;
$langGroup = BlackboardNewsController::LANG_GROUP;
?>

<h3 class="ui dividing header">
    <?= __($langGroup, 'Editar noticia'); ?>
</h3>
<form action="<?= get_route('blackboard-news-edit'); ?>" method="POST" class="ui form" blackboard-news-create>
    <input type="hidden" name="id" value="<?=$new->id;?>">
    <input type="hidden" name="author" value="<?=$new->author->id;?>">
    <div class="field required">
        <label><?= __($langGroup, 'Tipo de perfil para el que será visible'); ?></label>
        <select required name="type" class="ui dropdown">
            <option value=""><?= __($langGroup, 'Elija una opción'); ?></option>
            <?php foreach($types as $name => $value): ?>
            <option <?= $value == $new->type ? 'selected' : ''; ?> value="<?= $value; ?>"><?= $name; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field required">
        <label><?= __($langGroup, 'Título'); ?></label>
        <input type="text" name="title" required value="<?=htmlentities($new->title);?>">
    </div>
    <div class="field required">
        <label><?= __($langGroup, 'Mensaje'); ?></label>
        <div image-name="image" rich-editor-js editor-target="[name='text']"><?=$new->text;?></div>
        <textarea name="text" required><?=$new->text;?></textarea>
    </div>
    <div class="two fields">
        <div class="field" calendar-group-js="test" start>
            <label><?= __($langGroup, 'Fecha de inicio'); ?></label>
            <input type="text" name="start_date" value="<?= !is_null($new->start_date) ? $new->start_date->format('Y-m-d H:i A') : '';?>">
        </div>
        <div class="field" calendar-group-js="test" end>
            <label><?= __($langGroup, 'Fecha final'); ?></label>
            <input type="text" name="end_date" value="<?= !is_null($new->end_date) ? $new->end_date->format('Y-m-d H:i A') : '';?>">
        </div>
    </div>
    <div class="field">
        <button class="ui button green" type="submit"><?= __($langGroup, 'Guardar'); ?></button>
    </div>
</form>
