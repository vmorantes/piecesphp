<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\BuiltIn\Banner\Mappers\BuiltInBannerMapper;

/**
 * @var BuiltInBannerMapper $element
 */

/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
$BuiltInBannerConfiguration = get_config('BuiltInBannerConfiguration');
?>
<section class="module-view-container">

    <div class="breadcrumb">
        <?= $breadcrumbs ?>
    </div>

    <div class="limiter-content">

        <div class="section-title">
            <div class="title"><?= $title ?></div>
            <?php if(isset($description) && is_string($description) && mb_strlen(trim($description)) > 0): ?>
            <div class="description"><?= $description; ?></div>
            <?php endif; ?>
        </div>

        <br>

        <div class="tabs-controls">
            <div class="active" data-tab="basic"><?= __($langGroup, 'Datos básicos'); ?></div>
        </div>

        <div class="container-standard-form">

            <?php if($manyLangs): ?>
            <div class="ui form">
                <div class="field required">
                    <label><?= __($langGroup, 'Idiomas'); ?></label>
                    <select required class="ui dropdown search langs">
                        <?= $allowedLangs; ?>
                    </select>
                </div>
            </div>
            <?php endif; ?>

            <form method='POST' action="<?= $action; ?>" class="ui form built-in-banner">

                <input type="hidden" name="id" value="<?= $element->id; ?>">
                <input type="hidden" name="lang" value="<?= $lang; ?>">

                <div class="ui tab active" data-tab="basic">

                    <div class="form-attachments-regular">
                        <div class="attach-placeholder desktop-image">
                            <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                            <div class="ui top right attached label green">
                                <i class="paperclip icon"></i>
                            </div>
                            <label for="<?= $uniqueIdentifier; ?>">
                                <div data-image="<?= $element->getLangData($lang, 'desktopImage', false, ''); ?>" class="image" data-on-change-text="<?= __($langGroup, 'Cambiar'); ?>">
                                    <i class="icon upload"></i>
                                    <div class="caption"><?= __($langGroup, 'Anexar'); ?></div>
                                </div>
                                <div class="text">
                                    <div class="filename"></div>
                                    <div class="header">
                                        <div class="title"><?= __($langGroup, 'Imagen'); ?></div>
                                        <div class="meta"><?= $BuiltInBannerConfiguration['desktop']['sizeRecommendedText']; ?></div>
                                    </div>
                                    <div class="description"><?= __($langGroup, 'Imagen preferiblemente en formato .jpg'); ?></div>
                                </div>
                            </label>
                            <input type="file" accept="image/*" id="<?= $uniqueIdentifier; ?>">
                        </div>

                        <div class="attach-placeholder mobile-image">
                            <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                            <div class="ui top right attached label green">
                                <i class="paperclip icon"></i>
                            </div>
                            <label for="<?= $uniqueIdentifier; ?>">
                                <div data-image="<?= $element->getLangData($lang, 'mobileImage', false, ''); ?>" class="image" data-on-change-text="<?= __($langGroup, 'Cambiar'); ?>">
                                    <i class="icon upload"></i>
                                    <div class="caption"><?= __($langGroup, 'Anexar'); ?></div>
                                </div>
                                <div class="text">
                                    <div class="filename"></div>
                                    <div class="header">
                                        <div class="title"><?= __($langGroup, 'Imagen teléfonos'); ?></div>
                                        <div class="meta"><?= $BuiltInBannerConfiguration['mobile']['sizeRecommendedText']; ?></div>
                                    </div>
                                    <div class="description"><?= __($langGroup, 'Imagen preferiblemente en formato .jpg'); ?></div>
                                </div>
                            </label>
                            <input type="file" accept="image/*" id="<?= $uniqueIdentifier; ?>">
                        </div>
                    </div>

                    <br><br>

                    <div class="field">
                        <label><?= __($langGroup, 'Posición'); ?></label>
                        <input type="number" name="orderPosition" value="<?= $element->getLangData($lang, 'orderPosition', false, ''); ?>" min="0">
                    </div>

                    <div class="field">
                        <label><?= __($langGroup, 'Enlace'); ?></label>
                        <input type="text" name="link" value="<?= $element->getLangData($lang, 'link', false, ''); ?>">
                    </div>

                    <div class="two fields">

                        <div class="field" calendar-group-js='periodo' start>
                            <label><?= __($langGroup, 'Iniciar'); ?></label>
                            <input type="text" name="startDate" autocomplete="off" value="<?= $element->startDate !== null ? $element->startDate->format('Y-m-d h:i:s A') : ''; ?>">
                        </div>

                        <div class="field" calendar-group-js='periodo' end>
                            <label><?= __($langGroup, 'Finalizar'); ?></label>
                            <input type="text" name="endDate" autocomplete="off" value="<?= $element->endDate !== null ? $element->endDate->format('Y-m-d h:i:s A') : ''; ?>">
                        </div>

                    </div>
                    <div class="field">
                        <label><?= __($langGroup, 'Nombre'); ?></label>
                        <input type="text" name="title" maxlength="300" value="<?= $element->getLangData($lang, 'title', false, ''); ?>" placeholder="">
                    </div>

                    <div class="field">
                        <label><?= __($langGroup, 'Contenido'); ?></label>
                        <div rich-editor-adapter-component></div>
                        <textarea name="content"><?= $element->getLangData($lang, 'content', false, ''); ?></textarea>
                    </div>

                </div>

                <br><br>

                <div class="field">
                    <div class="ui buttons">
                        <button type="submit" class="ui button brand-color"><?= __($langGroup, 'Guardar'); ?></button>
                        <?php if($allowDelete): ?>
                        <button type="submit" class="ui button brand-color alt2" delete-built-in-banner-button data-route="<?= $deleteRoute; ?>"><?= __($langGroup, 'Eliminar'); ?></button>
                        <?php endif; ?>
                    </div>
                </div>

            </form>

        </div>

    </div>

</section>

<?php

    $croppers = [
        [
            'id' => 'desktop-image',
            'cropperOptions' => [
                'type' => 'image/*',
                'required' => false,
                'referenceW' => $BuiltInBannerConfiguration['desktop']['referenceW'],
                'referenceH' => $BuiltInBannerConfiguration['desktop']['referenceH'],
                'image' => $element->getLangData($lang, 'desktopImage'),
                'imageName' => $element->getLangData($lang, 'desktopImage', false, null) === null ? 'image_' . str_replace('.', '', uniqid('', true)) : '',
            ],
        ],
        [
            'id' => 'mobile-image',
            'cropperOptions' => [
                'type' => 'image/*',
                'required' => false,
                'referenceW' => $BuiltInBannerConfiguration['mobile']['referenceW'],
                'referenceH' => $BuiltInBannerConfiguration['mobile']['referenceH'],
                'image' => $element->getLangData($lang, 'ASFSAFSA'),
                'imageName' => $element->getLangData($lang, 'ASFSAFSA', false, null) === null ? 'image_' . str_replace('.', '', uniqid('', true)) : '',
            ],
        ],
    ];

    foreach ($croppers as $cropperData) {
        $id = $cropperData['id'];
        $cropperOptions = $cropperData['cropperOptions'];
        $cropperOptions['selectorAttr'] = $id;
        modalImageUploaderForCropperAdminViews([
            //El contenido (si se usa simpleCropperAdapterWorkSpace o similar debe ser con el parámetro $echo en false)
            'content' => simpleCropperAdapterWorkSpace($cropperOptions, false),
            //Atributos que se asignarán al modal (el contenedor principal), string
            'modalContainerAttrs' => "modal='{$id}'",
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
    }

?>
