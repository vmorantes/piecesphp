<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
$sidebarMenu = sidebar_menu();
?>
<div class="ui-pcs sidebar-toggle">
    <i class="icon bars"></i>
</div>
<aside class="ui-pcs sidebar">

    <div class="logo">

        <div class="image">
            <img src="<?= get_config('logo'); ?>">
        </div>

        <div class="text"><?= strReplaceTemplate(__('general', 'Versión {ver}'), ['{ver}' => APP_VERSION,])?></div>

    </div>

    <article class="links">

        <?php foreach($sidebarMenu->getItems() as $element): ?>

        <?php $hasIcon = $element->hasIcon(); ?>
        <?php $icon = $element->getIcon(false); ?>
        <?php $isCurrent = $element->isCurrent(); ?>
        <?php $elementAttributes = $element->getAttributes(); ?>
        <?php $elementClasses = array_key_exists('class', $elementAttributes) ? ' ' . $elementAttributes['class'] : ''; ?>
        <?php $hrefTarget = $element->getHrefTarget(); ?>

        <ul <?= $isCurrent ? ' current' : '' ?> class="group <?= !$hasIcon ? 'no-icon' : ''; ?> <?= $elementClasses; ?>" <?= $element->getAttributes(true); ?>>

            <?php if($element->asLink()): ?>

            <a class="title-group as-link <?= $isCurrent ? ' current' : '' ?>" href="<?= $element->getHref(); ?>" <?= mb_strlen($hrefTarget) > 0 ? "target='$hrefTarget'" : ''; ?>>
                <?php if($hasIcon): ?>
                <i class="icon <?= $icon; ?>"></i>
                <?php endif; ?>
                <span><?= $element->getName(); ?></span>
            </a>

            <?php else: ?>

            <?php $subItems = $element->getItems(); ?>

            <div class="title-group <?= $isCurrent ? ' current' : '' ?>">
                <?php if($hasIcon): ?>
                <i class="icon <?= $icon; ?>"></i>
                <?php endif; ?>
                <span><?= $element->getName(); ?></span>
            </div>

            <div class="items">

                <?php foreach($subItems as $subElement): ?>

                <?php $subIsCurrent = $subElement->isCurrent(); ?>
                <?php $subElementAttributes = $subElement->getAttributes(); ?>
                <?php $subElementClasses = array_key_exists('class', $subElementAttributes) ? ' ' . $subElementAttributes['class'] : ''; ?>

                <li>
                    <?php if(!$subIsCurrent): ?>
                    <a class="item <?= $subIsCurrent ? ' current' : '' ?>" href="<?= $subElement->getHref(); ?>">
                        <?= $subElement->getText(); ?>
                    </a>
                    <?php else: ?>
                    <span class="item <?= $subIsCurrent ? ' current' : '' ?>" href="<?= $subElement->getHref(); ?>">
                        <?= $subElement->getText(); ?>
                    </span>
                    <?php endif; ?>
                </li>

                <?php endforeach; ?>

            </div>

            <?php endif; ?>

        </ul>

        <?php endforeach; ?>
    </article>

    <div class="footer-sidebar">
        <span class="main"><?= get_config('owner'); ?></span>
        <span class="text"><?= __(LANG_GROUP, 'Todos los derechos reservados'); ?></span>
        <img src="<?= get_config('logo'); ?>">
        <span class="meta"><?= date('Y'); ?></span>
    </div>
</aside> <!-- .ui-pcs.sidebar -->
