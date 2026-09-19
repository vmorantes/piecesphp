<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use Importers\Controller\ImporterController;
$langGroup = ImporterController::LANG_GROUP;
?>
<section class="module-view-container">

    <div class="breadcrumb">
        <?= $breadcrumbs ?>
    </div>

    <div class="section-title">
        <div class="title"><?= $title; ?></div>
        <?php if(isset($description) && is_string($description) && mb_strlen(trim($description)) > 0): ?>
        <div class="description"><?= $description; ?></div>
        <?php endif; ?>
    </div>

    <br><br>

    <div class="limiter-content background">

        <div class="section-title">
            <?php if(isset($subtitle) && is_string($subtitle) && mb_strlen(trim($subtitle)) > 0): ?>
            <div class="title"><?= $subtitle; ?></div>
            <?php endif; ?>
            <?php if(isset($text) && is_string($text) && mb_strlen(trim($text)) > 0): ?>
            <div class="description"><?= $text; ?></div>
            <?php endif; ?>
        </div>

        <div class="cards-importer">

            <div class="item">
                <form action="<?=$action?>" method="POST" enctype="multipart/form-data" importer-js>
                    <div class="attach-placeholder template-upload fluid w-card">
                        <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                        <div class="ui top right attached label green">
                            <i class="paperclip icon"></i>
                        </div>
                        <label for="<?= $uniqueIdentifier; ?>">
                            <div data-image="" class="image" data-on-change-text="<?= __($langGroup, 'Cambiar'); ?>">
                                <i class="icon upload"></i>
                                <div class="caption"><?= __($langGroup, 'Anexar'); ?></div>
                            </div>
                            <div class="text">
                                <div class="filename"></div>
                                <div class="header">
                                    <div class="title"><?= __($langGroup, 'Cargar archivo con datos'); ?></div>
                                    <div class="meta"><?= __($langGroup, 'Documento excel'); ?></div>
                                </div>
                                <div class="description">&nbsp;</div>
                            </div>
                        </label>
                        <input type="file" name="archivo" accept="application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" id="<?= $uniqueIdentifier; ?>">
                    </div>
                </form>
            </div>

            <div class="item">
                <a class="attach-placeholder template-download fluid w-card" href="<?= $template; ?>" download>
                    <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                    <label for="<?= $uniqueIdentifier; ?>">
                        <div data-image="" class="image mark" data-on-change-text="<?= __($langGroup, 'Cambiar'); ?>">
                            <i class="icon download"></i>
                            <div class="caption"><?= __($langGroup, 'Descargar'); ?></div>
                        </div>
                        <div class="text">
                            <div class="filename"></div>
                            <div class="header">
                                <div class="title"><?= __($langGroup, 'Plantilla de importación de usuarios'); ?></div>
                            </div>
                        </div>
                    </label>
                </a>
            </div>

        </div>

        <br>

        <div class="result-container" import-result-js>
            <div class="ui header medium"><?= __($langGroup, 'Resultado de la importación'); ?></div>
            <div class="ui statistics">
                <div class="statistic">
                    <div class="value">
                        <i class="cloud upload icon"></i>
                        <span class="number total">0</span>
                    </div>
                    <div class="label"><?= __($langGroup, 'Total'); ?></div>
                </div>
                <div class="statistic">
                    <div class="value">
                        <i class="check icon"></i>
                        <span class="number success">0</span>
                    </div>
                    <div class="label"><?= __($langGroup, 'Exitosos'); ?></div>
                </div>
                <div class="statistic">
                    <div class="value">
                        <i class="close icon"></i>
                        <span class="number errors">0</span>
                    </div>
                    <div class="label"><?= __($langGroup, 'Errores'); ?></div>
                </div>
            </div>
            <div>
                <br>
                <button view-detail class="ui button blue icon"><i class="icon eye"></i> <?= __($langGroup, 'Ver detalle'); ?></button>
                <br>
            </div>
            <div class="ui modal messages">
                <div class="header"><?= __($langGroup, 'Detalles de la importación'); ?></div>
                <div class="content"></div>
            </div>
            <br><br>
        </div>


    </div>

</section>
