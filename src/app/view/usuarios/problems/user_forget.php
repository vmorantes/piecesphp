<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>
<!DOCTYPE html>
<html lang="<?=get_config('app_lang');?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <base href="<?=baseurl();?>">
        <title><?=get_title();?></title>
		<link rel="shortcut icon" href="<?= get_config('favicon'); ?>" type="image/x-icon">
        <?php load_css(['base_url' => "", 'custom_url' => ""])?>
    </head>
    <body>

		<div class="container-main">

			<div class="topbar">
				<div class="brand">
					<div class="icon">
						<img src="<?=baseurl('statics/login-and-recovery/icons/burbuja-pregunta.svg');?>">
					</div>
					<div class="text">
						<div class="title">¿Problemas para ingresar?</div>
						<div class="subtitle">Usuario olvidado:</div>
					</div>
				</div>
				<div class="back">
					<a href="<?=get_route('user-problems-list')?>">
						<img src="<?=baseurl('statics/login-and-recovery/icons/flecha.svg');?>">
					</a>
				</div>
			</div>
			<div class="container-form">				
			
				<div message>	
					<div class="ui message success username">
						<div class="content"></div>
					</div>
				</div>

				<div recovery>

					<h3>Recupera tu nombre de usuario</h3>

					<p>Ingresa el correo electrónico con el cual fuiste registrado en la plataforma.</p>

					<form class="ui form">
						<div class="field required">
							<label>Correo electrónico</label>
							<input required type="email" name="username" placeholder="Ingrese su correo electrónico">
						</div>
						<p><strong><a href="#" class="ui mini button blue" has-code>Ya tengo un código</a></strong></p>
						<div class="field"><button type="submit" class="ui button green ">Siguiente</button></div>
					</form>

				</div>

				<div code>

					<h3>Recupera tu nombre de usuario</h3>

					<p>Introduzca el código que se le fue enviado al correo electrónico para conocer su nombre de usuario.</p>

					<form class="ui form">
						<div class="field required">
							<label>Código</label>
							<input required type="text" name="code" placeholder="######">
						</div>		
						<p><strong><a href="#" class="ui mini button blue" repeat>Introducir un usuario/email diferente</a></strong></p>
						<div class="field"><button type="submit" class="ui button green ">Enviar</button></div>
					</form>

				</div>

			</div>

		</div>

        <?php load_js(['base_url' => "", 'custom_url' => ""])?>
    </body>
</html>
