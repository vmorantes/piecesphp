<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $title
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var string $processTableLink
 * @var \Directory\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */

?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
        $helpController->_render('panel/built-in/content/page-header.php', [
            'breadcrumbs' => $breadcrumbs,
            'title' => $title,
            'description' => $description,
        ]);
        ?>

        <div class="mirror-scroll-x all" mirror-scroll-target=".container-standard-table.all">
                <div class="mirror-scroll-x-content"></div>
            </div>

            <div class="container-standard-table all">

                <template style="display: none;">
                    <search-filters>
                        <div class="ui form">
                            <div class="inline field">
                                <label><?= __($langGroup, 'Filtrar'); ?></label>
                                <div search-input class="ui icon input">
                                    <input type="text" placeholder="<?= __($langGroup, 'Buscar'); ?>">
                                    <i class="search icon"></i>
                                </div>
                            </div>
                        </div>
                    </search-filters>
                </template>
                <table url="<?= $processTableLink; ?>" class="ui basic table content-table all">

                    <thead>

                        <tr>
                            <th><?= __($langGroup, 'Actor'); ?></th>
                            <th><?= __($langGroup, 'Ubicación'); ?></th>
                            <th order="false" class-name="collapsing" with-container="true"><?= __($langGroup, 'Ver perfil'); ?></th>
                        </tr>

                    </thead>

                </table>

            </div>

    </div>

</section>