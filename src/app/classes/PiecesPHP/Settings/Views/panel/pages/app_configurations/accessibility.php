<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * La pantalla Accesibilidad: lo que ve quien no ha elegido lo suyo en «Mi cuenta › Preferencias», en las dos tarjetas
 * de las de cada persona (Apariencia, y Lectura y movimiento; H354). Se guarda con Statics/js/accessibility.js.
 *
 * @var string $langGroup
 * @var string $actionURL
 * @var string $textSize Uno de AdminPanelController::TEXT_SIZES
 * @var string $themeMode Uno de AdminPanelController::THEME_MODES
 * @var string $contrast Uno de AdminPanelController::CONTRASTS
 * @var string $motion Uno de AdminPanelController::MOTIONS
 * @var string $focus Uno de AdminPanelController::FOCUSES
 * @var string $textSpacing Uno de AdminPanelController::TEXT_SPACINGS
 * @var string $transparency Uno de AdminPanelController::TRANSPARENCIES
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
    'accessibility_focus' => ['accessibility-focus', __($langGroup, 'Indicador de foco por omisión'), ['normal' => __($langGroup, 'Normal'), 'strong' => __($langGroup, 'Reforzado')], $focus],
    'accessibility_text_spacing' => ['accessibility-text-spacing', __($langGroup, 'Espaciado del texto por omisión'), ['normal' => __($langGroup, 'Normal'), 'wide' => __($langGroup, 'Amplio')], $textSpacing],
    'accessibility_transparency' => ['accessibility-transparency', __($langGroup, 'Transparencias por omisión'), ['auto' => __($langGroup, 'Como el sistema'), 'reduce' => __($langGroup, 'Reducir')], $transparency],
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

        <?php
            //Un desplegable de la pantalla: su campo, su etiqueta, sus opciones y el valor del sitio.
            $selectField = function (string $name) use ($preferenceSelects): void {
                [$selectId, $selectLabel, $selectOptions, $selectValue] = $preferenceSelects[$name];
            ?>
                <div class="field">
                    <label for="<?= $selectId; ?>"><?= $selectLabel; ?></label>
                    <select class="ui dropdown" id="<?= $selectId; ?>" name="<?= $name; ?>" accessibility-select>
                        <?php foreach ($selectOptions as $optionValue => $optionName): ?>
                        <option value="<?= escape_html($optionValue); ?>"<?= $optionValue === $selectValue ? ' selected' : ''; ?>><?= $optionName; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php
                    };
                ?>
        <form accessibility-form action="<?= escape_html($actionURL); ?>" method="POST" class="ui form" data-content-form>
            <div class="ui stackable two column grid">
                <div class="column">
                    <section class="ui segment" aria-labelledby="accessibility-appearance-title">
                        <h2 class="ui small header" id="accessibility-appearance-title"><?= __($langGroup, 'Apariencia'); ?></h2>
                        <div class="field">
                            <label for="accessibility-theme-mode"><?= __($langGroup, 'Modo de color por omisión'); ?></label>
                            <select class="ui dropdown" id="accessibility-theme-mode" name="accessibility_theme_mode" accessibility-select>
                                <?php foreach ($themeModeOptions as $modeValue => $modeName): ?>
                                <option value="<?= escape_html($modeValue); ?>"<?= $modeValue === $themeMode ? ' selected' : ''; ?>><?= $modeName; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="accessibility-text-size"><?= __($langGroup, 'Tamaño de letra por omisión'); ?></label>
                            <select class="ui dropdown" id="accessibility-text-size" name="accessibility_text_size" aria-describedby="accessibility-text-size-help" accessibility-select>
                                <?php foreach ($textSizeOptions as $sizeValue => $sizeName): ?>
                                <option value="<?= escape_html($sizeValue); ?>"<?= $sizeValue === $textSize ? ' selected' : ''; ?>><?= $sizeName; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="ui muted text" id="accessibility-text-size-help"><?= __($langGroup, 'Grande y Más grande suman 2 y 4 píxeles al tamaño base de Colores.'); ?></small>
                        </div>
                        <?php $selectField('accessibility_text_spacing'); ?>
                    </section>
                </div>
                <div class="column">
                    <section class="ui segment" aria-labelledby="accessibility-reading-title">
                        <h2 class="ui small header" id="accessibility-reading-title"><?= __($langGroup, 'Lectura y movimiento'); ?></h2>
                        <?php foreach (['accessibility_contrast', 'accessibility_motion', 'accessibility_transparency', 'accessibility_focus'] as $selectName): ?>
                        <?php $selectField($selectName); ?>
                        <?php endforeach; ?>
                    </section>
                </div>
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
