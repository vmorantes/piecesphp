<?php
use Publications\Controllers\PublicationsPublicController;
use Publications\Mappers\PublicationMapper;

/**
 * @var PublicationMapper $element
 */
$excerpt = $element->excerpt(300);
$excerpt = mb_strpos($excerpt, '...') !== false ? $excerpt : $excerpt . '...';
?>
<article class="ui card">
    <a class="image" href="<?= PublicationsPublicController::routeName('single', ['slug' => $element->getSlug()]); ?>">
        <img src="<?= $element->currentLangData('thumbImage'); ?>" alt="<?= $element->currentLangData('title'); ?>" loading="lazy">
    </a>
    <div class="content">
        <div class="header"><?= $element->currentLangData('title'); ?></div>
        <div class="meta">
            <span><?= $element->authorFullName(); ?></span>
        </div>
        <div class="description"><?= $excerpt; ?></div>
    </div>
</article>
