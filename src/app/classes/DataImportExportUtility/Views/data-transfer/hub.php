<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var string $langGroup
 * @var string $title
 * @var string $breadcrumbs
 * @var array<int,array{title:string,link:string}> $importers
 * @var array<int,array{title:string,link:string}> $exporters
 */
?>
<section class="module-view-container">

    <div class="breadcrumb">
        <?= $breadcrumbs; ?>
    </div>

    <div class="limiter-content">

        <div class="section-title">
            <div class="title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="description"><?= __($langGroup, 'Elige qué quieres importar.'); ?></div>
        </div>

        <br>

        <h3 class="ui header"><?= __($langGroup, 'Importar'); ?></h3>
        <?php if (count($importers) === 0): ?>
        <p><?= __($langGroup, 'No hay importadores disponibles para tu usuario.'); ?></p>
        <?php else: ?>
        <div class="main-buttons">
            <?php foreach ($importers as $importer): ?>
            <a class="ui button brand-color" href="<?= htmlspecialchars($importer['link'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($importer['title'], ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <br>

        <h3 class="ui header"><?= __($langGroup, 'Exportar'); ?></h3>
        <?php if (count($exporters) === 0): ?>
        <p><?= __($langGroup, 'No hay exportadores disponibles para tu usuario.'); ?></p>
        <?php else: ?>
        <div class="main-buttons">
            <?php foreach ($exporters as $exporter): ?>
            <a class="ui button brand-color alt" href="<?= htmlspecialchars($exporter['link'] . '?format=xlsx', ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($exporter['title'] . ' (XLSX)', ENT_QUOTES, 'UTF-8'); ?></a>
            <a class="ui button brand-color alt" href="<?= htmlspecialchars($exporter['link'] . '?format=csv', ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($exporter['title'] . ' (CSV)', ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>

</section>
