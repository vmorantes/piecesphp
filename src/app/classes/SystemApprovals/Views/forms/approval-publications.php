<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use Publications\Util\AttachmentPackage;
use PiecesPHP\Core\Config;
use Publications\Mappers\PublicationCategoryMapper;
use Publications\Mappers\PublicationMapper;
use PiecesPHP\UserSystem\ORM\UsersModel;
use SystemApprovals\Mappers\SystemApprovalsMapper;
/**
 * @var SystemApprovalsMapper $approvalMapper
 * @var string $langGroup
 * @var string $action
 */
$mapper = new PublicationMapper($approvalMapper->referenceValue);
$approvalElementExtended = $approvalMapper->getExtendedElement();
//Lo que escribe un usuario se escapa al pintar: nada lo limpia al entrar (pendientes.md 394).
$escape = fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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

            <div class="base-title"><?= __($langGroup, 'Categoría'); ?></div>
            <div class="base-text"><?= $mapper->category instanceof PublicationCategoryMapper ? $escape($mapper->category->currentLangData('name')) : ''; ?></div>

            <div class="base-horizontal-space"></div>

            <div class="base-title size2"><?= __($langGroup, 'Nombre de la publicación'); ?></div>
            <div class="base-text mark"><?= $escape($mapper->currentLangData('title')); ?></div>

            <div class="base-horizontal-space"></div>

            <div class="ui stackable grid">
                <div class="three wide column">
                    <div class="base-title"><?= __($langGroup, 'Autor'); ?></div>
                    <div class="base-text"><?= $mapper->author instanceof UsersModel ? $escape($mapper->author->getFullName()) : ''; ?></div>
                </div>
                <div class="three wide column">

                    <div class="base-title"><?= __($langGroup, 'Fecha pública'); ?></div>
                    <div class="base-text"><?= strReplaceTemplate(localeDateFormat('%e %1 %B %1 Y', $mapper->publicDate), ['%1' => __(LANG_GROUP, 'de')]); ?></div>
                </div>
            </div>

            <div class="base-horizontal-space"></div>

            <div class="ui stackable grid">
                <div class="three wide column">
                    <div class="base-title"><?= __($langGroup, 'Fecha inicial'); ?></div>
                    <div class="base-text"><?= $mapper->startDate != null ? strReplaceTemplate(localeDateFormat('%e %1 %B %1 Y', $mapper->startDate), ['%1' => __(LANG_GROUP, 'de')]) : __($langGroup, 'N/A'); ?></div>
                </div>
                <div class="three wide column">
                    <div class="base-title"><?= __($langGroup, 'Fecha final'); ?></div>
                    <div class="base-text"><?= $mapper->endDate != null ? strReplaceTemplate(localeDateFormat('%e %1 %B %1 Y', $mapper->endDate), ['%1' => __(LANG_GROUP, 'de')]) : __($langGroup, 'N/A'); ?></div>
                </div>
            </div>

            <div class="base-horizontal-space"></div>

            <div class="base-title"><?= __($langGroup, 'Descripción'); ?></div>
            <?php //HTML del editor, que NADA limpia al guardar: se lee aislado, en un iframe sandbox SIN allow-scripts (pendientes.md 396). ?>
            <iframe class="base-text" sandbox="" referrerpolicy="no-referrer" title="<?= $escape(__($langGroup, 'Descripción')); ?>" style="width: 100%; min-height: 28rem; border: 1px solid rgba(0, 0, 0, 0.15); background: #fff;" srcdoc="<?= $escape((string) $mapper->currentLangData('content')); ?>"></iframe>

            <div class="base-horizontal-space"></div>

            <div class="container-standard-form">
                <div class="base-title size3"><?= __($langGroup, 'Imágenes'); ?></div>
                <div class="base-horizontal-space"></div>
                <div class="form-attachments-regular">
                    <div data-trigger-open-link="<?= $escape($mapper->currentLangData('mainImage')); ?>" class="attach-placeholder tall">
                        <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                        <div class="ui top right attached label green">
                            <i class="paperclip icon"></i>
                        </div>
                        <label for="<?= $uniqueIdentifier; ?>">
                            <div class="image fullsize">
                                <img src="<?= $escape($mapper->currentLangData('mainImage')); ?>">
                            </div>
                            <div class="text">
                                <div class="header">
                                    <div class="title"><?= __($langGroup, 'Imagen principal'); ?></div>
                                </div>
                            </div>
                        </label>
                        <input type="file" accept="image/*" id="<?= $uniqueIdentifier; ?>">
                    </div>

                    <div data-trigger-open-link="<?= $escape($mapper->currentLangData('thumbImage')); ?>" class="attach-placeholder tall">
                        <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                        <div class="ui top right attached label green">
                            <i class="paperclip icon"></i>
                        </div>
                        <label for="<?= $uniqueIdentifier; ?>">
                            <div class="image fullsize">
                                <img src="<?= $escape($mapper->currentLangData('thumbImage')); ?>">
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
                    <?php foreach($mapper->getAttachmentsByLang($allowedLang, true) as $attachmentRecord): ?>
                    <?php $attachmentElement = new AttachmentPackage($mapper->id, $attachmentRecord->id, $attachmentRecord->attachmentName, false, $attachmentRecord->lang); ?>
                    <?php $attachmentMapper = $attachmentElement->getMapper(); ?>
                    <?php $hasAttachment = $attachmentElement->hasAttachment() && $attachmentMapper !== null; ?>
                    <?php $fileLocation = $hasAttachment ? $attachmentMapper->fileLocation : ''; ?>
                    <?php $isImage = $hasAttachment ? $attachmentMapper->fileIsImage() : ''; ?>
                    <?php $existingFileAttr = "data-trigger-download-link"; ?>
                    <?php $existingFileAttr = "{$existingFileAttr}='" . $escape($fileLocation) . "'"; ?>
                    <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                    <div <?= $existingFileAttr; ?> class="attach-placeholder" data-dynamic-attachment="<?= $uniqueIdentifier; ?>" data-mapper-id="<?= $attachmentMapper !== null ? $attachmentMapper->id : ''; ?>">
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
                                    <div class="title"><?= $escape($attachmentElement->getDisplayName()); ?></div>
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
