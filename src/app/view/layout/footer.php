<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>

<footer class="footer">

    <div class="content bg-2">

        <div class="wrapper">

            <ul class="social">

                <li>
                    <a href="#" aria-label="instagram">
                        <i class="icon instagram"></i>
                    </a>
                </li>

                <li>
                    <a href="#" aria-label="facebook">
                        <i class="icon facebook"></i>
                    </a>
                </li>

                <li>
                    <a href="#" aria-label="twitter">
                        <i class="icon twitter"></i>
                    </a>
                </li>

                <li>
                    <a href="#" aria-label="youtube">
                        <i class="icon youtube"></i>
                    </a>
                </li>

            </ul>

            <div class="address">
                <address>Calle 123, Edificio 1. Barranquilla, Colombia.</address>
            </div>

            <div class="copy">
                <?= get_config('owner'); ?> | <?= __(LANG_GROUP, 'Todos los derechos reservados'); ?>, <?= date('Y'); ?>
            </div>

        </div>

    </div>

</footer>

<button class="to-top" aria-label="<?= __(LANG_GROUP, 'Desplazarse hacia arriba'); ?>">
    <i class="icon arrow up"></i>
</button>

<!-- Scripts -->
<?php load_js([
    'base_url' => "",
    'custom_url' => "",
    'attr' => [
        'test-attr' => 'yes',
    ],
    'attrApplyTo' => [
        'test-attr' => [
            '.*configurations\.js$',
        ],
    ],
]) ?>
</body>

</html>
