<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use PiecesPHP\Geo\GeoFilter;
use PiecesPHP\Geo\GeoSource;

/**
 * @var string $langGroup
 * @var array<string,GeoSource> $sources Las visibles para el rol, en orden de registro.
 * @var array<string,GeoFilter> $filters Uno por clave.
 * @var array<string,mixed> $client Lo que lee geo-viewer.js: las URL de las dos rutas y las fuentes.
 */

$withSources = count($sources) > 0;
?>
<section class="geo-viewer-container" data-geo-viewer="<?= escape_html(json_encode($client, \JSON_THROW_ON_ERROR)); ?>">

    <div class="geo-map" data-geo-map></div>

    <?php if (!$withSources): ?>
    <div class="geo-empty"><?= __($langGroup, 'No tiene acceso a ningún dato del mapa.'); ?></div>
    <?php endif; ?>

    <div class="geo-toolbar">
        <button type="button" class="ui icon button" data-geo-toggle="filters" aria-expanded="false" title="<?= __($langGroup, 'Filtros'); ?>">
            <i class="sliders horizontal icon"></i>
        </button>
        <button type="button" class="ui icon button" data-geo-toggle="legend" aria-expanded="false" title="<?= __($langGroup, 'Leyenda'); ?>">
            <i class="list ul icon"></i>
        </button>
    </div>

    <div class="geo-panel geo-panel-filters" data-geo-panel="filters" hidden>
        <div class="geo-panel-header">
            <span><?= __($langGroup, 'Filtros'); ?></span>
            <button type="button" class="ui icon basic button" data-geo-close="filters" title="<?= __($langGroup, 'Cerrar'); ?>"><i class="close icon"></i></button>
        </div>
        <form class="ui form" data-geo-filters autocomplete="off">
            <?php foreach ($filters as $filter): ?>
            <?php $fieldID = 'geo-filter-' . $filter->key(); ?>
            <?php if ($filter->type() === GeoFilter::TYPE_TEXT): ?>
            <div class="field">
                <label for="<?= escape_html($fieldID); ?>"><?= escape_html($filter->label()); ?></label>
                <input type="text" id="<?= escape_html($fieldID); ?>" data-geo-filter="<?= escape_html($filter->key()); ?>" data-geo-filter-type="<?= GeoFilter::TYPE_TEXT; ?>">
            </div>
            <?php elseif ($filter->type() === GeoFilter::TYPE_OPTIONS): ?>
            <div class="field">
                <label for="<?= escape_html($fieldID); ?>"><?= escape_html($filter->label()); ?></label>
                <select multiple class="ui search multiple selection dropdown" id="<?= escape_html($fieldID); ?>" data-geo-filter="<?= escape_html($filter->key()); ?>" data-geo-filter-type="<?= GeoFilter::TYPE_OPTIONS; ?>">
                    <option value=""><?= escape_html($filter->label()); ?></option>
                    <?php foreach ($filter->options() as $value => $text): ?>
                    <option value="<?= escape_html($value); ?>"><?= escape_html($text); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php else: ?>
            <div class="two fields" data-geo-filter="<?= escape_html($filter->key()); ?>" data-geo-filter-type="<?= GeoFilter::TYPE_DATE_RANGE; ?>">
                <div class="field" calendar-js calendar-type="date">
                    <label><?= escape_html($filter->label()); ?> · <?= __($langGroup, 'desde'); ?></label>
                    <input type="text" data-geo-date="from">
                </div>
                <div class="field" calendar-js calendar-type="date">
                    <label><?= escape_html($filter->label()); ?> · <?= __($langGroup, 'hasta'); ?></label>
                    <input type="text" data-geo-date="to">
                </div>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>
            <?php if (count($filters) > 0): ?>
            <button type="reset" class="ui button brand-color alt" data-geo-reset><?= __($langGroup, 'Quitar los filtros'); ?></button>
            <?php endif; ?>
        </form>
    </div>

    <div class="geo-panel geo-panel-legend" data-geo-panel="legend" hidden>
        <div class="geo-panel-header">
            <span><?= __($langGroup, 'Leyenda'); ?></span>
            <button type="button" class="ui icon basic button" data-geo-close="legend" title="<?= __($langGroup, 'Cerrar'); ?>"><i class="close icon"></i></button>
        </div>
        <?php foreach ($sources as $key => $source): ?>
        <div class="geo-legend-row" style="--geo-legend-color: var(<?= escape_html($source->colorVariable()); ?>);">
            <span class="geo-legend-sample" data-geo-legend-sample="<?= escape_html($source->marker()); ?>"></span>
            <label for="<?= escape_html('geo-source-' . $key); ?>"><?= escape_html($source->title()); ?></label>
            <div class="ui toggle checkbox">
                <input type="checkbox" checked id="<?= escape_html('geo-source-' . $key); ?>" data-geo-source-toggle="<?= escape_html($key); ?>">
                <label></label>
            </div>
        </div>
        <?php endforeach; ?>
        <div class="geo-legend-styles">
            <span><?= __($langGroup, 'Vista del mapa'); ?></span>
            <div class="ui two mini buttons">
                <button type="button" class="ui button brand-color" data-geo-style="streets" aria-pressed="true"><?= __($langGroup, 'Calles'); ?></button>
                <button type="button" class="ui button brand-color alt" data-geo-style="satellite" aria-pressed="false"><?= __($langGroup, 'Satélite'); ?></button>
            </div>
        </div>
    </div>

    <div class="geo-counter" aria-live="polite">
        <span data-geo-counter></span>
        <span class="geo-truncated" data-geo-truncated hidden><?= __($langGroup, 'Hay más puntos de los que se ven: acerque el mapa o filtre'); ?></span>
    </div>

    <div class="geo-card-holder" data-geo-card-holder hidden>
        <button type="button" class="ui icon basic mini button geo-card-close" data-geo-card-close title="<?= __($langGroup, 'Cerrar'); ?>"><i class="close icon"></i></button>
        <div class="geo-card-loading" data-geo-card-loading hidden><div class="ui active small inline loader"></div></div>
        <div class="geo-card-message" data-geo-card-message hidden></div>
        <div data-geo-card-body></div>
    </div>

    <?php foreach ($sources as $key => $source): ?>
    <template data-geo-card="<?= escape_html($key); ?>"><?php include $source->cardView(); ?></template>
    <?php endforeach; ?>

    <template data-geo-marker="dot"><span class="geo-marker geo-marker-dot"></span></template>
    <template data-geo-marker="avatar"><span class="geo-marker geo-marker-avatar"><img alt="" data-geo-marker-image hidden><span data-geo-marker-initials></span></span></template>

</section>
