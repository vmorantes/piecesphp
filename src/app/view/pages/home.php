<?php
use PiecesPHP\BuiltIn\Article\Controllers\ArticleControllerPublic;
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>

<section class="hero">

    <div class="content bg-1">

        <div class="wrapper unbounds">

            <div class="slideshow text-center" data-url="<?= $sliderAjax; ?>">
                <span class="prev">&#10094;</span>
                <span class="next">&#10095;</span>
                <div class="navigation-dots"></div>
            </div>

        </div>

    </div>

</section>

<section class="body">

    <div class="content bg-1 overlay-effect">

        <div class="wrapper no-padding-top-mobile">

            <h2 class="segment-title text-center"><?= __(LANG_GROUP, 'Publicaciones'); ?></h2>

            <section class="posts-list" articles-container data-route="<?= $ajaxArticlesURL; ?>"></section>

            <a href="<?= ArticleControllerPublic::routeName('list'); ?>" class="more element-center"><?= __($langGroup, 'Ver más'); ?></a>

        </div>

    </div>

    <div class="content">

        <div class="wrapper no-padding-top-mobile">

            <h2 class="segment-title text-center">Personas</h2>

            <section class="persons-list">

                <article class="item">

                    <div class="image">
                        <img src="https://via.placeholder.com/300x300">
                    </div>

                    <div class="content">

                        <div class="title">Juan Pedro</div>
                        <div class="meta">Bimba Pérez</div>

                        <div class="description">
                            Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo
                            consequat.
                        </div>

                    </div>

                </article>

                <article class="item">

                    <div class="image">
                        <img src="https://via.placeholder.com/300x300">
                    </div>

                    <div class="content">

                        <div class="title">Juan Pedro</div>
                        <div class="meta">Bimba Pérez</div>

                        <div class="description">
                            Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo
                            consequat.
                        </div>

                    </div>

                </article>

                <article class="item">

                    <div class="image">
                        <img src="https://via.placeholder.com/300x300">
                    </div>

                    <div class="content">

                        <div class="title">Juan Pedro</div>
                        <div class="meta">Bimba Pérez</div>

                        <div class="description">
                            Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo
                            consequat.
                        </div>

                    </div>

                </article>

                <article class="item">

                    <div class="image">
                        <img src="https://via.placeholder.com/300x300">
                    </div>

                    <div class="content">

                        <div class="title">Juan Pedro</div>
                        <div class="meta">Bimba Pérez</div>

                        <div class="description">
                            Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo
                            consequat.
                        </div>

                    </div>

                </article>

            </section>

        </div>

    </div>

</section>
