<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * El formulario de alta y edición de las cuatro entidades de Ubicaciones: solo cambian el título, el id oculto y los
 * campos. Los atributos de los desplegables (`locations-component-auto-filled-*`) y los del mapa los leen
 * LocationsAdapter.js y locations-config.js: se pasan tal cual.
 *
 * Cada campo es un arreglo con `kind`:
 * - `hidden`: name, value.
 * - `input`: name, label, required, requiredAttribute (por defecto, required), maxlength, value, note (junto a la etiqueta).
 * - `select`: name, label, required, class, attributes, options (HTML de `array_to_html_options`), help (bajo la etiqueta).
 * - `map`: label, longitude, latitude (el bloque del mapa de las coordenadas).
 *
 * @var \PiecesPHP\App\Locations\Controllers\HelperController $pieces Pinta las piezas del panel (la vista se pinta desde el módulo)
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var string $title
 * @var string $action
 * @var string $back_link
 * @var array<int,array<string,mixed>> $fields
 */
?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
            $pieces->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
            ]);
        ?>

        <form pcs-generic-handler-js method="POST" action="<?= escape_html($action); ?>" class="ui form initial locations" data-content-form>

            <div class="container-standard-form">

                <?php foreach ($fields as $field): ?>
                <?php
                    $kind = $field['kind'] ?? 'input';
                    $required = ($field['required'] ?? false) === true;
                    $label = isset($field['label']) && is_string($field['label']) ? $field['label'] : '';
                ?>

                <?php if ($kind === 'hidden'): ?>
                <input type="hidden" name="<?= escape_html($field['name']); ?>" value="<?= escape_html((string) ($field['value'] ?? '')); ?>">

                <?php elseif ($kind === 'select'): ?>
                <div class="field<?= $required ? ' required' : ''; ?>">
                    <label><?= escape_html($label); ?><?php if (isset($field['help'])): ?><br><small>(<?= escape_html($field['help']); ?>)</small><?php endif; ?></label>
                    <select<?= isset($field['class']) ? ' class="' . escape_html($field['class']) . '"' : ''; ?><?= $required ? ' required' : ''; ?> name="<?= escape_html($field['name']); ?>"<?= \PiecesPHP\Core\Utilities\Helpers\RowActions::attributes($field['attributes'] ?? []); ?>><?= $field['options'] ?? ''; ?></select>
                </div>

                <?php elseif ($kind === 'map'): ?>
                <div class="field required">
                    <label><?= escape_html($label); ?></label>
                    <input latitude-mapbox-handler name="latitude" type="hidden" required value="<?= escape_html((string) ($field['latitude'] ?? '')); ?>">
                    <input longitude-mapbox-handler name="longitude" type="hidden" required value="<?= escape_html((string) ($field['longitude'] ?? '')); ?>">
                </div>

                <div class="field">
                    <button type="button" set-satelital-view class="ui mini basic button"><i class="satellite icon"></i><?= __(LOCATIONS_LANG_GROUP, 'Vista satelital'); ?></button>
                    <button type="button" set-draw-view class="ui mini basic button"><i class="map outline icon"></i><?= __(LOCATIONS_LANG_GROUP, 'Vista de dibujo'); ?></button>
                    <button type="button" set-center-view class="ui mini basic button"><i class="crosshairs icon"></i><?= __(LOCATIONS_LANG_GROUP, 'Centrar'); ?></button>
                    <small><?= __(LOCATIONS_LANG_GROUP, '(para mayor precisión, puede mover el cursor de posición)'); ?></small>
                </div>

                <div class="field">
                    <div id="map"></div>
                </div>

                <?php else: ?>
                <?php $requiredAttribute = ($field['requiredAttribute'] ?? $required) === true; ?>
                <div class="field<?= $required ? ' required' : ''; ?>">
                    <label><?= escape_html($label); ?><?php if (isset($field['note'])): ?> <small><?= escape_html($field['note']); ?></small><?php endif; ?></label>
                    <input type="text" name="<?= escape_html($field['name']); ?>"<?= isset($field['maxlength']) ? ' maxlength="' . (int) $field['maxlength'] . '"' : ''; ?> value="<?= escape_html((string) ($field['value'] ?? '')); ?>"<?= $requiredAttribute ? ' required' : ''; ?>>
                </div>
                <?php endif; ?>

                <?php endforeach; ?>

            </div>

            <?php
                $pieces->_render('panel/built-in/content/form-action-bar.php', [
                    'saveText' => __(LOCATIONS_LANG_GROUP, 'Guardar'),
                    'secondaryActions' => [
                        ['text' => __(LOCATIONS_LANG_GROUP, 'Cancelar'), 'url' => $back_link, 'icon' => 'times'],
                    ],
                ]);
            ?>

        </form>

    </div>

</section>
