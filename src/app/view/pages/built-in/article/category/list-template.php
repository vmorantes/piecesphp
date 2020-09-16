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

            <ul built-in-categories-items-js built-in-categories-url="<?= $ajaxURL; ?>"></ul>

            <button type="button primary" built-in-categories-load-more-js><?= __(ArticleControllerPublic::LANG_GROUP, 'Cargar más'); ?></button>

        </section>
