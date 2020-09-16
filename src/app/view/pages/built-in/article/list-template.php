<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\BuiltIn\Article\Controllers\ArticleControllerPublic;
?>
<!-- Main -->
<div id="main">
    <div class="inner">

        <!-- Header -->
        <?php if(isset($withSocialBar) && $withSocialBar === true):?>
        <?php $this->render('layout/template-inc/social-bar', isset($socialBarData) ? $socialBarData : []); ?>
        <?php endif;?>

        <!-- Section -->
        <section>

            <div class="posts" built-in-articles-items-js built-in-articles-url="<?= $ajaxURL; ?>">
            </div>

            <button class="button primary" built-in-articles-load-more-js><?= __(ArticleControllerPublic::LANG_GROUP, 'Cargar más'); ?></button>

        </section>
