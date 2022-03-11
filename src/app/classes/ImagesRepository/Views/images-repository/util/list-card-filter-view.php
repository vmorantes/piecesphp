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

            <?php if($hasSingle): ?>
            <a class="fluid ui custom-color button" href="<?= $singleLink; ?>">
                <?= __($langGroup, 'Ver'); ?>
            </a>
            <?php endif;?>
        
    </div>

</div>
