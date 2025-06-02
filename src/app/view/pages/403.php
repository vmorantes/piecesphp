<!DOCTYPE html>
<html lang="<?= get_config('app_lang'); ?>" dlang="<?= get_config('default_lang'); ?>" class="no-js">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __('page403', '403 - Acceso denegado'); ?></title>
    <base href="<?= base_url(); ?>">
    <link rel="shortcut icon" href="<?= add_cache_stamp_to_url(get_config('favicon')); ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?= get_route('admin-global-variables-css'); ?>">
    <link rel="stylesheet" href="statics/core/css/ui-pcs.css">
    <link rel="stylesheet" href="statics/css/errors.css">
    <style>
    </style>
</head>

<body>
    <main class="errors-main-container">

        <div></div>

        <div class="information-card">

            <div class="decored-card">
                <span>Oops!</span>
                <img src="statics/images/errors/403.svg" alt="">
            </div>

            <div class="content">
                <div class="head">
                    <p class="title"><?= __('page403', 'Algo está mal aquí'); ?></p>
                    <p class="text"><?= __('page403', 'El enlace al que intenta ingresar no está disponible para su acceso.'); ?></p>
                </div>
                <img class="error-image" src="statics/images/errors/403.svg" alt="">
                <div class="body">
                    <a class="btn" href="<?= isset($url) && is_string($url) ? $url : base_url(); ?>"><?= __('page404', 'Ir a Inicio'); ?></a>
                    <?php if(!isset($showReportButton) || $showReportButton === true): ?>
                    <a class="btn report" href="<?= get_route("other-problems-form"); ?>"><?= __('page403', 'Reportar problema'); ?></a>
                    <?php endif; ?>
                </div>
                <div class="logo-footer">
                    <img class="img-logo-footer" src="<?= get_config('logo'); ?>">
                </div>
            </div>

        </div>

        <div class="platform-name">
            <span><?= mb_strtoupper(get_config('title_app'), 'UTF-8'); ?></span>
            <p><?= __(USER_LOGIN_LANG_GROUP, 'Todos los derechos reservados') . date('Y'); ?></p>
        </div>

    </main>
</body>

</html>
