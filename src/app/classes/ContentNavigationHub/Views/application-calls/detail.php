<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Locations\LocationsLang;
use ApplicationCalls\Mappers\ApplicationCallsMapper;
use ApplicationCalls\Util\AttachmentPackage;
use ContentNavigationHub\Controllers\ContentNavigationHubController;
use MySpace\Controllers\ProfileController;
use PiecesPHP\Core\Config;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * @var ApplicationCallsMapper $element
 * @var string $langGroup
 */
$participatingInstitutions = is_array($element->participatingInstitutions) ? $element->participatingInstitutions : [];
$researchAreas = $element->interestResearhAreas;
$researchAreas = is_array($researchAreas) ? $researchAreas : [];
$title = $element->currentLangData('title');
$mainImage = $element->currentLangData('mainImage');
$contentTypeForFullDisplayText = $element->contentTypeForFullDisplayText();
$contentTypeIcon = $element->contentTypeIcon();
$iconColor = $element->contentTypeIconColor();
$bgColor = $element->contentTypeBackgroundColor();
$startDate = strReplaceTemplate(localeDateFormat('%e %1 %B %1 Y', $element->startDate), ['%1' => __(LANG_GROUP, 'de')]);
$endDate = strReplaceTemplate(localeDateFormat('%e %1 %B %1 Y', $element->endDate), ['%1' => __(LANG_GROUP, 'de')]);
$targetCountries = implode(', ', array_map(fn($e) => __(LocationsLang::LANG_GROUP_NAMES, $e->name), $element->targetCountries));
$attachments = [];
foreach (Config::get_allowed_langs() as $allowedLang) {
    foreach ($element->getAttachmentsByLang($allowedLang, true) as $attachment) {
        $attachments[] = $attachment;
    }
}
$createdBy = $element->createdBy;
$createdByPackage = new UserDataPackage($createdBy->id);
$excerpt = $element->excerpt(403);
$excerpt = mb_strpos($excerpt, '...') !== false ? $excerpt : $excerpt . '...';
$singleURL = ContentNavigationHubController::routeName('application-calls-detail', ['id' => $element->id]);
?>

