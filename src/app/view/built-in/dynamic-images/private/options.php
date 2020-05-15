<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 */;
$langGroup;

?>

<div style="max-width:850px;">

    <h3>
        <strong><?= $title; ?></strong>
    </h3>

    <div>

        <a href="<?= $backLink; ?>" class="ui labeled icon button">
            <i class="icon left arrow"></i>
            <?= __($langGroup, 'Regresar'); ?>
        </a>

    </div>

</div>

<br>
<br>

<div style="max-width:100%;">

    <div class="ui cards">

        <?php foreach($options as $option): ?>

        <div class="ui card">

            <div class="content">

                <div class="header">
                    <?= $option->title; ?>
                </div>

                <br>

                <div class="description">

                    <div>

                        <a class="fluid ui olive button icon" href="<?= $option->link; ?>">
                            <i class="icon plus"></i> &nbsp; <?= __($langGroup, 'Ver'); ?>
                        </a>

                    </div>

                </div>

            </div>

        </div>

        <?php endforeach;?>

    </div>

</div>
