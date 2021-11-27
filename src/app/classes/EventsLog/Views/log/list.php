<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use App\Model\AvatarModel;
use App\Model\UsersModel;
/**
 * @var string $langGroup
 * @var string $title
 */;
$langGroup;
$title;
$user = new UsersModel(get_config('current_user')->id);
$avatar = AvatarModel::getAvatar($user->id);
?>

<div class="person-title">
    <?php if($avatar !== null):?>
    <div class="image">
        <img src="<?= $avatar; ?>" alt="<?= $user->getFullName(); ?>">
    </div>
    <?php endif; ?>
    <div class="text">
        <span class="mark"><?= __($langGroup, 'Hola'); ?>,&nbsp;</span> <?= $user->getFullName(); ?>
    </div>
</div>

<br>
<br>

<div class="mirror-scroll-x" mirror-scroll-target=".container-table-standard-list">
    <div class="mirror-scroll-x-content"></div>
</div>

<div class="container-table-standard-list">

    <div class="table-title"><?= $title; ?></div>

    <table url="<?= $processTableLink; ?>" class="ui table stripped celled">

        <thead>

            <tr>
                <th><?= __($langGroup, 'Evento'); ?></th>
                <th><?= __($langGroup, 'Fecha'); ?></th>
            </tr>

        </thead>

    </table>

</div>