<section class="module-view-container content-hub-detail">

    <div class="breadcrumb">
        <?= $breadcrumbs ?>
    </div>

    <div class="limiter-content">

        <div class="section-title">
            <div class="title"><?= $element->contentTypeForFullDisplayText(); ?></div>
            <?php if(isset($description) && is_string($description) && mb_strlen(trim($description)) > 0): ?>
            <div class="description"><?= $description; ?></div>
            <?php endif; ?>
        </div>

        <div class="element-content">

            <div class="main-content">

                <div class="main-information-block">

                    <div class="main-information">
                        <div class="picture">
                            <img src="<?= $mainImage; ?>" alt="<?= $title; ?>">
                        </div>
                        <div class="data">
                            <div class="content">
                                <div class="element-title"><?= $title; ?></div>
                                <div class="meta">
                                    <div class="item">
                                        <div class="icon">
                                            <i class="calendar alternate outline icon"></i>
                                            <div class="text"><?= __($langGroup, 'Inicia'); ?></div>
                                        </div>
                                        <div class="data"><?= $startDate; ?></div>
                                    </div>
                                    <div class="item">
                                        <div class="icon">
                                            <i class="calendar alternate outline icon"></i>
                                            <div class="text"><?= __($langGroup, 'Finaliza'); ?></div>
                                        </div>
                                        <div class="data"><?= $endDate; ?></div>
                                    </div>
                                    <div class="item">
                                        <div class="icon">
                                            <i class="map outline icon"></i>
                                        </div>
                                        <div class="data mark"><?= $targetCountries; ?></div>
                                    </div>
                                    <?php if($element->amount > 0): ?>
                                    <div class="item">
                                        <div class="data"><?= __($langGroup, $element->currency); ?></div>
                                    </div>
                                    <div class="item">
                                        <div class="data mark"><?= number_format($element->amount, 0, ',', '.'); ?></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="bottombar" style="--bg-type-color: <?= $bgColor; ?>; --bg-icon-type-color: <?= $iconColor; ?>;">
                                <div class="element-type <?= $contentTypeIcon; ?>">
                                    <div class="icon">
                                        <i class="icon <?= $contentTypeIcon; ?>"></i>
                                    </div>
                                    <div class="text"><?= $contentTypeForFullDisplayText; ?></div>
                                </div>
                                <div class="actions">
                                    <div class="action" share-action data-title="<?= $title; ?>" data-text="<?= $excerpt; ?>" data-url="<?= $singleURL; ?>">
                                        <div class="icon">
                                            <i class="share icon"></i>
                                        </div>
                                        <?= __($langGroup, 'Compartir'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="section research-areas">
                    <?php if(!empty($researchAreas)): ?>
                    <div class="title"><?= __($langGroup, 'Áreas de investigación'); ?></div>
                    <div class="special-tags">
                        <?php foreach($researchAreas as $researchArea): ?>
                        <div class="tag" style="--tag-color: <?= $researchArea->color; ?>;"><?= $researchArea->currentLangData('areaName'); ?></div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="section contact-data mobile">
                    <div class="title"><?= __($langGroup, 'Persona encargada'); ?></div>
                    <div class="person-contact-data">
                        <div class="topbar">
                            <div class="avatar">
                                <img src="<?= $createdByPackage->getAvatarURL(); ?>" alt="<?= $createdByPackage->getMapper()->getFullName(); ?>">
                            </div>
                            <div class="actions">
                                <a class="button-link" href="<?= ProfileController::routeName('profile', ['userID' => $createdByPackage->id]); ?>">
                                    <i class="icon plus"></i>
                                    <?= __($langGroup, 'Ver perfil'); ?>
                                </a>
                            </div>
                        </div>
                        <div class="data">
                            <div class="name"><?= $createdByPackage->getMapper()->getFullName(); ?></div>
                            <div class="meta"><?= $createdByPackage->profile->currentLangData('jobPosition'); ?></div>
                            <div class="link"><a href="mailto:<?= $createdByPackage->email; ?>"><?= $createdByPackage->email; ?></a></div>
                        </div>
                    </div>
                </div>

                <div class="section institutions mobile">
                    <div class="title small-m-b"><?= __($langGroup, 'Instituciones que participan'); ?></div>
                    <ul class="container-list">
                        <?php foreach($participatingInstitutions as $i): ?>
                        <li class="item"><?= $i; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="section description"><?= $element->currentLangData('content'); ?></div>

                <?php if(!empty($attachments)): ?>
                <div class="section attachments">
                    <div class="title"><?= __($langGroup, 'Anexos'); ?></div>
                    <br>
                    <div class="form-attachments-regular">
                        <?php foreach($attachments as $attachmentMapper): ?>
                        <?php $attachmentElement = new AttachmentPackage($element->id, $attachmentMapper->id, $attachmentMapper->attachmentName, false, $attachmentMapper->lang); ?>
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
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="secondary-content">

                <div class="section contact-data desktop">
                    <div class="title"><?= __($langGroup, 'Persona encargada'); ?></div>
                    <div class="person-contact-data">
                        <div class="topbar">
                            <div class="avatar">
                                <img src="<?= $createdByPackage->getAvatarURL(); ?>" alt="<?= $createdByPackage->getMapper()->getFullName(); ?>">
                            </div>
                            <div class="actions">
                                <a class="button-link" href="<?= ProfileController::routeName('profile', ['userID' => $createdByPackage->id]); ?>">
                                    <i class="icon plus"></i>
                                    <?= __($langGroup, 'Ver perfil'); ?>
                                </a>
                            </div>
                        </div>
                        <div class="data">
                            <div class="name"><?= $createdByPackage->getMapper()->getFullName(); ?></div>
                            <div class="meta"><?= $createdByPackage->profile->currentLangData('jobPosition'); ?></div>
                            <div class="link"><a href="mailto:<?= $createdByPackage->email; ?>"><?= $createdByPackage->email; ?></a></div>
                        </div>
                    </div>
                </div>

                <div class="section institutions desktop">
                    <div class="title small-m-b"><?= __($langGroup, 'Instituciones que participan'); ?></div>
                    <ul class="container-list">
                        <?php foreach($participatingInstitutions as $i): ?>
                        <li class="item"><?= $i; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

            </div>
        </div>

    </div>
</section>
