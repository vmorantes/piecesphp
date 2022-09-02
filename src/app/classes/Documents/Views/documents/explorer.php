<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use Documents\Controllers\DocumentsController;
/**
 * @var DocumentsController $this
 */
/**
 * @var string $langGroup
 * @var string $editLink
 */
$langGroupDatatables = 'datatables';
?>

<div class="custom-ribbon mw-1200">
    <img src="statics/images/dashboard/decoration-ribbon.png" class="decoration">
    <div class="header-area center fluid">
        <div class="title"><?= $title; ?></div>
    </div>
</div>

<section class="module-view-container">

    <div class="cards-container-standard">

        <div class="table-to-cards">

            <div class="ui form component-controls mw-800 block-centered">

                <div class="field">

                    <div class="field">

                        <div class="ui icon input">
                            <input type="search" placeholder="<?= __($langGroupDatatables, 'Buscar') ?>">
                            <i class="search icon"></i>
                        </div>

                    </div>

                </div>

            </div>

            <table url="<?= $processTableLink; ?>" style='display:none;'>

                <thead>

                    <tr>
                        <th><?= __($langGroup, 'Nombre'); ?></th>
                    </tr>

                </thead>

            </table>

        </div>

    </div>

</section>
