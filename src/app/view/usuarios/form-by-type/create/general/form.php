<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Model\UsersModel;
?>

<form class="ui form users create general" action="<?= get_route('register-request'); ?>">

    <input type="hidden" name="type" value="<?=UsersModel::TYPE_USER_GENERAL;?>">

    <div class="ui grid">

        <div class="doubling two column row">

            <div class="column">

                <div class="field">
                    <input required type="text" name="firstname" placeholder="<?=__('general', 'firstname');?>">
                </div>

            </div>

            <div class="column">

                <div class="field">
                    <input type="text" name="secondname" placeholder="<?=__('general', 'secondname');?>">
                </div>

            </div>

        </div>

        <div class="doubling two column row">

            <div class="column">

                <div class="field">
                    <input required type="text" name="first_lastname"
                        placeholder="<?=__('general', 'first-lastname');?>">
                </div>

            </div>

            <div class="column">

                <div class="field">
                    <input type="text" name="second_lastname" placeholder="<?=__('general', 'second-lastname');?>">
                </div>

            </div>

        </div>

    </div>

    <br>

    <div class="field required">

        <div class="ui labeled input">
            <div class="ui label">
                <i class="icon user outline large"></i>
                <?=__('general', 'user');?>
            </div>
            <input required type="text" name="username">
        </div>

    </div>

    <div class="field required">

        <div class="ui labeled input">

            <div class="ui label">
                <i class="icon mail outline large"></i>
                <?=__('general', 'email-standard');?>
            </div>

            <input required type="email" name="email">

        </div>

    </div>

    <div class="field">

        <div class="ui labeled input">

            <div class="ui label">
                <i class="icon key  large"></i>
                <?=__('general', 'password');?>
            </div>
            <input type="password" name="password" value="">

        </div>

    </div>

    <div class="field">

        <div class="ui labeled input">
            <div class="ui label">
                <i class="icon key  large"></i>
                <?=__('general', 'confirm-password');?>
            </div>
            <input type="password" name="password2" value="">
        </div>

    </div>

    <div class="field">

        <select class="ui dropdown" required name="status">

            <option value=""><?=__('general', 'status');?></option>
            <?php foreach ($status_options as $name => $value): ?>
            <option value="<?=$value;?>"><?=$name;?></option>
            <?php endforeach;?>

        </select>

    </div>

    <div class="field">

        <button type="submit" class="ui button green">
            <i class="save icon"></i>
            <?=__('general', 'save');?>
        </button>

    </div>

</form>
