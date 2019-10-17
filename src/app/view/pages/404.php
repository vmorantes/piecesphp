<!DOCTYPE html>
<html lang="<?= get_config('app_lang'); ?>" dlang="<?= get_config('default_lang'); ?>" class="no-js">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= __('page404', '404 - Página no encontrada'); ?></title>
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

    .content-404 {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        min-width: 300px;
        max-width: 100%;
        text-align: center;
    }

    .title-404 {
        font-size: 32px;
        text-transform: uppercase;
        font-weight: 800;
    }

    .text-404 {
        font-size: 20px;
    }

    .bt-404 {
        width: 200px;
        padding: 10px;
        font-size: 18px;
        font-weight: 800;
        color: white;
        background: var(--color-text);
        cursor: pointer;
        text-decoration: none;
    }

    .bt-404:hover {
        background: var(--color-link);
    }
    </style>
</head>

<body>
    <div class="content-404">
        <img src="<?= base_url('statics/images/404-bg.svg'); ?>">
        <p class="title-404"><?= __('page404', 'Algo está mal aquí'); ?></h1>
            <p class="text-404"><?= __('page404', 'El enlace al que intenta ingresar ya no existe o fue cambiado.'); ?></p>
            <a class="ui button bt-404" href="<?= isset($url) && is_string($url) ? $url : base_url(); ?>"><?= __('page404', 'Ir a Inicio'); ?></a>
    </div>
</body>

</html>
