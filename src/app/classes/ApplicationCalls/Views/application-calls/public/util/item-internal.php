<?php
use App\Locations\LocationsLang;
use ApplicationCalls\Mappers\ApplicationCallsMapper;
use ContentNavigationHub\ContentNavigationHubLang;
use ContentNavigationHub\Controllers\ContentNavigationHubController;
/**
 * @var ApplicationCallsMapper $element
 */
$researchAreas = $element->interestResearhAreas;
$researchAreas = is_array($researchAreas) ? $researchAreas : [];
$singleURL = ContentNavigationHubController::routeName('application-calls-detail', ['id' => $element->id]);
$title = $element->currentLangData('title');
$thumbImage = $element->currentLangData('thumbImage');
$contentTypeForFullDisplayText = $element->contentTypeForFullDisplayText();
$contentTypeIcon = $element->contentTypeIcon();
$iconColor = $element->contentTypeIconColor();
$bgColor = $element->contentTypeBackgroundColor();
$startDate = strReplaceTemplate(localeDateFormat('%e %1 %B %1 Y', $element->startDate), ['%1' => __(LANG_GROUP, 'de')]);
$endDate = strReplaceTemplate(localeDateFormat('%e %1 %B %1 Y', $element->endDate), ['%1' => __(LANG_GROUP, 'de')]);
$targetCountries = implode(', ', array_map(fn($e) => __(LocationsLang::LANG_GROUP_NAMES, $e->name), $element->targetCountries));
$excerpt = $element->excerpt(403);
$excerpt = mb_strpos($excerpt, '...') !== false ? $excerpt : $excerpt . '...';
$langGroup = ContentNavigationHubLang::LANG_GROUP;
?>
<a class="element-row" href="<?= $singleURL; ?>">

    <div class="main-information">
        <div class="picture">
            <img src="<?= $thumbImage; ?>" alt="<?= $title; ?>">
        </div>
        <div class="data">
            <div class="topbar" style="--bg-type-color: <?= $bgColor; ?>; --bg-icon-type-color: <?= $iconColor; ?>;">
                <div class="element-type <?= $contentTypeIcon; ?>">
                    <div class="icon">
                        <i class="icon <?= $contentTypeIcon; ?>"></i>
                    </div>
                    <div class="text"><?= $contentTypeForFullDisplayText; ?></div>
                </div>
                <div class="actions">
                    <div class="action" share-action data-title="<?= $title; ?>" data-text="<?= $excerpt; ?>" data-url="<?= $singleURL; ?>">
                        <div class="icon">
                            <i class="share icon"></i>
                        </div>
                        <?= __($langGroup, 'Compartir'); ?>
                    </div>
                </div>
            </div>
            <div class="content">
                <div class="element-title"><?= $title; ?></div>
                <div class="meta">
                    <div class="item">
                        <div class="icon">
                            <i class="calendar alternate outline icon"></i>
                            <div class="text"><?= __($langGroup, 'Inicia'); ?></div>
                        </div>
                        <div class="data"><?= $startDate; ?></div>
                    </div>
                    <div class="item">
                        <div class="icon">
                            <i class="calendar alternate outline icon"></i>
                            <div class="text"><?= __($langGroup, 'Finaliza'); ?></div>
                        </div>
                        <div class="data"><?= $endDate; ?></div>
                    </div>
                    <div class="item">
                        <div class="icon">
                            <i class="map outline icon"></i>
                        </div>
                        <div class="data mark"><?= $targetCountries; ?></div>
                    </div>
                    <?php if($element->amount > 0): ?>
                    <div class="item">
                        <div class="data"><?= __($langGroup, $element->currency); ?></div>
                    </div>
                    <div class="item">
                        <div class="data mark"><?= number_format($element->amount, 0, ',', '.'); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if(!empty($researchAreas)): ?>
    <div class="segment-title"><?= __($langGroup, 'Áreas de investigación'); ?></div>
    <div class="tags">
        <?php foreach($researchAreas as $researchArea): ?>
        <div class="tag" style="--tag-color: <?= $researchArea->color; ?>;"><?= $researchArea->currentLangData('areaName'); ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="description"><?= $excerpt; ?></div>

</a>
