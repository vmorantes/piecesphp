<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
$sidebarMenu = get_sidebar_menu();
?>
<div class="ui-pcs sidebar-toggle">
    <i class="icon bars"></i>
</div>
<aside main-aside class="ui-pcs sidebar">

    <div bar-controller class="bar-controller">
        <i class="angle left icon"></i>
    </div>

    <div class="content">

        <div class="logo">

            <div class="image round">
                <img src="<?= get_config('logo'); ?>">
            </div>

            <div class="expand-vs text"><?= strReplaceTemplate(__('general', 'Versión {ver}'), ['{ver}' => APP_VERSION,]) ?></div>
            <div class="contrac-vs text"><?= strReplaceTemplate(__('general', 'V {ver}'), ['{ver}' => APP_VERSION,]) ?></div>

        </div>

        <article class="links">

            <?php foreach ($sidebarMenu->getItems() as $element) : ?>

            <?php $hasIcon = $element->hasIcon(); ?>
            <?php $icon = $element->getIcon(false); ?>
            <?php $isCurrent = $element->isCurrent(); ?>
            <?php $elementAttributes = $element->getAttributes(); ?>
            <?php $elementClasses = array_key_exists('class', $elementAttributes) ? ' ' . $elementAttributes['class'] : ''; ?>
            <?php $hrefTarget = $element->getHrefTarget(); ?>

            <ul <?= $isCurrent ? ' current' : '' ?> class="group <?= !$hasIcon ? 'no-icon' : ''; ?> <?= $elementClasses; ?>" <?= $element->getAttributes(true); ?>>

                <?php if($element->isVisible() && (!$element->asLink() ? count($element->getItems()) > 0 : true)): ?>

                <?php if($element->asLink()): ?>

                <a class="title-group as-link <?= $isCurrent ? ' current' : '' ?>" href="<?= $element->getHref(); ?>" <?= mb_strlen($hrefTarget) > 0 ? "target='$hrefTarget'" : ''; ?>>
                    <?php if($hasIcon): ?>
                    <i class="icon <?= $icon; ?>"></i>
                    <?php endif; ?>
                    <span><?= $element->getName(); ?></span>
                    <div class="tool-tip">
                        <?= $element->getName(); ?>
                    </div>
                </a>

                <?php else: ?>

                <?php $subItems = $element->getItems(); ?>

                <div class="title-group <?= $isCurrent ? ' current' : '' ?>">
                    <?php if($hasIcon): ?>
                    <i class="icon <?= $icon; ?>"></i>
                    <?php endif; ?>
                    <span><?= $element->getName(); ?></span>

                    <div class="tool-tip">
                        <span class="title"><?= $element->getName(); ?></span>

                        <div class="options">
                            <?php foreach ($subItems as $subElement) : ?>

                            <?php $subIsCurrent = $subElement->isCurrent(); ?>
                            <?php $subElementAttributes = $subElement->getAttributes(); ?>
                            <?php $subElementClasses = array_key_exists('class', $subElementAttributes) ? ' ' . $subElementAttributes['class'] : ''; ?>

                            <li>
                                <?php if (!$subIsCurrent) : ?>
                                <a class="tool-item <?= $subIsCurrent ? ' current' : '' ?>" href="<?= $subElement->getHref(); ?>">
                                    <?= $subElement->getText(); ?>
                                </a>
                                <?php else : ?>
                                <span class="tool-item <?= $subIsCurrent ? ' current' : '' ?>" href="<?= $subElement->getHref(); ?>">
                                    <?= $subElement->getText(); ?>
                                </span>
                                <?php endif; ?>
                            </li>

                            <?php endforeach; ?>
                        </div>

                    </div>

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

                <?php endif; ?>
            </ul>

            <?php endforeach; ?>
        </article>

        <div class="footer-sidebar">
            <span only-expanded class="main"><?= get_config('owner'); ?></span>
            <span only-expanded class="text"><?= __(LANG_GROUP, 'Todos los derechos reservados'); ?></span>
            <span class="meta"><?= date('Y'); ?></span>
            <div menu-footer-images class="images-grup">
                <img class="horizontal" src="<?= get_config('partners'); ?>">
                <img class="vertical" src="<?= get_config('partnersVertical'); ?>">
            </div>
        </div>
    </div>
</aside> <!-- .ui-pcs.sidebar -->