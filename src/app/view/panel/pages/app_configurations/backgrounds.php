<?php
    defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>

<div class="ui header"><?=  __($langGroup, 'Fondos'); ?></div>
<div class="ui header small"><?=  __($langGroup, 'Fondos del login'); ?></div>

<div class="container-backgrounds">

    <?php foreach (get_config('backgrounds') as $index => $background): ?>

    <form bg="<?= ($index + 1); ?>" action="<?= $actionURL; ?>" method="POST" class="ui form">

        <div class="field required">

            <div class="ui card">

                <div class="content">

                    <div class="ui form cropper-adapter">

                        <input type="file" accept="image/*" required>

                        <?php $this->_render('panel/built-in/utilities/cropper/workspace.php', [
                            'withTitle'=> false,
                            'image'=> $background,
                            'referenceW'=> '1920',
                            'referenceH'=> '1080',
                            'cancelButtonText' => null,
                            'saveButtonText' => __($langGroup, 'Seleccionar imagen'),                            
                            'controls' =>[
                                'rotate' => false, 
                                'flip' => false, 
                                'adjust' => false, 
                            ],
                        ]); ?>

                    </div>

                </div>

                <div class="content">
                    <label class="header"><?= __($langGroup, 'Fondo'); ?> #<?= ($index + 1); ?></label>
                    <div class="meta">
                        <span><?= strReplaceTemplate(__($langGroup, 'Tamaño de la imagen {dimensions}'), ['{dimensions}' => "1920x1080px",])?></span>
                    </div>
                </div>

                <button class="ui bottom attached button green" type="submit">
                    <?= __($langGroup, 'Guardar imagen'); ?>
                </button>

            </div>

        </div>

    </form>

    <br><br>

    <?php endforeach;?>

</div>
