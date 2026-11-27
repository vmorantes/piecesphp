<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * La vista previa de Imágenes de marca (H416): cada sitio que pinta una imagen de marca, en claro y en oscuro, con lo
 * GUARDADO y con el marcado de ese sitio (sus vistas o sus helpers), dentro de `<iframe srcdoc>` como «Estilo del panel»
 * (SettingsController::themePreviewDocument(); theme-preview.js escala). La pinta logos-favicons.php y, sola, la ruta de
 * la pantalla con `?preview=yes`, que logos-favicons.js pide al guardar una imagen. La lista sale del censo de lectores
 * (brandImageTag(), developerLogoTag(), loaderHtmlAttributes(), mailing_logo() y los favicon): un sitio nuevo, aquí.
 *
 * @var string $langGroup
 */
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Settings\SettingsRoutes;

//Una vista del sitio tal como la pinta él, en su propio ámbito: sus variables no pisan las de esta (ni `$mode` de render()).
$siteView = static function (string $pcsPreviewFile, array $pcsPreviewVariables = []): string {
    extract($pcsPreviewVariables);
    ob_start();
    try {
        require $pcsPreviewFile;
    } catch (\Throwable $pcsPreviewError) {
        ob_end_clean();
        throw $pcsPreviewError;
    }
    return (string) ob_get_clean();
};
//Un documento propio (la página de error) con el modo fijo de cada muestra y la Accesibilidad del SITIO: su <html> trae
//las preferencias de quien la mira (htmlPreferenceAttributes()), todas en data-*, y se cambian por themePreviewAttributes().
$withTheme = static fn (string $document, string $theme): string => (string) preg_replace_callback('/<html\b[^>]*>/i', fn (array $html): string => rtrim((string) preg_replace('/\sdata-[a-z0-9-]+="[^"]*"/i', '', substr($html[0], 0, -1)))
    . ' data-theme="' . $theme . '"' . SettingsController::themePreviewAttributes() . '>', $document, 1);
$panelShell = static fn (string $navigation, string $classes = ''): string => '<div class="ui ' . $classes . ' shell admin-shell">' . $navigation . '<div class="main"></div></div>';
$menuHtml = $siteView(app_basepath('view/panel/layout/menu.php'));
$titleApp = escape_html((string) get_config('title_app'));
$favicons = [
    add_cache_stamp_to_url((string) get_config('favicon-back')),
    add_cache_stamp_to_url((string) get_config('favicon')),
];
$scripts = '<script src="' . escape_html(add_cache_stamp_to_url('statics/core/js/helpers-lib/GenericLoaderElement.js')) . '"></script><script src="' . escape_html(add_cache_stamp_to_url(SettingsRoutes::staticRoute('js/brand-preview-loader.js'))) . '"></script>';
$accessHead = SettingsController::accessPreviewHead();
$accessBackgrounds = get_config('backgrounds');
$accessHtml = $siteView(app_basepath('classes/PiecesPHP/UserSystem/Views/usuarios/inc/access-frame.php'), [
    'accessContent' => '',
    'accessBackgrounds' => is_array($accessBackgrounds) ? $accessBackgrounds : [],
]);
$errorHtml = $siteView(app_basepath('view/pages/404.php'));
$mailHtml = $siteView(app_basepath('view/mailing/template_base.php'), [
    'header_image' => base_url(mailing_logo()),
]);

/**
 * Cada sitio: su título, qué enseña y sus muestras. Cada muestra: rótulo, su documento por modo y cómo la mide
 * theme-preview.js (`data-preview-*`); `wide`, su ancho en la rejilla si el sitio tiene dos; `scripts`, sin sandbox.
 *
 * @var array<string,array{title:string,description:string,frames:array<string,array{caption:string,document:\Closure(string):string,attributes:array<string,string>,wide?:string,scripts?:bool}>}> $sites
 */
