<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use MySpace\Controllers\MySpaceController;

/**
 * Un capítulo del manual de usuario (ADR 0072), con la lista de los que ve el usuario al lado. Solo piezas y marcado de
 * Fomantic: el cuerpo lo genera bin/manual con sus clases.
 *
 * @var string $langGroup
 * @var string $title
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var array<int,array{slug:string,titulo:string}> $chapters
 * @var string $current El slug del capítulo que se lee.
 * @var string $content El HTML generado del capítulo.
 * @var \MySpace\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
?>
<section class="module-view-container">

    <?php
        $helpController->_render('panel/built-in/content/page-header.php', [
            'breadcrumbs' => $breadcrumbs,
            'title' => $title,
        ]);
    ?>

    <div class="ui stackable grid">
        <div class="four wide computer sixteen wide tablet column">
            <nav class="ui vertical fluid secondary menu" aria-label="<?= escape_html(__($langGroup, 'Capítulos del manual')); ?>">
                <?php foreach ($chapters as $chapter): ?>
                <a class="<?= $chapter['slug'] === $current ? 'active ' : ''; ?>item" href="<?= escape_html(MySpaceController::routeName('manual-page', ['slug' => $chapter['slug']])); ?>"<?= $chapter['slug'] === $current ? ' aria-current="page"' : ''; ?>><?= escape_html($chapter['titulo']); ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
        <div class="twelve wide computer sixteen wide tablet column">
            <article class="ui basic fitted segment">
                <?= $content; ?>
            </article>
        </div>
    </div>

</section>
