<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>
<div class="user-form-component">

    <div class="ui pointing secondary menu items-pointing">
        <a class="item active" data-tab="form-container"><?= __('usersModule', 'Datos de usuario'); ?></a>
        <?php if (!$create): ?>
        <a class="item" data-tab="avatar-container"><?= __('usersModule', 'Avatar'); ?></a>
        <?php endif;?>
	</div>
	
    <div class="ui bottom attached tab active" data-tab="form-container">
        <?= $form; ?>
	</div>
	
    <?php if (!$create): ?>
    <div class="ui bottom attached tab" data-tab="avatar-container">
        <?php if ($hasAvatar): ?>
        <div>
            <h2><?= __('usersModule', 'Avatar actual'); ?></h2>
            <img class="ui middle aligned small circular image" src="<?=$avatar;?>">
            <button change-avatar class="ui mini button green">
            <i class="edit icon"></i>
            <?= __('usersModule', 'Cambiar'); ?>
            </button>
        </div>
        <?php else: ?>
        <div class="ui message warning">
            <div class="header">
                <?= __('usersModule', 'Aún no has seleccionado un avatar.'); ?>
            </div>
        </div>
        <?php endif;?>
        <div>
            <h3><?= __('usersModule', 'Seleccionar avatar'); ?></h3>
            <div <?=$hasAvatar ? 'hide' : '';?> class="avatar-component" user="<?=$edit_user->id;?>"
                resources-route="<?=get_route('avatars');?>" save-route="<?=get_route('push-avatars');?>">
                <div class="frame">
            		<div style="position:absolute;top:0;left:0;width:100%;height: 100%;z-index: 10;"></div>
                    <div item group="cabello" sub-group='fondo'></div>
                    <div item group="silueta"></div>
                    <div item group="ojo"></div>
                    <div item group="boca"></div>
                    <div item group="nariz"></div>
                    <div item group="ceja"></div>
                    <div item group="ropa"></div>
                    <div item group="cabello" sub-group='frente'></div>
                    <div item group="cabello" sub-group='accesorio'></div>
                    <div class="caption">
                        <?=is_null($edit_user) ? '' : $edit_user->firstname . ' ' . $edit_user->first_lastname;?>
                    </div>
                </div>
                <div class="ui form controls-select">
					<input style="display:none;" type="radio" name="gender" value='all' checked>
                    <div class="field">
                        <div buttons-move='cabello'>
                            <button prev class="ui-pcs arrow left icon button"><i class="arrow left icon large"></i></button>
                            <input label="<?= __('usersModule', 'Cabello'); ?>">
                            <button next class="ui-pcs arrow left icon button"><i class="arrow right icon large"></i></button>
                        </div>
                        <div group-color='cabello'>
                            <div class="caption"><i class="tint icon large"></i><?= __('usersModule', 'Color'); ?></div>
                            <div container-colors></div>
                        </div>
                    </div>
                    <div class="field">
                        <div buttons-move='ojo'>
                            <button prev class="ui-pcs arrow left icon button"><i class="arrow left icon large"></i></button>
                            <input label="<?= __('usersModule', 'Ojos'); ?>">
                            <button next class="ui-pcs arrow left icon button"><i class="arrow right icon large"></i></button>
                        </div>
                        <div group-color='ojo'>
                            <div class="caption"><i class="tint icon large"></i><?= __('usersModule', 'Color'); ?></div>
                            <div container-colors></div>
                        </div>
                    </div>
                    <div class="field">
                        <div buttons-move='nariz'>
                            <button prev class="ui-pcs arrow left icon button"><i class="arrow left icon large"></i></button>
                            <input label="<?= __('usersModule', 'Nariz'); ?>">
                            <button next class="ui-pcs arrow left icon button"><i class="arrow right icon large"></i></button>
                        </div>
                        <div group-color='nariz'>
                            <div class="caption"><i class="tint icon large"></i><?= __('usersModule', 'Color'); ?></div>
                            <div container-colors></div>
                        </div>
                    </div>
                    <div class="field">
                        <div buttons-move='boca'>
                            <button prev class="ui-pcs arrow left icon button"><i class="arrow left icon large"></i></button>
                            <input label="<?= __('usersModule', 'Boca'); ?>">
                            <button next class="ui-pcs arrow left icon button"><i class="arrow right icon large"></i></button>
                        </div>
                        <div group-color='boca'>
                            <div class="caption"><i class="tint icon large"></i><?= __('usersModule', 'Color'); ?></div>
                            <div container-colors></div>
                        </div>
                    </div>
                    <div class="field">
                        <div buttons-move='silueta'>
                            <button prev class="ui-pcs arrow left icon button"><i class="arrow left icon large"></i></button>
                            <input label="<?= __('usersModule', 'Cuerpo'); ?>">
                            <button next class="ui-pcs arrow left icon button"><i class="arrow right icon large"></i></button>
                        </div>
                        <div group-color='silueta'>
                            <div class="caption"><i class="tint icon large"></i><?= __('usersModule', 'Color'); ?></div>
                            <div container-colors></div>
                        </div>
                    </div>
                    <div class="field">
                        <div buttons-move='ropa'>
                            <button prev class="ui-pcs arrow left icon button"><i class="arrow left icon large"></i></button>
                            <input label="<?= __('usersModule', 'Ropa'); ?>">
                            <button next class="ui-pcs arrow right icon button"><i class="arrow right icon large"></i></button>
                        </div>
                    </div>
                </div>
                <div class="center">
                    <div class="ui button green" save-button>
                        <i class="save icon"></i>
                        <?= __('usersModule', 'Guardar'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
	<?php endif;?>
	
</div>
