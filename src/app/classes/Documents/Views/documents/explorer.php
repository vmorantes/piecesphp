<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use Documents\Controllers\DocumentsController;
/**
 * @var DocumentsController $this
 */
/**
 * @var string $langGroup
 * @var string $processTableLink
 */
$langGroupDatatables = 'datatables';
?>

<section class="module-view-container">

    <div class="limiter-content">

        <?php
            //La cabecera del diseño de contenidos (diseno/01-contenidos.md §2.1 y §2.5), sin acciones.
            $this->helpController->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
                'description' => $description,
            ]);
        ?>

        <?php //content-table-container: gancho para el CSS de un proyecto; el framework no le da estilo (la paginación la envuelve el tema). ?>
        <div class="table-to-cards content-table-container">

            <div class="ui form component-controls">

                <div class="field">

                    <div class="ui icon input">
                        <input type="search" placeholder="<?= __($langGroupDatatables, 'Buscar') ?>">
                        <i class="search icon"></i>
                    </div>

                </div>

            </div>

            <table url="<?= $processTableLink; ?>" hidden>

                <thead>

                    <tr>
                        <th><?= __($langGroup, '-'); ?></th>
                        <th><?= __($langGroup, '-'); ?></th>
                    </tr>

                </thead>

            </table>

        </div>

    </div>

</section>
