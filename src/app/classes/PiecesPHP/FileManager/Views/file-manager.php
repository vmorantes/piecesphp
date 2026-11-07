<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $configurationRoute
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var string $title
 */

?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
            $this->helpController->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
            ]);
        ?>

        <div class="ui segment filemanager-container">
            <div class="filemanager-component" data-route="<?= $configurationRoute; ?>" data-base-url="<?= baseurl('statics/plugins/elfinder'); ?>"></div>
        </div>

    </div>

</section>
