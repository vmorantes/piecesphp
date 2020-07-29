<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>
<!-- Sidebar -->
<div id="sidebar">
    <div class="inner">

        <section class="image logo">
            <img loading="lazy" src="<?= baseurl("statics/images/logo.png"); ?>">
        </section>

        <?php if(isset($withSearchEngine) && $withSearchEngine): ?>
        <!-- Search -->
        <section id="search" class="alt">
            <form method="post" action="#">
                <input type="text" name="query" id="query" placeholder="Search" />
            </form>
        </section>
        <?php endif; ?>

        <!-- Menu -->
        <?php $this->render('layout/menu'); ?>

        <section class="featured-sidebar-image">
            <a href="#" target="_blank">
                <img src="img-gen/500/300">
            </a>
        </section>

        <?php if(isset($withRecents) && $withRecents): ?>
        <!-- Section recents articles -->
        <?php $this->render('layout/inc/sidebar-recents'); ?>
        <?php endif; ?>

        <!-- Section -->
        <section>
            <header class="major">
                <h2><?= __(LANG_GROUP, 'Contáctenos'); ?></h2>
            </header>
            <p><?= __(LANG_GROUP, 'Información de contacto'); ?></p>
            <ul class="contact">
                <li class="icon solid fa-envelope"><a target="_blank" href="mailto:#">algo@sitio.com</a></li>
                <li class="icon solid fa-phone"><?= __(LANG_GROUP, 'Teléfono'); ?> 123-456-7891 <br /> <?= __(LANG_GROUP, 'Fax'); ?> 123-456-7891</li>
                <li class="icon solid fa-home">123 Avenida 50 <br /> ATL 000000 CO</li>
                <li class="icon solid fa-share-alt">
                    <a href="#">
                        <span class="icon brands fa-facebook-f"></span>
                        <span> Facebook</span>
                    </a>
                    <br />
                    <a href="#">
                        <span class="icon brands fa-instagram"></span>
                        <span>Instagram</span>
                    </a>
                </li>

            </ul>
        </section>



        <!-- Footer -->
        <footer id="footer">
            <p class="copyright">
                &copy; <?=  \PiecesPHP\Core\Config::get_config('title_app'); ?>.
                <br />
                <?= __(LANG_GROUP, 'Todos los derechos reservados'); ?>.
                <br />
                <small class="small"><?= __(LANG_GROUP, 'Diseño'); ?>: <a href="https://html5up.net">HTML5 UP</a></small>.
            </p>
        </footer>

    </div>
</div>
