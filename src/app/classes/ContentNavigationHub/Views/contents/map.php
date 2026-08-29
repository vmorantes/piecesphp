<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use GeoJSONManager\Enums\FeaturesTypes;
use Organizations\Mappers\OrganizationMapper;

/**
 * @var string $langGroup
 */

$featureTypesOptions = array_to_html_options(FeaturesTypes::valuesForSelect());

?>
<script>
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
                        <label><?= __($langGroup, 'Organización'); ?></label>
                        <select name="organizations[]" multiple class="ui dropdown multiple search special-tags" control-organizations>
                            <?= array_to_html_options(OrganizationMapper::allForSelect('', '', false, false, __($langGroup, 'Sin organización')), null); ?>
                        </select>
                        <div class="current-selection-filter organizations"></div>
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
