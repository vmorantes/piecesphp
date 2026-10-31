<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
$BuiltInBannerConfiguration = get_config('BuiltInBannerConfiguration');
?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
            //La cabecera del panel (diseno/01-contenidos.md §2.1).
            $this->helpController->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
                'description' => $description ?? '',
            ]);
        ?>

        <div class="ui secondary pointing menu tabs-controls content-tabs">
            <div class="active item" data-tab="basic"><?= __($langGroup, 'Datos básicos'); ?></div>
        </div>

        <form method='POST' action="<?= $action; ?>" class="ui form initial built-in-banner" data-content-form>

            <div class="container-standard-form">

                <input type="hidden" name="lang" value="<?= \PiecesPHP\Core\Config::get_lang(); ?>">

                <div class="ui tab active" data-tab="basic">

                    <div class="two fields">
                        <?php
                            $this->helpController->_render('panel/built-in/content/image-field.php', [
                                'card' => [
                                    'id' => 'desktop-image',
                                    'title' => __($langGroup, 'Imagen'),
                                    'hint' => __($langGroup, 'Imagen preferiblemente en formato .jpg'),
                                    'field' => 'desktopImage',
                                    'width' => (int) $BuiltInBannerConfiguration['desktop']['referenceW'],
                                    'height' => (int) $BuiltInBannerConfiguration['desktop']['referenceH'],
                                    'required' => true,
                                ],
                            ]);
                            $this->helpController->_render('panel/built-in/content/image-field.php', [
                                'card' => [
                                    'id' => 'mobile-image',
                                    'title' => __($langGroup, 'Imagen teléfonos'),
                                    'hint' => __($langGroup, 'Imagen preferiblemente en formato .jpg'),
                                    'field' => 'mobileImage',
                                    'width' => (int) $BuiltInBannerConfiguration['mobile']['referenceW'],
                                    'height' => (int) $BuiltInBannerConfiguration['mobile']['referenceH'],
                                ],
                            ]);
                        ?>
                    </div>

                    <div class="field">
                        <label><?= __($langGroup, 'Posición'); ?></label>
                        <input type="number" name="orderPosition" value="0" min="0">
                    </div>

                    <div class="field">
                        <label><?= __($langGroup, 'Enlace'); ?></label>
                        <input type="text" name="link" value="">
                    </div>

                    <div class="two fields">

                        <div class="field" calendar-group-js='periodo' start>
                            <label><?= __($langGroup, 'Iniciar'); ?></label>
                            <input type="text" name="startDate" autocomplete="off">
                        </div>

                        <div class="field" calendar-group-js='periodo' end>
                            <label><?= __($langGroup, 'Finalizar'); ?></label>
                            <input type="text" name="endDate" autocomplete="off">
                        </div>

                    </div>

                    <div class="field">
                        <label><?= __($langGroup, 'Nombre'); ?></label>
                        <input type="text" name="title" maxlength="300" placeholder="">
                    </div>

                    <div class="field">
                        <label><?= __($langGroup, 'Contenido'); ?></label>
                        <div rich-editor-adapter-component></div>
                        <textarea name="content"></textarea>
                    </div>

                </div>

            </div>

            <?php $this->helpController->_render('panel/built-in/content/form-action-bar.php', ['saveText' => __($langGroup, 'Guardar')]); ?>

        </form>

    </div>

</section>

<?php $this->helpController->_render('panel/built-in/content/image-editor.php'); ?>
