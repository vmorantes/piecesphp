<?php
    defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>

<div class="ui header"><?=  __($langGroup, 'Imágenes de marca'); ?></div>

<div class="ui header small"><?=  __($langGroup, 'Íconos de favoritos (favicon)'); ?></div>

<div class="container-logos-favicons">

    <form action="<?= $actionURL; ?>" method="POST" class="ui form public-favicon">

        <div class="field required">

            <div class="ui card">

                <div class="content">

                    <div class="ui form cropper-adapter">

                        <input type="file" accept="image/*" required>
                        <?php 
                            cropperAdapterWorkSpace([
                                'withTitle'=> false,
                                'image'=> $publicFavicon,
                                'referenceW'=> '400',
                                'referenceH'=> '400',
                                'cancelButtonText' => null,
                                'saveButtonText' => __($langGroup, 'Seleccionar imagen'),                            
                                'controls' =>[
                                    'rotate' => false, 
                                    'flip' => false, 
                                    'adjust' => false, 
                                ],
                            ]); 
                        ?>
                    </div>

                </div>

                <div class="content">
                    <label class="header"><?= __($langGroup, 'Favicon en zona pública'); ?></label>

                    <div class="meta">
                        <span><?= strReplaceTemplate(__($langGroup, 'Imagen preferiblemente con fondo transparente. Tamaño de la imagen {dimensions}'), ['{dimensions}' => "400x400px",])?></span>
                    </div>

                </div>

                <button class="ui bottom attached button green" type="submit">
                    <?= __($langGroup, 'Guardar imagen'); ?>
                </button>

            </div>

        </div>

    </form>

    <form action="<?= $actionURL; ?>" method="POST" class="ui form back-favicon">

        <div class="field required">

            <div class="ui card">

                <div class="content">

                    <div class="ui form cropper-adapter">

                        <input type="file" accept="image/*" required>
                        <?php 
                            cropperAdapterWorkSpace([
                                'withTitle'=> false,
                                'image'=> $backFavicon,
                                'referenceW'=> '400',
                                'referenceH'=> '400',
                                'cancelButtonText' => null,
                                'saveButtonText' => __($langGroup, 'Seleccionar imagen'),                            
                                'controls' =>[
                                    'rotate' => false, 
                                    'flip' => false, 
                                    'adjust' => false, 
                                ],
                            ]); 
                        ?>

                    </div>

                </div>

                <div class="content">
                    <label class="header"><?= __($langGroup, 'Favicon en zona administrativa'); ?></label>

                    <div class="meta">
                        <span><?= strReplaceTemplate(__($langGroup, 'Imagen preferiblemente con fondo transparente. Tamaño de la imagen {dimensions}'), ['{dimensions}' => "400x400px",])?></span>
                    </div>

                </div>

                <button class="ui bottom attached button green" type="submit">
                    <?= __($langGroup, 'Guardar imagen'); ?>
                </button>

            </div>

        </div>

    </form>

</div>

<div class="ui header small"><?=  __($langGroup, 'Logos'); ?></div>

<div class="container-logos-favicons">

    <form action="<?= $actionURL; ?>" method="POST" class="ui form logo">

        <div class="field required">

            <div class="ui card">

                <div class="content">

                    <div class="ui form cropper-adapter">

                        <input type="file" accept="image/*" required>
                        <?php 
                            cropperAdapterWorkSpace([
                                'withTitle'=> false,
                                'image'=> $logo,
                                'referenceW'=> '400',
                                'referenceH'=> '400',
                                'cancelButtonText' => null,
                                'saveButtonText' => __($langGroup, 'Seleccionar imagen'),                            
                                'controls' =>[
                                    'rotate' => false, 
                                    'flip' => false, 
                                    'adjust' => false, 
                                ],
                            ]); 
                        ?>

                    </div>

                </div>

                <div class="content">
                    <label class="header"><?= __($langGroup, 'Logo'); ?></label>

                    <div class="meta">
                        <span><?= strReplaceTemplate(__($langGroup, 'Imagen preferiblemente con fondo transparente. Tamaño de la imagen {dimensions}'), ['{dimensions}' => "400x400px",])?></span>
                    </div>

                </div>

                <button class="ui bottom attached button green" type="submit">
                    <?= __($langGroup, 'Guardar imagen'); ?>
                </button>

            </div>

        </div>

    </form>

</div>
