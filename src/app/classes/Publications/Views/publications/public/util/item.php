<?php
use Publications\Controllers\PublicationsPublicController;
use Publications\Mappers\PublicationMapper;

/**
 * @var PublicationMapper $element
 */
$excerptTitle = $element->excerptTitle(300);
$excerptTitle = mb_strpos($excerptTitle, '...') !== false ? $excerptTitle : $excerptTitle . '...';
?>
<a class="ui card" href="<?= PublicationsPublicController::routeName('single', ['slug' => $element->getSlug()]); ?>">
    <div class="image">
        <img src="<?= $element->currentLangData('thumbImage'); ?>" alt="<?= $element->currentLangData('title'); ?>" loading="lazy">
    </div>
    <div class="content">
        <div class="header"><?= $element->publicDateFormat(); ?></div>
        <div class="meta">
            <span><?= $element->authorFullName(); ?></span>
        </div>
        <div class="description"><?= $excerptTitle; ?></div>
    </div>
</a>