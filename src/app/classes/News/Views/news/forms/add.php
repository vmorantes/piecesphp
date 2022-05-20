<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
?>
<section class="module-view-container">

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
                    <div class="subtitle"><?= __($langGroup, 'Agregar'); ?></div>
                </div>

            </div>

        </div>

    </div>

    <div class="container-standard-form">

        <form method='POST' action="<?= $action; ?>" class="ui form news">

            <input type="hidden" name="lang" value="<?= \PiecesPHP\Core\Config::get_lang(); ?>">

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
                    <input type="text" name="startDate" autocomplete="off">
                </div>

                <div class="field" calendar-group-js='periodo' end>
                    <label><?= __($langGroup, 'Fecha de final'); ?></label>
                    <input type="text" name="endDate" autocomplete="off">
                </div>

            </div>

            <div class="field required">
                <label><?= __($langGroup, 'Título'); ?></label>
                <input required type="text" name="newsTitle" maxlength="300">
            </div>

            <div class="field required">
                <label><?= __($langGroup, 'Contenido'); ?></label>
                <div rich-editor-adapter-component></div>
                <textarea name="content" required></textarea>
            </div>

            <div class="field">
                <button type="submit" class="ui button brand-color"><?= __($langGroup, 'Guardar'); ?></button>
            </div>

        </form>
    </div>

</section>
