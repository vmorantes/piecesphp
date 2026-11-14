<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * La pantalla Accesibilidad: el tamaño de letra y el modo de color de quien no ha elegido los suyos en el menú de su
 * nombre. Se guarda con Statics/js/accessibility.js.
 *
 * @var string $langGroup
 * @var string $actionURL
 * @var string $textSize Uno de AdminPanelController::TEXT_SIZES
 * @var string $themeMode Uno de AdminPanelController::THEME_MODES
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
                <div class="field">
                    <label for="accessibility-theme-mode"><?= __($langGroup, 'Modo de color por omisión'); ?></label>
                    <select class="ui dropdown" id="accessibility-theme-mode" name="accessibility_theme_mode" accessibility-select>
                        <?php foreach ($themeModeOptions as $modeValue => $modeName): ?>
                        <option value="<?= escape_html($modeValue); ?>"<?= $modeValue === $themeMode ? ' selected' : ''; ?>><?= $modeName; ?></option>
                        <?php endforeach; ?>
                    </select>
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
