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

    <?php //Modals edición de imagenes de marca ?>
    <div logo-modal class="ui tiny modal">
        <div class="content">
            <div class="cropper-info-content">
                <span><?= __($langGroup, "Editar imagen"); ?></span>
                <p><?= __($langGroup, "Edite la foto moviendo la imagen o cambiando su tamaño. Puede usar el mouse o las teclas de dirección"); ?></p>
            </div>

            <form action="<?= $actionURL; ?>" method="POST" class="ui form logo">
                <?php simpleCropperAdapterWorkSpace([
                    'type' => 'image/*',
                    'required' => false,
                    'selectorAttr' => 'logo-cropper',
                    'referenceW' => '400',
                    'referenceH' => '400',
                    'image' => $logo,
                ]); ?>
            </form>
        </div>
    </div>

    
    <div admin-favicon-modal class="ui tiny modal">
        <div class="content">
            <div class="cropper-info-content">
                <span><?= __($langGroup, "Editar imagen"); ?></span>
                <p><?= __($langGroup, "Edite la foto moviendo la imagen o cambiando su tamaño. Puede usar el mouse o las teclas de dirección"); ?></p>
            </div>

            <form action="<?= $actionURL; ?>" method="POST" class="ui form back-favicon">
                <?php simpleCropperAdapterWorkSpace([
                    'type' => 'image/*',
                    'required' => false,
                    'selectorAttr' => 'admin-fav-cropper',
                    'referenceW' => '400',
                    'referenceH' => '400',
                    'image' => $backFavicon,
                ]); ?>
            </form>
        </div>
    </div>

    <div favicon-modal class="ui tiny modal">
        <div class="content">
            <div class="cropper-info-content">
                <span><?= __($langGroup, "Editar imagen"); ?></span>
                <p><?= __($langGroup, "Edite la foto moviendo la imagen o cambiando su tamaño. Puede usar el mouse o las teclas de dirección"); ?></p>
            </div>

            <form action="<?= $actionURL; ?>" method="POST" class="ui form public-favicon">
                <?php simpleCropperAdapterWorkSpace([
                    'type' => 'image/*',
                    'required' => false,
                    'selectorAttr' => 'fav-cropper',
                    'referenceW' => '400',
                    'referenceH' => '400',
                    'image' => $publicFavicon,
                ]); ?>
            </form>
        </div>
    </div>

    <div partners-modal class="ui tiny modal">
        <div class="content">
            <div class="cropper-info-content">
                <span><?= __($langGroup, "Editar imagen"); ?></span>
                <p><?= __($langGroup, "Edite la foto moviendo la imagen o cambiando su tamaño. Puede usar el mouse o las teclas de dirección"); ?></p>
            </div>

            <form action="<?= $actionURL; ?>" method="POST" class="ui form partners">
                <?php simpleCropperAdapterWorkSpace([
                    'type' => 'image/*',
                    'required' => false,
                    'selectorAttr' => 'partners-cropper',
                    'referenceW' => '250',
                    'referenceH' => '80',
                    'image' => $partners,
                ]); ?>
            </form>
        </div>
    </div>

    <div partners-vertical-modal class="ui tiny modal">
        <div class="content">
            <div class="cropper-info-content">
                <span><?= __($langGroup, "Editar imagen"); ?></span>
                <p><?= __($langGroup, "Edite la foto moviendo la imagen o cambiando su tamaño. Puede usar el mouse o las teclas de dirección"); ?></p>
            </div>

            <form action="<?= $actionURL; ?>" method="POST" class="ui form partners-vertical">
                <?php simpleCropperAdapterWorkSpace([
                    'type' => 'image/*',
                    'required' => false,
                    'selectorAttr' => 'partners-vertical-cropper',
                    'referenceW' => '50',
                    'referenceH' => '280',
                    'image' => $partnersVertical,
                ]); ?>
            </form>
        </div>
    </div>

</main>
