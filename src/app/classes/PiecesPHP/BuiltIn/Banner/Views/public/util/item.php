<?php
use PiecesPHP\BuiltIn\Banner\Mappers\BuiltInBannerMapper;

/**
 * @var BuiltInBannerMapper $element
 */

 $hasString = function(?string $str){
    return is_string($str) && mb_strlen($str) > 0;
 };
 $title = $element->currentLangData('title');
 $content = $element->currentLangData('content');
 $desktopImage = $element->currentLangData('desktopImage');
 $mobileImage = $element->currentLangData('mobileImage');
 $link = $element->currentLangData('link');
 $hasTitle = ($hasString)($title);
 $hasContent = ($hasString)($content);
 $hasLink = ($hasString)($link);
 $hasMobileImage = ($hasString)($mobileImage);
 $withCaption = $hasContent || $hasTitle;
 $mainTag = $hasLink ? 'a' : 'div';
 $mobileImage = $hasMobileImage ? $mobileImage : $desktopImage;
?>
<<?= $mainTag;?> class="item" <?= $hasLink ? 'href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
    <img class="desktop" src="<?= htmlspecialchars($desktopImage, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($hasTitle ? $title : basename($desktopImage), ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
    <img class="mobile" src="<?= htmlspecialchars($mobileImage, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($hasTitle ? $title : basename($mobileImage), ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
    <?php if($withCaption): ?>
    <div class="caption">
        <?php if($hasTitle): ?>
        <div class="title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if($hasContent): ?>
        <div class="text"><?= $content; ?></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</<?= $mainTag;?>>
