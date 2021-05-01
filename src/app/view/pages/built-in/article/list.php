<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>

<section class="body">

    <div class="content">

        <div class="wrapper">

            <h2 class="segment-title text-center"><?= $titleSection; ?></h2>

            <section class="posts-list horizontal" built-in-articles-items-js built-in-articles-url="<?= $ajaxURL; ?>">

            </section>

            <a href="#" class="more element-center" built-in-articles-load-more-js><?= __(LANG_GROUP, 'Cargar más'); ?></a>

        </div>

    </div>

</section>
