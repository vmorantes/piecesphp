<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 */

?>
<section class="module-view-container">

    <div class="breadcrumb">
        <?= $breadcrumbs ?>
    </div>

    <div class="limiter-content">

        <div class="section-title">
            <div class="title"><?= $title ?></div>
            <?php if(isset($description) && is_string($description) && mb_strlen(trim($description)) > 0): ?>
            <div class="description"><?= $description; ?></div>
            <?php endif; ?>
        </div>

        <br>

        <div class="tabs-controls" style="display: none;">
            <div class="active" data-tab="all"><?= __($langGroup, 'Todas'); ?></div>
        </div>

        <div class="ui tab tab-element active" data-tab="all">

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
                <table url="<?= $processTableLink; ?>" class="ui basic table all">

                    <thead>

                        <tr>
                            <th><?= __($langGroup, 'Actor'); ?></th>
                            <th><?= __($langGroup, 'Ubicación'); ?></th>
                            <th><?= __($langGroup, 'Áreas de investigación'); ?></th>
                            <th order="false" class-name="buttons" with-container="true"><?= __($langGroup, 'Ver perflil'); ?></th>
                        </tr>

                    </thead>

                </table>

            </div>

        </div>

    </div>

</section>