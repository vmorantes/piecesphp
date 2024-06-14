<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use News\Mappers\NewsMapper;

/**
 * @var NewsMapper $element
 */

/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
?>
<section class="module-view-container limit-size">

    <div class="header-options">

        <div class="main-options">

            <a href="<?= $backLink; ?>" class="ui icon button brand-color alt2" title="<?= __($langGroup, 'Regresar'); ?>">
                <i class="icon left arrow"></i>
            </a>

        </div>

        <div class="columns">

            <div class="column">

                <div class="section-title">
                    <div class="title"><?= $title; ?></div>
                    <div class="subtitle"><?= __($langGroup, 'Editar'); ?></div>
                </div>

            </div>

        </div>

    </div>

    <div class="container-standard-form">

        <?php if($manyLangs): ?>
        <div class="ui form">
            <div class="field required">
                <label><?= __($langGroup, 'Idiomas'); ?></label>
                <select required class="ui dropdown search langs">
                    <?= $allowedLangs; ?>
                </select>
            </div>
        </div>
        <?php endif; ?>

        <form method='POST' action="<?= $action; ?>" class="ui form news">

            <input type="hidden" name="id" value="<?= $element->id; ?>">
            <input type="hidden" name="lang" value="<?= $lang; ?>">

            <div class="two fields">
                <div class="field required">
                    <label><?= __($langGroup, 'Tipos de perfil para los que será visible'); ?></label>
                    <select class="ui dropdown multiple" multiple name="profilesTarget[]" required>
                        <?= $allUsersTypes; ?>
                    </select>
                </div>
                <div class="field required">
                    <label><?= __($langGroup, 'Categorías'); ?></label>
                    <select class="ui dropdown" name="category" required>
                        <?= $allCategories; ?>
                    </select>
                </div>
            </div>

            <div class="two fields">

                <div class="field" calendar-group-js='periodo' start>
                    <label><?= __($langGroup, 'Fecha de inicio'); ?></label>
                    <input type="text" name="startDate" autocomplete="off" value="<?= $element->startDate !== null ? $element->startDate->format('Y-m-d h:i:s A') : ''; ?>">
                </div>

                <div class="field" calendar-group-js='periodo' end>
                    <label><?= __($langGroup, 'Fecha de final'); ?></label>
                    <input type="text" name="endDate" autocomplete="off" value="<?= $element->endDate !== null ? $element->endDate->format('Y-m-d h:i:s A') : ''; ?>">
                </div>

            </div>

            <div class="field required">
                <label><?= __($langGroup, 'Título'); ?></label>
                <input required type="text" name="newsTitle" maxlength="300" value="<?= $element->getLangData($lang, 'newsTitle', false, ''); ?>">
            </div>

            <div class="field required">
                <label><?= __($langGroup, 'Contenido'); ?></label>
                <div rich-editor-adapter-component></div>
                <textarea name="content" required><?= $element->getLangData($lang, 'content', false, ''); ?></textarea>
            </div>

            <div class="field">
                <div class="ui buttons">
                    <button type="submit" class="ui button brand-color"><?= __($langGroup, 'Guardar'); ?></button>
                    <?php if($allowDelete): ?>
                    <button type="submit" class="ui button brand-color alt2" delete-news-button data-route="<?= $deleteRoute; ?>"><?= __($langGroup, 'Eliminar'); ?></button>
                    <?php endif; ?>
                </div>
            </div>

        </form>
    </div>

</section>
