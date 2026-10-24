<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * La prueba del recortador: CropperAdapterComponent en un formulario y SimpleCropperAdapter en la página y en una ventana.
 * Su JavaScript, en Statics/js/test-cropper.js.
 *
 * @var string $langGroup
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController Pinta las piezas del panel
 */
$withImage = true;
?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
        $helpController->_render('panel/built-in/content/page-header.php', [
            'title' => __($langGroup, 'Prueba del recortador'),
            'description' => __($langGroup, 'Los dos recortadores del panel, en la página y en una ventana.'),
        ]);
        ?>

        <form class="ui form" test-cropper>
            <div class="ui form cropper-adapter" cropper-image-main data-edit="<?= $withImage ? 'yes' : 'no'; ?>">

                <div class="field required">
                    <label><?= __($langGroup, 'Imagen de prueba'); ?></label>
                    <input type="file" accept="image/*">
                </div>

                <?php cropperAdapterWorkSpace([
                    'referenceW' => '400',
                    'referenceH' => '300',
                    'image' => $withImage ? 'statics/images/logo.png' : '',
                ]); ?>

            </div>
            <br>
            <div class="field">
                <button type="submit" class="ui button brand-color"><?= __($langGroup, 'Probar'); ?></button>
            </div>
        </form>

        <?php simpleCropperAdapterWorkSpace([
            'selectorAttr' => 'simple-cropper',
            'referenceW' => '400',
            'referenceH' => '300',
            'image' => $withImage ? 'statics/images/logo.png' : '',
        ]); ?>

        <div class="ui modal cropper-test">
            <div class="content">
                <?php simpleCropperAdapterWorkSpace([
                    'selectorAttr' => 'simple-cropper-modal',
                    'referenceW' => '400',
                    'referenceH' => '300',
                    'image' => $withImage ? baseurl('img-gen/1920/1080') : '',
                ]); ?>
            </div>
        </div>

        <button type="button" class="ui basic button" open-modal><i class="window maximize outline icon"></i><?= __($langGroup, 'Abrir en una ventana'); ?></button>

    </div>

</section>
