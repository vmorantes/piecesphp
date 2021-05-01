<?php

use Publications\Controllers\PublicationsPublicController;
use Publications\Mappers\PublicationCategoryMapper;
use Publications\Mappers\PublicationMapper;

/**
 * @var PublicationMapper $element
 */

?>
<a class="item" href="<?= PublicationsPublicController::routeName('single', ['slug' => $element->getSlug()]); ?>">
    <div class="image">
        <img src="<?= $element->currentLangData('images')[0]; ?>">
    </div>
    <div class="text">
        <div class="title"><?= $element->currentLangData('name'); ?></div>
        <div class="category"><?= PublicationCategoryMapper::getBy($element->currentLangData('category'), 'id', true)->currentLangData('name'); ?></div>
    </div>
</a>
