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

    <section class="container" bg-js>

        <article class="form-container">

            <div class="caption">
                <img src="<?=get_config('logo-login');?>">
            </div>

            <div class="content">

                <?php if(isset($session_errors) && count($session_errors)>0): ?>
                <ul class="ui error message">
                    <?= isset($session_errors) && count($session_errors)>0 ? '<li>'.implode('<li>',$session_errors).'</li>': '';?>
                </ul>
                <?php endif; ?>

                <form login-form-js last-uri='<?= $requested_uri; ?>' class="ui form">

                    <div class="field">
                        <input type="text" required name='username' placeholder="Digita tu nombre de usuario">
                    </div>

                    <div class="field">
                        <input type="password" required name='password' placeholder="Digita tu contraseña">
                    </div>

                    <div class="field">
                        <button type="submit" class="ui blue fluid button">Ingresar</button>
                    </div>

                    <div class="field">
                        <a href="<?=get_route('user-problems-list')?>" class="ui button red labeled icon">
                            <i class="question circle outline icon"></i>
                            ¿Problemas para ingresar?
                        </a>
                    </div>

                </form>

            </div>

            <div class="footer">
                <div class="developer"><?= __('general','developed_by') . ' ' . get_config('developer');?></div>
            </div>

        </article>

    </section>

    <?php load_js(['base_url' => "", 'custom_url' => ""]) ?>

</body>

</html>
