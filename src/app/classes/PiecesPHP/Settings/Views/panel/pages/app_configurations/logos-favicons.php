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
 * @var string $partnersText
 * @var array<string,bool> $invertOnDark La casilla de cada imagen que el modo oscuro puede volver blanca.
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
?>
<section class="module-view-container logos-favicons-view" data-action="<?= escape_html($actionURL); ?>">

    <?php
        //Cada imagen se guarda al aplicar el editor: la barra de guardar solo la lleva el texto del patrocinador.
        $helpController->_render('panel/built-in/content/page-header.php', [
            'title' => $title,
            'description' => $description,
        ]);
    ?>

    <section class="ui segment" aria-labelledby="logos-own-title">
        <?php
            $helpController->_render('panel/built-in/content/section-header.php', [
                'id' => 'logos-own-title',
                'title' => __($langGroup, 'Propios'),
                'description' => __($langGroup, 'Imágenes de la plataforma del cliente.'),
            ]);
        ?>
        <div class="ui two stackable horizontal cards">
            <?php
                ($imageCard)([
                    'id' => 'image-logo',
                    'title' => __($langGroup, 'Logo'),
                    'description' => __($langGroup, 'Se usa en el inicio de sesión, el menú lateral y cualquier elemento que identifique la plataforma.'),
                    'image' => $logo,
                    'field' => 'logo',
                    'invertOnDark' => $invertOnDark['logo'],
                    'invertOnDarkSaved' => get_config(\PiecesPHP\Settings\Controllers\SettingsController::BRAND_IMAGES_INVERT_ON_DARK['logo']) !== false,
                    'width' => 400,
                    'height' => 400,
                ]);
                ($imageCard)([
                    'id' => 'image-favicon-back',
                    'title' => __($langGroup, 'Favicon zona administrativa'),
                    'description' => __($langGroup, 'Ícono de la pestaña del navegador en el área administrativa.'),
                    'image' => $backFavicon,
                    'field' => 'favicon-back',
                    'width' => 400,
                    'height' => 400,
                ]);
                ($imageCard)([
                    'id' => 'image-favicon',
                    'title' => __($langGroup, 'Favicon zona pública / sitio web'),
                    'description' => __($langGroup, 'Ícono de la pestaña en el sitio público. Vista previa sobre fondo oscuro para comprobar el contraste.'),
                    'image' => $publicFavicon,
                    'field' => 'favicon',
                    'width' => 400,
                    'height' => 400,
                    'dark' => true,
                ]);
                ($imageCard)([
                    'id' => 'image-mailing-logo',
                    'title' => __($langGroup, 'Correos electrónicos'),
                    'description' => __($langGroup, 'Encabezado de los correos que envía la plataforma.'),
                    'image' => $mailingLogo,
                    'field' => 'mailingLogo',
                    'width' => 500,
                    'height' => 172,
                ]);
            ?>
        </div>
    </section>

    <section class="ui segment" aria-labelledby="logos-third-title">
        <?php
            $helpController->_render('panel/built-in/content/section-header.php', [
                'id' => 'logos-third-title',
                'title' => __($langGroup, 'Terceros'),
                'description' => __($langGroup, 'Patrocinadores y aliados que aparecen en el menú lateral y en el inicio de sesión.'),
            ]);
        ?>
        <div class="ui two stackable horizontal cards">
            <?php
                ($imageCard)([
                    'id' => 'image-partners',
                    'title' => __($langGroup, 'Bandera horizontal'),
                    'description' => __($langGroup, 'Patrocinadores en el menú lateral expandido y en el inicio de sesión.'),
                    'image' => $partners,
                    'field' => 'partners',
                    'invertOnDark' => $invertOnDark['partners'],
                    'invertOnDarkSaved' => get_config(\PiecesPHP\Settings\Controllers\SettingsController::BRAND_IMAGES_INVERT_ON_DARK['partners']) !== false,
                    'width' => 280,
                    'height' => 50,
                ]);
                ($imageCard)([
                    'id' => 'image-partners-vertical',
                    'title' => __($langGroup, 'Bandera vertical'),
                    'description' => __($langGroup, 'Patrocinadores en el menú lateral contraído.'),
                    'image' => $partnersVertical,
                    'field' => 'partnersVertical',
                    'invertOnDark' => $invertOnDark['partnersVertical'],
                    'invertOnDarkSaved' => get_config(\PiecesPHP\Settings\Controllers\SettingsController::BRAND_IMAGES_INVERT_ON_DARK['partnersVertical']) !== false,
                    'width' => 50,
                    'height' => 280,
                ]);
            ?>
        </div>

        <div class="ui hidden divider"></div>

        <?php //El texto sobre el patrocinador (P258): opcional; vacío, el pie del menú no lo pinta. ?>
        <form action="<?= $actionURL; ?>" method="POST" class="ui form initial" partners-text-form data-content-form>
            <div class="field">
                <label for="partners-text"><?= __($langGroup, 'Texto sobre el patrocinador'); ?></label>
                <input type="text" id="partners-text" name="partnersText" maxlength="<?= (int) $partnersTextMaxLength; ?>" value="<?= escape_html($partnersText); ?>">
                <div class="description"><?= __($langGroup, 'Opcional: sale en el pie del menú, encima de la imagen del patrocinador. Vacío, no se pinta.'); ?></div>
            </div>
            <?php
                $helpController->_render('panel/built-in/content/form-action-bar.php', [
                    'saveText' => __($langGroup, 'Guardar'),
                ]);
            ?>
        </form>
    </section>

    <?php $helpController->_render('panel/built-in/content/image-editor.php'); ?>

</section>
