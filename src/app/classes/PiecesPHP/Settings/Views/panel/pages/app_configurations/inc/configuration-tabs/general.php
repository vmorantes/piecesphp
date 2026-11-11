<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\Settings\Controllers\SettingsController;

/**
 * La pantalla Colores: un solo formulario, con el marcado del escaparate del tema (piecesphp-colores). Todo se ve en
 * vivo (Statics/js/colors.js) y se guarda junto.
 *
 * @var string $langGroup
 * @var string $actionURL
 * @var string $accentColor
 * @var string $secondColor
 * @var string $thirdColor
 * @var array<string,array{value:string,factory:string}> $seriesColors Por opción
 * @var string $browserBar
 * @var string $themeFont
 * @var int $themeFontSize
 * @var int $themeRadius
 * @var string $title
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
$colorField = function (array $options) use ($helpController): void {
    $helpController->_render('panel/built-in/content/color-field.php', $options);
};
$logoURL = escape_html(get_config('logo'));
$liveSeries = range(1, 5);
?>
<section class="module-view-container colors-view">

    <div class="limiter-content">

        <?php
            $helpController->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
                'description' => $description,
            ]);
        ?>

        <form colors-form action="<?= escape_html($actionURL); ?>" method="POST" class="ui form initial" data-content-form>
            <div class="ui stackable grid">

                <div class="sixteen wide column">
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
                        <div class="ui divider"></div>
                        <div class="field">
                            <label><?= __($langGroup, 'Tonos derivados'); ?> <span class="ui muted text">· <?= __($langGroup, 'automáticos, solo lectura'); ?></span></label>
                            <div class="ui relaxed flex">
                                <?php //El title dice cómo sale cada tono (semantic.css: color-mix del acento sobre la superficie del tema). ?>
                                <?php foreach ([
                                        '--accent-100' => ['100', __($langGroup, '14 % sobre el fondo')],
                                        '--accent-300' => ['300', __($langGroup, '45 % sobre el fondo')],
                                        '--accent-600' => ['600', __($langGroup, '88 % sobre negro; 70 % sobre blanco en oscuro')],
                                        '--accent-50' => [__($langGroup, 'Tinte 8 %'), __($langGroup, 'Fondos activos · 8 % sobre el fondo')],
                                ] as $toneVariable => [$toneLabel, $toneTitle]): ?>
                                <div class="ui vertical centered tight flex" title="<?= escape_html($toneTitle); ?>" data-colors-tone="<?= escape_html($toneVariable); ?>"><svg width="36" height="36" aria-hidden="true"><rect x=".5" y=".5" width="35" height="35" rx="6" fill="var(<?= $toneVariable; ?>)" stroke="var(--theme-border)"/></svg><small class="ui muted text"><?= $toneLabel; ?></small></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="stretched row">
                    <div class="sixteen wide tablet eleven wide computer column">
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
                    </div>
                    <div class="sixteen wide tablet five wide computer column">
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
                    </div>
                </div>

                <div class="stretched row">
                    <div class="sixteen wide tablet eight wide computer column">
                        <section class="ui segment" aria-labelledby="colors-highlight-title">
                            <h2 class="ui medium header" id="colors-highlight-title">
                                <?= __($langGroup, 'Colores de realce'); ?>
                                <div class="sub header"><?= __($langGroup, 'Para realces puntuales: el panel no los usa por su cuenta.'); ?></div>
                            </h2>
                            <div class="inline field"><?php $colorField(['id' => 'colors-second', 'inputName' => 'second_brand_color', 'value' => $secondColor, 'label' => __($langGroup, 'Acento 2'), 'attributes' => ['data-token' => '--accent-2']]); ?></div>
                            <div class="inline field"><?php $colorField(['id' => 'colors-third', 'inputName' => 'third_brand_color', 'value' => $thirdColor, 'label' => __($langGroup, 'Acento 3'), 'attributes' => ['data-token' => '--accent-3']]); ?></div>
                        </section>
                    </div>
                    <div class="sixteen wide tablet eight wide computer column">
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
                    </div>
                </div>

                <div class="sixteen wide column">
                    <section class="ui segment" aria-labelledby="colors-live-title">
                        <h2 class="ui medium header" id="colors-live-title">
                            <?= __($langGroup, 'Muestra en vivo'); ?>
                            <div class="sub header"><?= __($langGroup, 'Así se verán el acento y la paleta en el panel.'); ?></div>
                        </h2>
                        <div class="ui stackable horizontal segments" inert aria-hidden="true">
                            <div class="ui compact segment">
                                <div class="ui vertical relaxed flex">
                                    <svg width="28" height="28"><image href="<?= $logoURL; ?>" width="28" height="28"/></svg>
                                    <div class="ui secondary vertical menu">
                                        <span class="active item"><i class="home icon"></i><?= __($langGroup, 'Inicio'); ?></span>
                                        <span class="item"><i class="users icon"></i><?= __($langGroup, 'Usuarios'); ?></span>
                                        <span class="item"><i class="chart bar icon"></i><?= __($langGroup, 'Reportes'); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="ui segment">
                                <div class="ui two column stackable grid">
                                    <div class="stretched row">
                                        <div class="column">
                                            <div class="ui vertical very relaxed flex">
                                                <div class="ui secondary pointing menu"><span class="active item"><?= __($langGroup, 'Resumen'); ?></span><span class="item"><?= __($langGroup, 'Detalle'); ?></span></div>
                                                <div class="ui relaxed flex">
                                                    <button type="button" class="ui primary button" tabindex="-1"><?= __($langGroup, 'Ejemplo'); ?></button>
                                                    <div class="ui checked checkbox"><input type="checkbox" checked tabindex="-1" id="colors-live-active"><label for="colors-live-active"><?= __($langGroup, 'Activo'); ?></label></div>
                                                </div>
                                                <div class="ui small primary progress" data-percent="64"><div class="bar"></div></div>
                                                <div class="fluid ui tall chart">
                                                    <svg class="canvas" width="100%" height="100%" viewBox="0 0 240 60" preserveAspectRatio="none">
                                                        <defs><linearGradient id="colors-live-area" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="var(--accent)" stop-opacity=".28"/><stop offset="1" stop-color="var(--accent)" stop-opacity=".02"/></linearGradient></defs>
                                                        <path d="M0 44 C30 30 50 38 80 26 S130 34 160 18 S210 22 240 10 V60 H0Z" fill="url(#colors-live-area)"/>
                                                        <path d="M0 44 C30 30 50 38 80 26 S130 34 160 18 S210 22 240 10" fill="none" stroke="var(--accent)" stroke-width="2" vector-effect="non-scaling-stroke"/>
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="column">
                                            <div class="ui vertical very relaxed flex">
                                                <div class="fluid ui very tall chart">
                                                    <svg class="canvas" width="100%" height="100%" viewBox="0 0 240 120" preserveAspectRatio="none">
                                                        <line x1="8" y1="110.5" x2="232" y2="110.5" stroke="var(--theme-border)"/>
                                                        <rect x="18" y="28" width="28" height="82" rx="4" fill="var(--data-1)"/>
                                                        <rect x="62" y="46" width="28" height="64" rx="4" fill="var(--data-2)"/>
                                                        <rect x="106" y="18" width="28" height="92" rx="4" fill="var(--data-3)"/>
                                                        <rect x="150" y="62" width="28" height="48" rx="4" fill="var(--data-4)"/>
                                                        <rect x="194" y="40" width="28" height="70" rx="4" fill="var(--data-5)"/>
                                                    </svg>
                                                </div>
                                                <div class="ui relaxed flex">
                                                    <?php foreach ($liveSeries as $liveSeriesNumber): ?>
                                                    <span class="ui tight nowrap flex"><svg width="8" height="8" aria-hidden="true"><rect width="8" height="8" rx="2" fill="var(--data-<?= $liveSeriesNumber; ?>)"/></svg><small><?= __($langGroup, 'Serie'); ?> <?= $liveSeriesNumber; ?></small></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                            //Las acciones son del formulario completo. Cancelar vuelve a los valores cargados, no sale de la pantalla.
                            $helpController->_render('panel/built-in/content/form-action-bar.php', [
                                'saveText' => __($langGroup, 'Guardar'),
                                'secondaryActions' => [['text' => __($langGroup, 'Cancelar'), 'type' => 'button', 'attributes' => ['colors-cancel' => '']]],
                            ]);
                        ?>
                    </section>
                </div>

            </div>
        </form>

    </div>

</section>
