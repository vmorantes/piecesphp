<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use App\Presentations\Mappers\PresentationCategoryMapper;
use PiecesPHP\Core\Config;

/**
 * @var PresentationCategoryMapper $element
 */

/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
?>

<div style="max-width:500px;">

    <h3><?= __($langGroup, 'Editar'); ?>
        <?= $title; ?>
    </h3>

    <div class="ui buttons">

        <a href="<?= $backLink; ?>" class="ui labeled icon button">
            <i class="icon left arrow"></i>
            <?= __($langGroup, 'Regresar'); ?>
        </a>

    </div>

    <br>
    <br>

    <div class="ui form">
        <div class="field required">
            <label><?= __($langGroup, 'Idiomas'); ?></label>
            <select required class="ui dropdown search langs">
                <?= $allowedLangs; ?>
            </select>
        </div>
    </div>

    <br><br>

    <form method='POST' action="<?= $action; ?>" class="ui form app-presentations-categories">

        <input type="hidden" name="id" value="<?= $element->id; ?>">
        <input type="hidden" name="lang" value="<?= $lang; ?>">

        <div class="field required">
            <label><?= __($langGroup, 'Nombre'); ?></label>
            <input required type="text" name="name" maxlength="300" value="<?= $element->getLangData($lang, 'name', false, ''); ?>">
        </div>

        <br>

        <div class="field">
            <div class="ui buttons">
                <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
                <?php if($allowDelete): ?>
                <button type="submit" class="ui button red" delete-presentation-category-button data-route="<?= $deleteRoute; ?>"><?= __($langGroup, 'Eliminar'); ?></button>
                <?php endif; ?>
            </div>
        </div>

    </form>

</div>
