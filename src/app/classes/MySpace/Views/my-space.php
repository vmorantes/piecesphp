<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use Documents\Controllers\DocumentsController;
use ImagesRepository\Controllers\ImagesRepositoryController;

/**
 * @var string $langGroup
 */
?>

<div class="module-view-container">

    <div class="home-hello-section-title">
        <div class="title"><?= __($langGroup, 'Hola,'); ?></div>
        <div class="subtitle"><?= $subtitle; ?></div>
    </div>

    <div class="my-space-content">

        <div class="content">

            <div class="col-system no-padding">

                <div class="col-12">

                    <div class="direct-access">

                        <?php if(DocumentsController::allowedRoute('explorer')): ?>
                        <a class="item tall border-bg bg-1" style="background-image: url('statics/images/my-space/icon-decoration.png');" href="<?= DocumentsController::routeName('explorer'); ?>">
                            <div class="content">
                                <div class="number"><?= $qtyDocuments > 0 ? $qtyDocuments : "&nbsp;{$qtyDocuments}&nbsp;" ; ?></div>
                                <div class="title"><?= __($langGroup, 'Documentos'); ?></div>
                                <div class="subtitle"><?= __($langGroup, 'disponibles'); ?></div>
                            </div>
                        </a>
                        <?php endif; ?>

                        <?php if(ImagesRepositoryController::allowedRoute('filter-view')): ?>
                        <a class="item tall border-bg-2 bg-2" style="background-image: url('statics/images/my-space/icon-decoration-2.png');" href="<?= ImagesRepositoryController::routeName('filter-view'); ?>">
                            <div class="content">
                                <div class="number"><?= $qtyImages > 0 ? $qtyImages : "&nbsp;{$qtyImages}&nbsp;" ; ?></div>
                                <div class="title"><?= __($langGroup, 'Fotografías'); ?></div>
                                <div class="subtitle"><?= __($langGroup, 'disponibles'); ?></div>
                            </div>
                        </a>
                        <?php endif; ?>

                        <?php if(DocumentsController::allowedRoute('list')): ?>
                        <a class="item" style="background-image: url('statics/images/my-space/documents.png');" href="<?= DocumentsController::routeName('list'); ?>">
                            <div class="content">
                                <div class="title"><?= __($langGroup, 'Carga de<br>documentos'); ?></div>
                            </div>
                        </a>
                        <?php endif; ?>

                        <?php if(ImagesRepositoryController::allowedRoute('list')): ?>
                        <a class="item" style="background-image: url('statics/images/my-space/images.png');" href="<?= ImagesRepositoryController::routeName('list'); ?>">
                            <div class="content">
                                <div class="title"><?= __($langGroup, 'Carga de<br>imágenes'); ?></div>
                            </div>
                        </a>
                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

    </div>
    
</div>
