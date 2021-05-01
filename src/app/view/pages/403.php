<!DOCTYPE html>
<html lang="<?= get_config('app_lang'); ?>" dlang="<?= get_config('default_lang'); ?>" class="no-js">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __('page403', '403 - Acceso denegado'); ?></title>
	<base href="<?= base_url(); ?>">
	<link rel="shortcut icon" href="<?= get_config('favicon'); ?>" type="image/x-icon">
    <link rel="stylesheet" href="statics/core/css/ui-pcs.css">
    <link rel="stylesheet" href="statics/css/403.css">
    <style>
    </style>
</head>

<body>
    <div class="content-403">
        <img src="<?=base_url('statics/images/403-bg.svg');?>">
        <p class="title-403"><?= __('page403', 'Algo está mal aquí'); ?></h1>
            <p class="text-403"><?= __('page403', 'El enlace al que intenta ingresar no está disponible para su acceso.'); ?></p>
            <a class="ui button bt-403" href="<?=isset($url) && is_string($url) ? $url : base_url();?>"><?= __('page403', 'Ir a Inicio'); ?></a>
    </div>
</body>

</html>
