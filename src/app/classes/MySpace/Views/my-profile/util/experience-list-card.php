<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Locations\LocationsLang;
use PiecesPHP\UserSystem\Profile\SubMappers\InterestResearchAreasMapper;
use PiecesPHP\UserSystem\Profile\SubMappers\PreviousExperiencesMapper;

/**
 * @var PreviousExperiencesMapper $mapper
 */

/**
 * @var string $langGroup
 * @var string $editLink
 * @var InterestResearchAreasMapper[] $researchAreas
 * @var string[] $institutions
 */
$researchAreas = array_map(fn($e) => (new InterestResearchAreasMapper($e)), $mapper->researchAreas);
$institutions = array_map(fn($e) => is_string($e) ? trim($e) : null, $mapper->institutionsParticipated);
$institutions = array_filter(
    $mapper->institutionsParticipated, 
    fn($e) => is_string($e) && mb_strlen($e) > 0
);
?>

<div class="experience-card">

    <div class="topbar">
        <div class="experience-type">
            <div class="icon <?= $mapper->experienceTypeIcon(); ?>">
                <i class="icon <?= $mapper->experienceTypeIcon(); ?>"></i>
            </div>
            <div class="name">
                <?= $mapper->experienceTypeDisplayText(); ?>
            </div>
        </div>
        <?php if($hasDelete): ?>
        <a class="delete-icon" data-tooltip="<?= __($langGroup, 'Eliminar'); ?>" delete-experience data-route="<?= $deleteRoute; ?>">
            <div class="icon">
                <i class="trash alternate outline icon"></i>
            </div>
        </a>
        <?php endif;?>
    </div>

    <div class="experience-title"><?= $mapper->currentLangData('experienceName'); ?></div>

    <div class="meta">
        <div class="item">
            <div class="icon">
                <i class="calendar alternate outline icon"></i>
                <div class="text"><?= __($langGroup, 'Inició'); ?></div>
            </div>
            <div class="data"><?= $mapper->startDateFormat(); ?></div>
        </div>
        <div class="item">
            <div class="icon">
                <i class="calendar alternate outline icon"></i>
                <div class="text"><?= __($langGroup, 'Finalizó'); ?></div>
            </div>
            <div class="data"><?= $mapper->endDateFormat(); ?></div>
        </div>
        <div class="item">
            <div class="icon">
                <i class="map outline icon"></i>
            </div>
            <div class="data big">
                <?= __(LocationsLang::LANG_GROUP_NAMES, $mapper->country->name); ?>,
                <?= __(LocationsLang::LANG_GROUP_NAMES, $mapper->city->name); ?>
            </div>
        </div>
    </div>

    <?php if(!empty($researchAreas)): ?>
    <div class="research-areas-title"><?= __($langGroup, 'Áreas de Investigación'); ?></div>
    <div class="research-areas">
        <?php foreach($researchAreas as $researchArea): ?>
        <div class="area" style="--area-color: <?= $researchArea->color; ?>;"><?= $researchArea->currentLangData('areaName'); ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if(!empty($institutions)): ?>
    <div class="institutions">
        <?= implode(', ', $institutions); ?>
    </div>
    <?php endif; ?>

</div>