$sites = [
    'menu' => [
        'title' => __($langGroup, 'Menú lateral'),
        'description' => __($langGroup, 'Expandido y contraído: el logo arriba; abajo, el texto y las banderas de los patrocinadores y el logo del desarrollador.'),
        'frames' => [
            'menu-expanded' => [
                'caption' => __($langGroup, 'Expandido'),
                'document' => fn (string $theme): string => SettingsController::themePreviewDocument($theme, $panelShell($menuHtml)),
                'attributes' => ['data-preview-width' => '1280', 'data-preview-height' => '720', 'data-preview-crop' => '.navigation'],
                'wide' => 'eleven',
            ],
            'menu-collapsed' => [
                'caption' => __($langGroup, 'Contraído'),
                'document' => fn (string $theme): string => SettingsController::themePreviewDocument($theme, $panelShell($menuHtml, 'collapsed')),
                //Las banderas van al final del menú, que desplaza: la muestra lo enseña desplazado hasta abajo.
                'attributes' => ['data-preview-width' => '1280', 'data-preview-height' => '720', 'data-preview-crop' => '.navigation', 'data-preview-scroll' => '.navigation > .ui.menu'],
                'wide' => 'five',
            ],
        ],
    ],
    'mobile-bar' => [
        'title' => __($langGroup, 'Barra del móvil'),
        'description' => __($langGroup, 'En el móvil el menú es un cajón: el logo va en la barra de arriba.'),
        'frames' => [
            'mobile-bar' => [
                'caption' => __($langGroup, 'Barra del móvil'),
                //El parcial real de la barra (panel/layout/header-bar.php, el mismo que pinta header.php), sin buscador ni derecha.
                'document' => fn (string $theme): string => SettingsController::themePreviewDocument($theme, '<div class="ui shell admin-shell"><div class="main">' . $siteView(app_basepath('view/panel/layout/header-bar.php'), ['noTopBar' => true]) . '</div></div>'),
                'attributes' => ['data-preview-width' => '390', 'data-preview-crop' => 'header.bar'],
            ],
        ],
    ],
    'access' => [
        'title' => __($langGroup, 'Inicio de sesión'),
        'description' => __($langGroup, 'La columna de la marca: el logo sobre el fondo con su velo, y el recuadro de los apoyos con la bandera horizontal y el logo del desarrollador. Lo mismo en sus páginas de ayuda.'),
        'frames' => [
            'access' => [
                'caption' => __($langGroup, 'Inicio de sesión'),
                'document' => fn (string $theme): string => SettingsController::themePreviewDocument($theme, $accessHtml, $accessHead),
                'attributes' => ['data-preview-width' => '1024', 'data-preview-height' => '680', 'data-preview-crop' => '.access-brand', 'data-preview-access' => 'yes'],
            ],
        ],
    ],
    'loader' => [
        'title' => __($langGroup, 'Cargador'),
        'description' => __($langGroup, 'Mientras carga una pantalla, en el panel y en el sitio público: la animación de carga y, debajo, el logo del cargador.'),
        'frames' => [
            'loader' => [
                'caption' => __($langGroup, 'Cargador'),
                //El cargador lo construye su propio JavaScript con los data-loader-* del <html>: corre dentro de la muestra.
                'document' => fn (string $theme): string => SettingsController::themePreviewDocument($theme, $scripts),
                'attributes' => ['data-preview-width' => 'fluid', 'data-preview-height' => '320'],
                'scripts' => true,
            ],
        ],
    ],
    'error' => [
        'title' => __($langGroup, 'Página de error'),
        'description' => __($langGroup, 'La página de «no encontrada», «sin permiso» y «en mantenimiento», en el panel y en el sitio público: el logo arriba y los patrocinadores en el pie.'),
        'frames' => [
            'error' => [
                'caption' => __($langGroup, 'Página de error'),
                'document' => fn (string $theme): string => $withTheme($errorHtml, $theme),
                'attributes' => ['data-preview-width' => 'auto', 'data-preview-height' => '640'],
            ],
        ],
    ],
    'mail' => [
        'title' => __($langGroup, 'Encabezado de un correo'),
        'description' => __($langGroup, 'El de todos los correos que envía la plataforma. El correo no tiene modo oscuro propio: lo decide el programa de quien lo recibe, así que las dos muestras son iguales.'),
        'frames' => [
            'mail' => [
                'caption' => __($langGroup, 'Encabezado de un correo'),
                'document' => fn (string $theme): string => $mailHtml,
                'attributes' => ['data-preview-width' => '640', 'data-preview-fit' => '.header'],
            ],
        ],
    ],
    'browser-tab' => [
        'title' => __($langGroup, 'Pestaña del navegador'),
        'description' => __($langGroup, 'Una imitación: la pestaña la dibuja el navegador. El favicon junto al título: la primera pestaña es la del panel y el inicio de sesión; la segunda, la del sitio público y la página de error.'),
        'frames' => [
            'browser-tab' => [
                'caption' => __($langGroup, 'Pestaña del navegador'),
                //La pestaña la dibuja el navegador: aquí, las pestañas del tema con el favicon que declara la cabecera de cada página.
                'document' => fn (string $theme): string => SettingsController::themePreviewDocument($theme, '<div data-brand-preview-tabs><div class="ui top attached tabular menu">'
                    . '<span class="active item"><img src="' . escape_html($favicons[0]) . '" width="16" height="16" alt="">&nbsp;' . $titleApp . '</span>'
                    . '<span class="item"><img src="' . escape_html($favicons[1]) . '" width="16" height="16" alt="">&nbsp;' . $titleApp . '</span>'
                    . '</div><div class="ui bottom attached segment"></div></div>'),
                'attributes' => ['data-preview-width' => 'fluid', 'data-preview-fit' => '[data-brand-preview-tabs]'],
            ],
        ],
    ],
];
$themes = [
    'light' => [__($langGroup, 'Modo claro'), __($langGroup, '${title} en modo claro')],
    'dark' => [__($langGroup, 'Modo oscuro'), __($langGroup, '${title} en modo oscuro')],
];
$previewFrame = static function (string $frameKey, array $frame, string $theme, string $titleTemplate): string {
    $attributes = '';
    foreach ($frame['attributes'] as $attributeName => $attributeValue) {
        $attributes .= ' ' . $attributeName . '="' . escape_html($attributeValue) . '"';
    }
    //Sin scripts salvo el cargador: allow-same-origin deja a theme-preview.js medir el documento.
    $sandbox = ($frame['scripts'] ?? false) === true ? '' : ' sandbox="allow-same-origin"';
    $title = strReplaceTemplate($titleTemplate, ['${title}' => $frame['caption']]);
    return '<div class="theme-preview-frame"><iframe data-brand-preview="' . escape_html($frameKey) . '" data-theme-preview="' . $theme . '"' . $attributes . $sandbox
        . ' title="' . escape_html($title) . '" tabindex="-1" aria-hidden="true" width="100%" height="' . escape_html($frame['attributes']['data-preview-height'] ?? '160') . '" frameborder="0" srcdoc="' . escape_html(($frame['document'])($theme)) . '"></iframe></div>';
};
?>
<?php foreach ($sites as $siteKey => $site): ?>
<section class="ui basic fitted segment" aria-labelledby="<?= escape_html("brand-preview-{$siteKey}-title"); ?>" data-brand-preview-site="<?= escape_html($siteKey); ?>">
    <h3 class="ui small header" id="<?= escape_html("brand-preview-{$siteKey}-title"); ?>">
        <?= escape_html($site['title']); ?>
        <div class="sub header"><?= escape_html($site['description']); ?></div>
    </h3>
    <div class="ui two column stackable compact grid">
        <?php foreach ($themes as $theme => [$themeLabel, $titleTemplate]): ?>
        <div class="column">
            <?php if (count($site['frames']) === 1): ?>
            <span class="ui mini label"><?= escape_html($themeLabel); ?></span>
            <?php foreach ($site['frames'] as $frameKey => $frame): ?>
            <?= $previewFrame($frameKey, $frame, $theme, $titleTemplate); ?>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="ui grid">
                <?php //Suelta antes de la rejilla, su margen superior negativo la tapaba: va en su fila. ?>
                <div class="sixteen wide column">
                    <span class="ui mini label"><?= escape_html($themeLabel); ?></span>
                </div>
                <?php foreach ($site['frames'] as $frameKey => $frame): ?>
                <div class="<?= escape_html($frame['wide'] ?? 'eight'); ?> wide column">
                    <small class="ui muted text"><?= escape_html($frame['caption']); ?></small>
                    <?= $previewFrame($frameKey, $frame, $theme, $titleTemplate); ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endforeach; ?>
