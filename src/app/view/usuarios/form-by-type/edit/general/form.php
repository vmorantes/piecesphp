<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Controller\UsersController;
use App\Model\UsersModel;
use Organizations\Mappers\OrganizationMapper;

$langGroup = UsersController::LANG_GROUP;
$organizations = OrganizationMapper::all(false);
$canAssignAll = OrganizationMapper::canAssignAnyOrganization(getLoggedFrameworkUser()->type);
?>
<h3><?=__($langGroup, 'profile');?>: <?=htmlentities("$edit_user->firstname $edit_user->first_lastname");?></h3>

<form class="ui form users edit general" action="<?= get_route('user-edit-request'); ?>">

    <input type="hidden" name='id' value="<?=$edit_user->id;?>">

    <?php if($canAssignAll): ?>
    <div class="field">

        <select class="ui dropdown" required name="organization">

            <option value=""><?=__($langGroup, 'Organización');?></option>
            <?php foreach ($organizations as $organization): ?>
            <option value="<?=$organization->id;?>" <?=$organization->id == $edit_user->organization ? 'selected' : '';?>><?=$organization->name;?></option>
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
                    <input required type="text" name="firstname" placeholder="<?=__($langGroup, 'firstname');?>" value="<?=htmlentities($edit_user->firstname);?>">
                </div>

            </div>

            <div class="column">

                <div class="field">
                    <input type="text" name="secondname" placeholder="<?=__($langGroup, 'secondname');?>" value="<?=htmlentities($edit_user->secondname);?>">
                </div>

            </div>

        </div>

        <div class="doubling two column row">

            <div class="column">

                <div class="field">
                    <input required type="text" name="first_lastname" placeholder="<?=__($langGroup, 'first-lastname');?>" value="<?=htmlentities($edit_user->first_lastname);?>">
                </div>

            </div>

            <div class="column">

                <div class="field">
                    <input type="text" name="second_lastname" placeholder="<?=__($langGroup, 'second-lastname');?>" value="<?=htmlentities($edit_user->second_lastname);?>">
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
            <input required type="text" name="username" value="<?= htmlentities($edit_user->username);?>">
        </div>

    </div>

    <div class="field required">

        <div class="ui labeled input">

            <div class="ui label">
                <i class="icon mail outline large"></i>
                <?=__($langGroup, 'email-standard');?>
            </div>

            <input required type="email" name="email" value="<?=htmlentities($edit_user->email);?>">

        </div>

    </div>

    <div class="field">

        <div class="ui labeled input">

            <div class="ui label">
                <i class="icon key  large"></i>
                <?=__($langGroup, 'password_restored');?>
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

    <div class="field required">

        <select class="ui dropdown" required name="status">

            <option value=""><?=__($langGroup, 'status');?></option>
            <?php foreach ($status_options as $name => $value): ?>
            <?php if ($value == $edit_user->status): ?>
            <option selected value="<?=$value;?>"><?=$name;?></option>
            <?php else: ?>
            <option value="<?=$value;?>"><?=$name;?></option>
            <?php endif;?>
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
