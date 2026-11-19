<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * Estilo del panel: Colores, Tipografía y forma, y Accesibilidad en pestañas del tema (la activa vive en el fragmento de
 * la dirección: #colores, #tipografia, #accesibilidad; Statics/js/style-panel.js), con la muestra en vivo debajo, en dos
 * iframe, claro y oscuro. Los formularios son los de siempre (inc/configuration-tabs/general.php y accessibility.php).
 *
 * @var string $langGroup
 * @var bool $accessibilityVisible Con el permiso de appearance-accessibility
 * @var string $previewAttributes Los del sitio en el <html> (Accesibilidad y el cargador), ya escapados y sin data-theme
 * @var string $title
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var array<string,mixed> $data Lo que recibe la vista; los dos formularios lo reciben entero
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
use PiecesPHP\Settings\Controllers\SettingsController;

//El logo de cada modo, como el menú (brandImageTag()): el data-theme de cada muestra es fijo, así que va el de ese modo.
$logoDarkView = SettingsController::brandImageDarkView('logo');
$logos = [
    'light' => [SettingsController::brandImagePath('logo'), ''],
    'dark' => [$logoDarkView['image'], $logoDarkView['mode'] === SettingsController::BRAND_IMAGE_DARK_INVERT ? ' class="invert-on-dark"' : ''],
];
$liveSeries = range(1, 5);
$previewSample = function (string $theme) use ($langGroup, $liveSeries, $logos): string {
    [$logoURL, $logoClass] = $logos[$theme];
    ob_start();
    ?>
<div class="ui stackable horizontal segments" inert aria-hidden="true" data-style-preview-content>
    <div class="ui compact segment">
        <div class="ui vertical relaxed flex">
            <svg width="28" height="28"<?= $logoClass; ?>><image href="<?= escape_html($logoURL); ?>" width="28" height="28"/></svg>
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
                                <defs><linearGradient id="colors-live-area" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="var(--accent-1, var(--accent))" stop-opacity=".28"/><stop offset="1" stop-color="var(--accent-1, var(--accent))" stop-opacity=".02"/></linearGradient></defs>
                                <path d="M0 44 C30 30 50 38 80 26 S130 34 160 18 S210 22 240 10 V60 H0Z" fill="url(#colors-live-area)"/>
                                <path d="M0 44 C30 30 50 38 80 26 S130 34 160 18 S210 22 240 10" fill="none" stroke="var(--accent-1, var(--accent))" stroke-width="2" vector-effect="non-scaling-stroke"/>
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
return (string) ob_get_clean();
};
//Las mismas hojas que esta página: el tema no tiene modo por contenedor, así que cada modo es un documento entero.
ob_start();
load_font();
load_css([
    'base_url' => '',
    'custom_url' => '',
]);
$previewHead = (string) ob_get_clean();
$previewDocument = fn (string $theme): string => '<!DOCTYPE html><html lang="' . escape_html((string) get_config('app_lang')) . '" data-theme="' . $theme . '"' . $previewAttributes . '>'
    . '<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><base href="' . escape_html(baseurl()) . '">' . $previewHead . '</head>'
    . '<body>' . $previewSample($theme) . '</body></html>';
$previews = [
    'light' => [__($langGroup, 'Modo claro'), __($langGroup, 'Muestra en modo claro')],
    'dark' => [__($langGroup, 'Modo oscuro'), __($langGroup, 'Muestra en modo oscuro')],
];
?>
<section class="module-view-container style-panel-view" data-style-panel data-reset-intro="<?= escape_html(__($langGroup, 'Vuelve a los valores de fábrica y se guarda al momento:')); ?>" data-reset-confirm="<?= escape_html(__($langGroup, 'Restablecer')); ?>" data-reset-cancel="<?= escape_html(__($langGroup, 'Cancelar')); ?>" data-reset-lost="<?= escape_html(__($langGroup, 'Los cambios sin guardar de esta pantalla se pierden.')); ?>" data-reset-error="<?= escape_html(__($langGroup, 'Ha ocurrido un error desconocido, intente más tarde.')); ?>">

    <div class="limiter-content">

        <?php
            $helpController->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
                'description' => $description,
            ]);
        ?>

        <div class="ui secondary pointing menu tabs-controls content-tabs" data-style-panel-tabs>
            <a class="active item" data-tab="colores"><i class="fill drip icon" aria-hidden="true"></i><?= __($langGroup, 'Colores'); ?></a>
            <a class="item" data-tab="tipografia"><i class="font icon" aria-hidden="true"></i><?= __($langGroup, 'Tipografía y forma'); ?></a>
            <?php if ($accessibilityVisible): ?>
            <a class="item" data-tab="accesibilidad"><i class="universal access icon" aria-hidden="true"></i><?= __($langGroup, 'Accesibilidad'); ?></a>
            <?php endif; ?>
        </div>

        <?php $this->render('panel/pages/app_configurations/inc/configuration-tabs/general', $data); ?>
        <?php if ($accessibilityVisible): ?>
        <div class="ui tab" data-tab="accesibilidad">
            <?php $this->render('panel/pages/app_configurations/accessibility', $data); ?>
        </div>
        <?php endif; ?>

        <section class="ui basic fitted segment" aria-labelledby="style-panel-live-title">
            <h2 class="ui medium header" id="style-panel-live-title">
                <?= __($langGroup, 'Muestra en vivo'); ?>
                <div class="sub header"><?= __($langGroup, 'Se actualiza al momento con lo que cambias en las tres pestañas.'); ?></div>
            </h2>
            <?php //compact: más ancho de columna, y la muestra (un panel de escritorio, escalado por style-panel.js) se lee mejor. ?>
            <div class="ui two column stackable compact grid">
                <?php foreach ($previews as $previewTheme => [$previewLabel, $previewTitle]): ?>
                <div class="column">
                    <h3 class="ui tiny header"><?= $previewLabel; ?></h3>
                    <div class="style-preview-frame" data-style-preview-frame>
                        <iframe data-style-preview="<?= $previewTheme; ?>" title="<?= escape_html($previewTitle); ?>" tabindex="-1" aria-hidden="true" width="100%" height="400" frameborder="0" srcdoc="<?= escape_html($previewDocument($previewTheme)); ?>"></iframe>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

    </div>

</section>
