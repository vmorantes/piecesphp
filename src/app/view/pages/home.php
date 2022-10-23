<?php
use Publications\Controllers\PublicationsPublicController;
use Publications\PublicationsRoutes;
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>

<section class="hero">

    <div class="content bg-1">

        <div class="wrapper oversize unbounds blured">
            <div class="blur"></div>
            <div class="slideshow slideshow-main-home text-center" data-url="<?= $sliderAjax; ?>">
                <span class="prev">&#10094;</span>
                <span class="next">&#10095;</span>
                <div class="navigation-dots"></div>
            </div>

        </div>

    </div>

</section>

<section class="body">

    <?php if(PublicationsRoutes::ENABLE): ?>
    <div class="content bg-1 overlay-effect">

        <div class="wrapper no-padding-top-mobile">

            <h2 class="segment-title text-center"><?= __(LANG_GROUP, 'Publicaciones'); ?></h2>

            <section class="ui cards centered posts-list" articles-container data-route="<?= $ajaxArticlesURL; ?>">
            </section>

            <a href="<?= PublicationsPublicController::routeName('list', [], true); ?>" class="more element-center"><?= __($langGroup, 'Ver más'); ?></a>

        </div>

    </div>
    <?php endif; ?>

    <div class="content">

        <div class="wrapper no-padding-top-mobile">

            <h2 class="segment-title text-center">Lorem, ipsum dolor.</h2>

        </div>

        <div class="wrapper">

            <div class="slideshow slideshow-static element-center text-center">

                <span class="prev">&#10094;</span>
                <span class="next">&#10095;</span>
                <div class="navigation-dots"></div>

                <div class="item">
                    <img loading="lazy" src="https://via.placeholder.com/400x300">
                    <div class="caption">
                        <div class="title">Lorem ipsum dolor sit.</div>
                        <div class="text">Reiciendis dolore minima officia assumenda asperiores quam</div>
                    </div>
                </div>

                <a class="item" href="//google.com" rel="noreferrer" target="_blank">
                    <img loading="lazy" src="https://via.placeholder.com/400x300">
                    <div class="caption">
                        <div class="title">Lorem ipsum dolor sit.</div>
                        <div class="text">Reiciendis dolore minima officia assumenda asperiores quam</div>
                    </div>
                </a>

                <a class="item" href="//google.com" rel="noreferrer" target="_blank">
                    <img loading="lazy" src="https://via.placeholder.com/400x300">
                </a>

                <div class="item">
                    <img loading="lazy" src="https://via.placeholder.com/400x300">
                    <div class="caption">
                        <div class="title">Lorem ipsum dolor sit.</div>
                    </div>
                </div>

                <div class="item">
                    <img loading="lazy" src="https://via.placeholder.com/400x300">
                    <div class="caption">
                        <div class="text">Reiciendis dolore minima officia assumenda asperiores quam</div>
                    </div>
                </div>

                <div class="item">
                    <img loading="lazy" src="https://via.placeholder.com/400x300">
                </div>

            </div>

        </div>

    </div>

    <?php if($suscriberEnable) : ?>
    <div class="content">

        <div class="wrapper mw-800">

            <h2 class="segment-title text-center"><?= __(LANG_GROUP, 'Suscribirse'); ?></h2>

            <p><?= __(LANG_GROUP, 'Recibe nuestras actualizaciones a tu correo'); ?></p>

            <form action="<?= $addSuscriberURL; ?>" class="ui form add-suscriber">
                <div class="field">
                    <div class="ui action input">
                        <input type="email" name="email">
                        <button class="ui right labeled icon button" type="submit">
                            <i class="send icon"></i>
                            <?= __(LANG_GROUP, 'Suscribirse'); ?>
                        </button>
                    </div>
                </div>
            </form>

        </div>

    </div>
    <?php endif; ?>

</section>
