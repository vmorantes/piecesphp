<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var string $langGroup
 * @var string $title
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var \PiecesPHP\Logs\Controllers\HelperController $helpController
 * @var string $scope El alcance cuando está recortado; vacío si ve todo
 * @var array<int,array{key:string,title:string,icon:string,url:string,active:bool}> $tabs
 * @var string $content El HTML de la pestaña activa, que lo escapa su módulo
 */
?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
            $helpController->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
                'description' => $description,
            ]);
        ?>

        <?php if ($scope !== ''): ?>
        <p data-logs-scope><span class="ui grey text"><i class="filter icon"></i><?= escape_html($scope); ?></span></p>
        <?php endif; ?>

        <?php //Enlaces y no pestañas de Fomantic: cada una pinta su propio DataTables, y solo se pinta la activa. Sin marco
        //alrededor del contenido: dentro van bloques hermanos, nunca una caja dentro de otra (el PO, 2026-10-10). ?>
        <div class="ui secondary pointing stackable menu" data-logs-tabs>
            <?php foreach ($tabs as $tab): ?>
            <a class="<?= $tab['active'] ? 'active ' : ''; ?>item" href="<?= escape_html($tab['url']); ?>" data-logs-tab="<?= escape_html($tab['key']); ?>">
                <i class="<?= escape_html($tab['icon']); ?> icon"></i>
                <?= escape_html($tab['title']); ?>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="logs-tab-content" data-logs-tab-content>
            <?= $content; ?>
        </div>

    </div>

</section>
