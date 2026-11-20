<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $title
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 * @var string $actionURL
 * @var string $logo
 * @var string $backFavicon
 * @var string $publicFavicon
 * @var string $mailingLogo
 * @var string $partners
 * @var string $partnersVertical
 * @var string $loaderAnimation La animación de carga (H345), la subida o la de fábrica
 * @var string $loaderLogo El «Logo del cargador» (H345): de fábrica, loader-logo.png; vacío, guardado vacío por el «Eliminar» de antes
 * @var bool $canUploadSvg Si el usuario puede subir una animación SVG (SettingsController::ROLES_SVG_BRAND_IMAGES); si no, solo GIF
 * @var array{width:int,height:int}|null $loaderAnimationSize Su medida real, para pintarla con su proporción (no la declara)
 * @var string $loaderScreen La pantalla de carga de escritorio (H417): la subida o la de fábrica (SVG animada); vacía, guardada vacía por el «Eliminar» de antes
 * @var array{width:int,height:int}|null $loaderScreenSize
 * @var string $loaderScreenMobile La de móvil en vertical; vacía, el cargador usa la de escritorio recortada
 * @var array{width:int,height:int}|null $loaderScreenMobileSize
 * @var array<string,bool> $loaderLayers Capa => si está encendida (SettingsController::LOADER_LAYERS_ENABLED)
 * @var bool $loaderScreenAvailable Si hay una pantalla de carga que enseñar (SettingsController::loaderScreenAvailable())
 * @var array<string,string> $partnersTexts Idioma => lo que muestra su campo
 * @var array<string,array{mode:string,saved:bool,onReplace:string,image:string,factoryDark:bool,field:string,view:array{mode:string,image:string},fallbackMode:string}> $darkModes
 * Lo que enseña cada imagen de marca en modo oscuro (H340): el modo elegido, si está guardado, el que toma al subir una
 * propia sin guardarlo, la imagen oscura (vacía si no hay), si esa es la de fábrica (se va al subir una clara propia) y
 * el campo de su archivo.
 * @var int $partnersTextMaxLength
 */
