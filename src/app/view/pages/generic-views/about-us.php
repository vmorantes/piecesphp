<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<!-- Main -->
<div id="main">
    <div class="inner">

        <!-- Header -->
        <?php if(isset($withSocialBar) && $withSocialBar === true):?>
        <?php $this->render('layout/social-bar', isset($socialBarData) ? $socialBarData : []); ?>
        <?php endif;?>

        <!-- Content -->
        <section class="vm-content-generic-views">

            <header class="main">
                <h1><?= __(LANG_GROUP, 'Sobre nosotros'); ?></h1>
            </header>

            <span class="image main">
                <img loading="lazy" src="<?= baseurl('statics/images/generic-views/about-us.jpg'); ?>" alt="<?= __(LANG_GROUP, 'Sobre nosotros'); ?>" />
            </span>

            <p><?= __(LANG_GROUP, 'ABOUT_TEXT'); ?></p>

        </section>
