<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * @var array<int|string,int> $countsByStatus Cuántos ve quien mira, por estado y en `all` (UsersController::visibleUsersCountByStatus()).
 */
//El orden del prototipo (vistas-referencia.md §3.45); se abre en Activos. Cada una con su contador.
$tabs = [
    'actives-elements' => [__($langGroup, 'Activos'), $process_table . '?with-status=' . UsersModel::STATUS_USER_ACTIVE, UsersModel::STATUS_USER_ACTIVE],
    'inactives' => [__($langGroup, 'Inactivos'), $process_table . '?with-status=' . UsersModel::STATUS_USER_INACTIVE, UsersModel::STATUS_USER_INACTIVE],
    'all' => [__($langGroup, 'Todos'), $process_table, 'all'],
    'blocked' => [__($langGroup, 'Bloqueados por intentos fallidos'), $process_table . '?with-status=' . UsersModel::STATUS_USER_ATTEMPTS_BLOCK, UsersModel::STATUS_USER_ATTEMPTS_BLOCK],
    'pending' => [__($langGroup, 'Por aprobar'), $process_table . '?with-status=' . UsersModel::STATUS_USER_APPROVED_PENDING, UsersModel::STATUS_USER_APPROVED_PENDING],
    'rejected' => [__($langGroup, 'No aprobados'), $process_table . '?with-status=' . UsersModel::STATUS_USER_REJECTED, UsersModel::STATUS_USER_REJECTED],
];
$firstTab = array_key_first($tabs);
$perPageOptions = [12, 24, 48];
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
            <?php foreach ($tabs as $tabKey => [$tabTitle, , $tabCountKey]): ?>
            <div class="<?= $tabKey === $firstTab ? 'active ' : ''; ?>item" data-tab="<?= $tabKey; ?>"><?= $tabTitle; ?><span class="ui small label" data-users-count="<?= escape_html((string) $tabCountKey); ?>"><?= (int) ($countsByStatus[$tabCountKey] ?? 0); ?></span></div>
            <?php endforeach; ?>
        </div>

        <?php foreach ($tabs as $tabKey => [$tabTitle, $tabURL]): ?>
        <section class="ui<?= $tabKey === $firstTab ? ' active' : ''; ?> tab tab-element" data-tab="<?= $tabKey; ?>" aria-label="<?= escape_html($tabTitle); ?>">

            <div class="table-to-cards <?= $tabKey; ?>">

                <?php //Los controles de las tablas del catálogo (§3.5): «Ver N elementos» (12/24/48) y el buscador. ?>
                <div class="ui form component-controls">
                    <div class="ui stackable middle aligned wide flex">
                        <div class="ui inline field">
                            <label for="users-length-<?= $tabKey; ?>"><?= __($langGroup, 'Ver'); ?></label>
                            <select id="users-length-<?= $tabKey; ?>" length-pagination>
                                <?php foreach ($perPageOptions as $perPageOption): ?>
                                <option value="<?= $perPageOption; ?>"><?= $perPageOption; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span><?= __($langGroup, 'elementos'); ?></span>
                        </div>
                        <div class="ui inline field">
                            <label for="users-search-<?= $tabKey; ?>"><?= __($langGroup, 'Buscar:'); ?></label>
                            <div class="ui icon input">
                                <input type="search" id="users-search-<?= $tabKey; ?>" placeholder="<?= escape_html(__($langGroup, 'Nombre, usuario, email u organización')); ?>">
                                <i class="search icon" aria-hidden="true"></i>
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