//Se guardan como PNG, venga lo que venga: el texto de ayuda no promete SVG ni ICO como salida.
$transparentHint = __($langGroup, 'Preferiblemente con fondo transparente · Se guarda como PNG');
//La ruta de la pantalla con un parámetro: `&` si ya lleva consulta (como el regreso de la página de error).
$actionQuery = fn (string $query): string => $actionURL . (str_contains($actionURL, '?') ? '&' : '?') . $query;
$imageCard = function (array $card) use ($helpController, $actionURL, $transparentHint): void {
    $helpController->_render('panel/built-in/content/image-card.php', $card + [
        'hint' => $transparentHint,
        'accept' => 'image/*,.ico',
        'action' => $actionURL,
        'format' => 'image/png',
        'fillColor' => 'transparent',
    ]);
};
//La vista en modo oscuro de una imagen de marca (H340), dentro de su tarjeta: el selector «En modo oscuro» y la vista
//previa sobre superficie oscura, que es otra tarjeta del editor (la de la imagen oscura, con su recorte).
$darkModeContent = function (string $image, string $cardId, string $title, string $lightImage, int $width, int $height, bool $direct = false, string $emptyText = '', string $accept = '') use ($langGroup, $actionURL, $darkModes, $canUploadSvg): array {
    $dark = $darkModes[$image];
    $darkControlsHtml = '';
    $darkPreviewHtml = '';
    require __DIR__ . '/inc/brand-image-dark-mode.php';
    return ['extraContentHtml' => $darkControlsHtml, 'extraPreviewHtml' => $darkPreviewHtml];
};
//Cada tarjeta de esta pantalla: la variante con el texto arriba y las vistas previas debajo, del mismo tamaño.
$brandCard = function (array $card, ?array $darkMode = null) use ($imageCard, $langGroup): void {
    ($imageCard)($card + ($darkMode ?? []) + [
        'previewsBelow' => true,
        'previewCaption' => $darkMode !== null ? __($langGroup, 'Modo claro') : '',
    ]);
};
$section = function (string $id, string $sectionTitle, string $sectionDescription = '') use ($helpController): void {
    $helpController->_render('panel/built-in/content/section-header.php', [
        'id' => $id,
        'title' => $sectionTitle,
        'description' => $sectionDescription,
    ]);
};
?>
<section class="module-view-container logos-favicons-view" data-action="<?= escape_html($actionURL); ?>" data-text-error="<?= escape_html(__($langGroup, 'Ha ocurrido un error inesperado.')); ?>">

    <?php
        //Cada imagen se guarda al aplicar el editor: la barra de guardar solo la lleva el texto del patrocinador.
        $helpController->_render('panel/built-in/content/page-header.php', [
            'title' => $title,
            'description' => $description,
        ]);
    ?>

    <div class="ui secondary pointing menu tabs-controls content-tabs" data-brand-images-tabs>
        <a class="active item" data-tab="images"><i class="images icon" aria-hidden="true"></i><?= __($langGroup, 'Imágenes'); ?></a>
        <a class="item" data-tab="loader"><i class="spinner icon" aria-hidden="true"></i><?= __($langGroup, 'Cargador'); ?></a>
        <a class="item" data-tab="preview"><i class="eye icon" aria-hidden="true"></i><?= __($langGroup, 'Vista previa'); ?></a>
    </div>

    <div class="ui active tab" data-tab="images">

    <?php //Las imágenes propias, en un solo bloque y sin títulos de grupo: cada tarjeta ya dice qué imagen es. ?>
    <section class="ui segment" aria-label="<?= escape_html(__($langGroup, 'Imágenes de marca')); ?>">
        <div class="ui one cards">
            <?php
                ($brandCard)([
                    'id' => 'image-logo',
                    'title' => __($langGroup, 'Logo'),
                    'description' => __($langGroup, 'Se usa en el menú lateral, la barra del móvil, el inicio de sesión y sus páginas de ayuda, la página de error, el cargador, la vista previa de Colores y los datos para buscadores, y en cualquier lugar donde el proyecto necesite identificar la plataforma.'),
                    'image' => $logo,
                    'field' => 'logo',
                    'width' => 400,
                    'height' => 400,
                ], ($darkModeContent)('logo', 'image-logo', __($langGroup, 'Logo'), $logo, 400, 400));
            ?>
        </div>
        <div class="ui hidden divider"></div>
        <?php //Dos columnas de una tarjeta cada una, no una fila de tarjetas: la fila las estiraba al alto de la más alta y dejaba un hueco sobre las vistas previas. ?>
        <div class="ui two column stackable grid">
            <div class="column">
                <div class="ui one cards">
                    <?php
                        ($brandCard)([
                            'id' => 'image-favicon-back',
                            'title' => __($langGroup, 'Favicon zona administrativa'),
                            'description' => __($langGroup, 'Ícono de la pestaña del navegador en el panel, el inicio de sesión y sus páginas de ayuda.'),
                            'image' => $backFavicon,
                            'field' => 'favicon-back',
                            'width' => 400,
                            'height' => 400,
                        ]);
                    ?>
                </div>
            </div>
            <div class="column">
                <div class="ui one cards">
                    <?php
                        ($brandCard)([
                            'id' => 'image-favicon',
                            'title' => __($langGroup, 'Favicon zona pública / sitio web'),
                            'description' => __($langGroup, 'Ícono de la pestaña en el sitio público, la página de error y las páginas de los enlaces que se envían por correo. Vista previa sobre fondo oscuro para comprobar el contraste.'),
                            'image' => $publicFavicon,
                            'field' => 'favicon',
                            'width' => 400,
                            'height' => 400,
                            'dark' => true,
                        ]);
                    ?>
                </div>
            </div>
        </div>
        <div class="ui hidden divider"></div>
        <div class="ui one cards">
            <?php
                ($brandCard)([
                    'id' => 'image-mailing-logo',
                    'title' => __($langGroup, 'Correos electrónicos'),
                    'description' => __($langGroup, 'Encabezado de los correos que envía la plataforma y de sus muestras en la Hoja de diseño.'),
                    'image' => $mailingLogo,
                    'field' => 'mailingLogo',
                    'width' => 500,
                    'height' => 172,
                ]);
            ?>
        </div>
    </section>

    <section class="ui segment" aria-labelledby="logos-third-title">
        <?php ($section)('logos-third-title', __($langGroup, 'Terceros'), __($langGroup, 'Patrocinadores y aliados que aparecen en el menú lateral y en el inicio de sesión.')); ?>
        <div class="ui two column stackable grid">
            <div class="column">
                <div class="ui one cards">
                    <?php
                        ($brandCard)([
                            'id' => 'image-partners',
                            'title' => __($langGroup, 'Bandera horizontal'),
                            'description' => __($langGroup, 'Patrocinadores en el pie del menú lateral expandido y en el inicio de sesión y sus páginas de ayuda (en el móvil, bajo el formulario).'),
                            'image' => $partners,
                            'field' => 'partners',
                            'width' => 280,
                            'height' => 50,
                        ], ($darkModeContent)('partners', 'image-partners', __($langGroup, 'Bandera horizontal'), $partners, 280, 50));
                    ?>
                </div>
            </div>
            <div class="column">
                <div class="ui one cards">
                    <?php
                        ($brandCard)([
                            'id' => 'image-partners-vertical',
                            'title' => __($langGroup, 'Bandera vertical'),
                            'description' => __($langGroup, 'Patrocinadores en el pie del menú lateral contraído.'),
                            'image' => $partnersVertical,
                            'field' => 'partnersVertical',
                            'width' => 50,
                            'height' => 280,
                        ], ($darkModeContent)('partnersVertical', 'image-partners-vertical', __($langGroup, 'Bandera vertical'), $partnersVertical, 50, 280));
                    ?>
                </div>
            </div>
        </div>

        <div class="ui hidden divider"></div>

        <?php //El texto sobre el patrocinador (P258), uno por idioma activo (H225): vacío en el principal, el pie no lo pinta. ?>
        <form action="<?= $actionURL; ?>" method="POST" class="ui form initial" partners-text-form data-content-form>
            <?php $partnersMultilang = count($partnersTexts) > 1; ?>
            <?php foreach ($partnersTexts as $partnersLang => $partnersValue): ?>
            <?php $partnersIsDefault = $partnersLang === \PiecesPHP\Core\Config::get_default_lang(); ?>
            <div class="field">
                <label for="partners-text-<?= escape_html($partnersLang); ?>"><?= __($langGroup, 'Texto sobre el patrocinador'); ?><?= $partnersMultilang ? ' (' . escape_html(__('lang', $partnersLang)) . ')' : ''; ?></label>
                <input type="text" id="partners-text-<?= escape_html($partnersLang); ?>" name="partnersText[<?= escape_html($partnersLang); ?>]" maxlength="<?= (int) $partnersTextMaxLength; ?>" value="<?= escape_html($partnersValue); ?>">
                <div class="description"><?= $partnersIsDefault ? __($langGroup, 'Opcional: sale en el pie del menú, encima de la imagen del patrocinador. Vacío, no se pinta.') : __($langGroup, 'Vacío, se usa el del idioma principal.'); ?></div>
            </div>
            <?php endforeach; ?>
            <?php
                $helpController->_render('panel/built-in/content/form-action-bar.php', [
                    'saveText' => __($langGroup, 'Guardar'),
                ]);
            ?>
        </form>
    </section>

    </div>

    <div class="ui tab" data-tab="loader">

    <?php
        //Un interruptor por capa (LOADER_LAYERS_ENABLED): se guarda al cambiarlo y apagar no borra la imagen.
        $layerSwitches = [
            'animation' => [__($langGroup, 'Animación'), __($langGroup, 'La animación de carga.')],
            'logo' => [__($langGroup, 'Logo'), __($langGroup, 'El logo del cargador, bajo la animación.')],
            'screen' => [__($langGroup, 'Pantalla de carga'), __($langGroup, 'La de escritorio y la de móvil a la vez.')],
        ];
    ?>
    <section class="ui segment" aria-labelledby="loader-layers-title" data-loader-layers>
        <?php ($section)('loader-layers-title', __($langGroup, 'Qué enseña el cargador'), __($langGroup, 'Apaga lo que no quieras ver mientras carga una pantalla. Las imágenes no se borran: al encenderla de nuevo vuelve la misma. Con la animación, el logo y la pantalla apagados, el cargador enseña un aro.')); ?>
        <div class="ui form">
            <?php foreach ($layerSwitches as $layer => [$layerLabel, $layerHelp]): ?>
            <div class="field">
                <div class="ui toggle checkbox">
                    <input type="checkbox" id="loader-layer-<?= escape_html($layer); ?>" data-loader-layer="<?= escape_html($layer); ?>" aria-describedby="loader-layer-<?= escape_html($layer); ?>-help"<?= ($loaderLayers[$layer] ?? true) ? ' checked' : ''; ?>>
                    <label for="loader-layer-<?= escape_html($layer); ?>"><?= escape_html($layerLabel); ?></label>
                </div>
                <p class="ui muted text" id="loader-layer-<?= escape_html($layer); ?>-help"><?= escape_html($layerHelp); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="ui segment" aria-label="<?= escape_html(__($langGroup, 'Cargador')); ?>">
        <?php
            //La animación no declara medida: la real del archivo, para su proporción; sin poder leerla, cuadrada.
            $loaderAnimationWidth = $loaderAnimationSize['width'] ?? 120;
            $loaderAnimationHeight = $loaderAnimationSize['height'] ?? 120;
        ?>
        <div class="ui two column stackable grid">
            <div class="column">
                <div class="ui one cards">
                    <?php
                        ($brandCard)([
                            'id' => 'image-loader-animation',
                            'title' => __($langGroup, 'Animación de carga'),
                            'description' => __($langGroup, 'Se ve mientras carga una pantalla, en el panel y en el sitio público, sobre el logo del cargador. La apaga el interruptor «Animación», arriba.'),
                            'hint' => $canUploadSvg ? __($langGroup, 'Cualquier tamaño · recomendado: 360 px de alto (GIF) o SVG') : __($langGroup, 'Cualquier tamaño · recomendado: 360 px de alto · GIF'),
                            'accept' => $canUploadSvg ? 'image/gif,image/svg+xml,.gif,.svg' : 'image/gif,.gif',
                            'image' => $loaderAnimation,
                            'field' => 'loaderAnimation',
                            'directUpload' => true,
                            'showSize' => false,
                            'emptyText' => __($langGroup, 'Sin animación: el cargador enseña el logo o, sin logo, el aro'),
                            'width' => $loaderAnimationWidth,
                            'height' => $loaderAnimationHeight,
                        ], ($darkModeContent)('loaderAnimation', 'image-loader-animation', __($langGroup, 'Animación de carga'), $loaderAnimation, $loaderAnimationWidth, $loaderAnimationHeight, true, __($langGroup, 'Sin animación: el cargador enseña el logo o, sin logo, el aro')));
                    ?>
                </div>
            </div>
            <div class="column">
                <div class="ui one cards">
                    <?php
                        ($brandCard)([
                            'id' => 'image-loader-logo',
                            'title' => __($langGroup, 'Logo del cargador'),
                            'description' => __($langGroup, 'Se ve bajo la animación mientras carga una pantalla, en el panel y en el sitio público. Lo apaga el interruptor «Logo», arriba.'),
                            'image' => $loaderLogo,
                            'field' => 'loaderLogo',
                            'emptyText' => __($langGroup, 'Sin imagen: el cargador enseña solo la animación'),
                            'width' => 400,
                            'height' => 400,
                        ], ($darkModeContent)('loaderLogo', 'image-loader-logo', __($langGroup, 'Logo del cargador'), $loaderLogo, 400, 400, false, __($langGroup, 'Sin imagen: el cargador enseña solo la animación')));
                    ?>
                </div>
            </div>
        </div>
    </section>

    <?php
        //La pantalla de carga (H417): se sube tal cual; de fábrica, las SVG animadas de statics/images/. La medida, la real del archivo; sin poder leerla, la recomendada.
        $screenAccept = $canUploadSvg ? 'image/gif,image/png,image/jpeg,image/webp,image/svg+xml,.gif,.png,.jpg,.jpeg,.webp,.svg' : 'image/gif,image/png,image/jpeg,image/webp,.gif,.png,.jpg,.jpeg,.webp';
        $screenEmpty = __($langGroup, 'Sin pantalla de carga');
        $screenCards = [
            ['loaderScreen', 'image-loader-screen', __($langGroup, 'Pantalla de carga'), __($langGroup, 'Llena toda la pantalla detrás del cargador, en el panel y en el sitio público: en horizontal y, sin la de móvil, también en vertical, recortada al centro. La animación, el logo, el mensaje y el porcentaje van encima, en el centro: deja el centro limpio para que se lean. La apaga, con la de móvil, el interruptor «Pantalla de carga», arriba.'), $canUploadSvg ? __($langGroup, 'Recomendado: 1920 × 1080 px · hasta 3 MB · GIF, PNG, JPG, WebP o SVG') : __($langGroup, 'Recomendado: 1920 × 1080 px · hasta 3 MB · GIF, PNG, JPG o WebP'), $loaderScreen, $loaderScreenSize ?? ['width' => 1920, 'height' => 1080], $screenEmpty],
            ['loaderScreenMobile', 'image-loader-screen-mobile', __($langGroup, 'Pantalla de carga para móvil'), __($langGroup, 'La de la pantalla en vertical (un móvil). Sin ella, en vertical se usa la de escritorio, recortada al centro; la de móvil de fábrica solo acompaña a la de escritorio de fábrica.'), $canUploadSvg ? __($langGroup, 'Recomendado: 1080 × 1920 px · hasta 3 MB · GIF, PNG, JPG, WebP o SVG') : __($langGroup, 'Recomendado: 1080 × 1920 px · hasta 3 MB · GIF, PNG, JPG o WebP'), $loaderScreenMobile, $loaderScreenMobileSize ?? ['width' => 1080, 'height' => 1920], __($langGroup, 'Sin pantalla para móvil: en vertical se usa la de escritorio, recortada al centro')],
        ];
    ?>
    <section class="ui segment" aria-labelledby="loader-screen-title">
        <?php ($section)('loader-screen-title', __($langGroup, 'Pantalla de carga'), __($langGroup, 'Una imagen a pantalla completa mientras carga una pantalla, animada (GIF o SVG) o fija (PNG, JPG o WebP), con su versión para el modo oscuro.')); ?>
        <div class="ui two column stackable grid">
            <?php foreach ($screenCards as [$screenField, $screenId, $screenTitle, $screenDescription, $screenHint, $screenImage, $screenSize, $screenEmptyText]): ?>
            <div class="column">
                <div class="ui one cards">
                    <?php
                        ($brandCard)([
                            'id' => $screenId,
                            'title' => $screenTitle,
                            'description' => $screenDescription,
                            'hint' => $screenHint,
                            'accept' => $screenAccept,
                            'image' => $screenImage,
                            'field' => $screenField,
                            'directUpload' => true,
                            'showSize' => false,
                            'emptyText' => $screenEmptyText,
                            'width' => $screenSize['width'],
                            'height' => $screenSize['height'],
                        ], ($darkModeContent)($screenField, $screenId, $screenTitle, $screenImage, $screenSize['width'], $screenSize['height'], true, $screenEmptyText, $screenAccept));
                    ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <?php
        //El área de pruebas lanza el cargador con lo GUARDADO (`?loader=yes`); la pantalla, solo si hay una que enseñar.
        $hasScreen = $loaderScreenAvailable;
        $screenMotive = ($loaderLayers['screen'] ?? true) ? 'missing' : 'off';
        $playgroundTexts = [
            'close' => __($langGroup, 'Cerrar'),
            'dark' => __($langGroup, 'Ver en modo oscuro'),
            'light' => __($langGroup, 'Ver en modo claro'),
            'message' => __($langGroup, 'Mensaje de prueba: preparando la pantalla…'),
        ];
        $playgroundTests = [
            'current' => __($langGroup, 'El de hoy'),
            'no-screen' => __($langGroup, 'Sin la pantalla de carga'),
            'message' => __($langGroup, 'Con mensaje'),
            'percent' => __($langGroup, 'Con porcentaje'),
            'message-percent' => __($langGroup, 'Con mensaje y porcentaje'),
            'screen' => __($langGroup, 'Con la pantalla de carga'),
            'off' => __($langGroup, 'Todo apagado'),
        ];
    ?>
    <section class="ui segment" aria-labelledby="loader-playground-title" data-loader-playground data-loader-url="<?= escape_html(($actionQuery)('loader=yes')); ?>" data-texts="<?= escape_html((string) json_encode($playgroundTexts)); ?>">
        <?php ($section)('loader-playground-title', __($langGroup, 'Área de pruebas'), __($langGroup, 'Lanza el cargador a pantalla completa unos segundos, con lo guardado y en el modo del panel. «Cerrar» o Escape lo quitan antes; el botón de modo alterna claro y oscuro solo mientras dura, sin cambiar tu preferencia.')); ?>
        <div class="ui flex">
            <?php foreach ($playgroundTests as $testName => $testText): ?>
            <button class="ui basic button" type="button" data-loader-test="<?= escape_html($testName); ?>"<?= $testName === 'screen' && !$hasScreen ? ' disabled aria-describedby="loader-playground-screen-' . $screenMotive . '"' : ''; ?><?= $testName === 'no-screen' && !$hasScreen ? ' hidden' : ''; ?>><?= escape_html($testText); ?></button>
            <?php endforeach; ?>
        </div>
        <p class="ui muted text" id="loader-playground-screen-missing" data-loader-screen-missing<?= $hasScreen || $screenMotive !== 'missing' ? ' hidden' : ''; ?>><?= escape_html(__($langGroup, '«Con la pantalla de carga» necesita una pantalla de carga guardada: sube una arriba.')); ?></p>
        <p class="ui muted text" id="loader-playground-screen-off" data-loader-screen-off<?= $hasScreen || $screenMotive !== 'off' ? ' hidden' : ''; ?>><?= escape_html(__($langGroup, '«Con la pantalla de carga» necesita el interruptor «Pantalla de carga» encendido, arriba.')); ?></p>
    </section>

    </div>

    <div class="ui tab" data-tab="preview">

    <section class="ui basic fitted segment" aria-labelledby="brand-previews-title">
        <h2 class="ui medium header" id="brand-previews-title">
            <?= __($langGroup, 'Vista previa'); ?>
            <div class="sub header"><?= __($langGroup, 'Cada sitio donde se ven estas imágenes, con lo guardado, en modo claro y en modo oscuro. Se actualiza al guardar una imagen.'); ?></div>
        </h2>
        <?php //logos-favicons.js la vuelve a pedir a esta misma ruta (`?preview=yes`) al guardar una imagen. ?>
        <div data-brand-previews data-preview-url="<?= escape_html(($actionQuery)('preview=yes')); ?>">
            <?php $this->render('panel/pages/app_configurations/inc/brand-previews', ['langGroup' => $langGroup]); ?>
        </div>
    </section>

    </div>

    <?php $helpController->_render('panel/built-in/content/image-editor.php'); ?>

</section>
