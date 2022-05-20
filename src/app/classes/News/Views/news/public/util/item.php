<?php
use News\Mappers\NewsCategoryMapper;
use News\Mappers\NewsMapper;

/**
 * @var NewsMapper $element
 */
$element->category = !is_object($element->category) ? new NewsCategoryMapper($element->category) : $element->category;
?>
<article class="news-card" style="--category-color: <?= $element->category->currentLangData('color'); ?>;" data-content-b64="<?= base64_encode($element->currentLangData('content')); ?>">

    <div class="header">
        <div class="text">
            <div class="title"><?= $element->currentLangData('newsTitle'); ?></div>
            <div class="meta">
                <span><?= $element->startDateFormat('d/m/Y - h:i A'); ?></span>
                <span><?= $element->category->currentLangData('name'); ?></span>
            </div>
        </div>
        <div class="image">
            <img src="<?= $element->category->currentLangData('iconImage'); ?>" alt="<?= $element->category->currentLangData('name'); ?>">
        </div>
    </div>

    <div class="content">
        <?= $element->excerpt(90); ?>
    </div>

    <div class="footer">
        <div class="ui button brand-color" see-more><?= __($langGroup, 'Ver más'); ?></div>
    </div>

</article>
