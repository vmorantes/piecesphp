<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\UserSystem\ORM\UsersModel;
$langGroupDatatables = 'datatables';

$tabs = [
    'all' => [__($langGroup, 'Todos'), $process_table],
    'actives-elements' => [__($langGroup, 'Activos'), $process_table . '?with-status=' . UsersModel::STATUS_USER_ACTIVE],
    'inactives' => [__($langGroup, 'Inactivos'), $process_table . '?with-status=' . UsersModel::STATUS_USER_INACTIVE],
    'blocked' => [__($langGroup, 'Bloqueados por intentos fallidos'), $process_table . '?with-status=' . UsersModel::STATUS_USER_ATTEMPTS_BLOCK],
    'pending' => [__($langGroup, 'Por aprobar'), $process_table . '?with-status=' . UsersModel::STATUS_USER_APPROVED_PENDING],
    'rejected' => [__($langGroup, 'No aprobados'), $process_table . '?with-status=' . UsersModel::STATUS_USER_REJECTED],
];
$firstTab = array_key_first($tabs);
?>

<section class="module-view-container">

    <div class="limiter-content">

        <?php
            //La cabecera del panel (diseno/01-contenidos.md §2.1): el alta, arriba, como acción principal.
            $this->helpController->_render('panel/built-in/content/page-header.php', [
                'title' => $title,
                'description' => $description,
                'primaryAction' => $addLink !== '' ? ['text' => __($langGroup, 'Agregar usuario'), 'url' => $addLink, 'icon' => 'user plus'] : null,
                'secondaryActions' => [],
            ]);
        ?>

        <div class="ui secondary pointing menu tabs-controls content-tabs">
            <?php foreach ($tabs as $tabKey => [$tabTitle]): ?>
            <div class="<?= $tabKey === $firstTab ? 'active ' : ''; ?>item" data-tab="<?= $tabKey; ?>"><?= $tabTitle; ?></div>
            <?php endforeach; ?>
        </div>

        <?php foreach ($tabs as $tabKey => [$tabTitle, $tabURL]): ?>
        <section class="ui<?= $tabKey === $firstTab ? ' active' : ''; ?> tab tab-element" data-tab="<?= $tabKey; ?>" aria-label="<?= escape_html($tabTitle); ?>">

            <div class="table-to-cards <?= $tabKey; ?>">

                <div class="ui form component-controls">
                    <div class="fields">
                        <div class="three wide field">
                            <label><?= __($langGroupDatatables, 'Resultados visibles'); ?></label>
                            <input type="number" length-pagination placeholder="10">
                        </div>
                        <div class="five wide field">
                            <label><?= __($langGroupDatatables, 'Buscador'); ?></label>
                            <div class="ui icon input">
                                <input type="search" placeholder="<?= __($langGroupDatatables, 'Buscar'); ?>">
                                <i class="search icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <table url="<?= escape_html($tabURL); ?>" hidden>

                    <thead>

                        <tr>
                            <th><?= __($langGroup, '#'); ?></th>
                            <th><?= __($langGroup, 'Nombres'); ?></th>
                            <th><?= __($langGroup, 'Apellidos'); ?></th>
                            <th><?= __($langGroup, 'Correo electrónico'); ?></th>
                            <th><?= __($langGroup, 'Usuario'); ?></th>
                            <th><?= __($langGroup, 'Activo/Inactivo'); ?></th>
                            <th><?= __($langGroup, 'Tipo'); ?></th>
                            <th order="false" class-name="buttons" with-container="true"><?= __($langGroup, 'Acciones'); ?></th>
                        </tr>

                    </thead>

                </table>

            </div>

        </section>
        <?php endforeach; ?>

    </div>

</section>
