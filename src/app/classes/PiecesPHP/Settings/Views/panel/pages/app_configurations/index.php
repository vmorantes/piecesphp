<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var array<int,array{name:string,visible:bool,items:array<int,array{name:string,icon:string,href:string,visible:bool}>}> $groups
 */
?>

<main class="configurations-index-view">
    <section class="main-body-header">
        <div class="head">
            <h2 class="tittle"><?= __($langGroup, 'Configuración'); ?></h2>
            <span class="sub-tittle"><?= __($langGroup, 'Apariencia, integraciones y sistema'); ?></span>
        </div>
        <div class="body-card">
            <div class="ui three column stackable grid">
                <?php foreach ($groups as $group): ?>
                <div class="column">
                    <div class="ui dividing header"><?= htmlspecialchars($group['name']); ?></div>
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
    </section>
</main>
