<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var string $langGroup
 * @var string $title
 * @var string $breadcrumbs
 * @var array<int,array{title:string,link:string}> $importers
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

        <?php if (count($importers) === 0): ?>
        <p><?= __($langGroup, 'No hay importadores disponibles para tu usuario.'); ?></p>
        <?php else: ?>
        <div class="main-buttons">
            <?php foreach ($importers as $importer): ?>
            <a class="ui button brand-color" href="<?= htmlspecialchars($importer['link'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($importer['title'], ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>

</section>
