<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
//La barra superior del panel: la comparten header.php y la vista previa de Imágenes de marca, que la pinta sin $this y con $noTopBar (sin buscador ni derecha).
?>
            <header class="bar">
                <button class="ui tertiary icon button drawer toggle" type="button"><i class="bars icon" aria-hidden="true"></i></button>
                <?php //En móvil la marca no se ve (el menú es un cajón): el logo va en la barra, como el del menú. ?>
                <span class="mobile only logo" aria-hidden="true"><?= \PiecesPHP\Settings\Controllers\SettingsController::brandImageTag('logo'); ?></span>
                <?php if(!isset($noTopBar) || $noTopBar === false): ?>
                <?php $this->render('panel/layout/topbar-search'); ?>
                <div class="right">
                    <?php $this->render('panel/layout/topbar-bar'); ?>
                </div>
                <?php endif; ?>
            </header>
