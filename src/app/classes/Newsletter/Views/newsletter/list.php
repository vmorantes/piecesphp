<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 */

?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
            //La cabecera del panel (diseno/01-contenidos.md §2.1): el alta, arriba, como acción principal.
            $this->helpController->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
                'description' => $description ?? '',
                'primaryAction' => $hasPermissionsAdd ? ['text' => __($langGroup, 'Agregar suscriptor'), 'url' => $addLink, 'icon' => 'plus'] : null,
            ]);
        ?>

        <div class="mirror-scroll-x" mirror-scroll-target=".container-standard-table">
            <div class="mirror-scroll-x-content"></div>
        </div>

        <div class="ui fluid card container-standard-table">

            <table url="<?= $processTableLink; ?>" class="ui selectable very basic table content-table">

                <thead>

                    <tr>
                        <th><?= __($langGroup, '#'); ?></th>
                        <th><?= __($langGroup, 'Nombre'); ?></th>
                        <th><?= __($langGroup, 'Email'); ?></th>
                        <th><?= __($langGroup, 'Acepta recibir correos'); ?></th>
                        <th order="false" search="false" class-name="collapsing" with-container="true"><?= __($langGroup, 'Acciones'); ?></th>
                    </tr>

                </thead>

            </table>

        </div>

    </div>

</section>
