<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use ImagesRepository\Mappers\ImagesRepositoryMapper;
/**
 * @var ImagesRepositoryMapper $mapper
 */
$mapper;

/**
 * @var string $langGroup
 * @var string $editLink
 */
$langGroup;
$editLink;

?>

<div class="ui card">

    <div class="image">
        <img src="<?= $mapper->getPreviewImagePath(); ?>" alt="<?= $mapper->getFriendlyImageName(false); ?>">
    </div>

    <div class="footer">

        <div class="ui three buttons">

            <?php if($hasSingle): ?>
            <a class="fluid ui custom-color button" href="<?= $singleLink; ?>">
                <?= __($langGroup, 'Ver'); ?>
            </a>
            <?php endif;?>

            <?php if($hasEdit): ?>
            <a class="fluid ui teal button" href="<?= $editLink; ?>">
                <?= __($langGroup, 'Editar'); ?>
            </a>
            <?php endif;?>

            <?php if($hasDelete): ?>
            <a class="fluid ui grey button" delete-images-repository-button data-route="<?= $deleteRoute; ?>">
                <?= __($langGroup, 'Eliminar'); ?>
            </a>
            <?php endif;?>

        </div>
        
    </div>

</div>
