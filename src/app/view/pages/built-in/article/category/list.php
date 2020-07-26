<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>
<!-- Main -->
<div id="main">
    <div class="inner">

        <!-- Header -->
        <?php if(isset($withSocialBar) && $withSocialBar === true):?>
        <?php $this->render('layout/social-bar', isset($socialBarData) ? $socialBarData : []); ?>
        <?php endif;?>

        <!-- Section -->
        <section class="vm-content-generic-views">

            <header class="major">
                <h2><?= __(LANG_GROUP, 'Categorías'); ?></h2>
            </header>

            <ul built-in-categories-items-js built-in-categories-url="<?= $ajaxURL; ?>"></ul>

            <button type="button primary" built-in-categories-load-more-js><?= __('articlesFrontEnd', 'Cargar más'); ?></button>

        </section>
