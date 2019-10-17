<!DOCTYPE html>
<html lang="<?= get_config('app_lang'); ?>" dlang="<?= get_config('default_lang'); ?>" class="no-js">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __('page403', '403 - Acceso denegado'); ?></title>
	<base href="<?= base_url(); ?>">
	<link rel="shortcut icon" href="<?= get_config('favicon'); ?>" type="image/x-icon">
    <link rel="stylesheet" href="statics/core/css/ui-pcs.css">
    <style>
    body {

        --color-text: rgb(40, 100, 133);

        --color-bg: #F1F2F2;

        --color-link: rgb(0, 146, 208);

        color: var(--color-text);

        background-color: var(--color-bg);

    }



    .content-403 {

        position: absolute;

        top: 50%;

        left: 50%;

        transform: translate(-50%, -50%);

        min-width: 300px;

        max-width: 100%;

        text-align: center;

    }



    .title-403 {

        font-size: 32px;

        text-transform: uppercase;

        font-weight: 800;

    }



    .text-403 {

        font-size: 20px;

    }



    .bt-403 {

        width: 200px;

        padding: 10px;

        font-size: 18px;

        font-weight: 800;

        color: white;

        background: var(--color-text);

        cursor: pointer;

        text-decoration: none;

    }



    .bt-403:hover {

        background: var(--color-link);

    }
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
