<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use Documents\Controllers\DocumentsController;
use Documents\Mappers\DocumentsMapper;
/**
 * @var DocumentsMapper $mapper
 */
/**
 * @var string $langGroup
 */
$fileURL = (string) $mapper->currentLangData('document');
$imageURL = (string) $mapper->currentLangData('documentImage');
$description = trim((string) $mapper->currentLangData('description'));
?>

<div class="card">
    <?php //Con miniatura o sin ella, el mismo alto (documents-style.scss): sin miniatura, el icono de su tipo (§2.5). ?>
    <div class="image document-media">
        <?php if (mb_strlen($imageURL) > 0): ?>
        <img src="<?= escape_html($imageURL); ?>" alt="">
        <?php else: ?>
        <i class="huge grey <?= escape_html(DocumentsController::fileTypeIcon($fileURL)); ?> icon"></i>
        <?php endif; ?>
    </div>
    <div class="content">
        <div class="header"><?= escape_html($mapper->currentLangData('documentName')); ?></div>
        <div class="meta"><?= escape_html($mapper->documentType->currentLangData('documentTypeName')); ?></div>
        <?php if (mb_strlen($description) > 0): ?>
        <div class="description"><?= escape_html($description); ?></div>
        <?php endif; ?>
    </div>
    <?php if (mb_strlen($fileURL) > 0): ?>
    <a class="ui bottom attached button" target="_blank" rel="noopener" href="<?= escape_html($fileURL); ?>">
        <i class="external alternate icon"></i>
        <?= __($langGroup, 'Abrir'); ?>
    </a>
    <?php else: ?>
    <span class="ui bottom attached disabled button">
        <i class="ban icon"></i>
        <?= __($langGroup, 'Sin archivo'); ?>
    </span>
    <?php endif; ?>
</div>
