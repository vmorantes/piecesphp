<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>"); ?>
<!DOCTYPE html>
<html lang="<?= escape_html((string) get_config('app_lang')); ?>" dlang="<?= escape_html((string) get_config('default_lang')); ?>"<?= \PiecesPHP\AdminPanel\Controllers\AdminPanelController::htmlPreferenceAttributes(); ?>>

<head>
    <meta charset="UTF-8">
    <meta name="front-configurations" value="<?= base64EncodeOrDefault(jsonEncodeFallbackNull(get_front_configurations()), '[]'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <base href="<?= escape_html(baseurl()); ?>">
    <?php \PiecesPHP\Core\Utilities\Helpers\MetaTags::setRobots('noindex, nofollow'); ?>
    <?= \PiecesPHP\Core\Utilities\Helpers\MetaTags::getMetaTagsGeneric(); ?>
    <link rel="shortcut icon" href="<?= add_cache_stamp_to_url(get_config('favicon-back')); ?>" type="image/x-icon">
    <?php load_css(['base_url' => "", 'custom_url' => ""]) ?>
    <?php $themeTogglePart = 'head'; include app_basepath('view/panel/built-in/content/theme-toggle.php'); ?>
</head>

<body>
    <?php
        $accessBackgrounds = [(string) get_config('backgoundProblems')];
        ob_start();
    ?>
    <?= strReplaceTemplate(__(\PiecesPHP\UserSystem\Controllers\UserProblemsController::LANG_GROUP, 'PROBLEMS_LIST'), [
    '${loginLink}' => \PiecesPHP\UserSystem\Controllers\UsersController::routeName('form-login'),
    '${valueMailUser}' => \PiecesPHP\Core\ConfigHelpers\MailConfig::getValue('user'),
    '${otherProblemsFormLink}' => \PiecesPHP\UserSystem\Controllers\UserProblemsController::routeName('other-problems-form'),
    '${owner}' => escape_html(get_config('owner')),
    '${logoURL}' => get_config('logo'),
    //Para una plantilla anterior a H340, de una sola imagen: invertida también si la oscura es la de fábrica (el logo invertido).
    '${logoClass}' => \PiecesPHP\Settings\Controllers\SettingsController::brandImageLooksInvertedOnDark('logo') ? 'invert-on-dark' : '',
    //El logo entero, con lo que enseña en modo oscuro (H340).
    '${logo}' => \PiecesPHP\Settings\Controllers\SettingsController::brandImageTag('logo'),
    '${mailSystem}' => \PiecesPHP\Core\ConfigHelpers\MailConfig::getValue('user'),
]); ?>
    <?php $accessContent = (string) ob_get_clean(); require dirname(__DIR__) . '/inc/access-frame.php'; ?>
    <?php load_js(['base_url' => "", 'custom_url' => ""]); ?>
</body>

</html>
