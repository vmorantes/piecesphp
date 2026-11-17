<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * La pantalla Accesibilidad: lo que ve quien no ha elegido lo suyo en «Mi cuenta › Preferencias», en dos secciones
 * como las de cada persona (Apariencia y Accesibilidad). Se guarda con Statics/js/accessibility.js.
 *
 * @var string $langGroup
 * @var string $actionURL
 * @var string $textSize Uno de AdminPanelController::TEXT_SIZES
 * @var string $themeMode Uno de AdminPanelController::THEME_MODES
 * @var string $contrast Uno de AdminPanelController::CONTRASTS
 * @var string $motion Uno de AdminPanelController::MOTIONS
 * @var string $focus Uno de AdminPanelController::FOCUSES
 * @var string $title
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
$textSizeOptions = [
    'normal' => __($langGroup, 'Normal'),
    'large' => __($langGroup, 'Grande'),
    'larger' => __($langGroup, 'Más grande'),
];
$themeModeOptions = [
    'light' => __($langGroup, 'Claro'),
    'dark' => __($langGroup, 'Oscuro'),
    'auto' => __($langGroup, 'Sistema'),
];
//Contraste, movimiento y foco (H252, fase 2): el campo, su etiqueta, sus opciones y el valor del sitio.
$preferenceSelects = [
    'accessibility_contrast' => ['accessibility-contrast', __($langGroup, 'Contraste por omisión'), ['auto' => __($langGroup, 'Automático'), 'more' => __($langGroup, 'Más contraste')], $contrast],
    'accessibility_motion' => ['accessibility-motion', __($langGroup, 'Movimiento por omisión'), ['auto' => __($langGroup, 'Automático'), 'reduce' => __($langGroup, 'Reducir movimiento')], $motion],
    'accessibility_focus' => ['accessibility-focus', __($langGroup, 'Foco por omisión'), ['normal' => __($langGroup, 'Normal'), 'strong' => __($langGroup, 'Reforzado')], $focus],
];
?>
<section class="module-view-container accessibility-view">

    <div class="limiter-content">

        <?php
            $helpController->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
                'description' => $description,
            ]);
        ?>

        <form accessibility-form action="<?= escape_html($actionURL); ?>" method="POST" class="ui form initial segment" data-content-form>
            <h3 class="ui header" id="accessibility-appearance-title"><?= __($langGroup, 'Apariencia'); ?></h3>
            <div class="two fields">
                <div class="field">
                    <label for="accessibility-theme-mode"><?= __($langGroup, 'Modo de color por omisión'); ?></label>
                    <select class="ui dropdown" id="accessibility-theme-mode" name="accessibility_theme_mode" accessibility-select>
                        <?php foreach ($themeModeOptions as $modeValue => $modeName): ?>
                        <option value="<?= escape_html($modeValue); ?>"<?= $modeValue === $themeMode ? ' selected' : ''; ?>><?= $modeName; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="ui divider"></div>

            <h3 class="ui header" id="accessibility-accessibility-title"><?= __($langGroup, 'Accesibilidad'); ?></h3>
            <div class="two fields">
                <div class="field">
                    <label for="accessibility-text-size"><?= __($langGroup, 'Tamaño de letra por omisión'); ?></label>
                    <select class="ui dropdown" id="accessibility-text-size" name="accessibility_text_size" aria-describedby="accessibility-text-size-help" accessibility-select>
                        <?php foreach ($textSizeOptions as $sizeValue => $sizeName): ?>
                        <option value="<?= escape_html($sizeValue); ?>"<?= $sizeValue === $textSize ? ' selected' : ''; ?>><?= $sizeName; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="ui muted text" id="accessibility-text-size-help"><?= __($langGroup, 'Grande y Más grande suman 2 y 4 píxeles al tamaño base de Colores.'); ?></small>
                </div>
                <?php [$selectId, $selectLabel, $selectOptions, $selectValue] = $preferenceSelects['accessibility_contrast']; ?>
                <div class="field">
                    <label for="<?= $selectId; ?>"><?= $selectLabel; ?></label>
                    <select class="ui dropdown" id="<?= $selectId; ?>" name="accessibility_contrast" accessibility-select>
                        <?php foreach ($selectOptions as $optionValue => $optionName): ?>
                        <option value="<?= escape_html($optionValue); ?>"<?= $optionValue === $selectValue ? ' selected' : ''; ?>><?= $optionName; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="two fields">
                <?php foreach (['accessibility_motion', 'accessibility_focus'] as $selectName): ?>
                <?php [$selectId, $selectLabel, $selectOptions, $selectValue] = $preferenceSelects[$selectName]; ?>
                <div class="field">
                    <label for="<?= $selectId; ?>"><?= $selectLabel; ?></label>
                    <select class="ui dropdown" id="<?= $selectId; ?>" name="<?= $selectName; ?>" accessibility-select>
                        <?php foreach ($selectOptions as $optionValue => $optionName): ?>
                        <option value="<?= escape_html($optionValue); ?>"<?= $optionValue === $selectValue ? ' selected' : ''; ?>><?= $optionName; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endforeach; ?>
            </div>
            <?php
                //Como en Colores: Cancelar vuelve a los valores cargados, no sale de la pantalla.
                $helpController->_render('panel/built-in/content/form-action-bar.php', [
                    'saveText' => __($langGroup, 'Guardar'),
                    'secondaryActions' => [['text' => __($langGroup, 'Cancelar'), 'type' => 'button', 'attributes' => ['accessibility-cancel' => '']]],
                ]);
            ?>
        </form>

    </div>

</section>
