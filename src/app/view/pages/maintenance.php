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
    <link rel="stylesheet" href="statics/css/errors.css">
</head>

<body>

    <main class="errors-main-container">

        <div></div>

        <div class="information-card">

            <div class="decored-card">
                <span>Oops!</span>
                <img src="statics/images/errors/maintenance.svg" alt="">
            </div>

            <div class="content">
                <div class="head">
                    <p class="title"><?= __('page503', 'Mantenimiento'); ?></p>
                    <p class="text"><?= __('page503', 'en curso, no es posible usar la plataforma en un tiempo.'); ?></p>
                </div>
                <img class="error-image" src="statics/images/errors/maintenance.svg" alt="">
                <div class="body">
                    <p class="data-time"><?= date('d/m/y / h:i A'); ?></p>
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
