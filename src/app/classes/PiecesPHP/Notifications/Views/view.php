<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * Todas las notificaciones (ADR 0073): la lista de la campana a página entera.
 *
 * @var string $title
 * @var string $description
 * @var string $markAllText
 * @var string $list El marcado de notifications-list.php
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController
 */
?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
            $helpController->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => [],
                'title' => $title,
                'description' => $description,
            ]);
        ?>

        <div class="ui right aligned basic fitted segment">
            <button class="ui basic button" type="button" notifications-mark-all="notifications-view-list" hidden><?= escape_html($markAllText); ?></button>
        </div>

        <?= $list; ?>

    </div>

</section>
