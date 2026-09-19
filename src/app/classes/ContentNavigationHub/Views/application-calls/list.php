<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use ApplicationCalls\Controllers\ApplicationCallsPublicController;
use ApplicationCalls\Mappers\ApplicationCallsMapper;
use Organizations\Mappers\OrganizationMapper;
use Spatie\Url\Url as URLManager;
/**
 * @var string $langGroup
 * @var string $editLink
 * @var string|null $contentTypeSelected
 */
$areaDataForOptions = getInteresResearchAreas(true, null, [], true);
$areasOptions = [];
foreach($areaDataForOptions as $areaData){
    if(is_string($areaData)){
        $areasOptions[] = "<option value=''>{$areaData}</option>";
    }else{
        $areaID = $areaData['id'];
        $areaName = $areaData['areaName'];
        $areaColor = $areaData['color'];
        $areasOptions[] = "<option value='{$areaID}' data-color='{$areaColor}'>{$areaName}</option>";
    }
}
$areasOptions = implode("\n", $areasOptions);
$contentsRequestURL = ApplicationCallsPublicController::routeName('ajax-all');
if($contentTypeSelected !== null){
    $contentsRequestURL = URLManager::fromString($contentsRequestURL);
    $contentsRequestURL = $contentsRequestURL->withQueryParameter('contentType[]', $contentTypeSelected);
}
?>
<section class="module-view-container content-hub-listing">

    <div class="breadcrumb">
        <?= $breadcrumbs ?>
    </div>

    <div class="limiter-content">

        <div class="section-title">
            <div class="title"><?= $title ?></div>
            <?php if(isset($description) && is_string($description) && mb_strlen(trim($description)) > 0): ?>
            <div class="description"><?= $description; ?></div>
            <?php endif; ?>
        </div>

        <div class="element-content">

            <div class="main-content">
                <div class="elements-listing" application-calls-js data-application-call-url="<?= $contentsRequestURL; ?>"></div>

                <button class="ui button brand-color load-more-button" application-calls-load-more-js><?= __($langGroup, 'Cargar más'); ?></button>
            </div>

            <div class="secondary-content">
                <form class="ui form section filters">

                    <div class="title icon mark">
                        <?= __($langGroup, 'Filtrar por')?>
                        <div class="icon">
                            <i class="icon sliders horizontal"></i>
                        </div>
                    </div>

                    <div class="segment">
                        <div class="field">
                            <div class="ui icon input">
                                <i class="search icon"></i>
                                <input name="search" type="text" placeholder="<?= __($langGroup, 'Buscar')?>" control-search>
                            </div>
                        </div>
                    </div>

                    <div class="segment">
                        <div class="field">
                            <label><?= __($langGroup, 'Áreas de investigación'); ?></label>
                            <select name="researchAreas[]" multiple id="" class="ui dropdown multiple special-tags" control-research-areas ctrl-target=".current-selection-filter.researchAreas">
                                <?= $areasOptions; ?>
                            </select>
                            <div class="current-selection-filter researchAreas"></div>
                        </div>
                    </div>

                    <div class="segment">

                        <div class="field">
                            <label><?= __($langGroup, 'Organización'); ?></label>
                            <select name="organizations[]" multiple id="" class="ui dropdown multiple special-tags" control-organizations ctrl-target=".current-selection-filter.organizations">
                                <?= array_to_html_options(OrganizationMapper::allForSelect('', '', false, true), null); ?>
                            </select>
                            <div class="current-selection-filter organizations"></div>
                        </div>

                        <?php if($contentTypeSelected === null): ?>
                        <div class="field">
                            <label><?= __($langGroup, 'Tipo de contenido'); ?></label>
                            <select name="contentType[]" multiple id="" class="ui dropdown multiple special-tags" control-content-type ctrl-target=".current-selection-filter.contentType">
                                <?= array_to_html_options(ApplicationCallsMapper::contentTypesForSelect(), null); ?>
                            </select>
                            <div class="current-selection-filter contentType"></div>
                        </div>
                        <?php else: ?>
                        <div class="field" style="display: none;">
                            <label><?= __($langGroup, 'Tipo de contenido'); ?></label>
                            <select name="contentType[]" multiple id="" class="ui dropdown multiple special-tags" control-content-type ctrl-target=".current-selection-filter.contentType">
                                <option selected value="<?= $contentTypeSelected; ?>"><?= $contentTypeSelected; ?></option>
                            </select>
                            <div class="current-selection-filter contentType"></div>
                        </div>
                        <?php endif; ?>

                        <div class="field">
                            <label><?= __($langGroup, 'Tipo de contratación'); ?></label>
                            <select name="financingType[]" multiple id="" class="ui dropdown multiple special-tags" control-financing-type ctrl-target=".current-selection-filter.financingType">
                                <?= array_to_html_options(ApplicationCallsMapper::financingTypesForSelect(), null); ?>
                            </select>
                            <div class="current-selection-filter financingType"></div>
                        </div>

                    </div>

                    <div class="segment">

                        <div class="segment-title"><?= __($langGroup, 'Fechas'); ?></div>

                        <div class="field required" calendar-group-js='periodo' start calendar-type="date">
                            <div class="ui icon input">
                                <i class="calendar alternate outline icon"></i>
                                <input control-start-date type="text" name="startDate" autocomplete="off" placeholder="<?= __($langGroup, 'Fecha de inicio'); ?>">
                            </div>
                        </div>

                        <div class="field required" calendar-group-js='periodo' end calendar-type="date">
                            <div class="ui icon input">
                                <i class="calendar alternate outline icon"></i>
                                <input control-end-date type="text" name="endDate" autocomplete="off" placeholder="<?= __($langGroup, 'Fecha de cierre'); ?>">
                            </div>
                        </div>

                        <div class="field">
                            <button type="submit" class="ui button blue"><?= __($langGroup, 'Filtrar'); ?></button>
                        </div>

                    </div>

                </form>
            </div>
        </div>

    </div>

</section>