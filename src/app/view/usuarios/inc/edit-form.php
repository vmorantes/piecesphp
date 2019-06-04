<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>


<h3><?=__('general', 'profile');?>: <?="$edit_user->firstname $edit_user->first_lastname";?></h3>

<form class="ui form users editar">

	<input type="hidden" name='id' value="<?=$edit_user->id;?>">
	
	<div class="ui grid">

		<div class="doubling two column row">

			<div class="column">

				<div class="field">
					<input required type="text" name="firstname" placeholder="<?=__('general', 'firstname');?>"
						value="<?=$edit_user->firstname;?>">
				</div>
				
			</div>
			
			<div class="column">

				<div class="field">
					<input type="text" name="secondname" placeholder="<?=__('general', 'secondname');?>" value="<?=$edit_user->secondname;?>">
				</div>
				
			</div>
			
		</div>
		
		<div class="doubling two column row">

			<div class="column">

				<div class="field">
					<input required type="text" name="first_lastname" placeholder="<?=__('general', 'first-lastname');?>"
						value="<?=$edit_user->first_lastname;?>">
				</div>
				
			</div>
			
			<div class="column">

				<div class="field">
					<input type="text" name="second_lastname" placeholder="<?=__('general', 'second-lastname');?>" value="<?=$edit_user->second_lastname;?>">
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
			<input required type="text" name="username" value="<?= $edit_user->username;?>">
		</div>
		
	</div>
	
	<div class="field required">

		<div class="ui labeled input">

			<div class="ui label">
				<i class="icon mail outline large"></i>
				<?=__('general', 'email-standard');?>
			</div>
			
			<input required type="email" name="email" value="<?=$edit_user->email;?>">
			
		</div>
		
	</div>	
	
	<div class="field">

		<div class="ui labeled input">

			<div class="ui label">
				<i class="icon key  large"></i>
				<?=__('general', 'password_restored');?>
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
	
	<div class="field required <?=$type_disabled;?>">
	
		<select class="ui dropdown" required <?=$type_disabled;?> name="type">
		
			<option value=""><?=__('general', 'type');?></option>
			<?php foreach ($type_options as $name => $value): ?>
			<?php if ($value == $edit_user->type): ?>
			<option selected value="<?=$value;?>"><?=$name;?></option>
			<?php else: ?>
			<option value="<?=$value;?>"><?=$name;?></option>
			<?php endif;?>
			<?php endforeach;?>
			
		</select>
		
	</div>
	
	<div class="field required <?=$status_disabled;?>">
		
		<select class="ui dropdown" required <?=$status_disabled;?> name="status">

			<option value=""><?=__('general', 'status');?></option>
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
		<?=__('general', 'save');?>
		</button>
		
	</div>
	
</form>
