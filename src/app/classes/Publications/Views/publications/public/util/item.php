<?php
use Publications\Controllers\PublicationsPublicController;
use Publications\Mappers\PublicationMapper;

/**
 * @var PublicationMapper $element
 */
?>
<article class="ui card">
    <a class="image" href="<?= PublicationsPublicController::routeName('single', ['slug' => $element->getSlug()]); ?>">
        <img src="<?= $element->currentLangData('thumbImage'); ?>" alt="<?= $element->currentLangData('title'); ?>">
    </a>
    <div class="content">
        <div class="header"><?= $element->currentLangData('title'); ?></div>
        <div class="meta">
            <a><?= $element->authorFullName(); ?></a>
        </div>
        <div class="description"><?= $element->excerpt(); ?></div>
    </div>
</article>
