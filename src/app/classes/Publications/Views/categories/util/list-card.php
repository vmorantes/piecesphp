<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use Publications\Mappers\PublicationCategoryMapper;
use Publications\Mappers\PublicationMapper;

/**
 * @var PublicationCategoryMapper $mapper
 */

/**
 * @var string $langGroup
 * @var string $editLink
 */

?>
<div class="list-card">
    <div class="meta-title"><?= __($langGroup, 'Categoría'); ?></div>
    <div class="title"><?= $mapper->currentLangData('name'); ?></div>
    <div class="footer">
        <div class="two-columns rows-mode">
            <div class="column">
                <div class="info-text">
                    <div class="icon">
                        <i class="newspaper outline icon"></i>
                    </div>
                    <div class="text">
                        <?php $publicationsQty = PublicationMapper::countByCategory($mapper->id); ?>
                        <?php $isPlural = $publicationsQty === 0 || $publicationsQty > 1; ?>
                        <?= $publicationsQty; ?> <?=  $isPlural ? __($langGroup, 'publicaciones') : __($langGroup, 'publicación'); ?>
                    </div>
                </div>
            </div>
            <div class="column">
                <?php if($hasEdit): ?>
                <a class="ui brand-color button icon" href="<?= $editLink; ?>">
                    <i class="icon plus"></i> &nbsp; <?= __($langGroup, 'Editar'); ?>
                </a>
                <?php endif;?>
                <?php if($hasDelete): ?>
                <a class="ui brand-color alt2 button icon" delete-publication-category-button data-route="<?= $deleteRoute; ?>">
                    <i class="icon trash"></i> &nbsp; <?= __($langGroup, 'Eliminar'); ?>
                </a>
                <?php endif;?>
            </div>
        </div>
    </div>
</div>
