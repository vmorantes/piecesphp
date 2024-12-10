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
    <div class="corner-icon" data-tooltip="<?= $mapper->currentLangData('name'); ?>">
        <div class="icon">
            <i class="newspaper outline icon"></i>
        </div>
    </div>
    <div class="meta-title"><?= __($langGroup, 'Categoría'); ?></div>
    <div class="title"><?= $mapper->currentLangData('name'); ?></div>
    <div class="content">
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
        </div>
    </div>
    <div class="footer">
        <div class="controls">
            <?php if($hasEdit): ?>
            <a class="control" href="<?= $editLink . '?mode=detail'; ?>">
                <div class="icon">
                    <i class="plus icon"></i>
                </div>
                <div class="text">
                    <?= __($langGroup, 'Detalle'); ?>
                </div>
            </a>
            <a class="control icon" data-tooltip="<?= __($langGroup, 'Editar'); ?>" href="<?= $editLink; ?>">
                <div class="icon">
                    <i class="edit outline icon"></i>
                </div>
            </a>
            <?php endif;?>
            <?php if($hasDelete): ?>
            <a class="control icon" data-tooltip="<?= __($langGroup, 'Eliminar'); ?>" delete-poll-stc-button data-route="<?= $deleteRoute; ?>">
                <div class="icon">
                    <i class="trash alternate outline icon"></i>
                </div>
            </a>
            <?php endif;?>
        </div>
    </div>
</div>
