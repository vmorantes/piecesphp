<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * Un capítulo del manual de usuario (ADR 0072), con la lista de los que ve el usuario al lado. Solo piezas y marcado de
 * Fomantic: el cuerpo lo genera bin/manual con sus clases.
 *
 * @var string $langGroup
 * @var string $title
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var array<int,array{label:string,href:string,active:bool,children:array<int,array{label:string,anchor:string}>}> $navItems
 *      Los capítulos que ve, para la pieza section-nav; bajo el actual, sus secciones visibles.
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
            <?php
                $helpController->_render('panel/built-in/content/section-nav.php', [
                    'label' => __($langGroup, 'Capítulos del manual'),
                    'items' => $navItems,
                ]);
            ?>
        </div>
        <div class="twelve wide computer sixteen wide tablet column">
            <article class="ui basic fitted segment">
                <?= $content; ?>
            </article>
        </div>
    </div>

</section>
