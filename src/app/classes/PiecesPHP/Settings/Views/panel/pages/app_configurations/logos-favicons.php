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
 * @var string $loaderLogo El «Logo del cargador» (H345): de fábrica, loader-logo.png; vacío, apagado por «Eliminar»
 * @var bool $canUploadSvg Si el usuario puede subir una animación SVG (SettingsController::ROLES_SVG_BRAND_IMAGES); si no, solo GIF
 * @var array{width:int,height:int}|null $loaderAnimationSize Su medida real, para pintarla con su proporción (no la declara)
 * @var array<string,string> $partnersTexts Idioma => lo que muestra su campo
 * @var array<string,array{mode:string,saved:bool,onReplace:string,image:string,factoryDark:bool,field:string,view:array{mode:string,image:string},fallbackMode:string}> $darkModes
 * Lo que enseña cada imagen de marca en modo oscuro (H340): el modo elegido, si está guardado, el que toma al subir una
 * propia sin guardarlo, la imagen oscura (vacía si no hay), si esa es la de fábrica (se va al subir una clara propia) y
 * el campo de su archivo.
 * @var int $partnersTextMaxLength
 */
//Se guardan como PNG, venga lo que venga: el texto de ayuda no promete SVG ni ICO como salida.
$transparentHint = __($langGroup, 'Preferiblemente con fondo transparente · Se guarda como PNG');
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
$darkModeContent = function (string $image, string $cardId, string $title, string $lightImage, int $width, int $height, bool $direct = false, string $emptyText = '') use ($langGroup, $actionURL, $darkModes, $canUploadSvg): array {
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
<?php //Los textos de «Eliminar» del logo del cargador: el JavaScript no lleva textos. ?>
<section class="module-view-container logos-favicons-view" data-action="<?= escape_html($actionURL); ?>" data-text-delete="<?= escape_html(__($langGroup, 'Eliminar')); ?>" data-text-delete-confirm="<?= escape_html(__($langGroup, '¿Quitar el logo del cargador? El cargador enseñará solo la animación.')); ?>" data-text-deleted="<?= escape_html(__($langGroup, 'Logo del cargador quitado.')); ?>" data-text-error="<?= escape_html(__($langGroup, 'Ha ocurrido un error inesperado.')); ?>">

    <?php
        //Cada imagen se guarda al aplicar el editor: la barra de guardar solo la lleva el texto del patrocinador.
        $helpController->_render('panel/built-in/content/page-header.php', [
            'title' => $title,
            'description' => $description,
        ]);
    ?>

    <?php //Las imágenes propias, en un solo bloque y sin títulos de grupo: cada tarjeta ya dice qué imagen es. ?>
    <section class="ui segment" aria-label="<?= escape_html(__($langGroup, 'Imágenes de marca')); ?>">
        <?php
            //La animación no declara medida: la real del archivo, para su proporción; sin poder leerla, cuadrada.
            $loaderAnimationWidth = $loaderAnimationSize['width'] ?? 120;
            $loaderAnimationHeight = $loaderAnimationSize['height'] ?? 120;
        ?>
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
        <div class="ui two stackable cards">
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
        <div class="ui hidden divider"></div>
        <div class="ui two stackable cards">
            <?php
                ($brandCard)([
                    'id' => 'image-loader-animation',
                    'title' => __($langGroup, 'Animación de carga'),
                    'description' => __($langGroup, 'Se ve mientras carga una pantalla, en el panel y en el sitio público, sobre el logo del cargador.'),
                    'hint' => $canUploadSvg ? __($langGroup, 'Cualquier tamaño · recomendado: 360 px de alto (GIF) o SVG') : __($langGroup, 'Cualquier tamaño · recomendado: 360 px de alto · GIF'),
                    'accept' => $canUploadSvg ? 'image/gif,image/svg+xml,.gif,.svg' : 'image/gif,.gif',
                    'image' => $loaderAnimation,
                    'field' => 'loaderAnimation',
                    'directUpload' => true,
                    'showSize' => false,
                    'width' => $loaderAnimationWidth,
                    'height' => $loaderAnimationHeight,
                ], ($darkModeContent)('loaderAnimation', 'image-loader-animation', __($langGroup, 'Animación de carga'), $loaderAnimation, $loaderAnimationWidth, $loaderAnimationHeight, true));
                ($brandCard)([
                    'id' => 'image-loader-logo',
                    'title' => __($langGroup, 'Logo del cargador'),
                    'description' => __($langGroup, 'Opcional: se ve bajo la animación mientras carga una pantalla, en el panel y en el sitio público. Vacío, el cargador enseña solo la animación.'),
                    'image' => $loaderLogo,
                    'field' => 'loaderLogo',
                    'emptyText' => __($langGroup, 'Sin imagen: el cargador enseña solo la animación'),
                    'deleteValue' => 'loaderLogo',
                    'width' => 400,
                    'height' => 400,
                ], ($darkModeContent)('loaderLogo', 'image-loader-logo', __($langGroup, 'Logo del cargador'), $loaderLogo, 400, 400, false, __($langGroup, 'Sin imagen: el cargador enseña solo la animación')));
            ?>
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
        <div class="ui two stackable cards">
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

    <?php $helpController->_render('panel/built-in/content/image-editor.php'); ?>

</section>
