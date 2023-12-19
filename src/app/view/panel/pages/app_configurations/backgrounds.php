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

                <?php $modalIndex = $index + 1; ?>
                <form bg="<?= $modalIndex; ?>" action="<?= $actionURL; ?>" method="POST" class="ui form">
                    <?php
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

                    <?php
                        modalImageUploaderForCropperAdminViews([
                            //El contenido (si se usa simpleCropperAdapterWorkSpace o similar debe ser con el parámetro $echo en false)
                            'content' => simpleCropperAdapterWorkSpace([
                                'type' => 'image/*',
                                'required' => false,
                                'selectorAttr' => 'back-login-' . $modalIndex,
                                'referenceW' => '650',
                                'referenceH' => '730',
                                'image' => $background,
                            ], false),
                            //Atributos que se asignarán al modal (el contenedor principal), string
                            'modalContainerAttrs' => "modal='{$modalIndex}'",
                            //Clases que se asignarán al modal (el contenedor principal), string
                            'modalContainerClasses' => "ui tiny modal",
                            //Atributos que se asignarán al elemento de contenido del modal (modal > .content), string
                            'modalContentElementAttrs' => null,
                            //Clase por defecto del elemento informativo del modal (donde están el título y la descripcion, por omisión cropper-info-content), string
                            'informationContentMainClass' => null,
                            //Clases que se asignarán al elemento informativo del modal (donde están el título y la descripcion), string
                            'informationContentClasses' => null,
                            //Título del modal, string
                            'titleModal' => null,
                            //Descripción del modal, string
                            'descriptionModal' => null,
                        ]);
                    ?>

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

                    <?php
                        modalImageUploaderForCropperAdminViews([
                            //El contenido (si se usa simpleCropperAdapterWorkSpace o similar debe ser con el parámetro $echo en false)
                            'content' => simpleCropperAdapterWorkSpace([
                                'type' => 'image/*',
                                'required' => false,
                                'selectorAttr' => 'background-problems-cropper',
                                'referenceW' => '1920',
                                'referenceH' => '1080',
                                'image' => get_config('backgoundProblems'),
                            ], false),
                            //Atributos que se asignarán al modal (el contenedor principal), string
                            'modalContainerAttrs' => "modal='{$modalBgProblemsIndex}'",
                            //Clases que se asignarán al modal (el contenedor principal), string
                            'modalContainerClasses' => "ui tiny modal",
                            //Atributos que se asignarán al elemento de contenido del modal (modal > .content), string
                            'modalContentElementAttrs' => null,
                            //Clase por defecto del elemento informativo del modal (donde están el título y la descripcion, por omisión cropper-info-content), string
                            'informationContentMainClass' => null,
                            //Clases que se asignarán al elemento informativo del modal (donde están el título y la descripcion), string
                            'informationContentClasses' => null,
                            //Título del modal, string
                            'titleModal' => null,
                            //Descripción del modal, string
                            'descriptionModal' => null,
                        ]);
                    ?>

                </form>

            </div>

        </div>
    </section>
</main>
