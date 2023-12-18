<?php
use News\Mappers\NewsCategoryMapper;
use News\Mappers\NewsMapper;

/**
 * @var NewsMapper $element
 */
$element->category = !is_object($element->category) ? new NewsCategoryMapper($element->category) : $element->category;
$now = new \DateTime();
$endDate = $element->endDate;
$isFinish = $endDate < $now;
$contentLength = mb_strlen(strip_tags($element->currentLangData('content')));
?>
<article class="notification-card <?= $isFinish ? ' finished' : ''; ?>" style="--category-color: <?= $element->category->currentLangData('color'); ?>;" data-content-b64="<?= base64_encode($element->currentLangData('content')); ?>">
    <div class="head">
        <div class="info">
            <span><?= $element->excerptTitle(); ?></span>
            <small><?= $element->startDateFormat('d/m/Y - h:i A'); ?></small>
        </div>
        <div class="icon">
            <img src="<?= $element->category->currentLangData('iconImage'); ?>" alt="<?= $element->category->currentLangData('name'); ?>">
        </div>
    </div>
    <div class="body">
        <?= $element->excerpt(120); ?>
    </div>

</article>
