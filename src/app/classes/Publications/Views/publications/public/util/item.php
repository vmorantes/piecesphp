<?php
use Publications\Controllers\PublicationsPublicController;
use Publications\Mappers\PublicationMapper;

/**
 * @var PublicationMapper $element
 */
?>
<article class="item">
    <div class="image">
        <a href="<?= PublicationsPublicController::routeName('single', ['slug' => $element->getSlug()]); ?>" class="element-link">
            <img src="<?= $element->currentLangData('thumbImage'); ?>" alt="<?= $element->currentLangData('title'); ?>">
        </a>
    </div>
    <div class="content">
        <div class="title"><?= $element->currentLangData('title'); ?></div>
        <div class="meta"><?= $element->authorFullName(); ?></div>
        <div class="description"><?= $element->excerpt(); ?></div>
    </div>
</article>
