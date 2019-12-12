<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>"); ?>

<h3><?=__('usersModule', 'profile');?>: <?=htmlentities("$edit_user->firstname $edit_user->first_lastname");?></h3>

<form class="ui form users edit admin" action="<?= get_route('user-edit-request'); ?>">

    <input type="hidden" name='id' value="<?=$edit_user->id;?>">

    <div class="ui grid">

        <div class="doubling two column row">

            <div class="column">

                <div class="field">
                    <input required type="text" name="firstname" placeholder="<?=__('usersModule', 'firstname');?>"
                        value="<?=htmlentities($edit_user->firstname);?>">
                </div>

            </div>

            <div class="column">

                <div class="field">
                    <input type="text" name="secondname" placeholder="<?=__('usersModule', 'secondname');?>"
                        value="<?=htmlentities($edit_user->secondname);?>">
                </div>

            </div>

        </div>

        <div class="doubling two column row">

            <div class="column">

                <div class="field">
                    <input required type="text" name="first_lastname"
                        placeholder="<?=__('usersModule', 'first-lastname');?>" value="<?=htmlentities($edit_user->first_lastname);?>">
                </div>

            </div>

            <div class="column">

                <div class="field">
                    <input type="text" name="second_lastname" placeholder="<?=__('usersModule', 'second-lastname');?>"
                        value="<?=htmlentities($edit_user->second_lastname);?>">
                </div>

            </div>

        </div>

    </div>

    <br>

    <div class="field required">

        <div class="ui labeled input">
            <div class="ui label">
                <i class="icon user outline large"></i>
                <?=__('usersModule', 'user');?>
            </div>
            <input required type="text" name="username" value="<?= htmlentities($edit_user->username);?>">
        </div>

    </div>

    <div class="field required">

        <div class="ui labeled input">

            <div class="ui label">
                <i class="icon mail outline large"></i>
                <?=__('usersModule', 'email-standard');?>
            </div>

            <input required type="email" name="email" value="<?=htmlentities($edit_user->email);?>">

        </div>

    </div>

    <div class="field">

        <div class="ui labeled input">

            <div class="ui label">
                <i class="icon key  large"></i>
                <?=__('usersModule', 'password_restored');?>
            </div>
            <input type="password" name="password" value="">

        </div>

    </div>

    <div class="field">

        <div class="ui labeled input">
            <div class="ui label">
                <i class="icon key  large"></i>
                <?=__('usersModule', 'confirm-password');?>
            </div>
            <input type="password" name="password2" value="">
        </div>

    </div>

    <div class="field required">

        <select class="ui dropdown" required name="status">

            <option value=""><?=__('usersModule', 'status');?></option>
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
            <?=__('usersModule', 'save');?>
        </button>

    </div>

</form>
