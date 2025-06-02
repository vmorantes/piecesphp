<?php
use ApplicationCalls\ApplicationCallsRoutes;
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<section class="body">

    <div class="content">
        <?php if(ApplicationCallsRoutes::ENABLE): ?>
        <div class="wrapper">

            <h2 class="segment-title text-center"><?= $titleSection; ?></h2>

            <section class="ui cards posts-list" data-application-call-url="<?= $ajaxURL; ?>" application-calls-js>

            </section>

            <a href="#" class="more element-center" application-calls-load-more-js><?= __(LANG_GROUP, 'Cargar más'); ?></a>

        </div>
        <?php endif; ?>
    </div>

</section>
