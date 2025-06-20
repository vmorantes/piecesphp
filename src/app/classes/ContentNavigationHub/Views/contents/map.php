<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use ApplicationCalls\Mappers\ApplicationCallsMapper;
use GeoJSONManager\Enums\FeaturesTypes;
use Organizations\Mappers\OrganizationMapper;

/**
 * @var string $langGroup
 */

$featureTypesOptions = array_to_html_options(FeaturesTypes::valuesForSelect());
$areaDataForOptions = getInteresResearchAreas(true, null, [], true);
$areasOptions = [];
foreach ($areaDataForOptions as $areaData) {
    if (is_string($areaData)) {
        $areasOptions[] = "<option value=''>{$areaData}</option>";
    } else {
        $areaID = $areaData['id'];
        $areaName = $areaData['areaName'];
        $areaColor = $areaData['color'];
        $areasOptions[] = "<option value='{$areaID}' data-color='{$areaColor}'>{$areaName}</option>";
    }
}
$areasOptions = implode("\n", $areasOptions);

?>
<script>
const FEATURE_TYPE_APPLICATION_CALLS = '<?= FeaturesTypes::APPLICATION_CALLS->value; ?>'
const FEATURE_TYPE_PROFILES = '<?= FeaturesTypes::PROFILES->value; ?>'
</script>
<section class="module-view-container">
    <div class="main-container">
        <div class="column filters">
            <form class="ui form section filters">

                <div class="title icon mark">
                    <?= __($langGroup, 'Filtrar por')?>
                    <div class="icon">
                        <i class="icon sliders horizontal"></i>
                    </div>
                </div>

                <div class="segment">
                    <div class="field" data-enable="yes">
                        <label><?= __($langGroup, 'Tipo de búsqueda'); ?></label>
                        <select control-features-type name="featuresType" class="ui dropdown search"><?= $featureTypesOptions; ?></select>
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
                        <select name="researchAreas[]" multiple class="ui dropdown multiple search special-tags" control-research-areas>
                            <?= $areasOptions; ?>
                        </select>
                    </div>
                </div>

                <div class="segment">

                    <div class="field">
                        <label><?= __($langGroup, 'Organización'); ?></label>
                        <select name="organizations[]" multiple class="ui dropdown multiple search special-tags" control-organizations>
                            <?= array_to_html_options(OrganizationMapper::allForSelect('', '', false, false, __($langGroup, 'Sin organización')), null); ?>
                        </select>
                        <div class="current-selection-filter organizations"></div>
                    </div>

                    <div class="field">
                        <label><?= __($langGroup, 'Tipo de contenido'); ?></label>
                        <select name="contentType[]" multiple class="ui dropdown multiple search special-tags" control-content-type>
                            <?= array_to_html_options(ApplicationCallsMapper::contentTypesForSelect(), null); ?>
                        </select>
                    </div>

                    <div class="field">
                        <label><?= __($langGroup, 'Tipo de contratación'); ?></label>
                        <select name="financingType[]" multiple class="ui dropdown multiple search special-tags" control-financing-type-NONE data-enable="no">
                            <?= array_to_html_options(ApplicationCallsMapper::financingTypesForSelect(), null); ?>
                        </select>
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
                </div>

                <div class="segment">

                    <div class="field">
                        <button type="submit" class="ui button blue"><?= __($langGroup, 'Filtrar'); ?></button>
                    </div>

                </div>

            </form>
        </div>
        <div class="column map">
            <div id="map"></div>
        </div>
    </div>
</section>
