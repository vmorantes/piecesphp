<?php

use PiecesPHP\BuiltIn\Article\Controllers\ArticleControllerPublic;

defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>
<!-- Main -->
<div id="main">
    <div class="inner">

        <!-- Header -->
        <?php if(isset($withSocialBar) && $withSocialBar === true):?>
        <?php $this->render('layout/social-bar', isset($socialBarData) ? $socialBarData : []); ?>
        <?php endif;?>

        <!-- Slideshow -->
        <section id="banner">

            <div class="vm-slideshow text-center" data-url="<?= $sliderAjax; ?>">
                <span class="prev">&#10094;</span>
                <span class="next">&#10095;</span>
                <div class="navigation-dots"></div>
            </div>

        </section>

        <!-- Section Products/Services-->
        <section data-smooth-target-id="services">
            <header class="major">
                <h2><?= __(LANG_GROUP, 'Características'); ?></h2>
            </header>
            <div class="features">
                <?= __(LANG_GROUP, 'FEATURES_HOME_ITEMS'); ?>
            </div>
        </section>

        <!-- Section Articles-->
        <section home-articles-container>

            <header class="major">
                <h2><?= __(LANG_GROUP, 'Publicaciones recientes'); ?></h2>
            </header>

            <div class="posts" home-articles-items-js home-articles-url="<?= $ajaxArticlesGlobalURL; ?>">
            </div>

            <ul class="actions">
                <li><a href="<?= ArticleControllerPublic::routeName('list'); ?>" class="button big primary"><?= __(LANG_GROUP, 'Más'); ?></a></li>
            </ul>

        </section>
