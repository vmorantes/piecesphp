<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<section class="body">

    <div class="content">

        <div class="wrapper">

            <h2 class="segment-title text-center"><?= $titleSection; ?></h2>

            <section class="ui cards posts-list" data-publication-url="<?= $ajaxURL; ?>" publications-js>

            </section>

            <a href="#" class="more element-center" publications-load-more-js><?= __(LANG_GROUP, 'Cargar más'); ?></a>

        </div>

    </div>

</section>
