<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Controller\UsersController;
use App\Model\UsersModel;
use Organizations\Mappers\OrganizationMapper;

$langGroup = UsersController::LANG_GROUP;
$organizations = OrganizationMapper::all(false);
$canModifyAll = OrganizationMapper::canModifyAnyOrganization(getLoggedFrameworkUser()->type);
?>

<form class="ui form users create comunicaciones" action="<?= get_route('register-request'); ?>">

    <input type="hidden" name="type" value="<?=UsersModel::TYPE_USER_COMUNICACIONES;?>">

    <?php if($canModifyAll): ?>
    <div class="field">

        <select class="ui dropdown" required name="organization">

            <option value=""><?=__($langGroup, 'Organización');?></option>
            <?php foreach ($organizations as $organization): ?>
            <option value="<?=$organization->id;?>"><?=$organization->name;?></option>
            <?php endforeach;?>

        </select>

    </div>
    <?php else: ?>
    <input type="hidden" name="organization" value="<?= getLoggedFrameworkUser()->organization; ?>">
    <?php endif; ?>

    <div class="ui grid">

        <div class="doubling two column row">

            <div class="column">

                <div class="field">
                    <input required type="text" name="firstname" placeholder="<?=__($langGroup, 'firstname');?>">
                </div>

            </div>

            <div class="column">

                <div class="field">
                    <input type="text" name="secondname" placeholder="<?=__($langGroup, 'secondname');?>">
                </div>

            </div>

        </div>

        <div class="doubling two column row">

            <div class="column">

                <div class="field">
                    <input required type="text" name="first_lastname" placeholder="<?=__($langGroup, 'first-lastname');?>">
                </div>

            </div>

            <div class="column">

                <div class="field">
                    <input type="text" name="second_lastname" placeholder="<?=__($langGroup, 'second-lastname');?>">
                </div>

            </div>

        </div>

    </div>

    <br>

    <div class="field required">

        <div class="ui labeled input">
            <div class="ui label">
                <i class="icon user outline large"></i>
                <?=__($langGroup, 'user');?>
            </div>
            <input required type="text" name="username">
        </div>

    </div>

    <div class="field required">

        <div class="ui labeled input">

            <div class="ui label">
                <i class="icon mail outline large"></i>
                <?=__($langGroup, 'email-standard');?>
            </div>

            <input required type="email" name="email">

        </div>

    </div>

    <div class="field">

        <div class="ui labeled input">

            <div class="ui label">
                <i class="icon key  large"></i>
                <?=__($langGroup, 'password');?>
            </div>
            <input type="password" name="password" value="">

        </div>

    </div>

    <div class="field">

        <div class="ui labeled input">
            <div class="ui label">
                <i class="icon key  large"></i>
                <?=__($langGroup, 'confirm-password');?>
            </div>
            <input type="password" name="password2" value="">
        </div>

    </div>

    <div class="field">

        <select class="ui dropdown" required name="status">

            <option value=""><?=__($langGroup, 'status');?></option>
            <?php foreach ($status_options as $name => $value): ?>
            <option value="<?=$value;?>"><?=$name;?></option>
            <?php endforeach;?>

        </select>

    </div>

    <div class="field">

        <button type="submit" class="ui button green">
            <i class="save icon"></i>
            <?=__($langGroup, 'save');?>
        </button>

    </div>

</form>
