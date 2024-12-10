<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use Publications\Mappers\PublicationCategoryMapper;
use PiecesPHP\Core\Config;

/**
 * @var PublicationCategoryMapper $element
 */

/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
?>
<section class="module-view-container">

    <div class="breadcrumb">
        <?= $breadcrumbs ?>
    </div>

    <div class="limiter-content">

        <div class="section-title">
            <div class="title"><?= $title ?></div>
            <?php if(isset($description) && is_string($description) && mb_strlen(trim($description)) > 0): ?>
            <div class="description"><?= $description; ?></div>
            <?php endif; ?>
        </div>

        <br>

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

            <form method='POST' action="<?= $action; ?>" class="ui form publications-categories <?= $detailMode ? 'detail-mode' : ''; ?>">

                <input type="hidden" name="id" value="<?= $element->id; ?>">
                <input type="hidden" name="lang" value="<?= $lang; ?>">

                <div class="field required">
                    <label><?= __($langGroup, 'Nombre'); ?></label>
                    <input required type="text" name="name" maxlength="300" value="<?= $element->getLangData($lang, 'name', false, ''); ?>">
                </div>

                <br>

                <div class="field">
                    <div class="ui buttons">
                        <button type="submit" class="ui button brand-color"><?= __($langGroup, 'Guardar'); ?></button>
                        <?php if($allowDelete): ?>
                        <button type="submit" class="ui button brand-color alt2" delete-publication-category-button data-route="<?= $deleteRoute; ?>"><?= __($langGroup, 'Eliminar'); ?></button>
                        <?php endif; ?>
                    </div>
                </div>

            </form>

        </div>

    </div>

</section>
