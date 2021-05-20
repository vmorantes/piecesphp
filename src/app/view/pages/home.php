<?php
use Publications\Controllers\PublicationsPublicController;

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

            <a href="<?= PublicationsPublicController::routeName('list'); ?>" class="more element-center"><?= __($langGroup, 'Ver más'); ?></a>

        </div>

    </div>

    <div class="content">

        <div class="wrapper no-padding-top-mobile">

            <h2 class="segment-title text-center">Lorem, ipsum dolor.</h2>

            <section class="persons-list">

                <article class="item">

                    <div class="image">
                        <img src="https://via.placeholder.com/300x300">
                    </div>

                    <div class="content">
                        <div class="title">John Doe</div>
                        <div class="meta">Junior Developer</div>
                        <div class="description">Lorem ipsum dolor sit amet consectetur adipisicing elit. Nobis exercitationem beatae magni praesentium ipsam ipsa, facere aliquam dolorum sequi earum commodi necessitatibus nihil obcaecati alias animi? Expedita enim nesciunt molestiae!</div>
                    </div>

                </article>

                <article class="item">

                    <div class="image">
                        <img src="https://via.placeholder.com/300x300">
                    </div>

                    <div class="content">
                        <div class="title">John Doe</div>
                        <div class="meta">Designer</div>
                        <div class="description">Lorem ipsum dolor sit amet consectetur adipisicing elit. Nobis exercitationem beatae magni praesentium ipsam ipsa, facere aliquam dolorum sequi earum commodi necessitatibus nihil obcaecati alias animi? Expedita enim nesciunt molestiae!</div>
                    </div>

                </article>

                <article class="item">

                    <div class="image">
                        <img src="https://via.placeholder.com/300x300">
                    </div>

                    <div class="content">
                        <div class="title">John Doe</div>
                        <div class="meta">Junior Developer</div>
                        <div class="description">Lorem ipsum dolor sit amet consectetur adipisicing elit. Nobis exercitationem beatae magni praesentium ipsam ipsa, facere aliquam dolorum sequi earum commodi necessitatibus nihil obcaecati alias animi? Expedita enim nesciunt molestiae!</div>
                    </div>

                </article>

                <article class="item">

                    <div class="image">
                        <img src="https://via.placeholder.com/300x300">
                    </div>

                    <div class="content">
                        <div class="title">John Doe</div>
                        <div class="meta">Designer</div>
                        <div class="description">Lorem ipsum dolor sit amet consectetur adipisicing elit. Nobis exercitationem beatae magni praesentium ipsam ipsa, facere aliquam dolorum sequi earum commodi necessitatibus nihil obcaecati alias animi? Expedita enim nesciunt molestiae!</div>
                    </div>

                </article>

            </section>

        </div>

    </div>

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

</section>
