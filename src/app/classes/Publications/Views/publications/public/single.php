<?php

use Publications\Mappers\AttachmentPublicationMapper;
use Publications\Mappers\PublicationMapper;

defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var PublicationMapper $element
 */;
$langGroup;
$element;
$iconByExtension = function (string $extension, string $mimeType = null) {
    $icon = 'file';
    $finded = false;
    $extension = trim(mb_strtolower($extension));
    $equivalencesExtensions = [
        'pdf' => 'pdf file',
        'xlsx' => 'excel file',
        'xls' => 'excel file',
        'xlx' => 'excel file',
        'ods' => 'excel file',
        'doc' => 'word file',
        'docx' => 'word file',
        'odt' => 'word file',
    ];
    $equivalencesMimeTypes = [
        'image/' => 'image file',
    ];
    foreach ($equivalencesExtensions as $key => $value) {
        if ($key === $extension) {
            $icon = $value;
            $finded = true;
            break;
        }
    }
    if (!$finded) {
        foreach ($equivalencesMimeTypes as $key => $value) {
            if (strpos($mimeType, $key) !== false) {
                $icon = $value;
                $finded = true;
                break;
            }
        }
    }
    return  $icon;
};
$ignoreNames = [
    AttachmentPublicationMapper::ATTACHMENT_TYPE_1,
];
?>
<section class="body">

    <div class="content">

        <div class="wrapper unbounds">

            <div class="post-image">
                <img src="<?= $element->currentLangData('mainImage'); ?>" alt="<?= $element->currentLangData('title'); ?>">
            </div>

            <div class="text-center">
                <strong><?= $element->publicDateFormat(); ?></strong>
                -
                <em><?= $element->authorFullName(); ?></em>
            </div>

            <h2 class="segment-title text-center mw-1200 element-center"><?= $element->currentLangData('title'); ?></h2>

        </div>

        <div class="wrapper unbounds">
            <div class="post-content"><?= $element->currentLangData('content'); ?></div>
        </div>

        <div class="wrapper">
            <div class="attachments">
                <div class="text-center">
                    <div class="ui horizontal list">
                        <?php $attachments = $element->getAttachments(true, true); ?>
                        <?php $order = 0; ?>
                        <?php foreach ($attachments as $attachment): ?>
                        <?php $order++; ?>
                        <a href="<?= $attachment->fileLocation; ?>" target="_blank" class="item">
                            <i class="large <?= ($iconByExtension)($attachment->getExtension(), $attachment->getMimeType()); ?> middle aligned icon"></i>
                            <div class="content">
                                <div class="header"><?=  __($langGroup, 'Anexo #') . $order; ?></div>
                                <?= !in_array($attachment->attachmentType, $ignoreNames) ? $attachment->typeName() : ''; ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>

</section>
