<?php
use News\Mappers\NewsCategoryMapper;
use News\Mappers\NewsMapper;
use News\NewsLang;

/**
 * @var NewsMapper $element
 */
$element->category = !is_object($element->category) ? new NewsCategoryMapper($element->category) : $element->category;
$now = new \DateTime();
$endDate = $element->endDate;
$isFinish = $endDate < $now;
$content = $element->currentLangData('content');
$contentLength = mb_strlen(strip_tags($content));
?>
<article class="notification-card <?= $isFinish ? ' finished' : ''; ?>" style="--category-color: <?= $element->category->currentLangData('color'); ?>;" data-content-b64="<?= base64_encode($content); ?>">
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
    <div class="footer">
        <?php if ($contentLength > 117) : ?>
        <div class="ui button brand-color<?= $isFinish ? ' alt2' : ''; ?>" see-more><?= __(NewsLang::LANG_GROUP, 'Ver más'); ?></div>
        <?php endif; ?>
    </div>
</article>
