<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Model\AvatarModel;
use PiecesPHP\Core\Validation\Validator;

/**
 * @var string $langGroup
 * @var string $title
 */
$user = getLoggedFrameworkUser(true)->userMapper;
$avatar = AvatarModel::getAvatar(Validator::isInteger($user->id) ? (int) $user->id : -1);
?>

<section class="module-view-container mw-800">

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
                    <div class="subtitle"><?= __($langGroup, 'Listado'); ?></div>
                </div>

            </div>

        </div>

    </div>

    <div class="person-title">
        <?php if ($avatar !== null) : ?>
        <div class="image">
            <img src="<?= $avatar; ?>" alt="<?= $user->getFullName(); ?>">
        </div>
        <?php endif; ?>
        <div class="text">
            <span class="mark"><?= __($langGroup, 'Hola'); ?>,&nbsp;</span> <?= $user->getFullName(); ?>
        </div>
    </div>

    <div class="mirror-scroll-x" mirror-scroll-target=".container-standard-table">
        <div class="mirror-scroll-x-content"></div>
    </div>

    <div class="container-standard-table">

        <table url="<?= $processTableLink; ?>" class="ui basic table">

            <thead>

                <tr>
                    <th><?= __($langGroup, '#'); ?></th>
                    <th><?= __($langGroup, 'Módulo'); ?></th>
                    <th><?= __($langGroup, 'Evento'); ?></th>
                    <th><?= __($langGroup, 'Usuario'); ?></th>
                    <th><?= __($langGroup, 'IP'); ?></th>
                    <th><?= __($langGroup, 'País'); ?></th>
                    <th><?= __($langGroup, 'Fecha'); ?></th>
                </tr>

            </thead>

        </table>

    </div>

</section>
