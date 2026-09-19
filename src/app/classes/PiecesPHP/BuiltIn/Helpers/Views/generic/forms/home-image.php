<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\Core\Config;
use PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper;
/**
 * @var GenericContentPseudoMapper $element
 */
/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
$defaultLang = Config::get_default_lang();
$allowedLangs = Config::get_allowed_langs();
$contentName = $element->userSetContentName();
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

        <div class="container-standard-form mw-800">

            <form method='POST' action="<?= $action; ?>" class="ui form generic">

                <input type="hidden" name="configName" value="<?= $contentName; ?>">
                <?php foreach($allowedLangs as $lang): ?>
                <?php $value = $element->getLangData($lang, $contentName, true, ''); ?>
                <?php $isMultilang = $element->isTranslatable($contentName); ?>
                <?php $langSuffix = $isMultilang ? " ({$lang})" : ''; ?>
                <?php if($isMultilang || $lang === $defaultLang): ?>
                <div class="form-attachments-regular">
                    <div class="attach-placeholder main-image" data-lang="<?= $lang; ?>" data-name="<?= $contentName; ?>">
                        <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                        <div class="ui top right attached label green">
                            <i class="paperclip icon"></i>
                        </div>
                        <label for="<?= $uniqueIdentifier; ?>">
                            <div data-image="<?= $value; ?>" class="image" data-on-change-text="<?= __($langGroup, 'Cambiar'); ?>">
                                <i class="icon upload"></i>
                                <div class="caption"><?= __($langGroup, 'Anexar'); ?></div>
                            </div>
                            <div class="text">
                                <div class="filename"></div>
                                <div class="header">
                                    <div class="title"><?= __($langGroup, 'Imagen principal'); ?></div>
                                    <div class="meta"><?= __($langGroup, 'Tamaño 1200x900'); ?></div>
                                </div>
                                <div class="description"><?= __($langGroup, 'Imagen preferiblemente en formato .jpg'); ?></div>
                            </div>
                        </label>
                        <input type="file" accept="image/*" id="<?= $uniqueIdentifier; ?>">
                    </div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>

                <br><br>

                <div class="field">
                    <div class="ui buttons">
                        <button type="submit" class="ui button brand-color"><?= __($langGroup, 'Guardar'); ?></button>
                    </div>
                </div>

            </form>

        </div>

    </div>

</section>
<?php
foreach ($allowedLangs as $lang) {
    //Modal para imagen
    $idElements = "main-image-cropper-{$lang}";
    $value = $element->getLangData($lang, $contentName, true, '');
    modalImageUploaderForCropperAdminViews([
        //El contenido (si se usa simpleCropperAdapterWorkSpace o similar debe ser con el parámetro $echo en false)
        'content' => simpleCropperAdapterWorkSpace([
            'type' => 'image/*',
            'required' => false,
            'selectorAttr' => $idElements,
            'referenceW' => '1200',
            'referenceH' => '900',
            'image' => $value,
            'imageName' => $value === null ? 'image_' . str_replace('.', '', uniqid('', true)) : '',
        ], false),
        //Atributos que se asignarán al modal (el contenedor principal), string
        'modalContainerAttrs' => "modal='{$idElements}'",
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