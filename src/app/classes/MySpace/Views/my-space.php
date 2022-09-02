<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use Documents\Controllers\DocumentsController;
use ImagesRepository\Controllers\ImagesRepositoryController;
use News\NewsRoutes;

/**
 * @var string $langGroup
 */
?>

<div class="custom-ribbon wide no-negative">
    <img src="statics/images/dashboard/decoration-ribbon.png" class="decoration">
    <div class="header-area center fluid">
        <div class="title big"><?= __($langGroup, '¡HOLA!'); ?></div>
        <div class="subtitle black big"><?= $subtitle; ?></div>
    </div>
</div>

<div class="my-space-content">

    <div class="column-medium-6">

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

    <div class="column-medium-6<?= NewsRoutes::ENABLE ? ' news-content' : ''; ?>" data-url="<?= $newsAjaxURL; ?>">

        <?php if(NewsRoutes::ENABLE): ?>
        <div class="title">
            <?= __($langGroup, 'Noticias'); ?>
        </div>

        <div class="non-results-content">
            <div class="title"><?= __($langGroup, 'Ups!'); ?></div>
            <div class="text"><?= __($langGroup, 'En este momento no tenemos noticias'); ?></div>
            <div class="image">
                <img src="statics/images/news/non-results.png" alt="<?= __($langGroup, 'En este momento no tenemos noticias'); ?>">
            </div>
        </div>

        <div class="content"></div>

        <div class="footer">
            <div class="ui button brand-color alt" news-load-more-js>
                <?= __($langGroup, 'Cargar más'); ?>...
            </div>
        </div>

        <?php endif; ?>

    </div>
</div>
