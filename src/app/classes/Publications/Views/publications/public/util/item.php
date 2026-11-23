<?php
use Publications\Controllers\PublicationsPublicController;
use Publications\Mappers\PublicationMapper;

/**
 * @var PublicationMapper $element
 */
$excerptTitle = $element->excerptTitle(300);
$excerptTitle = mb_strpos($excerptTitle, '...') !== false ? $excerptTitle : $excerptTitle . '...';
$escape = fn($value): string => htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
//En modo headless la página de detalle no existe: la tarjeta va sin enlace, nunca con href="".
$singleURL = PublicationsPublicController::routeName('single', ['slug' => $element->getSlug()], true);
$tag = $singleURL !== '' ? 'a' : 'div';
?>
<<?= $tag; ?> class="ui card"<?= $singleURL !== '' ? ' href="' . $escape($singleURL) . '"' : ''; ?>>
    <div class="image">
        <img src="<?= $escape($element->currentLangData('thumbImage')); ?>" alt="<?= $escape($element->currentLangData('title')); ?>" loading="lazy">
    </div>
    <div class="content">
        <div class="header"><?= $element->publicDateFormat(); ?></div>
        <div class="meta">
            <span><?= $escape($element->authorFullName()); ?></span>
        </div>
        <div class="description"><?= $escape($excerptTitle); ?></div>
    </div>
</<?= $tag; ?>>
