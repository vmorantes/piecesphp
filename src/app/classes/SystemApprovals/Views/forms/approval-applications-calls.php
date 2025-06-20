<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use ApplicationCalls\Mappers\ApplicationCallsMapper;
use ApplicationCalls\Util\AttachmentPackage;
use PiecesPHP\Core\Config;
use SystemApprovals\Mappers\SystemApprovalsMapper;
/**
 * @var SystemApprovalsMapper $approvalMapper
 * @var string $langGroup
 * @var string $action
 */
$mapper = new ApplicationCallsMapper($approvalMapper->referenceValue);
$researchAreas = implode(', ', array_map(fn($e) => $e->currentLangData('areaName'), $mapper->interestResearhAreas));
$participatingInstitutions = implode(', ', array_map(fn($e) => $e, $mapper->participatingInstitutions));
$approvalElementExtended = $approvalMapper->getExtendedElement();
?>
<section class="module-view-container">

    <div class="breadcrumb">
        <?= $breadcrumbs ?>
    </div>

    <div class="limiter-content">

        <div class="section-topbar">
            <div class="section-title">
                <div class="title"><?= $title ?></div>
                <?php if(isset($description) && is_string($description) && mb_strlen(trim($description)) > 0): ?>
                <div class="description"><?= $description; ?></div>
                <?php endif; ?>
                <br>
                <?= $approvalMapper->getTimeTag(); ?>
            </div>
            <div class="actions">
            </div>
        </div>

        <br>

        <form method="POST" action="<?= $action; ?>" class="ui form system-approval datasheet">

            <div class="container-standard-form mw-800">
                <div class="field">
                    <label><?= __($langGroup, 'Motivo'); ?></label>
                    <textarea name="reason"></textarea>
                </div>
                <div class="field global-clearfix">
                    <div class="ui right floated buttons">
                        <button type="submit" class="ui button brand-color" approve-trigger><?= __($langGroup, 'Aprobar'); ?></button>
                        <button type="submit" class="ui red button" reject-trigger><?= __($langGroup, 'Rechazar'); ?></button>
                    </div>
                </div>
            </div>
            <br>

            <input type="hidden" name="id" value="<?= $approvalMapper->id; ?>">
            <button type="submit" style="display: none;" save></button>

            <div class="base-title"><?= __($langGroup, 'Tipo de proyecto'); ?></div>
            <div class="base-text"><?= $mapper->contentTypeText(); ?></div>
            <div class="base-title"><?= __($langGroup, 'Tipo de contratación'); ?></div>
            <div class="base-text"><?= $mapper->financingTypeText(); ?></div>

            <div class="base-horizontal-space"></div>

            <div class="base-title size2"><?= __($langGroup, 'Nombre del contenido'); ?></div>
            <div class="base-text mark"><?= $mapper->currentLangData('title'); ?></div>

            <div class="base-horizontal-space"></div>

            <div class="ui stackable grid">
                <div class="three wide column">
                    <div class="base-title"><?= __($langGroup, 'Fecha inicial'); ?></div>
                    <div class="base-text"><?= strReplaceTemplate(localeDateFormat('%e %1 %B %1 Y', $mapper->startDate), ['%1' => __(LANG_GROUP, 'de')]); ?></div>
                </div>
                <div class="three wide column">
                    <div class="base-title"><?= __($langGroup, 'Fecha final'); ?></div>
                    <div class="base-text"><?= strReplaceTemplate(localeDateFormat('%e %1 %B %1 Y', $mapper->startDate), ['%1' => __(LANG_GROUP, 'de')]); ?></div>
                </div>
            </div>

            <div class="base-horizontal-space"></div>

            <div class="base-title"><?= __($langGroup, 'Áreas de investigación'); ?></div>
            <div class="base-text"><?= $researchAreas; ?></div>

            <div class="base-horizontal-space"></div>

            <div class="base-title"><?= __($langGroup, 'Instituciones que participan'); ?></div>
            <div class="base-text"><?= $participatingInstitutions; ?></div>

            <div class="base-horizontal-space"></div>

            <div class="base-title"><?= __($langGroup, 'Descripción'); ?></div>
            <div class="base-text"><?= $mapper->currentLangData('content'); ?></div>

            <div class="base-horizontal-space"></div>

            <div class="container-standard-form">
                <div class="base-title size3"><?= __($langGroup, 'Imágenes'); ?></div>
                <div class="base-horizontal-space"></div>
                <div class="form-attachments-regular">
                    <div data-trigger-open-link="<?= $mapper->currentLangData('mainImage'); ?>" class="attach-placeholder tall">
                        <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                        <div class="ui top right attached label green">
                            <i class="paperclip icon"></i>
                        </div>
                        <label for="<?= $uniqueIdentifier; ?>">
                            <div class="image fullsize">
                                <img src="<?= $mapper->currentLangData('mainImage'); ?>">
                            </div>
                            <div class="text">
                                <div class="header">
                                    <div class="title"><?= __($langGroup, 'Imagen principal'); ?></div>
                                </div>
                            </div>
                        </label>
                        <input type="file" accept="image/*" id="<?= $uniqueIdentifier; ?>">
                    </div>

                    <div data-trigger-open-link="<?= $mapper->currentLangData('thumbImage'); ?>" class="attach-placeholder tall">
                        <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                        <div class="ui top right attached label green">
                            <i class="paperclip icon"></i>
                        </div>
                        <label for="<?= $uniqueIdentifier; ?>">
                            <div class="image fullsize">
                                <img src="<?= $mapper->currentLangData('thumbImage'); ?>">
                            </div>
                            <div class="text">
                                <div class="header">
                                    <div class="title"><?= __($langGroup, 'Imagen miniatura'); ?></div>
                                </div>
                            </div>
                        </label>
                        <input type="file" accept="image/*" id="<?= $uniqueIdentifier; ?>">
                    </div>
                </div>
            </div>

            <div class="base-horizontal-space"></div>

            <div class="container-standard-form">
                <div class="form-attachments-regular">
                    <div class="base-title size3"><?= __($langGroup, 'Anexos'); ?></div>
                    <div class="base-horizontal-space"></div>
                    <?php foreach(Config::get_allowed_langs() as $allowedLang): ?>
                    <?php foreach($mapper->getAttachmentsByLang($allowedLang, true) as $attachmentMapper): ?>
                    <?php $attachmentElement = new AttachmentPackage($mapper->id, $attachmentMapper->id, $attachmentMapper->attachmentName, false, $attachmentMapper->lang); ?>
                    <?php $hasAttachment = $attachmentElement->hasAttachment(); ?>
                    <?php $attachmentMapper = $attachmentElement->getMapper(); ?>
                    <?php $fileLocation = $hasAttachment ? $attachmentMapper->fileLocation : ''; ?>
                    <?php $isImage = $hasAttachment ? $attachmentMapper->fileIsImage() : ''; ?>
                    <?php $existingFileAttr = "data-trigger-download-link"; ?>
                    <?php $existingFileAttr = "{$existingFileAttr}='{$fileLocation}'"; ?>
                    <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                    <div <?= $existingFileAttr; ?> class="attach-placeholder" data-dynamic-attachment="<?= $uniqueIdentifier; ?>" data-mapper-id="<?= $attachmentMapper->id; ?>">
                        <div class="ui top right attached label green">
                            <i class="paperclip icon"></i>
                        </div>
                        <label for="<?= $uniqueIdentifier; ?>">
                            <div class="image mark">
                                <i class="icon download"></i>
                                <div class="caption"><?= __($langGroup, 'Descargar'); ?></div>
                            </div>
                            <div class="text">
                                <div class="filename"></div>
                                <div class="header">
                                    <div class="title"><?= $attachmentElement->getDisplayName(); ?></div>
                                </div>
                            </div>
                        </label>
                    </div>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>

        </form>

    </div>

</section>
