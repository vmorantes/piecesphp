<!DOCTYPE html>
<html lang="<?= get_config('app_lang'); ?>" dlang="<?= get_config('default_lang'); ?>" class="no-js">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __('page503', '503 - En mantenimiento'); ?></title>
    <base href="<?= base_url(); ?>">
    <link rel="shortcut icon" href="<?= get_config('favicon'); ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?= get_route('admin-global-variables-css'); ?>">
    <link rel="stylesheet" href="statics/core/css/ui-pcs.css">
    <link rel="stylesheet" href="statics/css/maintenance.css">
</head>

<body>
    <div class="container">

        <div class="overlay-two"></div>

        <div class="container-card">

            <div class="ui centered middle aligned grid">

                <div class="centered row">

                    <div class="computer only twelve wide column"></div>

                    <div class="three wide column">
                        <div class="content-503">

                            <div class="panel">
                                <p class="mega-title-503">Ups !</p>
                                <div class="img503">
                                    <img src="<?= base_url('statics/images/maintenance.svg'); ?>">
                                </div>
                                <p class="title-503"><?= __('page503', 'Mantenimiento'); ?></p>
                                <p class="text-503"><?= __('page503', 'en curso, no es posible usar la plataforma en un tiempo.'); ?></p>
                                <p class="data-time"><?= date('d/m/y / h:i A'); ?></p>
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
            <img class="img-503" src="<?= base_url('statics/images/maintenance.svg'); ?>">
        </div>
    </div>
</body>

</html>
