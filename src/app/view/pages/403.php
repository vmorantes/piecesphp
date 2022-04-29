<!DOCTYPE html>
<html lang="<?= get_config('app_lang'); ?>" dlang="<?= get_config('default_lang'); ?>" class="no-js">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __('page403', '403 - Acceso denegado'); ?></title>
    <base href="<?= base_url(); ?>">
    <link rel="shortcut icon" href="<?= get_config('favicon'); ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?= get_route('admin-global-variables-css'); ?>">
    <link rel="stylesheet" href="statics/core/css/ui-pcs.css">
    <link rel="stylesheet" href="statics/css/403.css">
    <style>
    </style>
</head>

<body>
    <div class="container">

        <div class="overlay-two"></div>

        <div class="container-card">

            <div class="ui centered middle aligned grid">

                <div class="centered row">

                    <div class="computer only twelve wide column"></div>

                    <div class="three wide column">

                        <div class="content-403">

                            <div class="panel">
                                <p class="mega-title-403">Ups !</p>
                                <div class="img403">
                                    <img src="<?=base_url('statics/images/403.png');?>">
                                </div>
                                <p class="title-403"><?= __('page403', 'Algo está mal aquí'); ?></p>
                                <p class="text-403"><?= __('page403', 'El enlace al que intenta ingresar no está disponible para su acceso.'); ?></p>
                                <div class="action">
                                    <a class="bt-403" href="<?= isset($url) && is_string($url) ? $url : base_url(); ?>"><?= __('page404', 'Ir a Inicio'); ?></a>
                                </div>
                                <div class="action">
                                    <a class="bt-report" href="<?= get_route("other-problems-form"); ?>">Reportar problema</a>
                                </div>
                            </div>

                            <div class="logo-footer">
                                <img class="img-logo-footer" src="<?=get_config('logo');?>">
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>

        <div class="overlay-one">
            <img class="img-403" src="<?= base_url('statics/images/403.png'); ?>">
        </div>

    </div>
</body>

</html>
