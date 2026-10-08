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
<article class="notification-card <?= $isFinish ? ' finished' : ''; ?>" style="--category-color: <?= escape_html(\PiecesPHP\Core\Validation\Validator::isColor($element->category->currentLangData('color')) ? $element->category->currentLangData('color') : NewsCategoryMapper::DEFAULT_COLOR); ?>;" data-content-b64="<?= base64_encode($content); ?>">
    <div class="head">
        <div class="info">
            <span><?= escape_html($element->excerptTitle()); ?></span>
            <small><?= $element->startDateFormat('d/m/Y - h:i A'); ?></small>
        </div>
        <div class="icon">
            <img src="<?= $element->category->currentLangData('iconImage'); ?>" alt="<?= escape_html($element->category->currentLangData('name')); ?>">
        </div>
    </div>
    <div class="body">
        <?php //Texto sacado del editor con strip_tags: trae sus entidades, que se decodifican antes de escapar una sola vez. ?>
        <?= escape_html(html_entity_decode($element->excerpt(120), ENT_QUOTES | ENT_HTML5, 'UTF-8')); ?>
    </div>
    <div class="footer">
        <?php if ($contentLength > 117) : ?>
        <div class="ui button brand-color<?= $isFinish ? ' alt2' : ''; ?>" see-more><?= __(NewsLang::LANG_GROUP, 'Ver más'); ?></div>
        <?php endif; ?>
    </div>
</article>
