<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use Persons\Controllers\PersonsController;
/**
 * @var PersonsController $this
 */
/**
 * @var string $langGroup
 * @var string $editLink
 */
$langGroupDatatables = 'datatables';
?>
<section class="module-view-container">

    <div class="header-options">

        <div class="main-options">

            <a href="<?= $backLink; ?>" class="ui icon button brand-color alt2" title="<?= __($langGroup, 'Regresar'); ?>">
                <i class="icon left arrow"></i>
            </a>

        </div>

        <div class="columns">

            <div class="column">

                <div class="section-title">
                    <div class="title"><?= $title; ?></div>
                    <div class="subtitle"><?= $subtitle; ?></div>
                </div>

            </div>

        </div>

    </div>

    <?php if(mb_strlen($formVariables['action']) > 0): ?>
    <?php $this->render($this::BASE_VIEW_DIR . '/forms/add', $formVariables); ?>
    <br>
    <br>
    <?php endif; ?>


    <div class="mirror-scroll-x" mirror-scroll-target=".container-standard-table">
        <div class="mirror-scroll-x-content"></div>
    </div>

    <div class="container-standard-table">

        <table url="<?= $processTableLink; ?>" class="ui table striped celled">

            <thead>

                <tr>
                    <th><?= __($langGroup, 'Identificación'); ?></th>
                    <th><?= __($langGroup, 'Nombres y apellidos'); ?></th>
                    <th><?= __($langGroup, 'Acciones'); ?></th>
                </tr>

            </thead>

        </table>

    </div>

</section>
