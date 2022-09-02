<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use Documents\Mappers\DocumentsMapper;
/**
 * @var DocumentsMapper $mapper
 */
/**
 * @var string $langGroup
 */
?>

<a class="card" target="_blank" href="<?= $mapper->currentLangData('document'); ?>">
    <div class="image">
        <img src="<?= $mapper->currentLangData('documentImage'); ?>">
    </div>
    <div class="content">
        <div class="header"><?= $mapper->currentLangData('documentName'); ?></div>
        <div class="meta">
            <span>
                <?= $mapper->createdAtFormat(); ?>
            </span>
        </div>
        <div class="description">
            <?= $mapper->currentLangData('description'); ?>
        </div>
    </div>
    <div class="extra content">
        <span>
            <i class="file icon"></i>
            <?= $mapper->documentType->currentLangData('documentTypeName'); ?>
        </span>
    </div>
</a>
