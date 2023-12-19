<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>

<main class="logos-favicons-view">
    <section class="main-body-header">
        <div class="head">
            <h2 class="tittle"><?= __($langGroup, 'Imágenes de marca'); ?></h2>
            <span class="sub-tittle"><?= __($langGroup, 'Personalización de Plataforma'); ?></span>
        </div>
        <div class="body-card max">

            <span class="card-tag">
                <?= __($langGroup, 'Propios'); ?>
            </span>

            <div class="cards-list">
                <?php
                    imageUploaderForCropperAdminViews([
                        //Imagen de vista previa, string
                        'image' => $logo,
                        //Texto en alt de la etiqueta img, string
                        'imageAlt' => null,
                        // Por defecto tiene la clase image-action pueden añadirse más, string
                        'classes' => null, 
                        //Atributos que se añaden al contenedor .image-action, string
                        'imageActionAttrs' => "card-logo",
                        //Texto que refiere al cambio de imagen, string
                        'changeImageText' => null,
                        //Título de la ficha, string
                        'title' => __($langGroup, "Logo"),
                        //Texto descriptivo de la ficha, string
                        'description' => null,
                        //Ancho de referencia, int
                        'width' => 400,
                        //Alto de referencia, int
                        'height' => 400,
                    ]);
                ?>
                <?php
                    imageUploaderForCropperAdminViews([
                        //Imagen de vista previa, string
                        'image' => $backFavicon,
                        //Texto en alt de la etiqueta img, string
                        'imageAlt' => null,
                        // Por defecto tiene la clase image-action pueden añadirse más, string
                        'classes' => null, 
                        //Atributos que se añaden al contenedor .image-action, string
                        'imageActionAttrs' => "admin-fav-card",
                        //Texto que refiere al cambio de imagen, string
                        'changeImageText' => null,
                        //Título de la ficha, string
                        'title' => __($langGroup, "Favicon en zona administrativa"),
                        //Texto descriptivo de la ficha, string
                        'description' => null,
                        //Ancho de referencia, int
                        'width' => 400,
                        //Alto de referencia, int
                        'height' => 400,
                    ]);
                ?>
                <?php
                    imageUploaderForCropperAdminViews([
                        //Imagen de vista previa, string
                        'image' => $publicFavicon,
                        //Texto en alt de la etiqueta img, string
                        'imageAlt' => null,
                        // Por defecto tiene la clase image-action pueden añadirse más, string
                        'classes' => null, 
                        //Atributos que se añaden al contenedor .image-action, string
                        'imageActionAttrs' => "fav-card",
                        //Texto que refiere al cambio de imagen, string
                        'changeImageText' => null,
                        //Título de la ficha, string
                        'title' => __($langGroup, "Favicon en zona pública"),
                        //Texto descriptivo de la ficha, string
                        'description' => null,
                        //Ancho de referencia, int
                        'width' => 400,
                        //Alto de referencia, int
                        'height' => 400,
                    ]);
                ?>
            </div>

            <span class="card-tag">
                <?= __($langGroup, 'Terceros'); ?>
            </span>

            <div class="cards-list">

                <?php
                    imageUploaderForCropperAdminViews([
                        //Imagen de vista previa, string
                        'image' => $partners,
                        //Texto en alt de la etiqueta img, string
                        'imageAlt' => null,
                        // Por defecto tiene la clase image-action pueden añadirse más, string
                        'classes' => 'fit-image', 
                        //Atributos que se añaden al contenedor .image-action, string
                        'imageActionAttrs' => "partners-card",
                        //Texto que refiere al cambio de imagen, string
                        'changeImageText' => null,
                        //Título de la ficha, string
                        'title' => __($langGroup, "Bandera horizontal"),
                        //Texto descriptivo de la ficha, string
                        'description' => null,
                        //Ancho de referencia, int
                        'width' => 280,
                        //Alto de referencia, int
                        'height' => 50,
                    ]);
                ?>

                <?php
                    imageUploaderForCropperAdminViews([
                        //Imagen de vista previa, string
                        'image' => $partnersVertical,
                        //Texto en alt de la etiqueta img, string
                        'imageAlt' => null,
                        // Por defecto tiene la clase image-action pueden añadirse más, string
                        'classes' => 'fit-image', 
                        //Atributos que se añaden al contenedor .image-action, string
                        'imageActionAttrs' => "partners-vertical-card",
                        //Texto que refiere al cambio de imagen, string
                        'changeImageText' => null,
                        //Título de la ficha, string
                        'title' => __($langGroup, "Bandera vertical"),
                        //Texto descriptivo de la ficha, string
                        'description' => null,
                        //Ancho de referencia, int
                        'width' => 50,
                        //Alto de referencia, int
                        'height' => 280,
                    ]);
                ?>

            </div>

        </div>

    </section>

    <?php 
        //Modals edición de imagenes de marca 
        $modalsData = [
            [
                //Formulario y cropper
                'actionURL' => $actionURL,
                'classForm' => 'logo',
                'selectorAttr' => 'logo-cropper',
                'referenceW' => '400',
                'referenceH' => '400',
                'image' => $logo,
                //Modal
                'modalContainerAttrs' => 'logo-modal',
                'modalContainerClasses' => 'ui tiny modal',
                'titleModal' => null,
                'descriptionModal' => null,
            ],
            [
                //Formulario y cropper
                'actionURL' => $actionURL,
                'classForm' => 'back-favicon',
                'selectorAttr' => 'admin-fav-cropper',
                'referenceW' => '400',
                'referenceH' => '400',
                'image' => $backFavicon,
                //Modal
                'modalContainerAttrs' => 'admin-favicon-modal',
                'modalContainerClasses' => 'ui tiny modal',
                'titleModal' => null,
                'descriptionModal' => null,
            ],
            [
                //Formulario y cropper
                'actionURL' => $actionURL,
                'classForm' => 'public-favicon',
                'selectorAttr' => 'fav-cropper',
                'referenceW' => '400',
                'referenceH' => '400',
                'image' => $publicFavicon,
                //Modal
                'modalContainerAttrs' => 'favicon-modal',
                'modalContainerClasses' => 'ui tiny modal',
                'titleModal' => null,
                'descriptionModal' => null,
            ],
            [
                //Formulario y cropper
                'actionURL' => $actionURL,
                'classForm' => 'partners',
                'selectorAttr' => 'partners-cropper',
                'referenceW' => '250',
                'referenceH' => '80',
                'image' => $partners,
                //Modal
                'modalContainerAttrs' => 'partners-modal',
                'modalContainerClasses' => 'ui tiny modal',
                'titleModal' => null,
                'descriptionModal' => null,
            ],
            [
                //Formulario y cropper
                'actionURL' => $actionURL,
                'classForm' => 'partners-vertical',
                'selectorAttr' => 'partners-vertical-cropper',
                'referenceW' => '50',
                'referenceH' => '280',
                'image' => $partnersVertical,
                //Modal
                'modalContainerAttrs' => 'partners-vertical-modal',
                'modalContainerClasses' => 'ui tiny modal',
                'titleModal' => null,
                'descriptionModal' => null,
            ],
        ];
        foreach ($modalsData as $modalData) {
            $modalData = (object) $modalData;
            $contentModal = "<form action='{$modalData->actionURL}' method='POST' class='ui form {$modalData->classForm}'>";
            $contentModal .= simpleCropperAdapterWorkSpace([
                'type' => 'image/*',
                'required' => false,
                'selectorAttr' => $modalData->selectorAttr,
                'referenceW' => $modalData->referenceW,
                'referenceH' => $modalData->referenceH,
                'image' => $modalData->image,
            ], false);
            $contentModal .= "</form>";
            modalImageUploaderForCropperAdminViews([
                //El contenido (si se usa simpleCropperAdapterWorkSpace o similar debe ser con el parámetro $echo en false)
                'content' => $contentModal,
                //Atributos que se asignarán al modal (el contenedor principal), string
                'modalContainerAttrs' => $modalData->modalContainerAttrs,
                //Clases que se asignarán al modal (el contenedor principal), string
                'modalContainerClasses' => $modalData->modalContainerClasses,
                //Atributos que se asignarán al elemento de contenido del modal (modal > .content), string
                'modalContentElementAttrs' => null,
                //Clase por defecto del elemento informativo del modal (donde están el título y la descripcion, por omisión cropper-info-content), string
                'informationContentMainClass' => null,
                //Clases que se asignarán al elemento informativo del modal (donde están el título y la descripcion), string
                'informationContentClasses' => null,
                //Título del modal, string
                'titleModal' => $modalData->titleModal,
                //Descripción del modal, string
                'descriptionModal' => $modalData->descriptionModal,
            ]);
        }
    ?>

</main>
