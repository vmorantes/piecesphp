<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\Settings\Controllers\SettingsController;

/**
 * El formulario de colores de Estilo del panel (style-panel.php), en dos pestañas: «Colores» y «Tipografía y forma». Es un
 * solo formulario: el Guardar de cualquiera de las dos guarda las dos. Lo ve en vivo Statics/js/colors.js, en la página y
 * en las dos muestras.
 *
 * @var string $langGroup
 * @var string $actionURL
 * @var string $accentColor
 * @var string $accentDarkColor '' = automático
 * @var string $secondColor
 * @var string $thirdColor
 * @var array<string,array{value:string,factory:string}> $seriesColors Por opción
 * @var string $browserBar
 * @var string $themeFont
 * @var int $themeFontSize
 * @var int $themeRadius
 * @var array<string,string|int> $resetColors Lo que envía «Restablecer» en Colores: lo guardado con lo de fábrica de la pestaña
 * @var array<string,string|int> $resetTypography Lo mismo en Tipografía y forma
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
$colorField = function (array $options) use ($helpController): void {
    $helpController->_render('panel/built-in/content/color-field.php', $options);
};
$logoURL = escape_html(get_config('logo'));
//La barra de cada pestaña: el aviso de cambios sin guardar en la otra (colors.js), Restablecer, Cancelar y Guardar.
$actionBar = function (string $title, array $values, array $items, string $otherTab) use ($helpController, $langGroup): void {
    echo '<div class="ui right aligned basic fitted segment" data-style-unsaved hidden aria-live="polite"><span class="ui orange text">' . escape_html(sprintf(__($langGroup, 'Hay cambios sin guardar en «%s».'), $otherTab)) . '</span></div>';
    $helpController->_render('panel/built-in/content/form-action-bar.php', [
        'saveText' => __($langGroup, 'Guardar colores y tipografía'),
        'secondaryActions' => [
            ['text' => __($langGroup, 'Restablecer'), 'type' => 'button', 'attributes' => ['style-reset' => (string) json_encode(['title' => $title, 'values' => $values, 'items' => $items], \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES)]],
            ['text' => __($langGroup, 'Cancelar'), 'type' => 'button', 'attributes' => ['colors-cancel' => '']],
        ],
    ]);
};
$withValue = fn (string $label, string $value): string => "{$label}: {$value}";
?>
<form colors-form action="<?= escape_html($actionURL); ?>" method="POST" class="ui form initial" data-content-form>

    <div class="ui active tab" data-tab="colores">
        <p><span class="ui muted text"><?= __($langGroup, 'El color del cliente se usa solo como acento (botones, selección, enlaces y foco). Los colores complementarios se usan únicamente en gráficas y datos.'); ?></span></p>
        <section class="ui segment" aria-labelledby="colors-accent-title">
            <h2 class="ui medium header" id="colors-accent-title">
                <?= __($langGroup, 'Color de acento'); ?>
                <div class="sub header"><?= __($langGroup, 'Un solo color de marca; los demás tonos se derivan de él.'); ?></div>
            </h2>
            <div class="field">
                <label for="colors-accent"><?= __($langGroup, 'Color de acento'); ?></label>
                <div class="ui top aligned very relaxed flex">
                    <?php //El único style= de la pantalla: es el dato de ui swatch, no maquetación. ?>
                    <div class="ui large inverted swatch" aria-hidden="true" style="--swatch: var(--accent)">
                        <div class="ui vertical fitted flex"><b><?= __($langGroup, 'Acento'); ?></b><small colors-accent-hero><?= escape_html($accentColor); ?></small></div>
                    </div>
                    <div class="fluid ui vertical left aligned flex">
                        <div class="inline field">
                            <?php $colorField(['id' => 'colors-accent', 'inputName' => 'main_brand_color', 'value' => $accentColor, 'attributes' => ['aria-describedby' => 'colors-accent-contrast']]); ?>
                            <span class="ui basic label" id="colors-accent-contrast" aria-live="polite"></span>
                        </div>
                        <small class="ui muted text" colors-accent-help></small>
                    </div>
                </div>
            </div>
            <div class="field">
                <label for="colors-accent-dark"><?= __($langGroup, 'Acento en modo oscuro'); ?></label>
                <div class="ui vertical left aligned flex">
                    <div class="inline field">
                        <?php //Vacío es automático: el servidor lo acepta solo en este campo, y el selector, solo aquí. ?>
                        <?php $colorField(['id' => 'colors-accent-dark', 'inputName' => 'main_brand_color_dark', 'value' => $accentDarkColor, 'attributes' => ['placeholder' => __($langGroup, 'Automático'), 'data-colorpicker-allow-empty' => 'true', 'aria-describedby' => 'colors-accent-dark-contrast']]); ?>
                        <button type="button" class="ui basic button" colors-accent-dark-auto><?= __($langGroup, 'Automático'); ?></button>
                        <span class="ui basic label" id="colors-accent-dark-contrast" aria-live="polite"></span>
                    </div>
                    <small class="ui muted text" colors-accent-dark-help></small>
                </div>
            </div>
            <div class="ui divider"></div>
            <div class="field">
                <label><?= __($langGroup, 'Tonos derivados'); ?> <span class="ui muted text">· <?= __($langGroup, 'automáticos, solo lectura'); ?></span></label>
                <div class="ui relaxed flex">
                    <?php //El title dice cómo sale cada tono en semantic.css (sobre --accent-1): cambia con el tema. ?>
                    <?php foreach ([
                            '--accent-100' => ['100', __($langGroup, '14 % sobre el fondo; en claro, con un mínimo de luminosidad')],
                            '--accent-300' => ['300', __($langGroup, '45 % sobre el fondo; en claro, con un mínimo de luminosidad')],
                            '--accent-600' => ['600', __($langGroup, 'El acento con la luminosidad acotada: como mucho 0,5 en claro; como poco 0,72 en oscuro, con el croma limitado')],
                            '--accent-50' => [__($langGroup, 'Tinte 8 %'), __($langGroup, 'Fondos activos · 8 % sobre el fondo; en claro, con un mínimo de luminosidad')],
                    ] as $toneVariable => [$toneLabel, $toneTitle]): ?>
                    <div class="ui vertical centered tight flex" title="<?= escape_html($toneTitle); ?>" data-colors-tone="<?= escape_html($toneVariable); ?>"><svg width="36" height="36" aria-hidden="true"><rect x=".5" y=".5" width="35" height="35" rx="6" fill="var(<?= $toneVariable; ?>)" stroke="var(--theme-border)"/></svg><small class="ui muted text"><?= $toneLabel; ?></small></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <section class="ui segment" aria-labelledby="colors-series-title">
            <h2 class="ui medium header" id="colors-series-title">
                <?= __($langGroup, 'Colores complementarios'); ?>
                <div class="sub header"><?= __($langGroup, 'Paleta de datos para gráficas: la serie 1 es siempre el acento.'); ?></div>
            </h2>
            <div class="field">
                <label><?= __($langGroup, 'Generar a partir del acento'); ?></label>
                <div class="ui very relaxed flex">
                    <?php foreach (['custom' => __($langGroup, 'Personalizados'), 'ana' => __($langGroup, 'Análogos'), 'tri' => __($langGroup, 'Tríada'), 'comp' => __($langGroup, 'Complementario')] as $harmonyValue => $harmonyLabel): ?>
                    <div class="ui radio checkbox"><input type="radio" id="colors-harmony-<?= $harmonyValue; ?>" name="harmony" value="<?= $harmonyValue; ?>"<?= $harmonyValue === 'custom' ? ' checked' : ''; ?>><label for="colors-harmony-<?= $harmonyValue; ?>"><?= $harmonyLabel; ?></label></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="field">
                <label><?= __($langGroup, 'Series'); ?></label>
                <div class="ui vertical left aligned flex">
                    <div class="inline field">
                        <?php $colorField(['id' => 'colors-series-1', 'value' => $accentColor, 'label' => __($langGroup, 'Serie 1'), 'disabled' => true]); ?>
                        <span class="ui label"><i class="lock icon" aria-hidden="true"></i><?= __($langGroup, 'Acento'); ?></span>
                    </div>
                    <?php foreach (SettingsController::DATA_TOKENS as $seriesOption): ?>
                    <?php $seriesNumber = substr($seriesOption, -1); ?>
                    <div class="inline field">
                        <?php $colorField([
                                'id' => "colors-series-{$seriesNumber}",
                                'inputName' => $seriesOption,
                                'value' => $seriesColors[$seriesOption]['value'],
                                'label' => __($langGroup, 'Serie') . " {$seriesNumber}",
                                'attributes' => ['data-series' => $seriesNumber, 'data-factory' => $seriesColors[$seriesOption]['factory']],
                        ]); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="field">
                <div class="ui vertical left aligned tight flex" colors-palette-warnings aria-live="polite"></div>
            </div>
            <div class="ui flex">
                <small class="ui muted text"><?= __($langGroup, 'Los colores de estado son fijos y no forman parte de la paleta:'); ?></small>
                <?php foreach (['green' => __($langGroup, 'Éxito'), 'red' => __($langGroup, 'Error'), 'yellow' => __($langGroup, 'Advertencia'), 'teal' => __($langGroup, 'Información')] as $stateColor => $stateLabel): ?>
                <span class="ui tight nowrap flex"><span class="ui mini <?= $stateColor; ?> swatch" aria-hidden="true"></span><small><?= $stateLabel; ?></small></span>
                <?php endforeach; ?>
            </div>
        </section>
        <section class="ui segment" aria-labelledby="colors-bar-title">
            <h2 class="ui medium header" id="colors-bar-title">
                <?= __($langGroup, 'Barra del navegador en móvil'); ?>
                <div class="sub header"><?= __($langGroup, 'Color de la barra superior del navegador en celulares (Chrome, Safari)'); ?></div>
            </h2>
            <div class="field">
                <div class="ui very relaxed flex">
                    <?php foreach ([SettingsController::BROWSER_BAR_ACCENT => __($langGroup, 'Usar acento'), SettingsController::BROWSER_BAR_DARK => __($langGroup, 'Neutro oscuro')] as $barValue => $barLabel): ?>
                    <div class="ui radio checkbox"><input type="radio" id="colors-bar-<?= $barValue; ?>" name="browser_bar" value="<?= $barValue; ?>"<?= $barValue === $browserBar ? ' checked' : ''; ?>><label for="colors-bar-<?= $barValue; ?>"><?= $barLabel; ?></label></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="ui very relaxed flex">
                <?php //Maqueta del teléfono, solo visual. ?>
                <svg width="150" height="214" viewBox="0 0 150 214" aria-hidden="true">
                    <rect width="150" height="214" rx="22" fill="<?= SettingsController::META_THEME_DARK; ?>"/>
                    <rect x="6" y="6" width="138" height="202" rx="16" fill="var(--theme-surface)"/>
                    <path colors-phone-bar d="M6 22 A16 16 0 0 1 22 6 H128 A16 16 0 0 1 144 22 V58 H6 Z" fill="var(--accent)"/>
                    <text x="14" y="23" fill="#fff" font-size="12" font-weight="600">9:41</text>
                    <rect x="124" y="17" width="3" height="4" rx="1" fill="#fff"/><rect x="129" y="15" width="3" height="6" rx="1" fill="#fff"/><rect x="134" y="13" width="3" height="8" rx="1" fill="#fff"/>
                    <rect x="14" y="31" width="122" height="19" rx="9.5" fill="#fff" fill-opacity=".18"/>
                    <rect x="21" y="38" width="7" height="6" rx="1" fill="#fff"/><path d="M22.5 38 V36.5 A2 2 0 0 1 26.5 36.5 V38" fill="none" stroke="#fff" stroke-width="1.2"/>
                    <text x="32" y="45" fill="#fff" font-size="12">proyecto.co</text>
                    <image href="<?= $logoURL; ?>" x="14" y="66" width="20" height="20"/>
                    <rect x="16" y="94" width="118" height="6" rx="3" fill="var(--theme-fill)"/>
                    <rect x="16" y="106" width="83" height="6" rx="3" fill="var(--theme-fill)"/>
                    <rect x="16" y="118" width="53" height="6" rx="3" fill="var(--theme-fill)"/>
                </svg>
                <div class="fluid ui vertical left aligned tight flex">
                    <span class="ui label" colors-bar-hex></span>
                    <small class="ui muted text"><?= __($langGroup, 'Se aplica como'); ?> <code>theme-color</code>.</small>
                </div>
            </div>
        </section>
        <section class="ui segment" aria-labelledby="colors-highlight-title">
            <h2 class="ui medium header" id="colors-highlight-title">
                <?= __($langGroup, 'Colores de realce'); ?>
                <div class="sub header"><?= __($langGroup, 'Para realces puntuales: el panel no los usa por su cuenta.'); ?></div>
            </h2>
            <div class="inline field"><?php $colorField(['id' => 'colors-second', 'inputName' => 'second_brand_color', 'value' => $secondColor, 'label' => __($langGroup, 'Acento 2'), 'attributes' => ['data-token' => '--accent-2']]); ?></div>
            <div class="inline field"><?php $colorField(['id' => 'colors-third', 'inputName' => 'third_brand_color', 'value' => $thirdColor, 'label' => __($langGroup, 'Acento 3'), 'attributes' => ['data-token' => '--accent-3']]); ?></div>
        </section>
        <?php
            $actionBar(__($langGroup, 'Restablecer los colores'), $resetColors, [
                $withValue(__($langGroup, 'Color de acento'), SettingsController::FACTORY_COLORS['main_brand_color']),
                $withValue(__($langGroup, 'Acento en modo oscuro'), __($langGroup, 'Automático')),
                __($langGroup, 'Series 2 a 5: las del tema'),
                $withValue(__($langGroup, 'Barra del navegador en móvil'), __($langGroup, 'Usar acento')),
                $withValue(__($langGroup, 'Acento 2'), SettingsController::FACTORY_COLORS['second_brand_color']),
                $withValue(__($langGroup, 'Acento 3'), SettingsController::FACTORY_COLORS['third_brand_color']),
            ], __($langGroup, 'Tipografía y forma'));
        ?>
    </div>

    <div class="ui tab" data-tab="tipografia">
        <section class="ui segment" aria-labelledby="colors-shape-title">
            <h2 class="ui medium header" id="colors-shape-title">
                <?= __($langGroup, 'Tipografía y forma'); ?>
                <div class="sub header"><?= __($langGroup, 'La fuente, el tamaño base y el radio de todo el panel.'); ?></div>
            </h2>
            <div class="three fields">
                <div class="field">
                    <label for="colors-font"><?= __($langGroup, 'Fuente'); ?></label>
                    <select class="ui dropdown" id="colors-font" name="theme_font" colors-select>
                        <?php foreach (SettingsController::THEME_FONTS as $fontKey => $font): ?>
                        <option value="<?= escape_html($fontKey); ?>" data-font-stack="<?= escape_html($font['stack']); ?>"<?= $fontKey === $themeFont ? ' selected' : ''; ?>><?= escape_html($font['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="colors-font-size"><?= __($langGroup, 'Tamaño base'); ?></label>
                    <select class="ui dropdown" id="colors-font-size" name="theme_font_size" colors-select>
                        <?php foreach (SettingsController::THEME_FONT_SIZES as $fontSize): ?>
                        <option value="<?= $fontSize; ?>"<?= $fontSize === $themeFontSize ? ' selected' : ''; ?>><?= $fontSize; ?> px</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="colors-radius"><?= __($langGroup, 'Radio'); ?></label>
                    <select class="ui dropdown" id="colors-radius" name="theme_radius" colors-select>
                        <?php foreach (SettingsController::THEME_RADII as $radius): ?>
                        <option value="<?= $radius; ?>"<?= $radius === $themeRadius ? ' selected' : ''; ?>><?= $radius; ?> px</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </section>
        <?php
            $actionBar(__($langGroup, 'Restablecer la tipografía y la forma'), $resetTypography, [
                $withValue(__($langGroup, 'Fuente'), SettingsController::THEME_FONTS[SettingsController::THEME_FONT_FACTORY]['name']),
                $withValue(__($langGroup, 'Tamaño base'), SettingsController::THEME_FONT_SIZE_FACTORY . ' px'),
                $withValue(__($langGroup, 'Radio'), SettingsController::THEME_RADIUS_FACTORY . ' px'),
            ], __($langGroup, 'Colores'));
        ?>
    </div>

</form>
