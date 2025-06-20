<?php
use ApplicationCalls\Controllers\ApplicationCallsPublicController;
use ApplicationCalls\Mappers\ApplicationCallsMapper;

/**
 * @var ApplicationCallsMapper $element
 */
$excerpt = $element->excerpt(300);
$excerpt = mb_strpos($excerpt, '...') !== false ? $excerpt : $excerpt . '...';
?>
<article class="ui card">
    <a class="image" href="#">
        <img src="<?= $element->currentLangData('thumbImage'); ?>" alt="<?= $element->currentLangData('title'); ?>" loading="lazy">
    </a>
    <div class="content">
        <div class="header"><?= $element->currentLangData('title'); ?></div>
        <div class="description"><?= $excerpt; ?></div>
    </div>
</article>
