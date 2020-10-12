<?php

use App\Presentations\Controllers\PresentationsPublicController;
use App\Presentations\Mappers\PresentationCategoryMapper;
use App\Presentations\Mappers\PresentationMapper;

/**
 * @var PresentationMapper $element
 */

?>
<a class="item" href="<?= PresentationsPublicController::routeName('single', ['slug' => $element->getSlug()]); ?>">
    <div class="image">
        <img src="<?= $element->currentLangData('images')[0]; ?>">
    </div>
    <div class="text">
        <div class="title"><?= $element->currentLangData('name'); ?></div>
        <div class="category"><?= PresentationCategoryMapper::getBy($element->currentLangData('category'), 'id', true)->currentLangData('name'); ?></div>
    </div>
</a>
