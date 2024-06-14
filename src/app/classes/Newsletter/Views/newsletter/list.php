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

        <div class="main-buttons">

            <?php if ($hasPermissionsAdd) :  ?>
            <a href="<?= $addLink; ?>" class="ui button brand-color alt"><?= __($langGroup, 'Agregar suscriptor'); ?></a>
            <?php endif; ?>

        </div>

        <br>

        <div class="mirror-scroll-x" mirror-scroll-target=".container-standard-table">
            <div class="mirror-scroll-x-content"></div>
        </div>

        <div class="container-standard-table">

            <table url="<?= $processTableLink; ?>" class="ui basic table">

                <thead>

                    <tr>
                        <th><?= __($langGroup, '#'); ?></th>
                        <th><?= __($langGroup, 'Nombre'); ?></th>
                        <th><?= __($langGroup, 'Email'); ?></th>
                        <th><?= __($langGroup, 'Acepta recibir correos'); ?></th>
                        <th order="no" search="no"> <?= __($langGroup, 'Acciones'); ?></th>
                    </tr>

                </thead>

            </table>

        </div>

    </div>

</section>
