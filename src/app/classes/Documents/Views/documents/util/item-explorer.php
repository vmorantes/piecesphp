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

<a class="card" target="_blank" href="<?= escape_html($mapper->currentLangData('document')); ?>">
    <?php if(mb_strlen($mapper->currentLangData('documentImage')) > 0): ?>
    <div class="image">
        <img src="<?= escape_html($mapper->currentLangData('documentImage')); ?>">
    </div>
    <?php else: ?>
    <div class="image">
        <img src="statics/images/document-placeholder.png">
    </div>
    <?php endif; ?>
    <div class="content">
        <div class="header"><?= escape_html($mapper->currentLangData('documentName')); ?></div>
        <div class="meta">
            <div>
                <?= escape_html(basename((string) $mapper->currentLangData('document'))); ?>
            </div>
            <div>
                <?= $mapper->createdAtFormat(); ?>
            </div>
        </div>
        <div class="description">
            <?= escape_html($mapper->currentLangData('description')); ?>
        </div>
    </div>
    <div class="extra content">
        <span>
            <i class="file icon"></i>
            <?= escape_html($mapper->documentType->currentLangData('documentTypeName')); ?>
        </span>
    </div>
</a>
