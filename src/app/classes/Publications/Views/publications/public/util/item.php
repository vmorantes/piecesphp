<?php

use Publications\Controllers\PublicationsPublicController;
use Publications\Mappers\PublicationCategoryMapper;
use Publications\Mappers\PublicationMapper;

/**
 * @var PublicationMapper $element
 */

?>
<article class="item">
    <a href="<?= PublicationsPublicController::routeName('single', ['slug' => $element->getSlug()]); ?>" class="link-item"></a>
    <div class="image">
        <img src="<?= $element->currentLangData('thumbImage'); ?>" alt="<?= $element->currentLangData('title'); ?>">
    </div>
    <div class="content">
        <div class="title"><?= $element->currentLangData('title'); ?></div>
        <div class="meta"><?= $element->authorFullName(); ?></div>
        <div class="description"><?= substr($element->currentLangData('content'), 0, 200); ?>...</div>
    </div>
</article>
