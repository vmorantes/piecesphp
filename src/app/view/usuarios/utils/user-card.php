<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Model\AvatarModel;
use App\Model\UsersModel;
?>

<div class="ui card user">

    <div class="content">

        <div class="head">

            <div class="user-info<?= $mapper->status == UsersModel::STATUS_USER_ATTEMPTS_BLOCK ? ' blocked-staus' : ''; ?>">
                <div class="image">
                    <?php if(AvatarModel::getAvatar($mapper->id)): ?>
                    <img src="<?= AvatarModel::getAvatar($mapper->id) ?>">
                    <?php else: ?>
                    <div class="defauld">
                        <i class="user outline icon"></i>
                    </div>
                    <?php endif; ?>
                    <div class="status<?= $mapper->status == UsersModel::STATUS_USER_ACTIVE ? ' online' : ''; ?><?= $mapper->status == UsersModel::STATUS_USER_ATTEMPTS_BLOCK ? ' blocked' : ''; ?>"></div>
                </div>
                <div class="info">
                    <span><?= $mapper->getFullName() ?></span>
                </div>
                <div class="place-status"><?= __($langGroup, 'Bloqueado') ?></div>
            </div>

            <a href="<?= get_route('users-form-edit', ['id' => $mapper->id]); ?>">
                <i class="ellipsis vertical icon"></i>
            </a>

        </div>

        <div class="body">
            <div class="item">
                <img src="<?= base_url('statics/images/dashboard/user_type.svg') ?>">
                <span><?= UsersModel::TYPES_USERS[$mapper->type] ?></span>
            </div>
            <div class="item">
                <img src="<?= base_url('statics/images/dashboard/user.svg') ?>">
                <span><?= $mapper->username ?></span>
            </div>
            <div class="item">
                <img src="<?= base_url('statics/images/dashboard/email.svg') ?>">
                <span><?= $mapper->email ?></span>
            </div>
        </div>

    </div>

</div>
