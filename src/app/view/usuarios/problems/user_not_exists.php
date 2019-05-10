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
						<img src="<?=baseurl('statics/vendor/users/icons/burbuja-pregunta.svg');?>">
					</div>
					<div class="text">
						<div class="title">¿Problemas para ingresar?</div>
						<div class="subtitle">Otros inconvenientes:</div>
					</div>
				</div>
				<div class="back">
					<a href="<?=get_route('user-problems-list')?>">
						<img src="<?=baseurl('statics/vendor/users/icons/flecha.svg');?>">
					</a>
				</div>
			</div>

			<div class="container-form">
				<div claim>

					<h3>Otros inconvenientes</h3>

					<p>Si tienes un problema que no está en las anteriores opciones, completa el siguiente formulario; verifica que tengas acceso al correo electrónico debido a que las respuestas de tu solicitud serán informadas a ese buzón.</p>
					<p>Recuerda ser lo más descriptivo posible con el problema para poder solucionarlo lo más pronto posible.</p>

					<form class="ui form">
						<div class="field required">
							<label>Nombre</label>
							<input required type="text" name="name" placeholder="Ingrese su nombre">
						</div>
						<div class="field required">
							<label>Correo electrónico</label>
							<input required type="email" name="email" placeholder="Ingrese su correo electrónico">
						</div>
						<div class="field required">
							<label>Información adicional</label>
							<textarea required name="message" placeholder="Información adicional"></textarea>
						</div>
						<div class="field"><button type="submit" class="ui button green ">Enviar</button></div>
					</form>

				</div>
			</div>

		</div>

        <?php load_js(['base_url' => "", 'custom_url' => ""])?>
    </body>
</html>
