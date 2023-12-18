<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>

<main class="backgrounds-view">
    <section class="main-body-header">
        <div class="head">
            <h2 class="tittle"><?= __($langGroup, 'Fondos'); ?></h2>
            <span class="sub-tittle"><?= __($langGroup, 'Personalización de Plataforma'); ?></span>
        </div>
        <div class="body-card max">

            <span class="card-tag">
                <?= __($langGroup, 'Login'); ?>
            </span>

            <div class="cards-list">

                <?php foreach (get_config('backgrounds') as $index => $background) : ?>

                <form bg="<?= ($index + 1); ?>" action="<?= $actionURL; ?>" method="POST" class="ui form">
                    <?php
                        $modalIndex = $index + 1;
                        imageUploaderForCropperAdminViews([
                            //Imagen de vista previa, string
                            'image' => $background,
                            //Texto en alt de la etiqueta img, string
                            'imageAlt' => null,
                            // Por defecto tiene la clase image-action pueden añadirse más, string
                            'classes' => null, 
                            //Atributos que se añaden al contenedor .image-action, string
                            'imageActionAttrs' => "image-card modal-index='{$modalIndex}'",
                            //Texto que refiere al cambio de imagen, string
                            'changeImageText' => null,
                            //Título de la ficha, string
                            'title' => strReplaceTemplate(__($langGroup, 'Fondo login #${n}'), [
                                '${n}' => $modalIndex,
                            ]),
                            //Texto descriptivo de la ficha, string
                            'description' => null,
                            //Ancho de referencia, int
                            'width' => 650,
                            //Alto de referencia, int
                            'height' => 730,
                        ]);
                    ?>

                    <div modal="<?= ($index + 1); ?>" class="ui tiny modal">
                        <div class="content">
                            <div class="cropper-info-content">
                                <span><?= __($langGroup, "Editar imagen"); ?></span>
                                <p><?= __($langGroup, "Edite la foto moviendo la imagen o cambiando su tamaño. Puede usar el mouse o las teclas de dirección"); ?></p>
                            </div>

                            <?php simpleCropperAdapterWorkSpace([
                                'type' => 'image/*',
                                'required' => false,
                                'selectorAttr' => 'back-login-'.($index+1),
                                'referenceW' => '650',
                                'referenceH' => '730',
                                'image' => $background,
                            ]); ?>

                        </div>
                    </div>

                </form>

                <?php endforeach; ?>

            </div>

            <span class="card-tag">
                <?= __($langGroup, 'Otros fondos'); ?>
            </span>

            <div class="cards-list">

                <form action="<?= $actionURL; ?>" method="POST" class="ui form background-problems">
                    <?php
                        $modalBgProblemsIndex = 'background-problems';
                        imageUploaderForCropperAdminViews([
                            //Imagen de vista previa, string
                            'image' => get_config('backgoundProblems'),
                            //Texto en alt de la etiqueta img, string
                            'imageAlt' => null,
                            // Por defecto tiene la clase image-action pueden añadirse más, string
                            'classes' => null, 
                            //Atributos que se añaden al contenedor .image-action, string
                            'imageActionAttrs' => "image-card modal-index='{$modalBgProblemsIndex}'",
                            //Texto que refiere al cambio de imagen, string
                            'changeImageText' => null,
                            //Título de la ficha, string
                            'title' => __($langGroup, 'Fondo problemas de inicio'),
                            //Texto descriptivo de la ficha, string
                            'description' => null,
                            //Ancho de referencia, int
                            'width' => 1920,
                            //Alto de referencia, int
                            'height' => 1080,
                        ]);
                    ?>

                    <div modal="<?= $modalBgProblemsIndex; ?>" class="ui tiny modal">
                        <div class="content">
                            <div class="cropper-info-content">
                                <span><?= __($langGroup, "Editar imagen"); ?></span>
                                <p><?= __($langGroup, "Edite la foto moviendo la imagen o cambiando su tamaño. Puede usar el mouse o las teclas de dirección"); ?></p>
                            </div>

                            <?php simpleCropperAdapterWorkSpace([
                                'type' => 'image/*',
                                'required' => false,
                                'selectorAttr' => 'background-problems-cropper',
                                'referenceW' => '1920',
                                'referenceH' => '1080',
                                'image' => get_config('backgoundProblems'),
                            ]); ?>

                        </div>
                    </div>

                </form>

            </div>

        </div>
    </section>
</main>
