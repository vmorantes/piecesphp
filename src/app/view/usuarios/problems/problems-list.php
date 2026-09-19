<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>"); ?>
<!DOCTYPE html>
<html lang="<?= get_config('app_lang'); ?>" dlang="<?= get_config('default_lang'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <base href="<?= baseurl(); ?>">
    <?= \PiecesPHP\Core\Utilities\Helpers\MetaTags::getMetaTagsGeneric(); ?>
    <link rel="shortcut icon" href="<?= get_config('favicon-back'); ?>" type="image/x-icon">
    <?php load_css(['base_url' => "", 'custom_url' => ""]) ?>
</head>

<body>
    <?= strReplaceTemplate(__(\App\Controller\UserProblemsController::LANG_GROUP, 'PROBLEMS_LIST'), [
        '${loginLink}' => get_route('users-form-login'),
        '${valueMailUser}' => \PiecesPHP\Core\ConfigHelpers\MailConfig::getValue('user'),
        '${otherProblemsFormLink}' => get_route("other-problems-form"),
        '${owner}' => get_config('owner'),
        '${logoURL}' => get_config('logo'),
        '${partnersImageURL}' => get_config('partners'),
        '${backgroundURL}' => get_config('backgoundProblems'),
        '${mailSystem}' => \PiecesPHP\Core\ConfigHelpers\MailConfig::getValue('user'),
    ]); ?>
    <?php load_js(['base_url' => "", 'custom_url' => ""]); ?>
</body>

</html>
