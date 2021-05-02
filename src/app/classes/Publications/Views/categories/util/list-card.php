<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use Publications\Mappers\PublicationMapper;

/**
 * @var PublicationMapper $mapper
 */
$mapper;

/**
 * @var string $langGroup
 * @var string $editLink
 */;
$langGroup;
$editLink;

?>

<div class="ui card">

    <div class="content">

        <div class="header">
            <?= $mapper->name; ?>
        </div>

        <br>

        <div class="description">

            <div>

                <?php if($hasEdit): ?>
                <a class="fluid ui olive button icon" href="<?= $editLink; ?>">
                    <i class="icon plus"></i> &nbsp; <?= __($langGroup, 'Editar'); ?>
                </a>
                <?php endif;?>

                <?php if($hasDelete): ?>
                <a class="fluid ui red button icon" delete-presentation-category-button data-route="<?= $deleteRoute; ?>">
                    <i class="icon trash"></i> &nbsp; <?= __($langGroup, 'Eliminar'); ?>
                </a>
                <?php endif;?>

            </div>

        </div>

    </div>

</div>
