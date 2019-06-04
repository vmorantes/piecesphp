<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>"); ?>
<!DOCTYPE html>
<html lang="<?=get_config('app_lang');?>">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <base href="<?=baseurl();?>">
        <title><?=get_title();?></title>
		<link rel="shortcut icon" href="<?= get_config('favicon'); ?>" type="image/x-icon">
        <?php load_css(['base_url' => "", 'custom_url' => ""]) ?>
	</head>
	
    <body>

		<section class="container">

			<div class="topbar">
				<div class="brand">
					<div class="icon">
						<img src="<?=baseurl('statics/vendor/users/icons/burbuja-pregunta.svg');?>">
					</div>
					<div class="text">
						<div class="title">¿Problemas para ingresar?</div>
						<div class="subtitle">Seleccione el problema con el que necesita ayuda:</div>
					</div>
				</div>
				<div class="back">
					<a href="<?=get_route('login-form')?>">
						<img src="<?=baseurl('statics/vendor/users/icons/flecha.svg');?>">
					</a>
				</div>
			</div>

			<div class="list">

				<div class="item">
					<a href="<?=get_route('user-forget-form')?>"></a>

					<div class="icon">
						<img src="<?=baseurl('statics/vendor/users/images/problems/problemas-usuario.svg');?>">
					</div>
					<div class="text">
						<div class="title">No recuerdo mi usuario</div>
						<div class="description">Si no estás seguro o no recuerdas cuál fue el usuario asignado.</div>
					</div>

				</div>

				<div class="item">
					<a href="<?=get_route('recovery-form')?>"></a>

					<div class="icon">
						<img src="<?=baseurl('statics/vendor/users/images/problems/problemas-contrasena.svg');?>">
					</div>
					<div class="text">
						<div class="title">Problemas con la contraseña</div>
						<div class="description">No recuerdas la contraseña o crees que alguien la cambio.</div>
					</div>

				</div>

				<div class="item">
					<a href="<?=get_route('user-blocked-form')?>"></a>

					<div class="icon">
						<img src="<?=baseurl('statics/vendor/users/images/problems/usuario-bloqueado.svg');?>">
					</div>
					<div class="text">
						<div class="title">Usuario bloqueado</div>
						<div class="description">Al intentar iniciar sesión el sistema me informa que el usuario está bloqueado.</div>
					</div>

				</div>

				<div class="item">
					<a href="<?=get_route('user-not-exists-form')?>"></a>

					<div class="icon">
						<img src="<?=baseurl('statics/vendor/users/images/problems/usuario-no-existe.svg');?>">
					</div>
					<div class="text">
						<div class="title">Otro inconveniente</div>
						<div class="description">Si tienes otro tipo de problema con tu cuenta.</div>
					</div>

				</div>

			</div>

		</section>

		<?php load_js(['base_url' => "", 'custom_url' => ""]) ?>
		
    </body>
</html>
