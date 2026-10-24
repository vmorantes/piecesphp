<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $title
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 * @var array<int,array{name:string,visible:bool,items:array<int,array{name:string,icon:string,href:string,visible:bool}>}> $groups
 */
?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
        //La cabecera del diseño de contenidos (diseno/02-configuracion.md §2.1-2.2).
        $helpController->_render('panel/built-in/content/page-header.php', [
            'breadcrumbs' => $breadcrumbs,
            'title' => $title,
            'description' => $description,
        ]);
        ?>

        <div class="container-standard-form">
                <div class="ui three column stackable grid">
                    <?php foreach ($groups as $group): ?>
                    <div class="column">
                        <div class="form-section-title"><?= htmlspecialchars($group['name']); ?></div>
                        <div class="ui relaxed list">
                            <?php foreach ($group['items'] as $item): ?>
                            <?php if ($item['visible']): ?>
                            <a class="item" href="<?= $item['href']; ?>">
                                <i class="<?= $item['icon']; ?> icon"></i>
                                <div class="content"><?= htmlspecialchars($item['name']); ?></div>
                            </a>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
        </div>

    </div>

</section>
