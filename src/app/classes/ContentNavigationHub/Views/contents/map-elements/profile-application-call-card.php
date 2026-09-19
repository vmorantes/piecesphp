<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use ApplicationCalls\Mappers\ApplicationCallsMapper;
use App\Locations\LocationsLang;
use ContentNavigationHub\ContentNavigationHubLang;
use ContentNavigationHub\Controllers\ContentNavigationHubController;

/**
 * @var ApplicationCallsMapper $mapper
 */
$avatar = $mapper->currentLangData('thumbImage');
$targetCountries = implode(', ', array_map(fn($e) => __(LocationsLang::LANG_GROUP_NAMES, $e->name), $mapper->targetCountries));
$contentTypeFullText = $mapper->contentTypeForFullDisplayText();
?>
<div class='custom-card application-call'>
    <div class="avatar">
        <img src='<?= $avatar; ?>'>
    </div>
    <div class="content">
        <div class="title">
            <?= $mapper->currentLangData('title'); ?>
        </div>
        <?php if(mb_strlen($contentTypeFullText) > 0): ?>
        <div class="subtitle"><?= $contentTypeFullText; ?></div>
        <?php endif; ?>
        <div class="meta">
            <?php if(mb_strlen($targetCountries) > 0): ?>
            <div class="item"><?= $targetCountries; ?></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="actions">
        <a class="action" target="_blank" href="<?= ContentNavigationHubController::routeName('application-calls-detail', ['id' => $mapper->id]); ?>">
            <i class="icon plus"></i>
            <?= __(ContentNavigationHubLang::LANG_GROUP, 'Ver contenido'); ?>
        </a>
    </div>
</div>
