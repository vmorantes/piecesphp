<?php
use Publications\PublicationsRoutes;
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<section class="body">

    <div class="content" publications-regular-section>
        <?php if(PublicationsRoutes::ENABLE): ?>
        <div class="wrapper">

            <h2 class="segment-title text-center"><?= $titleSection; ?></h2>

            <section class="ui cards posts-list" data-url="<?= $ajaxURL; ?>" publications-regular-block>

            </section>

            <a href="#" class="more element-center" publications-regular-block-load-more><?= __(LANG_GROUP, 'Cargar más'); ?></a>

        </div>
        <?php endif; ?>
    </div>

</section>
