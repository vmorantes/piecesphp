<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $actionURL
 * @var string|null $url
 * @var bool $hasKey Si hay una clave guardada; la clave no llega a la vista.
 * @var string $title
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var string $indexLink
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
?>
<section class="module-view-container os-ticket-view">

    <div class="limiter-content">

        <?php
            //La cabecera del diseño de contenidos (diseno/02-configuracion.md §2.1).
            $helpController->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
                'description' => $description,
            ]);
        ?>

        <form action="<?= $actionURL; ?>" method="POST" class="ui form initial os-ticket" data-content-form>

            <div class="container-standard-form">

                <div class="field">
                    <label><?= __($langGroup, 'URL'); ?></label>
                    <input type="text" name="url" value="<?= escape_html((string) $url); ?>" placeholder="<?= __($langGroup, 'https://api.dominio.com/'); ?>">
                </div>

                <div class="field">
                    <label><?= __($langGroup, 'Key'); ?></label>
                    <?php //La clave no viaja al HTML: el campo va vacío y, vacío, al guardar la conserva. ?>
                    <input autocomplete="off" type="text" name="key" value="" placeholder="<?= $hasKey ?__($langGroup, 'Hay una clave guardada: déjelo vacío para conservarla') : 'ABCD123456EFGH'; ?>">
                </div>

            </div>

            <?php
                //La barra al final del formulario (§2.3).
                $helpController->_render('panel/built-in/content/form-action-bar.php', [
                    'saveText' => __($langGroup, 'Guardar'),
                    'secondaryActions' => [['text' => __($langGroup, 'Cancelar'), 'url' => $indexLink, 'icon' => 'times']],
                ]);
            ?>

        </form>

    </div>

</section>
